"""
WebSocket 连接管理器
管理所有客户端连接、消息路由、心跳
"""

import asyncio
import time
from typing import Dict, Any, Optional, Callable
from fastapi import WebSocket

from ..utils.protocol import decode_message, encode_message, success_response, error_response
from ..services.auth import verify_token
from ..config import WS_HEARTBEAT_INTERVAL, WS_RECEIVE_TIMEOUT
from .games.bjl import BJLGame
from .games.base import GameBase


class Connection:
    """单个 WebSocket 连接"""
    def __init__(self, ws: WebSocket, client_id: str):
        self.ws = ws
        self.client_id = client_id
        self.uid: int = 0
        self.token: str = ""
        self.last_heartbeat = time.time()
        self.current_game: Optional[GameBase] = None
        self.connected = True


class WSManager:
    """WebSocket 全局管理器（单例）"""

    def __init__(self):
        self.connections: Dict[str, Connection] = {}
        self.games: Dict[int, GameBase] = {}  # gtype -> game instance

    # ─── 连接管理 ───

    async def handle(self, ws: WebSocket):
        await ws.accept()
        client_id = f"{int(time.time()*1000)}_{id(ws)}"
        conn = Connection(ws, client_id)
        self.connections[client_id] = conn

        try:
            while conn.connected:
                try:
                    raw = await asyncio.wait_for(
                        ws.receive_text(), timeout=WS_RECEIVE_TIMEOUT
                    )
                    await self.on_message(conn, raw)
                except asyncio.TimeoutError:
                    # 超时断连
                    conn.connected = False
                except Exception:
                    conn.connected = False
        finally:
            await self.disconnect(conn)

    async def disconnect(self, conn: Connection):
        if conn.current_game:
            await conn.current_game.on_player_leave(conn.uid)
        self.connections.pop(conn.client_id, None)
        try:
            await conn.ws.close()
        except Exception:
            pass

    # ─── 消息处理 ───

    async def on_message(self, conn: Connection, raw: str):
        msg = decode_message(raw)
        if not msg:
            return

        event = msg.get("event", "")
        data = msg.get("data", {}) or {}
        uid = msg.get("uid", 0)

        # 更新最后心跳
        conn.last_heartbeat = time.time()

        # 路由
        if event == "Msg_Hall_Connect":
            await self._handle_connect(conn, data)
        elif event == "Msg_Hall_Heart":
            await self._handle_heartbeat(conn)
        elif event == "Msg_Hall_EnterGame":
            await self._handle_enter_game(conn, data)
        elif event == "Msg_Hall_EnterRoom":
            await self._handle_enter_room(conn, data)
        elif event == "Msg_Hall_GameSessions":
            await self._handle_game_sessions(conn, data)
        elif event.startswith("Msg_") and "_Out" in event:
            await self._handle_out_game(conn, event)
        elif event.startswith("Msg_") and "_Start" in event:
            await self._handle_bet(conn, event, data)
        elif event == "Msg_Hall_ChangeGolds":
            uid = int(data.get("uid", conn.uid))
            await self._send_player_info(conn, uid)
        else:
            # 其他消息返回成功（兼容）
            pass

    # ─── Hall 消息处理 ───

    async def _handle_connect(self, conn: Connection, data: dict):
        """WS 连接认证"""
        token = data.get("token", "")
        uid = data.get("uid", 0)

        if token and uid:
            if verify_token(int(uid), token):
                conn.uid = int(uid)
                conn.token = token

                # 返回玩家信息
                await self._send_player_info(conn, conn.uid)
                return

        # 认证失败
        await self._send_error(conn, "认证失败")

    async def _handle_heartbeat(self, conn: Connection):
        await self._send(conn, encode_message("Msg_Hall_Heart", {}, uid=conn.uid))

    async def _handle_enter_game(self, conn: Connection, data: dict):
        """请求进入游戏"""
        gtype = int(data.get("gtype", 0))

        # 获取或创建游戏实例
        if gtype not in self.games:
            await self._create_game(gtype)

        game = self.games.get(gtype)
        if not game:
            await self._send_error(conn, f"游戏 {gtype} 未实现")
            return

        conn.current_game = game
        await game.on_player_join(conn.uid, {
            "gold": 100000,
            "nickname": f"Player{conn.uid}",
        })

    async def _handle_enter_room(self, conn: Connection, data: dict):
        """进入房间响应"""
        room_id = int(time.time() * 1000) % 100000000
        await self._send(conn, success_response("Msg_Hall_EnterRoom", {
            "rid": room_id,
            "gtype": data.get("gtype", 0),
            "level": data.get("level", 5),
            "rule": [],
        }, uid=conn.uid, area=1))

    async def _handle_game_sessions(self, conn: Connection, data: dict):
        """获取游戏会话配置"""
        gtype = int(data.get("gtype", 0))
        # 返回房间配置
        await self._send(conn, success_response("Msg_Hall_GameSessions", {
            "5": {"level": 5, "min_gold": 0, "max_gold": -1, "doublescore": 0},
        }, uid=conn.uid))

    async def _handle_out_game(self, conn: Connection, event: str):
        """退出游戏"""
        game = conn.current_game
        if game:
            await game.on_player_leave(conn.uid)
            conn.current_game = None
        await self._send(conn, success_response(event, {}, uid=conn.uid))

    async def _handle_bet(self, conn: Connection, event: str, data: dict):
        """游戏下注 (Msg_<Game>_Start)"""
        game = conn.current_game
        if not game:
            await self._send_error(conn, "未在游戏中")
            return

        position = data.get("position", "")
        amount = data.get("amount", 0)
        if position and amount:
            await game.on_bet(conn.uid, position, amount)

    # ─── 发送辅助 ───

    async def _send(self, conn: Connection, message: str):
        try:
            await conn.ws.send_text(message)
        except Exception:
            conn.connected = False

    async def _send_to(self, message: str, uid: int):
        for conn in self.connections.values():
            if conn.uid == uid:
                await self._send(conn, message)

    # ─── 辅助方法 ───

    async def _send_player_info(self, conn: Connection, uid: int):
        from ..models import SessionLocal, User
        db = SessionLocal()
        try:
            user = db.query(User).filter(User.uid == uid).first()
            if user:
                await self._send(conn, success_response("Msg_Hall_Connect", {
                    "uid": user.uid,
                    "gold": user.gold or 0,
                    "bank": user.bank or 0,
                    "nickname": user.nickname or "测试账号",
                    "headimgurl": user.headimgurl or "",
                    "mcard": user.mcard or 0,
                    "rcard": user.rcard or 0,
                    "power": user.power or 0,
                }, uid=uid))
        finally:
            db.close()

    async def _send_error(self, conn: Connection, msg: str):
        await self._send(conn, error_response("Msg_Hall_ERROR", msg, uid=conn.uid))

    async def _create_game(self, gtype: int):
        """动态创建游戏实例"""
        async def send_cb(event, data, uid):
            await self._send_to(
                success_response(event, data, uid=uid), uid
            )

        game_map = {
            8: BJLGame,    # 百家乐
            # 其他游戏后续添加
        }

        game_cls = game_map.get(gtype, BJLGame)  # 默认用 BJL 作为 demo
        game = game_cls(send_callback=send_cb)
        self.games[gtype] = game
        await game.start_round()


# 全局单例
ws_manager = WSManager()

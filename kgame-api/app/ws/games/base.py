"""
游戏基类 — 通用游戏状态机
所有游戏逻辑继承此类

状态流转:
  IDLE → BETTING → DEALING → RESULT → IDLE → ...
"""

import asyncio
import time
import random
from abc import ABC, abstractmethod
from typing import Any, Dict, List, Optional, Callable


class GameState:
    IDLE = "idle"           # 空闲等待
    BETTING = "betting"     # 下注阶段
    DEALING = "dealing"     # 开奖结算
    RESULT = "result"       # 结果展示


class GameBase(ABC):
    """游戏基类"""

    gtype: int = 0
    game_name: str = ""
    room_level: int = 5

    def __init__(self, send_callback: Callable):
        self.send = send_callback    # async fn(event, data, uid)
        self.state = GameState.IDLE
        self.bets: Dict[int, Dict[str, int]] = {}  # uid -> {position: amount}
        self.players: Dict[int, Any] = {}           # uid -> player info
        self.round_id = 0
        self.timer_task: Optional[asyncio.Task] = None

    # ─── 生命周期 ───

    async def on_player_join(self, uid: int, info: dict):
        """玩家进入房间"""
        self.players[uid] = info
        await self.send(self._event("EnterRoom"), {
            "rid": self._make_rid(),
            "gtype": self.gtype,
            "level": self.room_level,
            "rule": [],
        }, uid)
        await self._send_game_state(uid)

    async def on_player_leave(self, uid: int):
        """玩家离开"""
        self.players.pop(uid, None)
        if uid in self.bets:
            del self.bets[uid]

    async def on_bet(self, uid: int, position: str, amount: int):
        """玩家下注"""
        if self.state != GameState.BETTING:
            await self.send(self._event("Error"),
                           {"msg": "当前不是下注阶段"}, uid)
            return

        if uid not in self.bets:
            self.bets[uid] = {}
        self.bets[uid][position] = self.bets[uid].get(position, 0) + amount

        await self.send(self._event("BetResult"), {
            "status": 1,
            "position": position,
            "amount": amount,
        }, uid)

    # ─── 状态机 ───

    async def start_round(self):
        """开始新一轮 → 进入下注"""
        self.round_id += 1
        self.bets.clear()
        self.state = GameState.BETTING
        self._cancel_timer()

        await self._broadcast(self._event("RoomInfo"), self._room_info())
        await self._broadcast(self._event("Start"), {
            "status": 1,
            "round_id": self.round_id,
            "bet_time": self.get_bet_time(),
        })

        # 下注倒计时
        bet_seconds = self.get_bet_time()
        self.timer_task = asyncio.create_task(self._countdown(bet_seconds, self.on_bet_timeout))

    async def on_bet_timeout(self):
        """下注结束 → 开奖"""
        self.state = GameState.DEALING
        await self._cancel_timer()
        await self._deal_and_show_result()

    async def on_result_timeout(self):
        """结果展示结束 → 下一轮"""
        self.state = GameState.IDLE
        await asyncio.sleep(0.5)
        await self.start_round()

    async def _deal_and_show_result(self):
        """开奖结算"""
        result = self._calculate_result()
        self.state = GameState.RESULT

        # 计算每个玩家的输赢并广播
        for uid in self.players:
            win = self._get_player_win(uid, result)
            await self.send(self._event("Result"), {
                "status": 1,
                "win": win,
                "result": result,
            }, uid)

        # 结果展示计时，结束后下一轮
        show_seconds = self.get_result_time()
        self.timer_task = asyncio.create_task(self._countdown(show_seconds, self.on_result_timeout))

    # ─── 子类实现 ───

    @abstractmethod
    def get_bet_time(self) -> int:
        """下注时间（秒）"""
        ...

    @abstractmethod
    def get_result_time(self) -> int:
        """结果展示时间（秒）"""
        ...

    @abstractmethod
    def _calculate_result(self) -> dict:
        """计算游戏结果，返回 {winner: ..., cards: [...]}"""
        ...

    @abstractmethod
    def _get_player_win(self, uid: int, result: dict) -> int:
        """计算单个玩家赢取"""
        ...

    @abstractmethod
    def get_positions(self) -> List[str]:
        """可下注位置"""
        ...

    # ─── 工具方法 ───

    def _event(self, suffix: str) -> str:
        return f"Msg_{self.game_name}_{suffix}"

    def _make_rid(self) -> int:
        return int(f"{self.gtype}{time.time() % 100000:05d}")

    def _room_info(self) -> dict:
        return {"status": 1, "data": {
            "level": self.room_level,
            "min_gold": 0,
            "max_gold": -1,
            "positions": self.get_positions(),
        }}

    async def _broadcast(self, event: str, data: Any):
        for uid in self.players:
            await self.send(event, data, uid)

    async def _send_game_state(self, uid: int):
        await self.send(self._event("RoomInfo"), self._room_info(), uid)

    async def _countdown(self, seconds: int, callback: Callable):
        try:
            await asyncio.sleep(seconds)
            await callback()
        except asyncio.CancelledError:
            pass

    async def _cancel_timer(self):
        if self.timer_task:
            self.timer_task.cancel()
            self.timer_task = None

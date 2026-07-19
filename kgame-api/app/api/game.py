"""
游戏接口 — /api/game/*
游戏列表、游戏入口 URL、游戏记录等
"""

import hashlib
import time
from fastapi import APIRouter
from pydantic import BaseModel

from ..config import GAME_PREFABS, TOKEN_SECRET
from ..models import GameStatus, GameConfig, GameRecord, SessionLocal, User
from ..services.auth import generate_token

router = APIRouter(prefix="/api/game", tags=["游戏"])


@router.post("/gameList")
async def game_list():
    """获取游戏列表 (开/关状态)"""
    db = SessionLocal()
    try:
        rows = db.query(GameStatus).all()
        data = [
            {
                "gtype": str(r.gtype),
                "status": str(r.status),
                "game_back_url": r.game_back_url or "",
                "game_icon_url": r.game_icon_url or "",
            }
            for r in rows
        ]
        return {"code": 200, "msg": "成功", "data": data}
    finally:
        db.close()


@router.post("/gameUrl")
async def game_url(req: dict):
    """生成游戏入口链接 + token"""
    gtype = req.get("gtype", 0)
    uid = req.get("uid", 0)

    # 生成 token
    token = generate_token(uid)

    return {
        "code": 200,
        "msg": "成功",
        "data": {
            "url": f"http://127.0.0.1:8083/index.html?{token}&gtype={gtype}",
            "token": token,
        }
    }


@router.post("/gameRecord")
async def game_record(req: dict):
    """获取玩家游戏记录（分页）"""
    uid = req.get("uid", 0)
    page = int(req.get("page", 1))
    limit = 20

    db = SessionLocal()
    try:
        total = db.query(GameRecord).filter(GameRecord.uid == uid).count()
        rows = (
            db.query(GameRecord)
            .filter(GameRecord.uid == uid)
            .order_by(GameRecord.rid.desc())
            .offset((page - 1) * limit)
            .limit(limit)
            .all()
        )

        data = [
            {
                "rid": r.rid,
                "gtype": r.gtype,
                "begingold": r.begingold,
                "endgold": r.endgold,
                "win": r.win,
                "starttime": r.starttime,
                "endtime": r.endtime,
            }
            for r in rows
        ]

        return {"code": 200, "msg": "成功", "data": {"list": data, "total": total, "page": page}}
    finally:
        db.close()


@router.post("/gamelist")
async def game_list_alt():
    """游戏列表 (兼容 URL)"""
    return await game_list()


@router.post("/game_list")
async def game_list_alt2():
    """游戏列表 (兼容 URL)"""
    return await game_list()

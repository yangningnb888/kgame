"""
KGame FastAPI 后端 — 主入口
替换 PHP WorkerMan + ThinkPHP，统一管理游戏接口

启动:
  pip install -r requirements.txt
  python -m app.main

端口: 8163 (覆盖原 PHP 端口)
"""

import uvicorn
from fastapi import FastAPI, WebSocket
from fastapi.middleware.cors import CORSMiddleware

from .api import login, game
from .ws.connection import ws_manager

app = FastAPI(
    title="KGame API",
    description="游戏平台 FastAPI 后端 — HTTP API + WebSocket 游戏服务器",
    version="1.0.0",
)

# CORS (允许 H5 跨域)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# ─── HTTP 路由 ───
app.include_router(login.router)   # /login/login/dispatch 等
app.include_router(game.router)    # /api/game/gameList 等

# ─── WebSocket ───
@app.websocket("/")
async def ws_endpoint(websocket: WebSocket):
    """游戏大厅 WebSocket 连接入口"""
    await ws_manager.handle(websocket)


# ─── 健康检查 ───
@app.get("/health")
async def health():
    return {"status": "ok", "service": "kgame-api"}


# ─── 启动入口 ───
if __name__ == "__main__":
    uvicorn.run(
        "app.main:app",
        host="0.0.0.0",
        port=9493,
        reload=False,
    )

"""
KGame API 配置
数据库连接、JWT 密钥、端口等
"""

import os
from urllib.parse import quote_plus

# ====== 数据库 ======
DB_HOST = os.getenv("KGAME_DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("KGAME_DB_PORT", "3306"))
DB_USER = os.getenv("KGAME_DB_USER", "root")
DB_PASS = os.getenv("KGAME_DB_PASS", "86e201113604c41f")
DB_NAME = os.getenv("KGAME_DB_NAME", "63")

DATABASE_URL = (
    f"mysql+pymysql://{DB_USER}:{quote_plus(DB_PASS)}@"
    f"{DB_HOST}:{DB_PORT}/{DB_NAME}?charset=utf8mb4"
)

# ====== 服务端口 ======
HTTP_PORT = int(os.getenv("KGAME_HTTP_PORT", "8163"))

# ====== JWT ======
JWT_SECRET = os.getenv("KGAME_JWT_SECRET", "kgame-local-dev-secret-key-2026")
JWT_ALGORITHM = "HS256"
JWT_EXPIRE_HOURS = 24

# ====== 游戏配置 ======
GAME_PREFABS = {
    1:  {"enName": "FQZS",  "zhName": "飞禽走兽", "type": "MULTI",   "prefabUrl": "prefab/FQZSMain"},
    2:  {"enName": "BRNN",  "zhName": "百人牛牛", "type": "MULTI",   "prefabUrl": "prefab/BRNNMain"},
    3:  {"enName": "HBSL",  "zhName": "红包扫雷", "type": "MULTI",   "prefabUrl": "prefab/HBSLMain"},
    5:  {"enName": "YYY",   "zhName": "摇一摇",   "type": "MULTI",   "prefabUrl": "prefab/YYYMain"},
    6:  {"enName": "LHD",   "zhName": "龙虎斗",   "type": "MULTI",   "prefabUrl": "prefab/LHDMain"},
    7:  {"enName": "BCBM",  "zhName": "奔驰宝马", "type": "MULTI",   "prefabUrl": "prefab/BCBMMain"},
    8:  {"enName": "BJL",   "zhName": "百家乐",   "type": "MULTI",   "prefabUrl": "prefab/BJLMain"},
    9:  {"enName": "HHDZ",  "zhName": "红黑大战", "type": "MULTI",   "prefabUrl": "prefab/HHDZMain"},
    10: {"enName": "XLDB",  "zhName": "寻龙夺宝", "type": "FISHING", "prefabUrl": "prefab/XLDBMain"},
    11: {"enName": "LKPY",  "zhName": "李逵劈鱼", "type": "FISHING", "prefabUrl": "prefab/LKBymain"},
    12: {"enName": "JCBY",  "zhName": "金蟾捕鱼", "type": "FISHING", "prefabUrl": "prefab/JCBYMain"},
    13: {"enName": "DNTG",  "zhName": "大闹天宫", "type": "FISHING", "prefabUrl": "prefab/DNTGMain"},
}

TOKEN_SECRET = "password"  # 前端 token 生成用
WS_HEARTBEAT_INTERVAL = 5   # 秒
WS_RECEIVE_TIMEOUT = 10     # 秒，超时断连

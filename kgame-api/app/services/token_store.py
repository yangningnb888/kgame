"""
Token 存储 — 写入 jh_user_online 表（带 jh_ 前缀，匹配游戏服务器查询）
"""

import time
import random
import hashlib
import pymysql
from ..config import DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME


def _generate_token(uid: int) -> str:
    """生成与原始 PHP Login.php create_token() 一致的 token"""
    # 随机小写字母 + uid 作为前缀
    prefix = chr(random.randint(97, 122)) + str(uid)  # e.g. "a66828962"
    # md5(uniqid()) UUID 格式
    raw = hashlib.md5(f"{random.random()}{time.time()}".encode()).hexdigest()
    uuid = (raw[0:8] + raw[8:12] + raw[12:16] + raw[16:20] + raw[20:32])
    return prefix + uuid


def save_user_token(uid: int, token: str = None) -> str:
    """
    登录成功后保存 token 到 jh_user_online 表
    返回生成的 token 字符串
    """
    if token is None:
        token = _generate_token(uid)

    conn = pymysql.connect(host=DB_HOST, port=DB_PORT, user=DB_USER,
                           password=DB_PASS, database=DB_NAME, charset='utf8mb4')
    try:
        with conn.cursor() as c:
            # 删除该 uid 旧 token
            c.execute("DELETE FROM jh_user_online WHERE uid = %s", (uid,))
            # 插入新 token (create_time = 30 天后过期)
            end_time = int(time.time()) + 2592000  # 30 days
            c.execute(
                "INSERT INTO jh_user_online (uid, token, create_time) "
                "VALUES (%s, %s, %s)",
                (uid, token, end_time)
            )
        conn.commit()
        return token
    except Exception as e:
        print(f"save_user_token error: {e}")
        return token
    finally:
        conn.close()

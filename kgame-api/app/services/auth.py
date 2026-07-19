"""
认证服务: 登录、token 生成/验证
"""

import hashlib
import time
import random
import string
from datetime import datetime, timedelta
from typing import Optional
import pymysql
from sqlalchemy import func

from ..config import TOKEN_SECRET, JWT_SECRET, JWT_ALGORITHM, JWT_EXPIRE_HOURS
from ..models import User, UserRegister, SessionLocal
from .token_store import save_user_token, _generate_token


# ─── Token 生成 (兼容原 PHP 格式) ───

def _make_raw_token(uid: int) -> str:
    """生成原始 token: z{uid}{md5_hex(32)}"""
    random_hex = ''.join(random.choices(string.hexdigits.lower(), k=32))
    raw = f"z{uid}{random_hex}"
    return raw


def _make_token_md5(raw_token: str) -> str:
    """生成 token_md5: md5(raw_token + '&key=password')"""
    return hashlib.md5(f"{raw_token}&key={TOKEN_SECRET}".encode()).hexdigest()


def generate_token(uid: int) -> str:
    """
    生成前端可用的完整 token 参数
    格式: token=z{uid}{hex32}&key=password
    """
    raw = _make_raw_token(uid)
    return f"token={raw}&x=1&key={TOKEN_SECRET}"


def generate_token_md5_only(uid: int) -> str:
    """仅返回 token_md5 (客户端用于 WS 连接)"""
    raw = _make_raw_token(uid)
    return _make_token_md5(raw)


# ─── 登录逻辑 ───

def verify_password(plain: str, hashed: str) -> bool:
    """验证密码 (原 PHP 用 md5，但本地数据明文存储)"""
    # 尝试直接比对 (明文)
    if plain == hashed:
        return True
    # 尝试 MD5 比对
    if hashlib.md5(plain.encode()).hexdigest() == hashed:
        return True
    return False


def user_login(telephone: str, password: str) -> Optional[dict]:
    """手机号密码登录"""
    db: SessionLocal = SessionLocal()
    try:
        reg = db.query(UserRegister).filter(
            func.trim(UserRegister.telephone) == telephone.strip()
        ).first()
        if not reg:
            return None
        if not verify_password(password, reg.password):
            return None

        user = db.query(User).filter(User.uid == reg.uid).first()
        if not user:
            return None

        uid = user.uid
        # 用 PHP 兼容格式生成 token 并写入 jh_user_online
        token_raw = _generate_token(uid)
        save_user_token(uid, token_raw)

        return {
            "uid": uid,
            "token": token_raw,
            "gold": user.gold or 0,
            "bank": user.bank or 0,
            "nickname": user.nickname or "测试账号",
            "headimgurl": user.headimgurl or "",
            "equipmentcard": user.equipmentcard or "",
            "mcard": user.mcard or 0,
            "rcard": user.rcard or 0,
            "power": user.battery or 0,
            "isbind": 0,
        }
    finally:
        db.close()


def tourist_login(equipmentcard: str) -> Optional[dict]:
    """
    游客登录 (按设备号)
    不存在则自动创建新用户
    """
    db: SessionLocal = SessionLocal()
    try:
        user = db.query(User).filter(
            User.equipmentcard == equipmentcard
        ).first()

        if not user:
            # 创建新用户
            max_uid = db.query(User.uid).order_by(User.uid.desc()).first()
            new_uid = (max_uid[0] + 1) if max_uid and max_uid[0] else 66820000

            user = User(
                uid=new_uid,
                gold=100000,
                bank=0,
                equipmentcard=equipmentcard,
                platform=1,
                registertime=int(time.time()),
                logintime=int(time.time()),
                nickname=f"游客{new_uid%100000}",
            )
            db.add(user)
            db.add(UserRegister(
                uid=new_uid,
                telephone=f"t_{equipmentcard[:10]}",
                password=hashlib.md5("123456".encode()).hexdigest(),
            ))
            db.commit()
            db.refresh(user)

        uid = user.uid
        # PHP 兼容格式
        token_raw = _generate_token(uid)
        save_user_token(uid, token_raw)

        user.logintime = int(time.time())
        db.commit()

        return {
            "uid": uid,
            "token": token_raw,
            "gold": user.gold or 0,
            "bank": user.bank or 0,
            "nickname": user.nickname or "测试账号",
            "headimgurl": user.headimgurl or "",
            "equipmentcard": user.equipmentcard or "",
            "mcard": user.mcard or 0,
            "rcard": user.rcard or 0,
            "power": user.power or 0,
            "isbind": user.isbind or 0,
        }
    finally:
        db.close()


def verify_token(uid: int, token: str) -> bool:
    """验证 token 是否有效"""
    db = SessionLocal()
    try:
        entry = db.query(UserToken).filter(
            UserToken.uid == uid, UserToken.token == token
        ).first()
        return entry is not None
    finally:
        db.close()

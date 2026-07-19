"""
登录接口 — 替换 PHP /login/login/*
兼容原 TP63 dispatch 格式 (JSON + url-encoded)
"""

import hashlib
import time
import json
import urllib.parse
from fastapi import APIRouter, Request, Form
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import Optional

from ..services.auth import user_login, tourist_login, generate_token, generate_token_md5_only
from ..models import UserRegister, User, SessionLocal

router = APIRouter(prefix="/login", tags=["认证"])


class DispatchRequest(BaseModel):
    event: str
    data: dict = {}


# ─── 分发函数表 ───

@router.post("/login/dispatch")
async def dispatch(req: Request):
    """统一接口分发 — 兼容 JSON 和 www-form-urlencoded"""
    ct = req.headers.get("content-type", "")

    # 先判断格式
    if "application/json" in ct:
        body = await req.json()
        event = body.get("event", "")
        data = body.get("data", {})
    else:
        # url-encoded: data=<json_string>
        # 注意：用标准库 urllib.parse 解析，避免依赖 python-multipart（未装会导致 500）
        raw_body = (await req.body()).decode("utf-8", "ignore")
        form = urllib.parse.parse_qs(raw_body)
        raw = form.get("data", [""])[0]
        if raw:
            parsed = json.loads(raw)
            event = parsed.get("event", "")
            data = parsed.get("data", {})
        else:
            return JSONResponse({"code": 400, "msg": "无法解析请求", "data": None})
    handler = {
        "Msg_User_Login":             _user_login,
        "Msg_User_getCode":           _get_code,
        "Msg_User_register":          _tourist_register,
        "Msg_User_ChangePassword":    _forget_userpass,
        "Msg_User_changePhone":       _stub_ok,
        "Msg_User_VerificationCode":  _stub_ok,
        "Msg_User_upgrade":           _stub_ok,
        "Msg_User_forgeBank":         _stub_ok,
    }.get(event, _unknown)

    return await handler(data)


async def _unknown(data: dict):
    return {"code": 404, "msg": "未知事件", "data": None}

async def _stub_ok(data: dict):
    return {"code": 200, "msg": "OK", "data": None}


# ─── 登录处理 ───

async def _user_login(data: dict):
    """账号登录"""
    telephone = data.get("telephone", "")
    password = data.get("password", "")

    if not telephone or not password:
        return {"code": 400, "msg": "手机号或密码不能为空", "data": None}

    result = user_login(telephone, password)
    if not result:
        return {"code": 401, "msg": "手机号或密码错误", "data": None}

    return {"code": 200, "msg": "成功", "data": result}


async def _get_code(data: dict):
    """获取验证码 (固定123456)"""
    return {"code": 200, "msg": "验证码发送成功", "data": {"code": "123456"}}


async def _tourist_register(data: dict):
    """游客绑定手机号"""
    uid = data.get("uid", 0)
    telephone = data.get("telephone", "")
    password = data.get("password", "")
    code = data.get("code", "")

    if code != "123456":
        return {"code": 400, "msg": "验证码错误", "data": None}

    db = SessionLocal()
    try:
        reg = db.query(UserRegister).filter(UserRegister.uid == uid).first()
        if not reg:
            return {"code": 404, "msg": "用户不存在", "data": None}
        reg.telephone = telephone
        reg.password = hashlib.md5(password.encode()).hexdigest()
        db.commit()
        return {"code": 200, "msg": "绑定成功", "data": None}
    finally:
        db.close()


async def _forget_userpass(data: dict):
    """修改密码"""
    uid = data.get("uid", 0)
    password = data.get("password", "")
    code = data.get("code", "")

    if code != "123456":
        return {"code": 400, "msg": "验证码错误", "data": None}

    db = SessionLocal()
    try:
        reg = db.query(UserRegister).filter(UserRegister.uid == uid).first()
        if not reg:
            return {"code": 404, "msg": "用户不存在", "data": None}
        reg.password = hashlib.md5(password.encode()).hexdigest()
        db.commit()
        return {"code": 200, "msg": "密码修改成功", "data": None}
    finally:
        db.close()

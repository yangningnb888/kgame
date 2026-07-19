"""
WebSocket 协议处理
前端 Cocos 客户端使用 Base64(JSON({event, area, uid, data})) 格式通信
"""

import base64
import json
from typing import Any, Dict, Optional


def encode_message(event: str, data: Any, uid: int = 0, area: int = 0) -> str:
    """编码为客户端可解析的 Base64 消息"""
    payload = {
        "event": event,
        "area": area,
        "uid": uid,
        "data": data,
    }
    json_str = json.dumps(payload, ensure_ascii=False)
    return base64.b64encode(json_str.encode()).decode()


def decode_message(raw: str) -> Optional[Dict[str, Any]]:
    """解码客户端发来的 Base64 消息"""
    try:
        json_str = base64.b64decode(raw).decode()
        return json.loads(json_str)
    except Exception:
        return None


def success_response(event: str, data: Any = None, uid: int = 0, area: int = 0,
                     status: int = 1, msg: str = "成功") -> str:
    """生成成功响应"""
    return encode_message(event, {
        "status": status,
        "msg": msg,
        "data": data,
    }, uid=uid, area=area)


def error_response(event: str, msg: str = "失败", uid: int = 0, area: int = 0,
                   status: int = 0) -> str:
    """生成错误响应"""
    return encode_message(event, {
        "status": status,
        "msg": msg,
        "data": None,
    }, uid=uid, area=area)

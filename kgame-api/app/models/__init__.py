"""
数据库模型 — 对应 MySQL `63` 库的核心表
"""

from datetime import datetime
from typing import Optional
from sqlalchemy import (
    Column, Integer, String, BigInteger, Text, DateTime,
    create_engine, MetaData
)
from sqlalchemy.orm import declarative_base, sessionmaker
from ..config import DATABASE_URL

engine = create_engine(DATABASE_URL, pool_size=10, max_overflow=20, pool_pre_ping=True)
SessionLocal = sessionmaker(bind=engine, autocommit=False, autoflush=False)
Base = declarative_base()
metadata = MetaData()


class User(Base):
    """用户主表 jh_user"""
    __tablename__ = "jh_user"
    id = Column(Integer, primary_key=True, autoincrement=True)
    uid = Column(Integer, index=True)
    gold = Column(BigInteger, default=0, comment="当前金币")
    bank = Column(BigInteger, default=0, comment="银行金币")
    equipmentcard = Column(String(64), default="", comment="设备号")
    platform = Column(String(255), default="", comment="平台ID")
    nickname = Column(String(64), default="测试账号")
    headimgurl = Column(String(512), default="")
    pictureframe = Column(Integer, default=0)
    mcard = Column(Integer, default=0, comment="月卡数量")
    rcard = Column(Integer, default=0, comment="兑换卡数量")
    battery = Column(Integer, default=0, comment="体力")
    agent = Column(Integer, default=0)
    online = Column(Integer, default=0)
    type = Column(Integer, default=0)
    status = Column(Integer, default=0)

    @property
    def power(self): return self.battery
    @property
    def isbind(self): return 1 if self.telephone else 0


class UserRegister(Base):
    """用户注册表 jh_register"""
    __tablename__ = "jh_register"
    id = Column(Integer, primary_key=True, autoincrement=True)
    uid = Column(Integer, index=True)
    telephone = Column(String(20), default="")
    password = Column(String(64), default="")
    created = Column(DateTime, default=None)
    status = Column(Integer, default=0)


class GameStatus(Base):
    """游戏开关状态表 jh_gamestatus"""
    __tablename__ = "jh_gamestatus"
    id = Column(Integer, primary_key=True, autoincrement=True)
    gtype = Column(Integer, index=True, comment="游戏类型ID")
    status = Column(Integer, default=1, comment="1=开 2=关")
    game_back_url = Column(String(256), default="")
    game_icon_url = Column(String(256), default="")


class GameConfig(Base):
    """游戏配置表 jh_game_config"""
    __tablename__ = "jh_game_config"
    id = Column(Integer, primary_key=True, autoincrement=True)
    gtype = Column(Integer, comment="游戏类型")
    level = Column(Integer, comment="房间等级")
    profit = Column(BigInteger, default=0, comment="库存")
    controls = Column(Text, default="", comment="控制参数JSON")


class GameRecord(Base):
    """游戏记录表 jh_game_record"""
    __tablename__ = "jh_game_record"
    rid = Column(Integer, primary_key=True, autoincrement=True)
    uid = Column(Integer, index=True)
    gtype = Column(Integer, comment="游戏类型")
    begingold = Column(BigInteger, default=0)
    endgold = Column(BigInteger, default=0)
    win = Column(BigInteger, default=0, comment="输赢")
    starttime = Column(Integer, default=0)
    endtime = Column(Integer, default=0)
    transfer = Column(Integer, default=0, comment="是否已转出")
    roomid = Column(Integer, default=0)


class UserProfit(Base):
    """用户流水表 jh_user_profit"""
    __tablename__ = "jh_user_profit"
    id = Column(Integer, primary_key=True, autoincrement=True)
    uid = Column(Integer, index=True)
    type = Column(String(32), comment="流水类型")
    num = Column(BigInteger, default=0)
    beforegold = Column(BigInteger, default=0)
    aftergold = Column(BigInteger, default=0)
    createtime = Column(Integer, default=0)


class UserAgent(Base):
    """代理关系表 jh_user_agent"""
    __tablename__ = "jh_user_agent"
    id = Column(Integer, primary_key=True, autoincrement=True)
    uid = Column(Integer, index=True, comment="代理UID")
    source = Column(Integer, default=0, comment="来源")
    stage = Column(String(32), default="")


class UserSuperior(Base):
    """场控表 jh_user_superior"""
    __tablename__ = "jh_user_superior"
    id = Column(Integer, primary_key=True, autoincrement=True)
    uid = Column(Integer, index=True)
    control = Column(Integer, default=0, comment="场控值")
    flagget = Column(Integer, default=0)
    curget = Column(Integer, default=0)


def get_db():
    """依赖注入: 获取数据库会话"""
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


def init_db():
    """初始化数据库（创建不存在的表）"""
    Base.metadata.create_all(bind=engine)

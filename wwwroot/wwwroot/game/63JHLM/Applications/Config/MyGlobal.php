<?php

date_default_timezone_set("Asia/Shanghai");
//工具类编号
define('MODULE_SEND_MSG', 1); //发送消息
define('MODULE_TIMER', 2); //定时器
define('MODULE_HISTORY', 3); //战绩详情
define('MODULE_BACK_FUNC', 4); //定时函数回调
define('MODULE_ANTI_CHEATING', 5); //防作弊
define('MODULE_TIME_OUT', 6); //延迟操作时长

//错误处理类型
define('ERROR_AGAIN', 0); //重连
define('ERROR_LOGIN', 1); //退出到登陆
define('ERROR_WARN', 2); //纯提示
define('ERROR_ROOM', 3); //退出房间
define('ERROR_UP', 10); //返回上一级
define('ERROR_RE_LOGIN', 11); //断开连接 并该链接不可用 重新登录

//游戏编号
define('GAME_TEMPLATE', 0); //模板
define('GAME_FQZS', 1); //飞禽走兽
define('GAME_BRNN', 2); //百人牛牛
define('GAME_HBSL', 3); //红包扫雷
define('GAME_SLWH', 4); //森林舞会
define('GAME_YYY', 5); //摇一摇
define('GAME_LHD', 6); //龙虎斗
define('GAME_BCBM', 7); //奔驰宝马
define('GAME_BJL', 8); //百家乐
define('GAME_HHDZ', 9); //红黑大战
define('GAME_XLDB', 10); //寻龙夺宝
define('GAME_LKPY', 11); //李逵劈鱼
define('GAME_JCBY', 12); //金蟾捕鱼
define('GAME_DNTG', 13); //大闹天宫
define('GAME_QZNN', 14); //抢庄牛牛
define('GAME_ERNN', 15); //二人牛牛
define('GAME_JDDDZ', 16); //经典斗地主
define('GAME_ERQS', 17); //二人雀神
define('GAME_TBNN', 18); //通比牛牛
define('GAME_DZPK', 19); //德州扑克
define('GAME_ZJH', 20); //炸金花
define('GAME_SRNN', 21); //四人牛牛
define('GAME_JXLW', 22); //九线拉王
define('GAME_SHZ', 23); //水浒传
define('GAME_XBLY', 24); //寻宝乐园
define('GAME_LDYX', 25); //铃铛游戏
define('GAME_DFDC', 26); //多福多财
define('GAME_GGL', 27); //刮刮乐
define('GAME_JSXN', 28); //僵尸新娘
define('GAME_WLPD', 29); //灵魂派对
define('GAME_XMDJ', 30); //熊猫dj
define('GAME_FFC', 31); //分分彩
define('GAME_ESYD', 32); //二十一点
define('GAME_QZEBG', 33); //抢庄二八杠
define('GAME_QZSG', 34); //抢庄三公
define('GAME_DZZH', 35); //盗贼之海
define('GAME_XSZY', 36); //血色之夜
define('GAME_RHEM', 37); //惹火恶魔
define('GAME_CLZS', 38); //丛林战士
define('GAME_THB', 39); //鹈鹕币
define('GAME_ZEUS', 40); //宙斯
define('GAME_DDML', 41); //大肚弥勒
define('GAME_JSBZ', 42); //急速宝藏
define('GAME_KXGN', 43); //科学怪牛
define('GAME_CFYS', 44); //财富医生
define('GAME_HSBF', 45); //火山爆发

//返利比例
define('BANKTRANSFER', 980);  //银行转账
define('GETWINER', 95);  //大赢家抽水

//币种
define('COIN', 1);  //金币
define('CARD', 2);  //兑换卡
define('MCARD', 3);  //月卡

//邮件类型
define('FROMGIVE', 1);  //赠送
define('FROMSYSTEM', 2);  //系统

//账单类型
define('GETGOLD', 1);  //入账
define('OUTGOLD', 2);  //出账

//月卡价值
define('MONTHLYCARD', 5000000);
define('USERMONTHLYCARD', 4700000);
define('AGENTMONTHLYCARD', 300000);
define('EXCHANGECARD', 100000);

define('ROBOTGOLD', 1000);
define('ROBOTMINGOLD', 100000);
define('ROBOTBANKERGOLD', 200000000);
define('ROBOTMAXGOLD', 1000000000);

define('GAME_NAME', array(
    GAME_TEMPLATE => 'GAME_TEMPLATE',
    GAME_FQZS => 'GAME_FQZS',
    GAME_BRNN => 'GAME_BRNN',
    GAME_HBSL => 'GAME_HBSL',
    GAME_SLWH => 'GAME_SLWH',
    GAME_YYY => 'GAME_YYY',
    GAME_LHD => 'GAME_LHD',
    GAME_BCBM => 'GAME_BCBM',
    GAME_BJL => 'GAME_BJL',
    GAME_HHDZ => 'GAME_HHDZ',
    GAME_XLDB => 'GAME_XLDB',
    GAME_LKPY => 'GAME_LKPY',
    GAME_JCBY => 'GAME_JCBY',
    GAME_DNTG => 'GAME_DNTG',
    GAME_QZNN => 'GAME_QZNN',
    GAME_ERNN => 'GAME_ERNN',
    GAME_JDDDZ => 'GAME_JDDDZ',
    GAME_ERQS => 'GAME_ERQS',
    GAME_TBNN => 'GAME_TBNN',
    GAME_DZPK => 'GAME_DZPK',
    GAME_ZJH => 'GAME_ZJH',
    GAME_SRNN => 'GAME_SRNN',
    GAME_JXLW => 'GAME_JXLW',
    GAME_SHZ => 'GAME_SHZ',
    GAME_XBLY => 'GAME_XBLY',
    GAME_LDYX => 'GAME_LDYX',
    GAME_DFDC => 'GAME_DFDC',
    GAME_GGL => 'GAME_GGL',
    GAME_JSXN => 'GAME_JSXN',
    GAME_WLPD => 'GAME_WLPD',
    GAME_XMDJ => 'GAME_XMDJ',
    GAME_FFC => 'GAME_FFC',
    GAME_ESYD => 'GAME_ESYD',
    GAME_QZEBG => 'GAME_QZEBG',
    GAME_QZSG => 'GAME_QZSG',
    GAME_DZZH => 'GAME_DZZH',
    GAME_XSZY => 'GAME_XSZY',
    GAME_RHEM => 'GAME_RHEM',
    GAME_CLZS => 'GAME_CLZS',
    GAME_THB => 'GAME_THB',
    GAME_ZEUS => 'GAME_ZEUS',
    GAME_DDML => 'GAME_DDML',
    GAME_JSBZ => 'GAME_JSBZ',
    GAME_KXGN => 'GAME_KXGN',
    GAME_CFYS => 'GAME_CFYS',
    GAME_HSBF => 'GAME_HSBF'
));

//逻辑服类型
define('LOGIC_CENTRAL', 0); //中心服
define('LOGIC_HALL', 1); //大厅
define('LOGIC_GAME', 2); //游戏

define('LOGIC_NAME', array(
    LOGIC_CENTRAL => 'LOGIC_CENTRAL',
    LOGIC_HALL => 'LOGIC_HALL',
    LOGIC_GAME => 'LOGIC_GAME',
));

define('CONCECT_NAME', array(
    LOGIC_CENTRAL => 'LOGIC_CENTRAL     : ',
    LOGIC_HALL => 'LOGIC_HALL      : ',
    LOGIC_GAME => 'LOGIC_GAME     : ',
));

define('LB_GAME_JACKPOT_MAX', 100000000); //拉拔奖池的最大值
define('LB_GAME_JACKPOT_MIN', 10000000); //拉拔奖池的最小值

define('GAMECONTROLS', true);
define('RANDSEAT', [GAME_XLDB => 1, GAME_LKPY => 1, GAME_JCBY => 1, GAME_DNTG => 1, GAME_ESYD => 1, GAME_DZPK => 1]);


define('OFFTIMES', 60);
define('ONLINE', 1);
define('OFFLINE', 2);

define("OUT_TIME", 120);  //退出房间时间


define('BEGINROOM', 1);

class MyGlobal
{
    //心跳设置
    public static $pingSwitch = true; //心跳开关
    public static $pingInterval = 15; //心跳间隔
    public static $pingNotResponseLimit = 1; //心跳次数
    public static $looptime = 5; //永久定时器间隔

    public static $buprint = 4; //0全部不打印 1只打印文档 2只打印小黑框 3全打印 4无视数组内消息

    //"中心服" 接收 "逻辑服" 消息调用函数
    public static $CenterLogicCallBackList = array();

    //"逻辑服" 接收 "客户端" 消息调用函数
    public static $LogicClientCallBackList = array(
        'Msg_Hall_EnterRoom' => '',
    );

    //"逻辑服" 接收 "中心服" 消息调用函数
    public static $LogicCenterCallBackList = array(
        'Msg_Hall_Connect' => 'SendToClient',
        'Msg_Hall_EnterRoom' => 'SendToClient',
    );

    //大厅 中心服不需要打印的消息
    public static $AllNoEcho = array(
        'CentralHeart',
        'Msg_Hall_Heart',
        'Msg_Hall_RoomList',
        'Msg_Hall_HorseLamp',
        'Msg_Game_Jackpot',
        'Msg_Hall_SyncGameTables',
    );

    //逻辑服不需要打印的消息
    public static $LogicNoEcho = array(
        LOGIC_CENTRAL => [],
        LOGIC_HALL => [],
        LOGIC_GAME => [],
    );

    //逻辑服不需要打印的消息
    public static $GameNoEcho = array(
        GAME_TEMPLATE => [],
        GAME_FQZS => [],
        GAME_BRNN => [],
        GAME_HBSL => [],
        GAME_SLWH => [],
        GAME_YYY => [],
        GAME_LHD => [],
        GAME_BCBM => [],
        GAME_BJL => [],
        GAME_HHDZ => [],
        GAME_XLDB => [
            'Msg_XLDB_CreateFish',
            'Msg_XLDB_ActShoot'
        ],
        GAME_LKPY => [
            'Msg_LKPY_CreateFish',
            'Msg_LKPY_ActShoot'
        ],
        GAME_JCBY => [
            'Msg_JCBY_CreateFish',
            'Msg_JCBY_ActShoot'
        ],
        GAME_DNTG => [
            'Msg_DNTG_CreateFish',
            'Msg_DNTG_ActShoot'
        ],
        GAME_QZNN => [],
        GAME_ERNN => [],
        GAME_JDDDZ => [],
        GAME_ERQS => [],
        GAME_TBNN => [],
        GAME_DZPK => [],
        GAME_ZJH => [],
        GAME_SRNN => [],
        GAME_JXLW => [],
        GAME_SHZ => [],
        GAME_XBLY => [],
        GAME_LDYX => [],
        GAME_DFDC => [],
        GAME_GGL => [],
        GAME_JSXN => [],
        GAME_WLPD => [],
        GAME_XMDJ => [],
        GAME_FFC => [],
        GAME_ESYD => [],
        GAME_QZEBG => [],
        GAME_QZSG => [],
        GAME_DZZH => [],
        GAME_XSZY => [],
        GAME_RHEM => [],
        GAME_CLZS => [],
        GAME_THB => [],
        GAME_ZEUS => [],
        GAME_DDML => [],
        GAME_JSBZ => [],
        GAME_KXGN => [],
        GAME_CFYS => [],
        GAME_HSBF => []
    );

    //逻辑服不需要打印的消息
    public static $LogicToHallMsg = array();

    //需要套接字的消息
    public static $NeedClient_id = array(
        'Msg_Hall_Heart', 'Msg_Hall_Connect',
    );

    //逻辑服不需要打印的消息
    public static $ClassMessage = array(
        GAME_TEMPLATE => [
            'Logic',
            'SendGlobal',
        ],
    );
}

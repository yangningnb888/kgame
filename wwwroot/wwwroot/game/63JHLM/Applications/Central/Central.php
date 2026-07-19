<?php

use \GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;

require_once __DIR__ . '/../Common/CentralCon.php';
require_once __DIR__ . '/../Common/DBInstance.php';
require_once __DIR__ . '/../Common/Common.php';
require_once __DIR__ . '/../Config/MyGlobal.php';

class Central
{
    //服务器维护状态
    public static $ServerStatus = 0;

    /**
     * 大厅服务器列表
     * $HallList[$businessid] => [
     *           'pnum' => 0, //人数
     *           'connection' => '$connection' //null连接断开 Connection对象
     *       ]
     */
    public static $HallList = array();

    /**
     * 游戏服务器列表
     * $GameList['游戏类型'] = array(
     *       '$businessid' => [
     *           'pnum' => 0, //人数
     *           'connection' => '$connection' //null连接断开 Connection对象
     *       ],
     *   );
     */
    public static $GameList = array();

    /**
     * 游戏房间
     * $GameRoom['游戏类型'] = array(
     *       'rids'
     *       ...
     *   );
     */
    public static $GameRoom = array();

    /**
     * 用户列表
     * $nUserList['uid'] = array(
     *      'uid' => 0,
     *      'client_id' => 0,
     * );
     * @var array
     */
    public static $nUserList = array();

    /**
     * 用户列表
     * $sUserList['client_id'] = 'uid'
     * @var array
     */
    public static $sUserList = array();

    /**
     * 用户所在房间、房间所在俱乐部、房间游戏gtype
     * $UserRoom['uid'] = array(
     *      'rid' => 0,
     *      'cid' => 0,
     *      'gtype' => 0,
     * );
     * @var array
     */
    public static $UserRoom = array();

    /**
     * 房间对应 rid 游戏服务器businessid
     * $RoomList['rid'] = array(
     *      'gtype' => 0,
     *      'level' => 0,
     *      'businessid' => 0,
     *      'players' => ['uid' => ....]
     * );
     * @var array
     */
    public static $RoomList = array();

    //缓存味绑定至大厅得用户
    public static $SaveConnect = array();
    //跑马灯列表
    private static $horseLamp = [];

    //循环次数
    private static $timerCount = 0;

    //逻辑服状态
    private static $LogicStatus = [];

    //--------------------------中心服用--------------------------

    /**
     * 打开服务器
     */
    public static function ServerBegin()
    {
        self::TimeLoop();
        //定时发送跑马灯
        Timer::add(MyGlobal::$looptime, function () {
            self::$timerCount++;
            self::SendHorseLamp();
            self::TimeLoop();
        }, array(), true);
    }

    /**
     * 循环
     */
    public static function TimeLoop()
    {
        $banuids = DBInstance::DealBan();
        foreach ($banuids as $key => $value) {
            if (isset(self::$nUserList[$value])) {
                $client_id = self::$nUserList[$value]['client_id'];
                Common::SendToClient(Common::MsgError(ERROR_LOGIN, '账号异常'), $client_id);
            }
        }

        if (rand(1, 3) == 1 && !empty(self::$HallList)) {
            self::ServerBeginRoom('', array(
                'event' => 'ServerBeginRoom',
                'area' => MyTools::$LTYPE,
                'uid' => -11111,
                'gtype' => array_keys(self::$GameList)
            ));
        }
    }

    /**
     * 查找分配游戏服
     */
    public static function FandRoom($gtype)
    {
        if (empty(self::$GameList[$gtype])) {
            return 0;
        }

        foreach (self::$GameList[$gtype] as $k => $val) {
            if ($val['connection'] && $val['pnum'] < Config::$maxPNum) {
                return $k;
            }
        }

        $businessid = 0;
        foreach (self::$GameList[$gtype] as $k => $val) {
            if ($val['connection'] && ($businessid == 0 || self::$GameList[$gtype][$businessid]['pnum'] > $val['pnum'])) {
                $businessid = $k;
            }
        }

        return $businessid;
    }

    /**
     * 逻辑服务器断开
     * @param $connection
     */
    public static function ClientClose($connection)
    {
        if (isset($connection->businessid)) {
            if (isset(self::$HallList[$connection->businessid])) {
                self::$HallList[$connection->businessid]['connection'] = null;
                if (self::$HallList[$connection->businessid]['pnum'] == 0) {
                    unset(self::$HallList[$connection->businessid]);
                }
                self::NotifyHallList();
            } else if (isset(self::$GameList[$connection->gtype])) {
                DBInstance::UpdateGameStatus($connection->gtype, 2);
                self::LogicClose($connection->gtype);
                if (isset(self::$GameList[$connection->gtype][$connection->businessid])) {
                    self::$GameList[$connection->gtype][$connection->businessid]['connection'] = null;
                    if (self::$GameList[$connection->gtype][$connection->businessid]['pnum'] == 0) {
                        unset(self::$GameList[$connection->gtype][$connection->businessid]);
                    }
                    if (empty(self::$GameList[$connection->gtype])) {
                        unset(self::$GameList[$connection->gtype]);
                    }
                }
            }
        }
    }

    /**
     * 逻辑服注册
     * @param $connection
     * @param $message
     */
    public static function LogicRegister($connection, $message)
    {
        $msg = array(
            'event' => 'LogicRegister',
            'area' => $message['area'],
            'msg' => 'Logic Register Success',
            'status' => 1, //0失败 1成功
            'connection' => $connection,
        );
        if (isset($connection->businessid)) {
            $msg['msg'] = '参数错误';
            $msg['status'] = 0;
            $connection->close(json_encode($msg));
            return;
        }

        $connection->businessid = $message['businessid'];
        $connection->gtype = $message['gtype'];
        $ltype = $message['area'];

        if ($ltype == LOGIC_HALL) {
            if (!isset(self::$HallList[$connection->businessid])) {
                self::$HallList[$connection->businessid] = array(
                    'pnum' => 0,
                    'connection' => null, //null连接断开 Connection对象
                );
            }
            self::$HallList[$connection->businessid]['connection'] = $connection;
        } else if ($ltype == LOGIC_GAME) {
            DBInstance::UpdateGameStatus($connection->gtype, 1);
            if (!isset(self::$GameList[$connection->gtype])) {
                self::$GameList[$connection->gtype] = [];
            }
            if (!isset(self::$GameList[$connection->gtype][$connection->businessid])) {
                self::$GameList[$connection->gtype][$connection->businessid] = array(
                    'pnum' => 0,
                    'connection' => null, //null连接断开 Connection对象
                );
            }
            self::$GameList[$connection->gtype][$connection->businessid]['connection'] = $connection;
        }

        unset($msg['connection']);
        CentralCon::SendToOther($connection, $msg);
        CentralCon::SendToOther($connection, array(
                'event' => 'ServerBegin',
                'area' => LOGIC_CENTRAL,
                'status' => 1,
                'gtype' => $message['gtype'],
                'data' => array(),
            )
        );
        self::NotifyHallList();

        if ($ltype == LOGIC_HALL) {
            foreach (Common::$NoSession as $key => $value) {
                Gateway::setSession($value, ['router' => $connection->businessid]);
                unset(Common::$NoSession[$key]);
            }

            foreach (self::$SaveConnect as $key => $value) {
                CentralCon::SendToOther(self::$HallList[$connection->businessid]['connection'], $value);
            }
        }
    }


    /**
     * 开服开放房间
     * @param $connection
     * @param $message
     */
    public static function ServerBeginRoom($connection, $message)
    {
        $businessid = array_rand(self::$HallList);
        CentralCon::SendToOther(self::$HallList[$businessid]['connection'], $message);
    }

    /**
     * 分发 同步大厅列表到逻辑服
     */
    public static function NotifyHallList()
    {
        $msg = array(
            'event' => 'HallList',
            'area' => LOGIC_HALL,
            'list' => array_keys(self::$HallList),
            'uid' => -11111,
        );

        foreach (self::$HallList as $key => $list) {
            $msg['area'] = LOGIC_HALL;
            if ($list['connection']) {
                CentralCon::SendToOther($list['connection'], $msg);
            }
        }

        foreach (self::$GameList as $key => $list) {
            $msg['area'] = $key;
            foreach ($list as $conn) {
                if ($conn['connection']) {
                    CentralCon::SendToOther($conn['connection'], $msg);
                }
            }
        }
    }

    /**
     * 与逻辑服心跳
     * @param $connection
     * @param $message
     */
    public static function CentralHeart($connection, $message)
    {
        $message['uid'] = -11111;
        CentralCon::SendToOther($connection, $message);
    }

    /**
     * 发送消息
     * @param $message
     */
    public static function SendToUid($message)
    {
        if (empty(self::$HallList)) {
            self::$SaveConnect[] = $message;
        } else {
            $businessid = array_rand(self::$HallList);
            CentralCon::SendToOther(self::$HallList[$businessid]['connection'], $message);
        }
    }

    /**
     * 发送错误消息
     * @param $connection
     * @param $uid
     * @param $state
     * @param $info
     * @param $client_id
     */
    public static function SendError($connection, $uid, $state, $info, $client_id = '')
    {
        CentralCon::SendToOther($connection, array(
            'event' => 'CloseClient',
            'client_id' => self::$nUserList[$uid]['client_id'] ?? $client_id,
            'uid' => $uid,
            'area' => LOGIC_CENTRAL,
            'status' => 1,
            'data' => array(
                'state' => $state,
                'info' => $info,
            ),
        ));
    }

    //--------------------------用户消息处理--------------------------

    /**
     * 转发
     * @param $connection
     * @param $message
     */
    public static function Transpond($connection, $message)
    {

    }

    /**
     * 游戏配置
     * @param $connection
     * @param $message
     */
    public static function Msg_Hall_GameStatus($connection, $message)
    {
        $old = self::$LogicStatus;
        foreach ($message['data'] as $key => $value) {
            foreach ($value as $key1 => $value1) {
                //判断配置中是否关闭游戏服
                self::$LogicStatus[$key1] = $value1;
                if ($value1 == 2 && !empty(self::$GameList[$key1])) {
                    $businessid = self::FandRoom($key1);
                    if (!empty(self::$GameRoom[$key1])) {
                        foreach (self::$GameRoom[$key1] as $key2 => $value2) {
                            self::Msg_Hall_Disband(self::$GameList[$key1][$businessid]['connection'], [
                                'event' => 'Msg_Hall_Disband',
                                'area' => 1,
                                'rid' => $key2,
                                'data' => [
                                    'rid' => $key2,
                                    'gtype' => $key1,
                                    'type' => 1
                                ]
                            ]);
                        }
                    }
                } elseif ((!isset($old[$key1]) || $old[$key1] == 2) && $value1 == 1) {
                    if (!empty(self::$GameList[$key1])) {
                        foreach (self::$GameList[$key1] as $key2 => $value2) {
                            CentralCon::SendToOther($value2['connection'], array(
                                    'event' => 'ServerBegin',
                                    'area' => LOGIC_CENTRAL,
                                    'status' => 1,
                                    'gtype' => $key1,
                                    'data' => array(),
                                )
                            );
                        }
                    }
                }
            }
        }

        foreach (self::$sUserList as $key => $value) {
            $message['uid'] = $value;
            Common::SendToClient($message, $key);
        }
    }

    /**
     * 用户登录
     * @param $connection
     * @param $message
     */
    public static function Msg_Hall_Connect($connection, $message)
    {
        $uid = $message['uid'];

        if (self::$ServerStatus) {
            self::SendError($connection, $uid, ERROR_LOGIN, '游戏维护中，请等待维护时间结束', $message['client_id']);
            return;
        }

        if (isset(self::$nUserList[$uid])) {
            MyTools::msg($message['uid'] . '----new:' . $message['client_id'] . '----old:' . self::$nUserList[$uid]['client_id']);
            self::SendError($connection, $uid, ERROR_LOGIN, '您的账号已在其他设备登陆');
            unset(self::$sUserList[self::$nUserList[$uid]['client_id']]);
        }

        //识别玩家是否重连
        $message['data']['rid'] = self::$UserRoom[$uid]['rid'] ?? 0;
        if (isset(self::$UserRoom[$uid]['rid'])) {
            $message['data']['level'] = self::$RoomList[$message['data']['rid']]['level'] ?? 0;
        }
        self::SendToUid($message);

        //玩家在线 状态
        DBInstance::UpdateUserOnline($uid);
        self::$nUserList[$uid] = $message['data'];
        self::$nUserList[$uid]['client_id'] = $message['client_id'];
        self::$sUserList[$message['client_id']] = $uid;
        Gateway::joinGroup($message['client_id'], 'ONLINE');
    }

    /**
     * 用户重连 加载完成 转发到游戏服务器
     */
    public static function Msg_Hall_FinishLoad($connection, $message)
    {
        $rid = $message['data']['rid'];
        $gtype = $message['data']['gtype'];
        $businessid = self::$RoomList[$rid]['businessid'];
        if (!empty(self::$GameList[$gtype][$businessid]['connection'])) {
            CentralCon::SendToOther(self::$GameList[$gtype][$businessid]['connection'], $message);
        }
    }

    /**
     * 玩家金币变动
     */
    public static function Msg_Hall_BankAccess($connection, $message)
    {
        if (!empty(self::$UserRoom[$message['uid']])) {
            $rid = self::$UserRoom[$message['uid']]['rid'];
            $gtype = self::$RoomList[$rid]['gtype'];
            $businessid = self::$RoomList[$rid]['businessid'];
            $message['event'] = 'Hall_BankAccess';
            CentralCon::SendToOther(self::$GameList[$gtype][$businessid]['connection'], $message);
        }
    }

    /**
     * 馆主解散房间 转发到游戏服务器
     */
    public static function Msg_Hall_Disband($connection, $message)
    {
        $rid = $message['data']['rid'];
        $gtype = $message['data']['gtype'];
        $businessid = self::$RoomList[$rid]['businessid'];
        CentralCon::SendToOther(self::$GameList[$gtype][$businessid]['connection'], $message);
    }

    /**
     * 进入房间
     */
    public static function EnterRoom($connection, $message)
    {
        if (!empty(self::$RoomList[$message['data']['rid']])) {
            self::$UserRoom[$message['uid']] = $message['data'];
            $gtype = self::$RoomList[$message['data']['rid']]['gtype'];
            DBInstance::SetUserPossition($message['uid'], $gtype, self::$RoomList[$message['data']['rid']]['level']);
            self::$GameRoom[$gtype][$message['data']['rid']][$message['uid']] = 1;
        }
    }

    /**
     * 退出房间
     */
    public static function QuitRoom($connection, $message)
    {
        $gtype = intval($message['data']['rid'] / 10000000);
        unset(self::$UserRoom[$message['data']['uid']]);
        unset(self::$GameRoom[$gtype][$message['data']['rid']][$message['data']['uid']]);
        $message['data']['client_id'] = self::$nUserList[$message['data']['uid']]['client_id'] ?? '';
        DBInstance::SetUserPossition($message['data']['uid'], 0, 0);
        $businessid = array_rand(self::$HallList);
        CentralCon::SendToOther(self::$HallList[$businessid]['connection'], $message);
    }

    /**
     * 房间开始
     * @param $connection
     * @param $message
     */
    public static function RoomBegin($connection, $message)
    {
        $rid = $message['data']['rid'];
        $gtype = $message['data']['gtype'];
        $player = $message['data']['players'];
        $businessid = self::FandRoom($gtype);
        if ($businessid == 0) {
            MyTools::log('FandRoom is not fand');
            self::LogicClose($gtype);
            return;
        }

        self::$RoomList[$rid] = $message['data'];
        if (!isset(self::$GameRoom[$gtype])) {
            self::$GameRoom[$gtype][$rid] = [];
        }
        self::$RoomList[$rid]['businessid'] = $businessid;
        self::$GameList[$gtype][$businessid]['pnum'] += count($message['data']['players']);
        foreach ($player as $k => $v) {
            $message['data']['players'][$k]['client_id'] = self::$nUserList[$k]['client_id'] ?? '';
            self::EnterRoom('', ['uid' => $k, 'data' => ['rid' => $rid]]);
        }
        CentralCon::SendToOther(self::$GameList[$gtype][$businessid]['connection'], $message);
    }

    /**
     * 玩家进房
     * @param $connection
     * @param $message
     */
    public static function UserEnter($connection, $message)
    {
        $rid = $message['data']['rid'];
        if (empty(self::$RoomList[$rid])) {
            self::QuitRoom('', [
                'event' => 'QuitRoom',
                'area' => MyTools::$LTYPE,
                'uid' => $message['uid'],
                'data' => [
                    'uid' => $message['data']['player']['uid'],
                    'rid' => $message['data']['rid'],
                ]
            ]);
            return;
        }

        $gtype = $message['data']['gtype'];
        $player = $message['data']['player'];
        self::$RoomList[$rid]['players'][$player['uid']] = $player;
        $businessid = self::FandRoom($gtype);
        if ($businessid == 0) {
            MyTools::log('FandRoom is not fand');
            self::LogicClose($gtype);
            return;
        }

        self::$RoomList[$rid]['businessid'] = $businessid;
        self::$GameList[$gtype][$businessid]['pnum']++;
        $message['data']['player']['client_id'] = self::$nUserList[$player['uid']]['client_id'] ?? '';
        CentralCon::SendToOther(self::$GameList[$gtype][$businessid]['connection'], $message);
    }

    /**
     * 解散房间
     * @param $connection
     * @param $message
     */
    public static function RoomOld($connection, $message)
    {
        $rid = $message['data']['rid'];
        $gtype = $message['data']['gtype'];
        $players = $message['data']['players'];
        $businessid = array_rand(self::$HallList);
        self::$GameList[$gtype][self::$RoomList[$rid]['businessid']]['pnum'] -= count($players);
        unset(self::$RoomList[$rid]);
        unset(self::$GameRoom[$gtype][$rid]);

        foreach ($players as $key => $val) {
            $message['data']['players'][$key]['client_id'] = self::$nUserList[$key]['client_id'] ?? '';
            unset(self::$UserRoom[$key]);
            DBInstance::SetUserPossition($key, 0, 0);
        }
        CentralCon::SendToOther(self::$HallList[$businessid]['connection'], $message);
    }

    /**
     * 跑马灯
     * @param $connection
     * @param $message
     */
    public static function HorseLamp($connection, $message)
    {
        self::$horseLamp[] = $message['data'];
        self::$timerCount = 0;
        self::SendHorseLamp();
    }

    /**
     * 游戏状态同步
     * @param $connection
     * @param $message
     */
    public static function TableStatus($connection, $message)
    {
        $businessid = array_rand(self::$HallList);
        CentralCon::SendToOther(self::$HallList[$businessid]['connection'], $message);
    }

    /**
     * 播报
     */
    public static function SendHorseLamp()
    {
        if (empty(self::$horseLamp) && self::$timerCount % 5 == 0 && self::$timerCount > 0) {
            $allgtype = DBInstance::GetGameStatus();
            unset($allgtype[1]);
            unset($allgtype[3]);
            unset($allgtype[5]);
            $gtypes = [];

            foreach ($allgtype as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    if (!empty(self::$GameList[$key1])) {
                        $gtypes[$key1] = $key;
                    }
                }
            }

            if (empty($gtypes)) {
                return;
            }

            $gtype = array_rand($gtypes);
            $level = DBInstance::GetDoubleScore($gtype);
            $df = $level[array_rand($level)];
            if ($gtypes[$gtype] == 2) {
                $double = rand(80, 200);
            } else {
                $score = rand(5000000, 15000000);
                $double = intval($score / $df);
            }

            if ($double * $df >= 5000000) {
                self::$horseLamp[] = [
                    'gtype' => array_rand($gtypes),
                    'nickname' => DBInstance::GetNickname(),
                    'double' => $double,
                    'score' => $double * $df,
                ];
            }
        }

        if (!empty(self::$horseLamp)) {
            $businessid = array_rand(self::$HallList);
            CentralCon::SendToOther(self::$HallList[$businessid]['connection'], [
                'event' => 'Msg_Hall_HorseLamp',
                'data' => array_shift(self::$horseLamp)
            ]);
        }
    }

    /**
     * 用户断开
     * @param $connection
     * @param $message
     */
    public static function UserClose($connection, $message)
    {
        //移除玩家
        if (isset(self::$sUserList[$message['client_id']])) {
            $uid = self::$sUserList[$message['client_id']];
            unset(self::$sUserList[$message['client_id']]);
            if ($message['client_id'] == self::$nUserList[$uid]['client_id']) {
                unset(self::$nUserList[$uid]);
                //玩家状态 推送
                DBInstance::UpdateUserOnline($uid, 2);
            }

            DBInstance::SetTokenTime($uid);
            $msg = array(
                'event' => 'GROUP_LEAVE',
                'area' => 1,
                'uid' => $uid,
                'client_id' => $message['client_id'],
                'data' => [
                    'uid' => $uid,
                ]
            );
            $businessid = array_rand(self::$HallList);
            CentralCon::SendToOther(self::$HallList[$businessid]['connection'], $msg);
            Gateway::leaveGroup($message['client_id'], 'ONLINE');
        }
    }

    /**
     * 逻辑服关闭同步至大厅
     * @param $gtype
     */
    private static function LogicClose($gtype)
    {
        if (!empty(self::$GameRoom[$gtype])) {
            foreach (self::$GameRoom[$gtype] as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    unset(self::$UserRoom[$key1]);
                }
            }
        }

        unset(self::$GameRoom[$gtype]);
        $msg = array(
            'event' => 'LOGIC_CLOSE',
            'area' => 1,
            'uid' => -11111,
            'data' => [
                'gtype' => $gtype,
            ]
        );
        if (!empty(self::$HallList)) {
            $businessid = array_rand(self::$HallList);
            CentralCon::SendToOther(self::$HallList[$businessid]['connection'], $msg);
        }
    }
}

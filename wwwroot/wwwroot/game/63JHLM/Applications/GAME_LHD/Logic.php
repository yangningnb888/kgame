<?php

use Workerman\Lib\Timer;
use \GatewayWorker\Lib\Gateway;

class Logic
{
    /**
     * 本机 IP:Name:worker_id
     * @var string
     */
    public static $businessWorkerID = '';

    /**
     * 逻辑服定时器id
     * @var int
     */
    public static $time_id = 0;

    /**
     * 用户列表
     * $nUserList['uid'] = ['rid' => 0, 'client_id' => '']
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
     * 房间列表
     * $RoomList['rid'] = array(
     *  'room'=> [],
     * 'palyers' => [],
     * 'rule' => [],
     * );
     * @var array
     */
    public static $RoomList = array();

    /**玩家当前房间
     * @var array
     */
    public static $UidRoom = [];

    //-----------------------------内部函数-----------------------------

    /**
     * 大厅注册返回
     * @param $message
     */
    public static function LogicRegister($message)
    {
        $status = $message['status'];
        if ($status == 1) {
            MyTools::msg($message['msg'] . '-success');
            self::ServerInit();
        } else {
            //注册错误
            MyTools::log($message['msg'] . '-failed');
        }
    }

    /**
     * 打开服务器
     */
    public static function ServerBegin($message)
    {
        Timer::add(1, function () use ($message) {
            self::serverBeginRoom($message['gtype']);
        }, array(), false);
    }

    /**
     * 开服启动房间
     */
    private static function serverBeginRoom ($gtype)
    {
        $beginroom = DBInstance::GetGameBeginRoom($gtype);
        if ($beginroom > 0) {
            //初始化房间
            CentralCon::SendToCentral(array(
                'event' => 'ServerBeginRoom',
                'area' => MyTools::$LTYPE,
                'uid' => -11111,
                'room' => $beginroom,
                'gtype' => $gtype
            ));
        }
    }

    /**
     * 初始化数据
     */
    public static function ServerInit()
    {
        self::$time_id = Timer::add(MyGlobal::$looptime, function () {
            self::TimeLoop();
        }, array(), true);
    }

    /**
     * 固定时间调用
     */
    public static function TimeLoop()
    {
        self::SendCentralHeart();
    }

    /**
     * 与中心服心跳
     */
    public static function SendCentralHeart()
    {
        CentralCon::SendToCentral(array(
            'event' => 'CentralHeart',
            'area' => MyTools::$LTYPE,
            'uid' => -11111,
        ));
    }

    /**
     * @param $message
     * 留用中心服心跳回调
     */
    public static function CentralHeart($message)
    {

    }

    /**
     * 用户断开连接 同步断开消息到中心服务器
     * @param $client_id
     */
    public static function UserClose($client_id)
    {
        if (!empty(self::$sUserList[$client_id])) {
            $uid = self::$sUserList[$client_id];
        } else {
            return;
        }

        self::$nUserList[$uid]['online'] = OFFLINE;
        $rid = self::$nUserList[$uid]['rid'];
        if (!empty(self::$RoomList[$rid]['room'])) {
            self::$RoomList[$rid]['room']->UserOff($uid);
        }

        //同步至中心服
        CentralCon::SendToCentral(array(
            'event' => 'UserClose',
            'area' => MyTools::$LTYPE,
            'client_id' => $client_id,
            'uid' => isset(self::$sUserList[$client_id]) ? self::$sUserList[$client_id] : -11111,
        ));
    }

    //-----------------------------中心服消息-----------------------------
    public static function Msg_Hall_FinishLoad($message)
    {
        if (!isset(self::$nUserList[$message['uid']])) {
            Common::SendToUID (
                [
                    'event' => 'Msg_Hall_FinishLoad',
                    'area' => MyTools::$LTYPE,
                    'uid' => $message['uid'],
                    'client_id' => $message['client_id'],
                    'status' => 0,
                    'msg' => '当前玩家不在游戏中',
                    'data' => [],
                ]);
            return;
        }

        self::$sUserList[$message['client_id']] = $message['uid'];
        self::$nUserList[$message['uid']]['client_id'] = $message['client_id'];
        self::$nUserList[$message['uid']]['rid'] = $message['data']['rid'];
        self::$nUserList[$message['uid']]['online'] = ONLINE;
        if ($message['client_id'] != '') {
            Gateway::setSession($message['client_id'], ['router' => self::$businessWorkerID]);
        }
        self::SendRight($message['uid'], 'Msg_Hall_FinishLoad', $message['data']);
        self::$RoomList[$message['data']['rid']]['room']->UserOnline($message['client_id'], $message['uid']);
    }

    //玩家金币变动
    public static function Hall_BankAccess ($message)
    {
        if (empty(self::$UidRoom[$message['uid']])) {
            return;
        }
        $rid = self::$UidRoom[$message['uid']];

        if (!empty(self::$RoomList[$rid]['room'])) {
            self::$RoomList[$rid]['room']->ChangeGold($message);
        }
    }

    //强制解散房间
    public static function Msg_Hall_Disband($message)
    {
        if (!isset(self::$RoomList[$message['data']['rid']])) {
            return; //房间已解散
        }

        MyTools::msg($message['event'] . "type::" . $message['data']['type']);

        //通知玩家解散房间
        self::$RoomList[$message['rid']]['room']->DisRoom($message);
    }

    /**
     * 回放列表
     * @param [type] $client_id
     * @param [type] $msg
     * @return void
     */
    public static function Msg_Game_Back_List($client_id, $msg)
    {
        $gtype = self::$RoomList[self::$UidRoom[$msg['uid']]]['gtype'];
        $data = DBInstance::GetBackAll($gtype);
        self::SendRight($msg['uid'], 'Msg_Game_Back_List', $data);
    }

    /**
     * 回放详情
     * @param [type] $client_id
     * @param [type] $msg
     * @return void
     */
    public static function Msg_Game_Back_Info($client_id, $msg)
    {
        $data = DBInstance::GetBankOne($msg['data']['id']);
        self::SendRight($msg['uid'], 'Msg_Game_Back_Info', $data);
    }

    /**
     * 查询奖池
     * @param [type] $client_id
     * @param [type] $msg
     * @return void
     */
    public static function Msg_Game_Jackpot($client_id, $msg)
    {
        $gtype = self::$RoomList[self::$UidRoom[$msg['uid']]]['gtype'];
        $level = self::$RoomList[self::$UidRoom[$msg['uid']]]['level'];
        $res = DBInstance::GetTableOneWord('game_jackpot', 'jackpot', ['gtype' => $gtype, 'level' => $level]);
        if ($res === false) {
            DBInstance::SaveData('game_jackpot',  ['gtype' => $gtype, 'level' => $level]);
            $res = DBInstance::GetTableOneWord('game_jackpot', 'jackpot', ['gtype' => $gtype, 'level' => $level]);
        }

        self::SendRight($msg['uid'], 'Msg_Game_Jackpot', ['score' => $res]);
    }

    //房间初始化
    public static function RoomBegin($message)
    {
        $num = 0;
        foreach ($message['data']['players'] as $key => $val) {
            self::$sUserList[$val['client_id']] = $key;
            self::$nUserList[$key] = ['client_id' => $val['client_id'], 'rid' => $message['data']['rid']];
            self::$nUserList[$key]['rid'] = $message['data']['rid'];
            self::$nUserList[$key]['online'] = ONLINE;
            if ($val['client_id'] != '') {
                Gateway::setSession($val['client_id'], ['router' => self::$businessWorkerID]);
            }
            self::$UidRoom[$key] = $message['data']['rid'];
            if ($val['client_id'] != '') {
                $num++;
            }
        }

        DBInstance::UpdateInGame(MyTools::$GTYPE, $num);
        self::$RoomList[$message['data']['rid']] = $message['data'];
        self::$RoomList[$message['data']['rid']]['room'] = new Room($message['data']);
    }

    //玩家进房
    public static function UserEnter ($message)
    {
        if (!isset(self::$RoomList[$message['data']['rid']])) {
            CentralCon::SendToCentral([
                'event' => 'QuitRoom',
                'area' => MyTools::$LTYPE,
                'uid' => $message['data']['player']['uid'],
                'data' => [
                    'uid' => $message['data']['player']['uid'],
                    'rid' => $message['data']['rid'],
                ]
            ]);

            $msg = Common::MsgError(3, '房间已经解散');
            Common::SendToClient($msg, $message['data']['player']['client_id']);
            return;
        }
        $uid = $message['data']['player']['uid'];
        self::$UidRoom[$message['data']['player']['uid']] = $message['data']['rid'];
        self::$sUserList[$message['data']['player']['client_id']] = $uid;
        self::$nUserList[$uid]['client_id'] = $message['data']['player']['client_id'];
        self::$nUserList[$uid]['rid'] = $message['data']['rid'];
        self::$nUserList[$uid]['online'] = ONLINE;
        self::$RoomList[$message['data']['rid']]['players'][$uid] = $message['data']['player'];
        if ($message['data']['player']['client_id'] != '') {
            Gateway::setSession($message['data']['player']['client_id'], ['router' => self::$businessWorkerID]);
            DBInstance::UpdateInGame(MyTools::$GTYPE, 1);
        }
        self::$RoomList[$message['data']['rid']]['room']->EnterRoom($message['data']['player']);
    }

    //玩家退出房间
    public static function QuitRoom ($data)
    {
        if (!isset(self::$RoomList[$data['rid']])) {
            return;
        }
        unset(self::$UidRoom[$data['uid']]);
        unset(self::$RoomList[$data['rid']]['players'][$data['uid']]);
        if (self::$nUserList[$data['uid']]['client_id'] != '') {
            Gateway::leaveGroup(self::$nUserList[$data['uid']]['client_id'], 'ROOM:' . $data['rid']);
            DBInstance::UpdateInGame(MyTools::$GTYPE, -1);
        }
        unset(self::$nUserList[$data['uid']]);
        CentralCon::SendToCentral([
            'event' => 'QuitRoom',
            'area' => MyTools::$LTYPE,
            'uid' => $data['uid'],
            'data' => $data
        ]);
    }

    //同步房间状态
    public static function TableStatus ($rid, $status)
    {
        CentralCon::SendToCentral([
            'event' => 'TableStatus',
            'area' => MyTools::$LTYPE,
            'uid' => -11111,
            'data' => [
                'rid' => $rid,
                'status' => $status
            ]
        ]);
    }

    /**
     * 回收房间
     * @param $data
     */
    public static function RoomOld($data)
    {
        if (empty(self::$RoomList[$data['rid']])) {
            return;
        }

        unset(self::$RoomList[$data['rid']]['room']);
        $num = 0;
        foreach (self::$RoomList[$data['rid']]['players'] as $key => $val) {
            if (!empty(self::$nUserList[$key]['client_id'])) {
                $num++;
            }
            unset(self::$UidRoom[$key]);
        }
        DBInstance::UpdateInGame(MyTools::$GTYPE, -$num);
        CentralCon::SendToCentral([
            'event' => 'RoomOld',
            'area' => MyTools::$LTYPE,
            'uid' => -11111,
            'data' => self::$RoomList[$data['rid']]
        ]);
        unset(self::$RoomList[$data['rid']]);
    }

    /**
     * 增加游戏盈亏
     */
    public static function InsertProfit ($level, $num)
    {
        if ($level != 1 || MyTools::$GTYPE == GAME_GGL) {
            DBInstance::InsertGameProfit(MyTools::$GTYPE, $level, -$num);
        }
    }

    /**
     * 跑马灯信息
     */
    public static function HorseLamp ($uid, $score, $double)
    {
        CentralCon::SendToCentral([
            'event' => 'HorseLamp',
            'area' => MyTools::$LTYPE,
            'uid' => $uid,
            'data' => [
                'gtype' => MyTools::$GTYPE,
                'nickname' => DBInstance::GetUserOneWord('nickname', $uid),
                'double' => $double,
                'score' => $score,
            ]
        ]);
    }

    //发送消息
    /**
     * 发送给所有人正确消息
     */
    public static function SendAll ($msgname, $array, $rid)
    {
        Common::SendToGroup('ROOM:' . $rid, [
            'event' => $msgname,
            'area' => MyTools::$LTYPE,
            'status' => 1,
            'msg' => '',
            'data' => $array
        ]);
    }

    /**
     * 发送错误消息
     */
    public static function SendError($uid, $msgname, $msg)
    {
        if (!empty(self::$nUserList[$uid]['client_id']) && self::$nUserList[$uid]['online'] == ONLINE) {
            Common::SendToClient([
                'event' => $msgname,
                'area' => MyTools::$LTYPE,
                'uid' => $uid,
                'client_id' => self::$nUserList[$uid]['client_id'],
                'status' => 0,
                'msg' => $msg,
                'data' => [],
            ], self::$nUserList[$uid]['client_id']);
        }
    }

    /**
     * 发送正确消息
     */
    public static function SendRight($uid, $msgname, $array)
    {
        if (!empty(self::$nUserList[$uid]['client_id']) && self::$nUserList[$uid]['online'] == ONLINE) {
            Common::SendToClient([
                'event' => $msgname,
                'area' => MyTools::$LTYPE,
                'uid' => $uid,
                'client_id' => self::$nUserList[$uid]['client_id'],
                'status' => 1,
                'msg' => "",
                'data' => $array,
            ], self::$nUserList[$uid]['client_id']);
        }
    }
}

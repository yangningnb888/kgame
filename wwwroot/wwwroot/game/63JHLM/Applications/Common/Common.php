<?php

use \GatewayWorker\Lib\Gateway;

class Common
{
    /**
     * 房间号生成起始位
     * bcmod(bcpow($curRoomid, 29), 898837)
     * @var int
     */
    private static $curRoomid = 2;

    /**
     * 房间号回收队列 【房间号生成完之后才使用】
     * $curRoomidList = array(房间号1，房间号2，...)
     * 出队列 array_shift($curRoomidList)
     * 入队列 $curRoomidList[] = 房间号
     * @var array
     */
    private static $curRoomidList = array();

    /**
     * 用户列表
     * $nUserList['uid'] = 'client_id'
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
     * 大厅服列表
     * @var array
     */
    public static $HallList = array();


    /**
     * 登录未绑定路由账号
     * @var  array
     */
    public static $NoSession = array();

    /**
     * 登录账号
     * @var  array
     */
    public static $UserStatus = array();

    /**
     * 维护公告
     * @var  string
     */
    public static $Maintenance = '';


    /**
     * 生成房间号
     */
    public static function CreatedRoomID($gtype)
    {
        if (self::$curRoomid >= MyTools::$maxRandID6) {
            $ret = array_shift(self::$curRoomidList);
        } else {
            $ret = MyTools::RandID6(self::$curRoomid) + 100000;
            self::$curRoomid += 1;
        }
        //return (int) $ret + 100000 + $cid * 1000000;
        $ret += $gtype * 10000000;
        return (int)$ret;
    }

    /**
     * 回收房间号
     */
    public static function OldRoomID($rid)
    {
        self::$curRoomidList[] = $rid % 10000000;
    }

    /**
     * 大厅列表
     * @param $message
     */
    public static function HallList($message)
    {
        self::$HallList = array_flip($message['list']);
    }

    /**
     * 用户连接
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_Connect($client_id, $message)
    {
        //获取用户token
        $auth = DBInstance::GetUserAuth($message['data']['token']);
        if (empty($auth)) {
            MyTools::msg("Connect Error : " . $client_id);
            self::SendToClient(self::MsgError(ERROR_LOGIN, '账号异常'), $client_id);
            return;
        } elseif (isset($auth['create_time']) && $auth['create_time'] < time()) {
            MyTools::msg("Connect Error Timeout: " . $client_id);
            self::SendToClient(self::MsgError(ERROR_LOGIN, '离线超时,请重新登录'), $client_id);
            return;
        }

        //获取用户信息
        DBInstance::CheckMotnly($auth['uid']);
        $userinfo = DBInstance::GetTableWords('user', '*', ['uid' => $auth['uid']]);
        $status = DBInstance::GetUserStauts($_SERVER['REMOTE_ADDR'], $userinfo['equipmentcard']);
        if ($status) {
            MyTools::msg("Connect Error : " . $client_id);
            DBInstance::UpdateUserString($userinfo['uid'], ['last_ip' => $_SERVER['REMOTE_ADDR'], 'status' => 0]);
            self::SendToClient(self::MsgError(ERROR_LOGIN, '账号存在异常，请联系管理'), $client_id);
            return;
        }

        $user = DBInstance::GetUser(['uid' => $auth['uid'], 'status' => 1]);
        //网关绑定
        Gateway::bindUid($client_id, $user['uid']);
        if (empty(self::$HallList)) {
            Common::$NoSession[$user['uid']] = $client_id;
        } else {
            Gateway::setSession($client_id, ['router' => array_rand(self::$HallList)]);
        }

        if (empty($user) || empty($user['uid'])) {
            MyTools::msg("Connect Error : " . $client_id);
            self::SendToClient(self::MsgError(ERROR_LOGIN, '账号异常'), $client_id);
            return;
        }

        $gamestatus = DBInstance::GetGameMaintenance();
        if ($gamestatus != 0) {
            self::SendToClient(self::MsgError(ERROR_LOGIN, '当前服务器维护中，请稍后再试'), $client_id);
            return;
        }

        self::$UserStatus[$user['uid']] = 0;
        DBInstance::UpdateUserString($user['uid'], ['last_ip' => $_SERVER['REMOTE_ADDR']]);
        $message['area'] = MyTools::$LTYPE;
        $message['status'] = 1;
        $message['uid'] = $user['uid'];
        $message['client_id'] = $client_id;
        $message['data'] = $user;
        $tel = DBInstance::GetRegister(['uid' => $user['uid']]);
        $message['data']['isbind'] = empty($tel) ? '' : $tel['telephone'];
        $message['data']['mail'] = DBInstance::GetMailStatus($user['uid']);
        $message['data']['gamestatus'] = DBInstance::GetGameStatus();
        $message['data']['maintenance'] = DBInstance::GetGameWH();
        $ret = DBInstance::GetTableWords('monthlycard', '*', ['uid' => $user['uid']]);
        $date = strtotime($ret['endtime']) - strtotime(MyTools::GET_TODAY());
        $message['data']['monthcard'] = $date > 0 ? round($date / 24 / 60 / 60) : 0;
        if (isset($ret['lastsigntime']) && $ret['lastsigntime'] == MyTools::GET_TODAY()) {
            $message['data']['lastsigntime'] = 1;
        } else {
            $message['data']['lastsigntime'] = 0;
        }
        $message['data'] += DBInstance::GetAgents();
        CentralCon::SendToCentral($message);
        if (strlen($user['uid']) >= 8) {
            $res = DBInstance::GetTableOneWord('monthlycard', 'uid', ['uid' => $auth['uid']]);
            if (!$res) {
                DBInstance::InsertMonthlyCard($auth['uid']);
            }
        }
        //玩家在线 状态
        DBInstance::UpdateUserOnline($user['uid']);
    }

    //修改玩家头像
    public static function Msg_Hall_ChangeHeadFrame($client_id, $message)
    {
        $ret = DBInstance::UpdateUserString($message['uid'], ['headimgurl' => $message['data']['head']]);
        if ($ret) {
            $msg = Common::NoticeMsg($message['event'], $message['uid']);
        } else {
            $msg = Common::NoticErrorMsg($message['event'], '修改失败', $message['uid']);
        }
        Common::SendToClient($msg, $client_id);
    }

    //修改玩家头像框
    public static function Msg_Hall_ChangePictureFrame($client_id, $message)
    {
        $uid = $message['uid'];
        $endtime = DBInstance::GetTableOneWord('monthlycard', 'endtime', ['uid' => $uid]);
        $flagtime = strtotime(MyTools::GET_TODAY());
        if ($endtime && strtotime($endtime) > $flagtime) {
            $endtime = strtotime($endtime);
            if ($endtime > $flagtime) {
                DBInstance::UpdateUserString($uid, ['pictureframe' => $message['data']['pictureframe']]);
                $msg = Common::NoticeMsg($message['event'], $message['uid']);
                Common::SendToClient($msg, $client_id);
                return;
            }
        }
        $msg = Common::NoticErrorMsg($message['event'], '没有权限', $message['uid']);
        Common::SendToClient($msg, $client_id);
    }

    //修改玩家昵称
    public static function Msg_Hall_ChangeNickName($client_id, $message)
    {
        $ret = DBInstance::UpdateUserString($message['uid'], ['nickname' => $message['data']['name']]);
        if ($ret) {
            $msg = Common::NoticeMsg($message['event'], $message['uid']);
        } else {
            $msg = Common::NoticErrorMsg($message['event'], '修改失败', $message['uid']);
        }
        Common::SendToClient($msg, $client_id);
    }

    /**
     *  银行存取
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_BankAccess($client_id, $message)
    {
        $money = $message['data']['gold'];
        if (!is_numeric($money)) {
            Common::SendToClient(Common::MsgError(ERROR_WARN, '金额错误', $message['uid']), $client_id);
            return;
        }

        //三方用户不能存取
        $user = DBInstance::GetUser(['uid' => $message['uid']]);
        if (!empty($user['openid'])) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '玩家不被允许操作', $message['uid']), $client_id);
            return;
        }

        if (abs($money) < 100) {
            Common::SendToClient(Common::MsgError(ERROR_WARN, '金额不能低于100', $message['uid']), $client_id);
            return;
        } else {  //银行取出
            $string = $money > 0 ? 'bank' : 'gold';
            $ret = DBInstance::GetUserOneWord($string, $message['uid']);
            if ($ret < abs($money)) {
                Common::SendToClient(Common::MsgError(ERROR_WARN, '金额过大', $message['uid']), $client_id);
                return;
            }
        }

        $ret = DBInstance::BankDeposit($message['uid'], $money);
        $message['status'] = 1;
        $message['data'] = [
            'bank' => DBInstance::GetUserOneWord('bank', $message['uid']),
            'gold' => DBInstance::GetUserOneWord('gold', $message['uid']),
        ];

        if ($ret) {
            Common::SendToClient($message, $client_id);
            CentralCon::SendToCentral($message);
        } else {
            Common::SendToClient(Common::MsgError(ERROR_WARN, '执行错误', $message['uid']), $client_id);
        }
    }

    /**
     *  购买月卡
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_BuyMonthlyCard ($client_id, $message)
    {
        $uid = $message['uid'];
        $endtime = DBInstance::GetTableOneWord('monthlycard', 'endtime', ['uid' => $uid]);
        $flagtime = strtotime(MyTools::GET_TOMORROW());
        if ($uid < 10000000) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '代理无法购买月卡', $message['uid']), $client_id);
            return;
        }

        //判定是否可以领取月卡
        if (strtotime($endtime) > $flagtime) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '当月月卡生效中,无法领取', $message['uid']), $client_id);
            return;
        }

        $bank = DBInstance::GetUserOneWord('bank', $uid);
        if ($bank < MONTHLYCARD) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '购买月卡需银行金币满足5000000', $message['uid']), $client_id);
            return;
        }

        //月卡直接入账
        DBInstance::IncrementGolds('bank', $uid, -AGENTMONTHLYCARD);
        $upadte = [];
        $time = MyTools::GET_TODAY();
        if (strtotime($endtime) < strtotime($time)) {
            $upadte['createtime'] = $time;
        }
        if (strtotime($endtime) > strtotime($time)) {
            $time = $endtime;
        }
        $upadte['endtime'] = date('Y-m-d', strtotime($time) + 30 * 24 * 60 * 60);
        DBInstance::UpdateMonthlyCard($uid, $upadte);
        Common::SendToClient(
            [
                'event' => 'Msg_Hall_BuyMonthlyCard',
                'area' => MyTools::$LTYPE,
                'client_id' => $client_id,
                'uid' => $uid,
                'status' => 1,
                'data' => [
                    'bank' => DBInstance::GetUserOneWord('bank', $uid)
                ],
            ]

        );
    }

    //用户心跳消息
    public static function Msg_Hall_Heart($client_id, $message)
    {
        $message['status'] = 1;
        self::SendToClient($message, $client_id);
    }

    /**
     * 查询奖池
     * @param [type] $client_id
     * @param [type] $msg
     * @return void
     */
    public static function Msg_Game_Jackpot($client_id, $msg)
    {
        $where = [];
        if (!empty($msg['data'])) {
            if (!isset($msg['data']['gtype']) || $msg['data']['level'] <= 0) {
                Common::SendToClient(Common::NoticErrorMsg('Msg_Game_Jackpot', '参数错误', $msg['uid']), $client_id);
                return;
            }
            $where['gtype'] = $msg['data']['gtype'];
            if (isset($msg['data']['level'])) {
                $where['level'] = $msg['data']['level'];
            }
        }

        if (isset($msg['data']['gtype'])  && $msg['data']['gtype'] == GAME_DFDC) {
            $where['level'] = 1;
        }

        $res = DBInstance::GetTableArr($where);

        if ($res === false) {
            Common::SendToClient(Common::NoticErrorMsg('Msg_Game_Jackpot', '参数错误', $msg['uid']), $client_id);
            return;
        }
        Common::SendToClient(
            [
                'event' => 'Msg_Game_Jackpot',
                'area' => MyTools::$LTYPE,
                'client_id' => $client_id,
                'uid' => $msg['uid'],
                'status' => 1,
                'data' => $res,
            ]

        );
    }

    /**
     * 通知玩家金币变动
     * @param $uid
     */
    public static function Msg_Hall_ChangeGolds($client_id, $msg)
    {
        $uid = $msg['uid'];
        $msg = Common::NoticeMsg('Msg_Hall_ChangeGolds', $uid);
        $msg['data'] = DBInstance::GetTableWords('user', 'gold,bank,rcard', ['uid' => $uid]);
        Common::SendToClient($msg, $client_id);
    }

    public static function ChangeJackpot($gtype)
    {
        $res = DBInstance::GetTableArr(['gtype' => $gtype]);

        foreach ($res as $key => $val) {
            $score = 0;
            $score = intval($val['jackpot'] * 0.01);
            if (($val['jackpot'] > LB_GAME_JACKPOT_MIN && $val['jackpot'] < LB_GAME_JACKPOT_MAX)) {
                $rand = rand(0, 100);
                if ($rand > 50) {
                    $score = -$score;
                }
            } elseif ($val['jackpot'] >= LB_GAME_JACKPOT_MAX) {
                $score = -$score;
            }
            DBInstance::SaveJackpot($val['gtype'], $val['level'], $score);
        }
    }

    /**
     * 断开玩家连接
     * @param $message
     */
    public static function CloseClient($message)
    {
        self::SendToClient(array(
            'event' => 'Msg_Hall_ERROR',
            'area' => MyTools::$LTYPE,
            'client_id' => $message['client_id'],
            'uid' => -11111,
            'status' => 1,
            'data' => $message['data'],
        ));
        Gateway::closeClient($message['client_id']);
    }

    /**
     * 错误消息
     * @param $state //1退出登陆 2纯提示 3返回大厅
     * @param $info //提示内容
     * @return array
     */
    public static function MsgError($state, $info, $uid = -111111)
    {
        return array(
            'event' => 'Msg_Hall_ERROR',
            'area' => MyTools::$LTYPE,
            'uid' => $uid,
            'status' => 1,
            'data' => array(
                'state' => $state,
                'info' => $info,
            )
        );
    }

    public static function NoticErrorMsg($event, $msg, $uid)
    {
        return array(
            'event' => $event,
            'area' => MyTools::$LTYPE,
            'uid' => $uid,
            'status' => 0,
            'msg' => $msg,
            'data' => array()
        );
    }

    public static function NoticeMsg($event, $uid)
    {
        return array(
            'event' => $event,
            'area' => MyTools::$LTYPE,
            'uid' => $uid,
            'status' => 1,
            'data' => array()
        );
    }

    /**
     * 发送消息至客户端
     * @param $message //消息内容
     * @param $client_id //玩家套接字
     */
    public static function SendToClient($message, $client_id = '')
    {
        if (empty($client_id)) {
            if (!empty($message['client_id'])) {
                $client_id = $message['client_id'];
                unset($message['client_id']);
            } else {
                return false;
            }
        }

        $uid = Gateway::getUidByClientId($client_id);
        MyTools::msg('uid : ' . $uid . '        SendToClient  : ' . $message['event'], $message['event']);
        Gateway::sendToClient($client_id, base64_encode(json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        return true;
    }

    /**
     * 保存客户端错误
     * @param $client_id //玩家套接字
     * @param $msg //消息内容
     */
    public static function SaveClientError ($client_id, $msg)
    {
        MyTools::log($msg['data'], 1);
    }

    /**
     * 发送消息至客户端
     * @param $message //消息内容
     * @param $uid //玩家uid
     */
    public static function SendToUID($message, $uid = '')
    {
        if (empty($uid)) {
            if (!isset($message['uid'])) {
                MyTools::msg("SendToUID Error : uid not null msg => " . json_encode($message));
                return;
            }
            $uid = $message['uid'];
        }
        MyTools::msg('uid : ' . $uid . '        SendToClient  : ' . $message['event'], $message['event']);
        Gateway::sendToUid($uid, base64_encode(json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }

    /**
     * 发送消息至客户端
     * @param $group //用户组
     * @param $message //消息
     * @throws Exception
     */
    public static function SendToGroup($group, $message)
    {
        MyTools::msg('group : ' . $group . '        SendToClient  : ' . $message['event'], $message['event']);
        Gateway::sendToGroup($group, base64_encode(json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }

    /**
     * 发送消息至客户端
     * @param $rid //房间号
     * @param $message //消息
     * @throws Exception
     */
    public static function SendToRoom($rid, $message)
    {
        self::SendToGroup('ROOM:' . $rid, $message);
    }
}

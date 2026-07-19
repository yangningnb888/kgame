<?php

use \GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;

class Hall
{
    /**
     * 逻辑服定时器id
     * @var int
     */
    public static $time_id = 0;

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
     * 游戏状态
     * $GameStatus['seession']['gtype'] = '状态 1 开启 2 关闭'
     * @var array
     */
    public static $GameStatus = array();

    /**
     * 桌子缓存
     * $TableInfo['gtype']['level'] = [
     *  'id' => [
     *      'rid' => '房间号',
     *      'robot' => '房间内机器人数量',
     *      'players' => [
     *          'seat' => [
     *              'head' =>'玩家头像',
     *          ]
     *      ]
     *  ]
     * ]
     * @var array
     */
    public static $TableInfo = array();

    /**
     * 房间缓存
     * $RoomInfo['rid'] [
     *      'tid' => '桌号',
     *      'rid' => '房号',
     *      'gtype' => '游戏类型',
     *      'level' => '房间等级',
     *      'status' => '状态 2 匹配中 1 游戏中',
     *      'min_pnum' => '房间开始人数',
     *      'max_pnum' => '房间人数上线',
     *      'min_gold' => '参赛最低分',
     *      'max_gold' => '参赛最高分',
     *      'players' => [
     *          'uid' => [
     *              'head' =>'玩家头像',
     *              ...
     *          ]
     *      ]
     *  ]
     * ]
     * @var array
     */
    public static $RoomInfo = array();

    //用户是否在游戏中 $UserRoom[uid] = ['cid' => 0, 'rid' => 0]
    public static $UserRoom = array();

    //陪玩进房数量配置 $RobotEnter[gtype][level] = ['ai_base' => 0, 'ai_max' => 0, 'inroom' => 0],
    private static $RobotEnter = array();

    //日期缓存
    private static $DateTime = '';

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
        } else {
            //注册错误
            MyTools::msg($message['msg'] . '-failed', true);
        }
        self::$DateTime = DBInstance::GetTableOneWord('sysconfig', 'val', ['key' => 'SEVER_DATE']);
    }

    /**
     * 打开服务器
     */
    public static function ServerBegin($message)
    {
        self::$RobotEnter = DBInstance::RobotGameSessions();
        self::TimeLoop();
        self::$time_id = Timer::add(3, function () {
            self::TimeLoop();
        }, array(), true);
    }

    /**
     * 服务器关闭
     * @param $message
     */
    public static function ServerOver($message)
    {

    }

    /**
     * 固定时间调用
     */
    public static function TimeLoop()
    {
        self::SendCentralHeart();
        $str = Common::$Maintenance;
        Common::$Maintenance = DBInstance::GetGameWH();
        if ($str != Common::$Maintenance) {
            self::Maintenance();
        }

        if (self::$DateTime != MyTools::GET_TODAY()) {
            self::$DateTime = MyTools::GET_TODAY();
            DBInstance::UpdateSeverDate(self::$DateTime);
            DBInstance::InspectJurisdiction();
            DBInstance::UpdateWinPointsList();
            DBInstance::UpdateRcardTime();
        }

        DBInstance::UpdateRobotGold();

        //定时获取游戏信息
        $data = DBInstance::GetGameStatus();
        self::RobotAdmission();
        if ($data != self::$GameStatus) {
            self::$GameStatus = $data;
            CentralCon::SendToCentral(array(
                'event' => 'Msg_Hall_GameStatus',
                'area' => 1,
                'uid' => -11111,
                'status' => 1,
                'data' => self::$GameStatus
            ));
        }
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
        if (isset(self::$sUserList[$client_id])) {
            $uid = self::$sUserList[$client_id];
        }

        CentralCon::SendToCentral(array(
            'event' => 'UserClose',
            'area' => MyTools::$LTYPE,
            'client_id' => $client_id,
            'uid' => $uid ?? -11111,
        ));
    }

    //中心服消息-----------------------------------------------------------------

    /**
     * 用户断开连接
     * @param $message
     */
    public static function GROUP_LEAVE($message)
    {
        $uid = $message['uid'];
        $client_id = $message['client_id'];

        //删除用户组
        if (!empty(self::$UserRoom[$uid]['cid'])) {
            if ($client_id != '') {
                Gateway::leaveGroup($client_id, 'HALL:' . self::$UserRoom[$uid]['cid']);
            }
            $rid = self::$UserRoom[$uid]['rid'] ?? 0;
            $gtype = intval(self::$UserRoom[$uid]['cid'] / 1000);
            $level = self::$UserRoom[$uid]['cid'] % 100;

            if ($rid > 0 && self::$RoomInfo[$rid]['status'] != 1) {
                $seat = self::$RoomInfo[$rid]['players'][$uid]['seat'];
                $tid = self::$RoomInfo[$rid]['tid'];
                unset(self::$RoomInfo[$rid]['players'][$uid]);
                unset(self::$TableInfo[$gtype][$level][$tid][$seat + 1]);
                unset(self::$UserRoom[$uid]);
            }
        }
    }

    /**
     * 开服开放房间
     * @param $message
     */
    public static function ServerBeginRoom($message)
    {
        $gtypes = [];
        if (is_array($message['gtype'])) {
            $gtypes = $message['gtype'];
        } else {
            $gtypes[] = $message['gtype'];
        }

        foreach ($gtypes as $key => $value) {
            $level = DBInstance::GetGameLevel($value);
            $status = DBInstance::GetTableOneWord('gamestatus', 'status', ['gtype' => $value]);

            if ($status != 1) {
                continue;
            }

            //安排桌号
            foreach ($level as $key1 => $value1) {
                $config = DBInstance::GetGameConfig($value, $key1);
                $num = $value1 < 2 ? $value1 : rand(intval($value1 / 2), $value1);
                $num -= empty(self::$TableInfo[$value][$key1]) ? 0 : count(self::$TableInfo[$value][$key1]);
                $arr = range(1, $num + 5);
                $gametype = DBInstance::GetTableOneWord('game_config', 'gametype', ['level' => $key1, 'gtype' => $value]);
                if ($gametype == 2) {
                    shuffle($arr);
                }

                foreach ($arr as $key2 => $value2) {
                    if (empty(self::$TableInfo[$value][$key1][$value2])) {
                        $table = $value2;
                        if ($table > 50 || $num <= 0) {
                            break;
                        }

                        $num--;
                        if (empty(self::$TableInfo[$value][$key1][$table]['rid'])) {
                            //新建房间
                            $rid = Common::CreatedRoomID($value);
                            self::$TableInfo[$value][$key1][$table] = [
                                'rid' => $rid,
                                'robot' => 0,
                                'status' => 0,
                                'players' => []
                            ];

                            self::$RoomInfo[$rid] = $config;
                            self::$RoomInfo[$rid]['rid'] = $rid;
                            self::$RoomInfo[$rid]['tid'] = $table;
                            self::$RoomInfo[$rid]['gtype'] = $value;
                            self::$RoomInfo[$rid]['level'] = $key1;
                            self::$RoomInfo[$rid]['status'] = 2;
                            self::$RoomInfo[$rid]['players'] = [];
                        }

                        $rid = self::$TableInfo[$value][$key1][$table]['rid'];
                        self::$RoomInfo[$rid]['status'] = 1;
                        self::RoomBegin($rid);
                    }
                }
            }
        }

        if (!empty($message)) {
            self::RobotAdmission($message['gtype']);
        }
    }

    //AI进房逻辑
    private static function RobotAdmission($gtype = 0)
    {
        if (DBInstance::GetGameMaintenance()) {
            return;
        }

        foreach (self::$TableInfo as $key => $value) {
            if (!empty($value) && ($gtype == 0 || $gtype == $key)) {
                foreach ($value as $key1 => $value1) {
                    $seession = DBInstance::GetSeession($key);
                    if ($seession == 1 && (self::$RobotEnter[$key][$key1]['ai_base'] <= 0 || self::$RobotEnter[$key][$key1]['ai_max'] <= self::$RobotEnter[$key][$key1]['inroom'])) {
                        continue;
                    }

                    foreach ($value1 as $key2 => $value2) {
                        $max_pnum = self::$RoomInfo[$value2['rid']]['max_pnum'];
                        $cur_pnum = count($value2['players']);
                        if (count($value2['players']) > self::$RobotEnter[$key][$key1]['ai_max']) {
                            continue;
                        }
                        $flag = rand(self::$RobotEnter[$key][$key1]['ai_base'], self::$RobotEnter[$key][$key1]['ai_max']);
                        if (self::$RobotEnter[$key][$key1]['ai_base'] > 0 && $cur_pnum < $max_pnum && $value2['robot'] < $flag) {
                            $ret = DBInstance::GetTableOneWord('game_config', 'vals', ['gtype' => $key, 'level' => $key1]);
                            $ret = $ret ? json_decode($ret, true) : [];
                            $count = 0;
                            $robots = [];

                            //计算坐庄机器人数量
                            if (isset($ret['bankergold']) && $seession == 1) {
                                $bankerrand = rand($ret['bankermin'], $ret['bankermax']);
                                $uids = array_keys(self::$RoomInfo[$value2['rid']]['players']);
                                $curbanker = DBInstance::CountBankerNum($uids, $ret['bankergold']);

                                if ($curbanker < $bankerrand) {
                                    $_need = $bankerrand - $curbanker;
                                    $robots = DBInstance::GetRobotList(ROBOTMAXGOLD, $_need, $ret['bankergold']);
                                    if (empty($robots) || count($robots) < $_need) {
                                        DBInstance::InsertBankerRobot($ret['bankergold'], $_need * 2);
                                        $robots = DBInstance::GetRobotList(ROBOTMAXGOLD, $_need, $ret['bankergold']);
                                        if ($robots == false) {
                                            $robots = [];
                                        }
                                    }
                                }
                            }

                            if ($max_pnum >= 50 && $cur_pnum >= rand(20, self::$RobotEnter[$key][$key1]['ai_max']) && rand(1, 3) <= 2 && empty($robots)) {
                                continue;
                            } elseif ($seession == 3 && $value2['robot'] >= self::$RobotEnter[$key][$key1]['ai_base'] && rand(1, 10) <= 7 && $max_pnum - $cur_pnum > 1) {
                                continue;
                            } elseif ($max_pnum - $cur_pnum == 1 && rand(1, 2) <= 1) {
                                continue;
                            }

                            if ($seession == 3 && $value2['robot'] <= 0) {
                                $randnum = rand(1, 10) <= 9 ? 1 : 2;
                            } elseif (isset($ret['min']) && $value2['robot'] > $ret['min'] || $value2['robot'] > self::$RobotEnter[$key][$key1]['ai_base']) {
                                $randnum = rand(1, 3) == 1 ? 0 : 1;
                            } else {
                                $randnum = $ret['min'] ?? self::$RobotEnter[$key][$key1]['ai_base'];
                            }

                            if (count($robots) > 0) {
                                $randnum += count($robots);
                            }

                            if ($randnum > $max_pnum - $cur_pnum) {
                                $randnum = $max_pnum - $cur_pnum;
                            }

                            $_maxgold = isset($ret['ingold']) && is_array($ret['ingold']) ? $ret['ingold'][1] : ROBOTBANKERGOLD;
                            $_mingold = isset($ret['ingold']) && is_array($ret['ingold']) ? $ret['ingold'][0] : ROBOTGOLD;
                            $addlist = DBInstance::GetRobotList($_maxgold, $randnum, $_mingold);

                            if (!empty($addlist) && $randnum > 0) {
                                $robots += $addlist;
                            } elseif ($randnum > 0 && ($addlist == false || count($addlist) < $randnum)) {
                                DBInstance::InsertNormalRobot($_mingold, $_maxgold, $randnum * 2);
                            }

                            foreach ($robots as $key3 => $value3) {
                                //陪玩导入
                                if (!empty(self::$UserRoom[$key3]['rid'])) {
                                    continue;
                                }

                                self::Msg_Hall_FinishLoad('', [
                                    'event' => 'Msg_Hall_FinishLoad',
                                    'area' => LOGIC_HALL,
                                    'uid' => $key3,
                                    'data' => ['rid' => $value2['rid']]
                                ]);

                                if (!empty(self::$RoomInfo[$value2['rid']]['players'][$key3])) {
                                    DBInstance::UpdateUserOnline($key3);
                                }
                                $count++;
                                if ($count >= $randnum) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    //用户消息-----------------------------------------------------------------

    /**
     * 查询游戏场次
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_GameSessions($client_id, $message)
    {
        $gtype = $message['data']['gtype'];
        $ret = DBInstance::GetGameSessions($gtype);
        if (!empty($ret)) {
            $message['status'] = 1;
            $message['data'] = $ret;
        } else {
            $message['status'] = 0;
            $message['msg'] = '暂无赛事';
        }
        Common::SendToClient($message, $client_id);
    }

    /**
     * 进入游戏大厅
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_EnterGame($client_id, $message)
    {
        $gtype = $message['data']['gtype'];
        $level = $message['data']['level'];
        $seession = DBInstance::GetSeession($gtype);

        if (!isset(self::$GameStatus[$seession][$gtype]) || self::$GameStatus[$seession][$gtype] == 2) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '当前赛事信息错误', $message['uid']), $client_id);
            return;
        }

        //将用户添加入赛事组
        $cid = $gtype * 1000 + $level;
        if ($client_id != '') {
            Gateway::joinGroup($client_id, 'HALL:' . $cid);
        }

        self::$UserRoom[$message['uid']] = ['cid' => $cid, 'rid' => 0];
        $gametype = DBInstance::GetTableOneWord('game_config', 'gametype', ['gtype' => $gtype, 'level' => $level]);
        $msg = Common::NoticeMsg('Msg_Hall_EnterGame', $message['uid']);
        $msg['data'] = $gametype == 2 && isset(self::$TableInfo[$gtype][$level]) ? self::$TableInfo[$gtype][$level] : [];
        Common::SendToClient($msg, $client_id);
        $msg = Common::NoticeMsg('Msg_Hall_SyncGameTables', $message['uid']);
        $msg['data'] = self::$TableInfo[$gtype][$level] ?? [];
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 游戏桌面同步
     * @param $gtype
     * @param $level
     * @throws Exception
     */
    private static function SyncGameTables($gtype, $level)
    {
        $seession = DBInstance::GetSeession($gtype);
        if (!isset(self::$GameStatus[$seession][$gtype]) || self::$GameStatus[$seession][$gtype] == 2) {
            return;
        }

        $cid = $gtype * 1000 + $level;
        $gametype = DBInstance::GetTableOneWord('game_config', 'gametype', ['gtype' => $gtype, 'level' => $level]);
        if ($gametype == 2) {
            $msg = Common::NoticeMsg('Msg_Hall_SyncGameTables', 0);
            $msg['data'] = self::$TableInfo[$gtype][$level];
            Common::SendToGroup('HALL:' . $cid, $msg);
        }
    }

    /**
     * 退出游戏大厅
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_OutGame($client_id, $message)
    {
        $cid = $message['data']['gtype'] * 1000 + $message['data']['level'];

        if (!empty(self::$UserRoom[$message['uid']]) && self::$UserRoom[$message['uid']]['cid'] == $cid) {
            //将玩家从组从删除
            if ($client_id != '') {
                Gateway::leaveGroup($client_id, 'HALL:' . self::$UserRoom[$message['uid']]['cid']);
            }
            unset(self::$UserRoom[$message['uid']]);
            Common::SendToClient(Common::NoticeMsg($message['event'], $message['uid']), $client_id);
        } else {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '玩家不在该场次中', $message['uid']), $client_id);
        }
    }

    /**
     * 进入房间
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_EnterRoom($client_id, $message, $send = true)
    {
        $gtype = $message['data']['gtype'];
        $level = $message['data']['level'];
        $table = $message['data']['tableid'];
        $config = DBInstance::GetGameConfig($gtype, $level);
        $status = DBInstance::GetUserOneWord('status', $message['uid']);
        $seession = DBInstance::GetSeession($gtype);

        if (!isset(self::$GameStatus[$seession][$gtype]) || self::$GameStatus[$seession][$gtype] == 2) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '当前赛事信息错误', $message['uid']), $client_id);
            return 0;
        }

        if ($seession == 1 && empty(self::$TableInfo[$gtype][$level])) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '游戏未开启，请稍后', $message['uid']), $client_id);
            return 0;
        }

        if (empty($config)) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '参数错误', $message['uid']), $client_id);
            return 0;
        }
        if ($status == 2) {
            if ($send) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '您已被暂停娱乐', $message['uid']), $client_id);
            }
            return 0;
        }

        $userinfo = DBInstance::GetTableWords('user', 'uid,gold,headimgurl,nickname,pictureframe,agent', ['uid' => $message['uid']]);
        if ($userinfo['agent'] == 1) {
            if ($send) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '代理不能进入游戏', $message['uid']), $client_id);
            }
            return 0;
        }

        //安排桌号
        if ($table == 0 || $table > 50 || $config['gametype'] == 1) {
            for ($i = 1; $i <= 51; $i++) {
                if (empty(self::$TableInfo[$gtype][$level][$i]) || $i > 50) {
                    $table = $i;
                    break;
                } else {
                    $rid = self::$TableInfo[$gtype][$level][$i]['rid'];
                    $maxpnum = self::$RoomInfo[$rid]['max_pnum'];
                    if (count(self::$TableInfo[$gtype][$level][$i]['players']) < $maxpnum) {
                        $table = $i;
                        break;
                    }
                }
            }
        }

        if ($table > 50) {
            if ($send) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '当前游戏太火爆了', $message['uid']), $client_id);
            }
            return 0;
        }

        if (empty(self::$TableInfo[$gtype][$level][$table]['rid'])) {
            //新建房间
            $rid = Common::CreatedRoomID($gtype);
            self::$TableInfo[$gtype][$level][$table] = [
                'rid' => $rid,
                'robot' => 0,
                'status' => 0,
                'players' => []
            ];

            self::$RoomInfo[$rid] = $config;
            self::$RoomInfo[$rid]['rid'] = $rid;
            self::$RoomInfo[$rid]['tid'] = $table;
            self::$RoomInfo[$rid]['gtype'] = $gtype;
            self::$RoomInfo[$rid]['level'] = $level;
            self::$RoomInfo[$rid]['status'] = 2;
            self::$RoomInfo[$rid]['players'] = [];
        }

        $rid = self::$TableInfo[$gtype][$level][$table]['rid'];
        if ((self::$RoomInfo[$rid]['min_gold'] > 0 && self::$RoomInfo[$rid]['min_gold'] > $userinfo['gold']) ||
            (self::$RoomInfo[$rid]['max_gold'] > 0 && self::$RoomInfo[$rid]['max_gold'] < $userinfo['gold']) && $gtype != GAME_DZPK) {
            if ($send) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '金币不符合入场规则', $message['uid']), $client_id);
            }
            return 0;
        }

        if (count(self::$RoomInfo[$rid]['players']) >= self::$RoomInfo[$rid]['max_pnum']) {
            if ($send) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '当前房间人数已满', $message['uid']), $client_id);
            }
            return 0;
        }

        if ($send) {
            $msg = Common::NoticeMsg($message['event'], $message['uid']);
            $msg['data'] = [
                'rid' => $rid,
                'gtype' => $rid,
                'level' => $level,
                'rule' => array(),
            ];
            Common::SendToClient($msg, $client_id);
        }
        return $rid;
    }

    /**
     * 进入房间
     * @param $client_id
     * @param $message
     * @throws Exception
     */
    public static function Msg_Hall_FinishLoad($client_id, $message)
    {
        self::$nUserList[$message['uid']] = $client_id;
        $rid = $message['data']['rid'];
        $msg = Common::NoticErrorMsg($message['event'], '', $message['uid']);
        $status = DBInstance::GetUserOneWord('status', $message['uid']);

        if (empty(self::$RoomInfo[$rid])) {
            Common::SendToClient(Common::MsgError(3, '房间不存在', $message['uid']), $client_id);
            return;
        }


        $gtype = self::$RoomInfo[$rid]['gtype'];
        $level = self::$RoomInfo[$rid]['level'];
        $seession = DBInstance::GetSeession($gtype);

        if (!isset(self::$GameStatus[$seession][$gtype]) || self::$GameStatus[$seession][$gtype] == 2) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '当前赛事信息错误', $message['uid']), $client_id);
            return;
        }

        if ($seession == 1 && empty(self::$TableInfo[$gtype][$level])) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '游戏未开启，请稍后', $message['uid']), $client_id);
            return;
        }

        if (!empty(self::$UserRoom[$message['uid']]['rid'])) {
            //玩家重连
            $message['data']['rid'] = self::$UserRoom[$message['uid']]['rid'];
            $message['data']['gtype'] = self::$RoomInfo[$rid]['gtype'];
            $message['data']['level'] = self::$RoomInfo[$rid]['level'];
            $message['client_id'] = $client_id;
            CentralCon::SendToCentral($message);
            $msg['status'] = 1;
            $msg['data']['palyers'] = self::$RoomInfo[$rid]['players'];
        } else {
            $uid = $message['uid'];
            $userinfo = DBInstance::GetTableWords('user', 'uid,gold,headimgurl,nickname,pictureframe,battery,sex,bank', ['uid' => $uid]);
            if (strlen($uid) <= 4) {
                $msg['msg'] = '代理不能进游戏桌子';
            } elseif ($status == 2) {
                $msg['msg'] = '您已被暂停娱乐';
            } elseif (count(self::$RoomInfo[$rid]['players']) >= self::$RoomInfo[$rid]['max_pnum']) {
                $rid = self::Msg_Hall_EnterRoom($client_id, [
                    'event' => 'Msg_Hall_EnterRoom',
                    'uid' => $message['uid'],
                    'data' => [
                        'tableid' => 0,
                        'gtype' => self::$RoomInfo[$rid]['gtype'],
                        'level' => self::$RoomInfo[$rid]['level']
                    ]
                ], false);
                if ($rid == 0) {
                    $msg['msg'] = '进房失败';
                }
            } elseif ((self::$RoomInfo[$rid]['min_gold'] > 0 && self::$RoomInfo[$rid]['min_gold'] > $userinfo['gold']) ||
                (self::$RoomInfo[$rid]['max_gold'] > 0 && self::$RoomInfo[$rid]['max_gold'] < $userinfo['gold']) && self::$RoomInfo[$rid]['gtype'] != GAME_DZPK) {
                $msg['msg'] = '金币不符合入场规则';
            }

            if (empty($msg['msg'])) {
                if (empty($client_id)) {
                    self::$RobotEnter[$gtype][$level]['inroom']++;
                }

                $msg['status'] = 1;
                $gtype = self::$RoomInfo[$rid]['gtype'];
                $level = self::$RoomInfo[$rid]['level'];
                $_seats = [];
                for ($i = 0; $i < self::$RoomInfo[$rid]['max_pnum']; $i++) {
                    $_seats[] = $i;
                }

                if (isset(RANDSEAT[$gtype])) {
                    shuffle($_seats);
                }

                foreach ($_seats as $key => $value) {
                    if (empty(self::$TableInfo[$gtype][$level][self::$RoomInfo[$rid]['tid']]['players'][$value + 1])) {
                        //设置房间信息
                        self::$RoomInfo[$rid]['players'][$uid] = $userinfo;
                        self::$RoomInfo[$rid]['players'][$uid]['factgold'] = $userinfo['gold'] + $userinfo['bank'];
                        self::$RoomInfo[$rid]['players'][$uid]['seat'] = $value;
                        self::$RoomInfo[$rid]['players'][$uid]['begintime'] = time();
                        self::$TableInfo[$gtype][$level][self::$RoomInfo[$rid]['tid']]['players'][$value + 1] = ['head' => $userinfo['headimgurl'], 'nickname' => $userinfo['nickname']];
                        break;
                    }
                }

                if (self::$RoomInfo[$rid]['base_score'] != 0) {
                    self::$RoomInfo[$rid]['players'][$uid]['gold'] = self::$RoomInfo[$rid]['base_score'];
                }

                $cid = self::$RoomInfo[$rid]['gtype'] * 1000 + self::$RoomInfo[$rid]['level'];
                self::$UserRoom[$message['uid']] = ['rid' => $rid, 'cid' => $cid];
                //缓存玩家房间信息 删除玩家当前用户组
                if ($client_id != '') {
                    Gateway::leaveGroup($client_id, 'HALL:' . self::$UserRoom[$message['uid']]['cid']);
                } else {
                    self::$TableInfo[$gtype][$level][self::$RoomInfo[$rid]['tid']]['robot']++;
                }

                self::$UserRoom[$message['uid']]['rid'] = $rid;
                //$msg['data']['palyers'] = self::$RoomInfo[$rid]['players'];

                Common::SendToClient($msg, $client_id);
                if (count(self::$RoomInfo[$rid]['players']) >= self::$RoomInfo[$rid]['min_pnum']) {
                    if (self::$RoomInfo[$rid]['status'] == 2) {
                        self::$RoomInfo[$rid]['status'] = 1;
                        self::RoomBegin($rid);
                    } else {
                        //单个玩家进房
                        self::UserEnter([
                            'gtype' => self::$RoomInfo[$rid]['gtype'],
                            'level' => self::$RoomInfo[$rid]['level'],
                            'rid' => $rid,
                            'player' => self::$RoomInfo[$rid]['players'][$message['uid']],
                        ]);
                        //中心服记录用户所在房间
                        CentralCon::SendToCentral([
                            'event' => 'EnterRoom',
                            'area' => 1,
                            'uid' => $uid,
                            'data' => ['rid' => $rid]
                        ]);
                    }
                }
            } else {
                Common::SendToClient($msg, $client_id);
            }

            self::SyncGameTables(self::$RoomInfo[$rid]['gtype'], self::$RoomInfo[$rid]['level']);
        }
    }

    /**
     * 房间开始
     * @param $rid
     */
    private static function RoomBegin($rid)
    {
        //通知 游戏服务器开始
        CentralCon::SendToCentral([
            'event' => 'RoomBegin',
            'area' => 1,
            'data' => self::$RoomInfo[$rid],
        ]);
    }

    /**
     * 退出房间
     * @param
     * @throws Exception
     */
    public static function QuitRoom($message)
    {
        $uid = $message['data']['uid'];
        $rid = $message['data']['rid'];
        if (!empty(self::$RoomInfo[$rid])) {
            $tid = self::$RoomInfo[$rid]['tid'];
            $seat = self::$RoomInfo[$rid]['players'][$uid]['seat'];
            $gtype = self::$RoomInfo[$rid]['gtype'];
            $level = self::$RoomInfo[$rid]['level'];
        }

        $_type = DBInstance::GetTableOneWord('user', 'type', ['uid' => $uid]);
        if ($_type == 0 && !empty($gtype) && !empty($level) && ($level != 1 || $gtype == GAME_GGL)) {
            DBInstance::InsertGameRecord($uid, $gtype, self::$RoomInfo[$rid]['players'][$uid]['begintime'], self::$RoomInfo[$rid]['players'][$uid]['factgold']);
        }

        if ($message['data']['client_id'] != '') {
            Gateway::setSession($message['data']['client_id'], ['router' => array_rand(Common::$HallList)]);
            unset(self::$UserRoom[$uid]['rid']);
        } else {
            DBInstance::UpdateUserOnline($uid, 2);
            if (!empty($gtype) && !empty($level)) {
                self::$RobotEnter[$gtype][$level]['inroom']--;
                self::$TableInfo[$gtype][$level][self::$RoomInfo[$rid]['tid']]['robot']--;
            }
            unset(self::$UserRoom[$uid]);
        }

        if (!empty(self::$RoomInfo[$rid])) {
            unset(self::$TableInfo[$gtype][$level][$tid]['players'][$seat + 1]);
            unset(self::$RoomInfo[$rid]['players'][$uid]);
        }

        if (!empty(self::$RoomInfo[$rid]) && self::$RoomInfo[$rid]['gametype'] == 2) {
            $cid = $gtype * 1000 + $level;
            if ($message['data']['client_id'] != '') {
                Gateway::joinGroup($message['data']['client_id'], 'HALL:' . $cid);
            }
            self::SyncGameTables($gtype, $level);
        }
    }

    /**
     * 房间解散
     * @param $message
     * @throws Exception
     */
    public static function RoomOld($message)
    {
        $rid = $message['data']['rid'];
        $tid = self::$RoomInfo[$rid]['tid'];
        $gtype = self::$RoomInfo[$rid]['gtype'];
        $level = self::$RoomInfo[$rid]['level'];
        self::$RobotEnter[$gtype][$level]['inroom'] = 0;
        foreach ($message['data']['players'] as $key => $value) {
            $_type = DBInstance::GetTableOneWord('user', 'type', ['uid' => $key]);
            if ($_type == 0 && ($level != 1 || $gtype == GAME_GGL)) {
                DBInstance::InsertGameRecord($key, $gtype, self::$RoomInfo[$rid]['players'][$key]['begintime'], self::$RoomInfo[$rid]['players'][$key]['factgold']);
            }

            if ($value['client_id'] != '') {
                Gateway::setSession($value['client_id'], ['router' => array_rand(Common::$HallList)]);
            } else {
                DBInstance::UpdateUserOnline($key, 2);
            }
            unset(self::$UserRoom[$key]);
        }
        Common::OldRoomID($rid);
        unset(self::$TableInfo[$gtype][$level][$tid]);
        unset(self::$RoomInfo[$rid]);
        self::SyncGameTables($gtype, $level);
    }

    /**
     * 玩家进房
     * @param $data
     */
    private static function UserEnter($data)
    {
        //通知 游戏服务器开始
        CentralCon::SendToCentral([
            'event' => 'UserEnter',
            'area' => 1,
            'uid' => $data['player']['uid'],
            'data' => $data,
        ]);
    }

    /**
     * 逻辑服关闭
     * @param $message
     * @throws Exception
     */
    public static function LOGIC_CLOSE($message)
    {
        $gtype = $message['data']['gtype'];
        if (!empty(self::$TableInfo[$gtype])) {
            foreach (self::$TableInfo[$gtype] as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    foreach (self::$RoomInfo[$value1['rid']]['players'] as $key2 => $value2) {
                        self::QuitRoom(['data' => ['uid' => $key2, 'rid' => $value1['rid'], 'client_id' => self::$nUserList[$key2]]]);
                        unset(self::$UserRoom[$key2]);
                        Common::SendToUID(Common::MsgError(3, '游戏关闭', $key2), $key2);
                    }
                    unset(self::$RoomInfo[$value1['rid']]);
                    Common::OldRoomID($value1['rid']);
                }
                self::$RobotEnter[$gtype][$key]['inroom'] = 0;
            }
        }

        unset(self::$TableInfo[$gtype]);
    }

    //跑马灯
    public static function Msg_Hall_HorseLamp($message)
    {
        Common::SendToGroup('ONLINE', [
            'event' => 'Msg_Hall_HorseLamp',
            'area' => LOGIC_HALL,
            'status' => 1,
            'msg' => '',
            'data' => $message['data']
        ]);
    }

    //跑马灯
    public static function TableStatus($message)
    {
        $gtype = self::$RoomInfo[$message['data']['rid']]['gtype'];
        $level = self::$RoomInfo[$message['data']['rid']]['level'];
        $tid = self::$RoomInfo[$message['data']['rid']]['tid'];
        self::$TableInfo[$gtype][$level][$tid]['status'] = $message['data']['status'];
        self::SyncGameTables($gtype, $level);
    }

    //维护公告
    public static function Maintenance()
    {
        Common::SendToGroup('ONLINE', [
            'event' => 'Msg_Hall_Maintenance',
            'area' => LOGIC_HALL,
            'status' => 1,
            'msg' => '',
            'data' => [
                'string' => Common::$Maintenance
            ]
        ]);
    }

    /**
     * 银行转账
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_BankTransfer($client_id, $message)
    {
        $uid = $message['uid'];
        $touid = $message['data']['touid'];
        $user = DBInstance::GetUser(['uid' => $uid]);
        $touser = DBInstance::GetUser(['uid' => $touid]);

        //三方用户不能存取
        if (!empty($user['openid']) || !empty($touser['openid'])) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '玩家不被允许操作', $message['uid']), $client_id);
            return;
        }

        $res = DBInstance::GetTableOneWord('user', 'uid', ['uid' => $touid, 'type' => 0]);

        if (!$res || $uid == $touid) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '错误赠送的玩家', $message['uid']), $client_id);
            return;
        }

        if (intval($message['data']['num']) != $message['data']['num']) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '数据格式错误', $message['uid']), $client_id);
            return;
        } else {
            $message['data']['num'] = intval($message['data']['num']);
        }

        //检测玩家余额
        if ($message['data']['type'] == 1) {
            $bank = DBInstance::GetUserOneWord('bank', $uid);
            if ($message['data']['num'] <= 0 || $bank < $message['data']['num']) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '无效的数量', $message['uid']), $client_id);
                return;
            }
        } elseif ($message['data']['type'] == 2) {
            $rcard = DBInstance::GetUserOneWord('rcard', $uid);
            if ($message['data']['num'] <= 0 || $rcard < $message['data']['num']) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '无效的数量', $message['uid']), $client_id);
                return;
            }
        } else {
            $mcard = DBInstance::GetUserOneWord('mcard', $uid);
            if ($message['data']['num'] <= 0 || $mcard < $message['data']['num']) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '无效的数量', $message['uid']), $client_id);
                return;
            }
        }

        $superior = DBInstance::GetUserSuperior($touid);
        $agent = DBInstance::GetUserOneWord('agent', $uid);
        $toagent = DBInstance::GetUserOneWord('agent', $touid);
        /*if ($agent == 1 && $uid != 20000) {
            //代理身份
            $send = false;
            if ($superior != false && $superior == $touid) {
                //向上级转账
                $send = true;
            } else {
                $superior = DBInstance::GetUserSuperior($touid);
                if ($superior != false && $superior == $uid) {
                    //向下级转账
                    $send = true;
                } else {
                    //是否可新绑定玩家
                    $user = DBInstance::GetTableWords('user', '*', ['uid' => $touid]);
                    $usergold = $user['gold'] + $user['bank'];
                    if ($superior == false || $usergold < 50000) {
                        if ($superior == false) {
                            DBInstance::InsertUserSuperior($touid, $uid);
                        } else {
                            DBInstance::UpdateUserSuperior($touid, $uid);
                        }
                        $send = true;
                    }
                }
            }

            if ($send == false) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '错误的赠送玩家', $message['uid']), $client_id);
                return;
            }
        } else {
            //玩家身份
            if (($superior != $touid || $message['data']['type'] != 1) && $uid != 20000) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '赠送失败', $message['uid']), $client_id);
                return;
            }
        }*/

        if ($superior === false) {
            DBInstance::InsertUserSuperior($touid, $uid);
        } elseif (!$superior) {
            DBInstance::UpdateUserSuperior($touid, $uid);
        }

        if ($agent == $toagent && $agent != 1) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '赠送失败', $message['uid']), $client_id);
            return;
        }

        if ($message['data']['type'] == 1) {
            DBInstance::IncrementGolds('bank', $uid, -$message['data']['num']);
            $currency = COIN;
        } elseif ($message['data']['type'] == 2) {
            //兑换卡直接到账
            DBInstance::IncrementGolds('rcard', $uid, -$message['data']['num']);
            DBInstance::IncrementGolds('rcard', $touid, $message['data']['num']);
            DBInstance::UpdateCardDate($uid, 1, $message['data']['num']);
            DBInstance::UpdateCardDate($touid, 2, $message['data']['num']);
            self::Msg_Hall_ChangeGolds($touid);
        } else {
            $toagent = DBInstance::GetUserOneWord('agent', $touid);
            if ($toagent == 2 && $message['data']['num'] > 1) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '仅能赠送一张月卡', $message['uid']), $client_id);
                return;
            } elseif ($toagent == 2) {
                $currency = MCARD;
            } else {
                DBInstance::IncrementGolds('mcard', $touid, $message['data']['num']);
            }
            DBInstance::IncrementGolds('mcard', $uid, -$message['data']['num']);
        }

        $ret = DBInstance::GetTableWords('user', 'bank,rcard,mcard', ['uid' => $uid]);
        $ret['nickname'] = DBInstance::GetTableOneWord('user', 'nickname', ['uid' => $touid]);
        //增加邮件
        if (!empty($currency)) {
            DBInstance::InsertMail($touid, $uid, $currency, $message['data']['num'], $uid == 20000 ? FROMSYSTEM : FROMGIVE);
            Common::SendToUID(Common::NoticeMsg('Msg_Hall_NewMail', $touid), $touid);
        } else {
            self::Msg_Hall_ChangeGolds($touid);
        }

        $message['status'] = 1;
        $message['data'] = $ret;
        Common::SendToClient($message, $client_id);
    }

    /**
     *  邮箱列表
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_MailList($client_id, $message)
    {
        $list = DBInstance::GetMailList($message['uid']);
        $msg = Common::NoticeMsg('Msg_Hall_MailList', $message['uid']);
        $msg['data'] = $list;
        Common::SendToClient($msg, $client_id);
    }

    /**
     *  操作邮件  撤回 打开 删除
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_TouchMail($client_id, $message)
    {
        $uid = $message['uid'];
        $touchuid = DBInstance::GetTableOneWord('mail', $message['data']['type'] == 2 ? 'outuid' : 'uid', ['id' => $message['data']['id']]);
        if ($touchuid != $uid) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '没有权限', $message['uid']), $client_id);
            return;
        }

        $res = DBInstance::GetTableWords('mail', '*', ['id' => $message['data']['id']]);
        if ($message['data']['type'] == 2 && $res['status'] < 2) {
            //撤回邮件
            DBInstance::UpdateMailStatus($message['data']['id'], 3);
            if ($res['currency'] == COIN) {
                DBInstance::IncrementGolds('bank', $uid, $res['number']);
            } elseif ($res['currency'] == CARD) {
                DBInstance::IncrementGolds('rcard', $uid, $res['number']);
            } else {
                DBInstance::IncrementGolds('mcard', $uid, $res['number']);
            }
        } elseif ($message['data']['type'] == 1 && $res['status'] < 3) {
            //打开未读
            if ($res['status'] == 0) {
                DBInstance::UpdateMailStatus($message['data']['id'], 1);
            }
        } elseif ($message['data']['type'] == 3 && $res['status'] == 2) {
            //删除已读
            DBInstance::UpdateMailStatus($message['data']['id'], 4);
        } else {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '操作失败', $message['uid']), $client_id);
            return;
        }

        Common::SendToClient(Common::NoticeMsg('Msg_Hall_TouchMail', $uid), $client_id);
        if ($message['data']['type'] == 2) {
            self::Msg_Hall_ChangeGolds($uid);
        }
    }

    /**
     * 领取邮件
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_GetEmail($client_id, $message)
    {
        $uid = $message['uid'];
        $mail = DBInstance::GetTableWords('mail', '*', ['id' => $message['data']['id']]);
        $agent = DBInstance::GetUserOneWord('agent', $uid);
        $_outagent = DBInstance::GetUserOneWord('agent', $mail['outuid']);

        if ($mail['uid'] != $uid) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '没有权限', $message['uid']), $client_id);
            return;
        }

        $superior = DBInstance::GetTableOneWord('user_superior', 'superior', ['uid' => $uid]);
        $lower = DBInstance::GetTableOneWord('user_superior', 'uid', ['superior' => $uid, 'uid' => $mail['outuid']]);
        //玩家验证是否是上级/下级发送的邮件
        /*if ($superior != $mail['outuid'] && $agent == 2 && $mail['outuid'] != 20000) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '权限错误', $message['uid']), $client_id);
            return;
        } elseif ($agent == $_outagent && $_outagent == 1 && $superior != $mail['outuid'] && $lower != $mail['outuid'] && $mail['outuid'] != 20000) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '权限错误', $message['uid']), $client_id);
            return;
        }*/

        //检测邮件是否已领取
        if ($mail['status'] < 2) {
            if ($mail['currency'] == 1) {
                //金币
                $getgold = $agent == 1 || $mail['outuid'] == 20000 ? $mail['number'] : intval($mail['number'] * BANKTRANSFER / 1000);
                DBInstance::IncrementGolds('bank', $uid, $getgold);
                if ($mail['number'] > $getgold && $mail['outuid'] != 20000) {
                    DBInstance::IncrementExtension($mail['outuid'], $mail['number'] - $getgold);
                }
                DBInstance::UpdateUserProfit($uid, $mail['outuid'], GETGOLD, $mail['currency'], $mail['number']);
            } else {
                if ($agent == 1) {
                    //新增月卡收入流水
                    DBInstance::UpdateUserProfit($uid, $mail['outuid'], GETGOLD, $mail['currency'], $mail['number']);
                    DBInstance::IncrementGolds('mcard', $uid, $mail['number']);
                } else {
                    $endtime = DBInstance::GetTableOneWord('monthlycard', 'endtime', ['uid' => $uid]);
                    $flagtime = strtotime(MyTools::GET_TOMORROW());
                    //判定是否可以领取月卡
                    if (strtotime($endtime) > $flagtime) {
                        Common::SendToClient(Common::NoticErrorMsg($message['event'], '当月月卡生效中,无法领取', $message['uid']), $client_id);
                        return;
                    }
                    //月卡直接入账
                    DBInstance::IncrementGolds('bank', $uid, USERMONTHLYCARD);
                    DBInstance::IncrementGolds('bank', $mail['outuid'], AGENTMONTHLYCARD);
                    self::Msg_Hall_ChangeGolds($mail['outuid']);
                    //新增月卡收入流水
                    DBInstance::UpdateUserProfit($uid, $mail['outuid'], GETGOLD, COIN, USERMONTHLYCARD);
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
                }
            }

            //修正邮件状态
            DBInstance::UpdateMailStatus($message['data']['id'], 2);
            //记录发送者消耗
            DBInstance::UpdateUserProfit($mail['outuid'], $uid, OUTGOLD, $mail['currency'], $mail['number']);
            $msg = Common::NoticeMsg('Msg_Hall_GetEmail', $uid);
            $msg['data']['bank'] = DBInstance::GetUserOneWord('bank', $uid);
            Common::SendToClient($msg, $client_id);
        } else {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '操作失败', $message['uid']), $client_id);
        }
    }

    /**
     *  收支明细
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_GoldDetailed($client_id, $message)
    {
        if (!isset($message['data']['type']) || !isset($message['data']['uid']) || !isset($message['data']['agent']) || !isset($message['data']['page'])) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '错误消息', $message['uid']), $client_id);
            return;
        }

        $list = DBInstance::GoldDetailed($message['uid'], $message['data']['type'], $message['data']['uid'], $message['data']['agent'], $message['data']['page']);
        $msg = Common::NoticeMsg('Msg_Hall_GoldDetailed', $message['uid']);
        $msg['data'] = $list;
        $msg['data']['type'] = $message['data']['type'];
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 收入列表
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_GetIncomeList($client_id, $message)
    {
        $list = DBInstance::GetIncomeList($message['uid']);
        $msg = Common::NoticeMsg('Msg_Hall_GetIncomeList', $message['uid']);
        $msg['data'] = $list;
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 修改密码
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_ChangeBankPass($client_id, $message)
    {
        if (!preg_match('/[0-9A-Za-z]/', $message['data']['newpass'])) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '密码格式错误', $message['uid']), $client_id);
            return;
        }

        $oldpass = DBInstance::GetUserOneWord('bankpass', $message['uid']);
        if ($oldpass != $message['data']['oldpass']) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '请输入正确的旧密码', $message['uid']), $client_id);
            return;
        }

        if (strlen($message['data']['newpass']) < 6 || strlen($message['data']['newpass']) > 8) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '密码长度错误', $message['uid']), $client_id);
        } else {
            DBInstance::UpdateUserString($message['uid'], ['bankpass' => $message['data']['newpass']]);
            Common::SendToClient(Common::NoticeMsg('Msg_Hall_ChangeBankPass', $message['uid']), $client_id);
        }
    }

    /**
     * 代理首页
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_Agent($client_id, $message)
    {
        $msg = Common::NoticeMsg('Msg_Hall_Agent', $message['uid']);
        $msg['data'] = DBInstance::Agent($message['uid']);
        $msg['data'] += DBInstance::GetMonthlyNum($message['uid']);
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 代理查询玩家
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_QueryUserList($client_id, $message)
    {
        $uid = $message['uid'];
        if (!isset($message['data']['type'])) {
            $message['data']['type'] = 1;
        }
        if (!isset($message['data']['page'])) {
            $message['data']['page'] = 1;
        }
        $msg = Common::NoticeMsg('Msg_Hall_QueryUserList', $uid);
        $msg['data'] = DBInstance::QueryUserList($uid, $message['data']['type'], $message['data']['page']);
        foreach ($msg['data']['list'] as $key => $value) {
            $msg['data']['list'][$key]['status'] = DBInstance::GetUserPossition($value['uid']);
        }
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 玩家信息
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_QueryUserInfo($client_id, $message)
    {
        $select = $message['data']['uid'];
        $ret = DBInstance::GetUserSuperior($select);
        $superior = DBInstance::GetUserSuperior($message['uid']);
        if ($message['uid'] != $ret && $select != $superior && $message['uid'] != 20000) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '您没有权限', $message['uid']), $client_id);
            return;
        }

        $msg = Common::NoticeMsg('Msg_Hall_QueryUserInfo', $message['uid']);
        $msg['data'] = DBInstance::Msg_Hall_QueryUserInfo($select);
        if (empty($msg['data'])) {
            $msg['status'] = 0;
            $msg['msg'] = '玩家不存在';
        }
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 封禁解封玩家
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_BanUser($client_id, $message)
    {
        $touchuid = $message['data']['uid'];
        $ret = DBInstance::GetUserSuperior($touchuid);
        if ($message['uid'] != $ret && $message['uid'] != 20000) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '您没有权限', $message['uid']), $client_id);
            return;
        }

        if (!isset($message['data']['status'])) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '非法操作', $message['uid']), $client_id);
            return;
        }

        if ($message['data']['status'] == 0) {
            Common::SendToUID(Common::MsgError(ERROR_LOGIN, '账号异常'), $touchuid);
        }

        DBInstance::UpdateUserStatus($touchuid, $message['data']['status']);
        $msg = Common::NoticeMsg('Msg_Hall_BanUser', $message['uid']);
        $msg['data'] = $message['data'];
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 控制玩家等级
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_ControlLevel($client_id, $message)
    {
        $ret = DBInstance::GetUserSuperior($message['data']['uid']);
        $res = DBInstance::GetTableOneWord('agent', 'power', ['uid' => $ret]);
        if ($message['uid'] != $ret || $res <= 0) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '您没有权限', $message['uid']), $client_id);
            return;
        }

        if (!isset($message['data']['level']) || !isset($message['data']['flagget']) || ($message['data']['power'] > 0 && $res != 2)) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '非法操作', $message['uid']), $client_id);
            return;
        }

        if ($message['data']['level'] < 0) {
            $message['data']['flagget'] = -$message['data']['flagget'];
        } elseif ($message['data']['level'] > 0 && $res != 2) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '您没有权限', $message['uid']), $client_id);
            return;
        } elseif ($message['data']['level'] == 0 && $message['data']['flagget'] != 0) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '金额只能为0', $message['uid']), $client_id);
            return;
        }

        //场控等级
        DBInstance::UpdateUserControl($message['data']['uid'], $message['data']['level'], $message['data']['flagget']);
        $msg = Common::NoticeMsg('Msg_Hall_ControlLevel', $message['uid']);
        $msg['data'] = $message['data'];
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 代理查询玩家
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_QueryAgentList($client_id, $message)
    {
        $uid = $message['uid'];
        if (!isset($message['data']['type'])) {
            $message['data']['type'] = 1;
        }

        if (!isset($message['data']['page'])) {
            $message['data']['page'] = 1;
        }

        $msg = Common::NoticeMsg('Msg_Hall_QueryAgentList', $uid);
        $msg['data'] = DBInstance::QueryUserList($uid, $message['data']['type'], $message['data']['page'], 1);
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 代理领取推广金
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_GetPromotionFund($client_id, $message)
    {
        $uid = $message['uid'];
        $gold = DBInstance::GetTableOneWord('agent', 'extension', ['uid' => $uid]);
        if ($gold > 0) {
            DBInstance::AgentGetExtension($uid, $gold);
        } else {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '暂无推广金', $message['uid']), $client_id);
            return;
        }
        $msg = Common::NoticeMsg('Msg_Hall_GetPromotionFund', $uid);
        $msg['data']['bank'] = DBInstance::GetUserOneWord('bank', $uid);
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 推广金列表
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_PromotionFundList($client_id, $message)
    {
        $uid = $message['uid'];
        $msg = Common::NoticeMsg('Msg_Hall_PromotionFundList', $uid);
        $msg['data'] = DBInstance::ExtensionList($uid);
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 财富榜单
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_WealthList($client_id, $message)
    {
        $uid = $message['uid'];
        $msg = Common::NoticeMsg('Msg_Hall_WealthList', $uid);
        $msg['data'] = DBInstance::FortuneList();
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 赢分榜
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_WinPointsList($client_id, $message)
    {
        $uid = $message['uid'];
        $msg = Common::NoticeMsg('Msg_Hall_WinPointsList', $uid);
        $msg['data'] = DBInstance::WinPointsList();
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 签到列表
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_SignList($client_id, $message)
    {
        $ret = DBInstance::GetTableOneWord('monthlycard', 'continuity', ['uid' => $message['uid']]);
        $msg = Common::NoticeMsg('Msg_Hall_SignList', $message['uid']);
        $msg['data'] = [
            'prizes' => json_decode(DBInstance::GetSysConfig('WEEK_PRIZES'), true),
            'days' => $ret != false ? $ret : 0,
        ];
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 玩家签到
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_UserSign($client_id, $message)
    {
        if ($message['uid'] < 10000000) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '代理无法签到', $message['uid']), $client_id);
            return;
        }

        $ret = DBInstance::GetTableWords('monthlycard', '*', ['uid' => $message['uid']]);
        if ($ret == false || strtotime($ret['endtime']) < strtotime(MyTools::GET_TODAY())) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '需要购买月卡', $message['uid']), $client_id);
        } else {
            if ($ret['lastsigntime'] == MyTools::GET_TODAY()) {
                Common::SendToClient(Common::NoticErrorMsg($message['event'], '请勿重复签到', $message['uid']), $client_id);
                return;
            } elseif ($ret['lastsigntime'] != MyTools::GET_TODAY(time() - 60 * 60 * 24)) {
                DBInstance::UpdateMonthlyCard($message['uid'], ['continuity' => 0]);
                $ret['continuity'] = 0;
            }

            $prizes = json_decode(DBInstance::GetSysConfig('WEEK_PRIZES'), true);
            $index = [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 0 => 'seven'];
            $ret['continuity']++;
            $gold = $prizes[$index[$ret['continuity'] % 7]];
            if ($ret['continuity'] % 7 == 0) {
                $gold *= rand(1.5, 3);
            }

            DBInstance::UpdateMonthlyCard($message['uid'], ['lastsigntime' => MyTools::GET_TODAY()]);
            DBInstance::IncrementContinuity($message['uid']);
            DBInstance::IncrementGolds('bank', $message['uid'], $gold);
            $msg = Common::NoticeMsg('Msg_Hall_UserSign', $message['uid']);
            $msg['data'] = [
                'bonus' => $gold,
            ];
            Common::SendToClient($msg, $client_id);
            self::Msg_Hall_ChangeGolds($message['uid']);
        }
    }

    /**
     * 玩家领取救济金
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_GetBenefits($client_id, $message)
    {
        $uid = $message['uid'];
        $ret = DBInstance::GetTableWords('user', 'gold,bank', ['uid' => $uid]);
        if ($ret['gold'] + $ret['bank'] >= 10000) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '玩家金币富足', $message['uid']), $client_id);
            return;
        }

        $ret = DBInstance::GetTableWords('monthlycard', 'endtime,relieftime,relief', ['uid' => $uid]);
        $endtime = $ret == false ? 0 : strtotime($ret['endtime']);

        if ($endtime < time()) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '月卡玩家才能领取', $message['uid']), $client_id);
            return;
        }

        if ($ret['relieftime'] == MyTools::GET_TODAY() && $ret['relief'] <= 0) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '每天只能领取两次救济金', $message['uid']), $client_id);
            return;
        } elseif ($ret['relieftime'] != MyTools::GET_TODAY()) {
            $ret['relief'] = 2;
        }

        $ret['relief']--;
        DBInstance::IncrementGolds('gold', $uid, 10000);
        DBInstance::UpdateMonthlyCard($uid, ['relieftime' => MyTools::GET_TODAY(), 'relief' => $ret['relief']]);
        $gold = DBInstance::GetUserOneWord('gold', $uid);
        $msg = Common::NoticeMsg('Msg_Hall_GetBenefits', $message['uid']);
        $msg['data'] = [
            'gold' => $gold,
        ];
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 玩家使用兑换卡
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_UseExchangeCard($client_id, $message)
    {
        $uid = $message['uid'];
        $info = DBInstance::GetTableWords('user', 'rcard,rcardtime,agent', ['uid' => $uid]);
        if ($info['agent'] == 1) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '代理不能使用兑换卡', $message['uid']), $client_id);
            return;
        } elseif ($info['rcard'] < $message['data']['num'] || $message['data']['num'] <= 0 || $message['data']['num'] > 2) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '非法使用兑换卡', $message['uid']), $client_id);
            return;
        }

        $num = DBInstance::GetTableOneWord('user_superior', 'usecard', ['uid' => $uid]);
        if ($num + $message['data']['num'] > 2) {
            $_num = 2 - $num;
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '今日剩余兑换数量为：' . $_num . '张', $message['uid']), $client_id);
            return;
        }

        DBInstance::IncrementGolds('gold', $uid, EXCHANGECARD * $message['data']['num']);
        DBInstance::IncrementGolds('rcard', $uid, -$message['data']['num']);
        DBInstance::IncrementRcard($uid, $message['data']['num']);
        $gold = DBInstance::GetUserOneWord('gold', $uid);
        $msg = Common::NoticeMsg('Msg_Hall_UseExchangeCard', $message['uid']);
        $msg['data'] = [
            'gold' => $gold,
        ];
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 查询玩家游戏记录
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_GameRecord($client_id, $message)
    {
        $ret = DBInstance::GetUserSuperior($message['data']['uid']);
        if ($message['uid'] != $ret) {
            Common::SendToClient(Common::NoticErrorMsg($message['event'], '您没有权限', $message['uid']), $client_id);
            return;
        }

        $uid = $message['uid'];

        if (!isset($message['data']['page'])) {
            $message['data']['page'] = 1;
        }

        $msg = Common::NoticeMsg('Msg_Hall_GameRecord', $uid);
        $msg['data'] = DBInstance::QueryGameRecord($message['data']['uid'], $message['data']['page']);
        Common::SendToClient($msg, $client_id);
    }

    /**
     * 通知玩家金币变动
     * @param $uid
     */
    public static function Msg_Hall_ChangeGolds($uid)
    {
        $msg = Common::NoticeMsg('Msg_Hall_ChangeGolds', $uid);
        $msg['data'] = DBInstance::GetTableWords('user', 'gold,bank,rcard', ['uid' => $uid]);
        Common::SendToUID($msg, $uid);
    }


    /**
     * 充值代理列表
     * @param $client_id
     * @param $message
     */
    public static function Msg_Hall_RechargeList($client_id, $message)
    {

    }
}
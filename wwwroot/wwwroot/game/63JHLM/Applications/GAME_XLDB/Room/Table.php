<?php

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

require_once __DIR__ . '/Algorithm.php';
const ZHEN = 0.02;

class Table
{
    private $roomRule = [];  //房间规则
    private $mUserList = [];  //玩家信息
    private $mSeat = [];   //玩家座位：uid
    private $mTimer = 0;   //定时器id
    private $zhenTimes = 0; //帧数总计
    private $addTimes = 0;  //新增鱼的次数
    private $userBullet = [];  //玩家子弹编号缓存
    private $stopTime = 0;   //暂停时间戳
    private $lastFishBoom = 0;  //上次鱼潮时间戳
    private $fishBoomTimer = 0;  //鱼潮定时器id
    private $fishboomId = 0;    //当前鱼潮id
    private $outTimer = 0;   //退场定时器id
    /*
     * $robotList = [
     *      'status' => 状态,
     *      'nexttime' => 下次切换状态时间,
     *      'berrtytime' => 下次切换炮台时间,
     *      'randchange' => 切换锁定时间,
     *      'stoptime' => 间歇时间,
     *      'angle' => 当前角度,
     *      'level' => 等级,
     *      'lock' => 锁定,
     *      'lastzhen' => 上次发射帧数,
     *      'endtime' => 出场时间
     * ];
     * */

    private $robotList = [];    //机器人列表
    /*
     * $curFinshList = [
     *      'id' => [
     *          'type' => '',
     *          'createtime' => '',
     *          'wayid' => '',
     *          'couples' => [],
     *          'speed' => '速度',
     *      ],
     * ];
     * */
    private $curFinshList = [];
    private $finshLife = [];
    private $Algorithm = null;
    private $recordTime = 0;
    private $drong = false;
    private $fishBoom = false;
    private $playerAgent = [];

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->mUserList = $msg['players'];
        unset($msg['players']);
        $this->roomRule = $msg;
        $this->Algorithm = new Algorithm();
        $this->lastFishBoom = MyTools::GET_MS();

        foreach ($this->mUserList as $key => $value) {
            $this->mSeat[$value['seat']] = $key;
            $this->mUserList[$key]['level'] = 1;  //初始化炮台等级
            $this->mUserList[$key]['locking'] = 0;  //锁定
            $ret = DBInstance::GetUserOneWord('battery', $key);//炮台
            $this->mUserList[$key]['battery'] = $ret == false ? 0 : $ret;  //炮台
            $this->mUserList[$key]['win'] = 0;  //局内所得
            $this->mUserList[$key]['get'] = 0;  //玩家得分
            $res = DBInstance::GetFishGetRand($this->roomRule['gtype'], $this->roomRule['level'], $key);
            $this->mUserList[$key]['rand_get'] = $res['rand_get'];
            $this->mUserList[$key]['control'] = $res['control'];
            $this->userBullet[$key] = [];   //初始化子弹列表
            if ($value['client_id'] != '') {
                Gateway::joinGroup($value['client_id'], 'ROOM:' . $this->roomRule['rid']);
            } else {
                $this->robotList[$key] = [
                    'status' => STAND,
                    'nexttime' => time(),
                    'berrtytime' => time(),
                    'randchange' => time(),
                    'stoptime' => time(),
                    'angle' => 0,
                    'lock' => 0,
                    'level' => 1,
                    'lastzhen' => 0,
                    'endtime' => rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time()
                ];
            }
        }

        Logic::SendAll('Msg_XLDB_RoomInfo', [
            'time_ms' => MyTools::GET_MS(),
            'stoptime' => $this->stopTime,
            'fishlist' => $this->curFinshList,
            'players' => $this->mUserList,
            'fishtideid' => $this->fishboomId,
            'lasttidetime' => $this->lastFishBoom,
            'setting' => [
                'doublescore' => $this->roomRule['doublescore'],
                'level' => $this->roomRule['level'],
                'min_gold' => $this->roomRule['min_gold'],
                'max_gold' => $this->roomRule['max_gold'],
            ]
        ], $this->roomRule['rid']);
        $this->CreateFishes();
        $this->GetPlayerAgent();
    }

    /**
     *  新增桌面鱼
     */
    public function CreateFishes()
    {
        $this->mTimer = Timer::add(ZHEN, function () {
            $curtime = time();
            if ($this->recordTime != $curtime) {
                $this->recordTime = $curtime;
                $this->ChangeRobotStatus();
            }

            foreach ($this->robotList as $key => $value) {
                if ($curtime - $value['endtime'] > 0) {
                    $this->Msg_XLDB_Out(['uid' => $key]);
                    continue;
                } elseif ($this->mUserList[$key]['gold'] < $this->roomRule['doublescore'] * 10 && $value['endtime'] - $curtime > 4) {
                    $this->robotList[$key]['endtime'] = $curtime + rand(4, 8);
                }

                if ($this->zhenTimes - $value['lastzhen'] >= 10 && $value['endtime'] > $curtime && $value['status'] > LOCK && $value['lock'] != 0) {
                    $data = [
                        'event' => 'Msg_XLDB_ActShoot',
                        'uid' => $key,
                        'data' => ['level' => $value['level'], 'lockID' => $value['lock']]
                    ];
                    if (!empty($this->curFinshList[$value['lock']])) {
                        $this->robotList[$key]['lastzhen'] = $this->zhenTimes;
                        $id = $this->Msg_XLDB_ActShoot($data);
                        if ($id) {
                            $this->Msg_XLDB_GetFish(
                                [
                                    'event' => "Msg_XLDB_GetFish",
                                    'uid' => $key,
                                    'data' => [
                                        'bulletid' => $id,
                                        'fish' => $value['lock'],
                                    ]
                                ]
                            );
                        }
                    }
                }
            }

            $this->zhenTimes++;
            if ($this->zhenTimes % 150 == 0) {
                foreach ($this->mUserList as $key => $value) {
                    $this->mUserList[$key]['get'] = 0;
                    if ($this->roomRule['level'] != 1 && abs($value['control']) != 2) {
                        Logic::InsertProfit($this->roomRule['level'], $value['get']);
                    }

                    if ($this->roomRule['level'] != 1 && !empty($value['client_id'])) {
                        DBInstance::IncrementUserGet($key, $value['get']);
                    }
                    $res = DBInstance::GetFishGetRand($this->roomRule['gtype'], $this->roomRule['level'], $key);
                    $this->mUserList[$key]['rand_get'] = $res['rand_get'];
                    $this->mUserList[$key]['control'] = $res['control'];
                }
            }

            //鱼潮
            if (MyTools::GET_MS() - $this->lastFishBoom >= $this->roomRule['vals']['fishboom'] * 1000 && MyTools::GET_MS() - $this->stopTime >= 15000 && $this->drong == false) {
                $this->CreateFishBoom();
                return;
            } elseif ($this->fishboomId > 0) {
                return;
            }

            if (MyTools::GET_MS() - $this->stopTime >= 15000) {
                if ($this->zhenTimes % 60 === 0) {
                    $this->addTimes++;
                    if ($this->addTimes > 200) {
                        $this->addTimes -= 200;
                    }
                    $news = $this->Algorithm->GetNewFinsh($this->addTimes, $this->curFinshList);
                } else {
                    $news = $this->Algorithm->CreatFishQueue();
                }
            } else {
                $news = [];
            }

            $send = $news;
            foreach ($news as $key => $value) {
                unset($send[$key]['endtime']);
                $type = $value['type'] > 100 ? intval($value['type'] / 100) * 100 : $value['type'];
                if ($type == 100) {
                    $this->drong = true;
                }
                if (!empty($value['fishes'])) {
                    $this->finshLife[$key] = [];
                    foreach ($value['fishes'] as $key1 => $value1) {
                        $this->finshLife[$key][$key1] = StaticValue::$FishList[$type]['life'];
                    }
                } else {
                    $this->finshLife[$key] = StaticValue::$FishList[$type]['life'];
                }
            }

            if (!empty($news)) {
                Logic::SendAll('Msg_XLDB_CreateFish', ['finsh' => $send], $this->roomRule['rid']);
                $this->curFinshList += $news;
            }

            if (MyTools::GET_MS() - $this->stopTime >= 15000) {
                $this->stopTime = 0;
            }
            $this->RefreshFinsh();
        });
    }

    /**
     *  开启鱼潮
     */
    public function CreateFishBoom()
    {
        $this->lastFishBoom = MyTools::GET_MS();
        $this->fishboomId = array_rand(StaticValue::$fishBoom);
        Logic::SendAll('Msg_XLDB_FishTide', ['tide' => $this->fishboomId], $this->roomRule['rid']);
        $this->outTimer = Timer::add(3, function () {
            Timer::del($this->outTimer);
            foreach ($this->curFinshList as $key => $value) {
                $this->PushFishId($key);
            }
            $this->fishBoom = true;
            foreach (StaticValue::$fishBoom[$this->fishboomId]['fish_list'] as $key => $value) {
                $this->finshLife[$key] = $value['life'];
                $this->curFinshList[$key]['type'] = $value['type'];
                if ($value['type'] > 100) {
                    $this->curFinshList[$key]['couples'] = [$value['type'] % 10];
                }
            }
        }, false, []);

        $this->fishBoomTimer = Timer::add(StaticValue::$fishBoom[$this->fishboomId]['time'] + 8, function () {
            //清空所有鱼类
            Timer::del($this->fishBoomTimer);
            $this->curFinshList = [];
            $this->fishBoomTimer = 0;
            $this->fishboomId = 0;
            $this->fishBoom = false;
        }, false, []);
    }

    /**
     *  刷新桌面鱼
     */
    public function RefreshFinsh()
    {
        $times = MyTools::GET_MS();
        foreach ($this->curFinshList as $key => $value) {
            if (isset($this->curFinshList[$key])) {
                if (!is_array($value['endtime']) && $value['endtime'] <= $times && $this->curFinshList[$key]['fishes'] != 0) {
                    $this->PushFishId($key);
                } elseif (is_array($value['endtime'])) {
                    foreach ($value['endtime'] as $key1 => $value1) {
                        if ($value1 <= $times) {
                            $this->curFinshList[$key]['fishes'][$key1] = 0;
                        }
                    }
                    if (array_sum($this->curFinshList[$key]['fishes']) == 0) {
                        $this->PushFishId($key);
                    }
                }
            }
        }
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_XLDB_Out':
                $this->Msg_XLDB_Out($message);
                break;
            case 'Msg_XLDB_ActChange':
                $this->Msg_XLDB_ActChange($message);
                break;
            case 'Msg_XLDB_ActShoot':
                $this->Msg_XLDB_ActShoot($message);
                break;
            case 'Msg_XLDB_GetFish':
                $this->Msg_XLDB_GetFish($message);
                break;
            case 'Msg_XLDB_Locking':
                $this->Msg_XLDB_Locking($message);
                break;
            case 'Msg_XLDB_FishBattery':
                $this->Msg_XLDB_FishBattery($message);
                break;
            default:
            {
                Logic::SendError($message['uid'], $message['event'], '');
                MyTools::msg('uid:' . $message['uid'] . '-----UnKnown!!!!!!!--------' . $message['event'], true);
                break;
            }
        }
    }

    /**
     * 切换炮台
     * @param array
     */
    public function Msg_XLDB_ActChange($message)
    {
        $uid = $message['uid'];
        $level = $message['data']['level'];
        /*if ($this->mUserList[$uid]['gold'] < $level * $this->roomRule['doublescore']) {
            Logic::SendError($uid, $message['event'], '玩家分数不足');
            return;
        }*/

        $this->mUserList[$uid]['level'] = $level;
        $data = ['seat' => $this->mUserList[$uid]['seat'], 'level' => $level];
        Logic::SendAll($message['event'], $data, $this->roomRule['rid']);
    }

    /**
     * 玩家发射子弹
     * @param array
     * @return int
     */
    public function Msg_XLDB_ActShoot($message)
    {
        $uid = $message['uid'];

        if (count($this->userBullet[$uid]) >= 30 || (isset($this->robotList[$uid]) && count($this->userBullet[$uid]) > 5)) {
            Logic::SendError($uid, $message['event'], '发射炮弹数量过多');
            return 0;
        }

        $usegold = $message['data']['level'] * $this->roomRule['doublescore'];
        if ($this->mUserList[$uid]['gold'] < $usegold) {
            Logic::SendError($uid, $message['event'], '玩家分数不足');
            return 0;
        }

        $this->ChangeGolds($uid, -$usegold);
        $sum = count($this->Algorithm->BulletIdList);
        foreach ($this->userBullet as $key => $value) {
            $sum += count($value);
        }

        $id = $this->Algorithm->GetBulletId();
        $this->userBullet[$uid][$id] = $message['data']['level'];
        $message['data']['bulletid'] = $id;
        $data = $message['data'];
        $data['seat'] = $this->mUserList[$uid]['seat'];
        Logic::SendAll($message['event'], $data, $this->roomRule['rid']);
        return $id;
    }

    /**
     * 子弹碰撞
     * @param array
     */
    public function Msg_XLDB_GetFish($message)
    {
        $uid = $message['uid'];
        $id = $message['data']['bulletid'];

        if (empty($this->userBullet[$uid][$id])) {
            Logic::SendError($uid, $message['event'], '参数错误1');
            return;
        }

        $bullet = $this->userBullet[$uid][$id];
        //删除子弹
        $this->Algorithm->PushBulletId($id);
        unset($this->userBullet[$uid][$id]);

        if ($message['data']['fish'] >= 1000) {
            $fishid = intval($message['data']['fish'] / 1000);
            $mindex = $message['data']['fish'];
            if (empty($this->curFinshList[$fishid]['fishes'][$mindex])) {
                //Logic::SendError($uid, $message['event'], '参数错误2');
                return;
            }
        } else {
            $fishid = $message['data']['fish'];
            if (empty($this->curFinshList[$fishid])) {
                //Logic::SendError($uid, $message['event'], '参数错误3');
                return;
            }
        }


        $fish = $this->curFinshList[$fishid];//随机降低鱼的生命值
        $type = $fish['type'] > 100 ? intval($fish['type'] / 100) * 100 : $fish['type'];
        $rand_get = StaticValue::$FishList[$type]['rand_get'];
        if ($this->fishboomId/* || isset($this->robotList[$uid])*/) {
            $rand_get += BOOMRAND;
        }

        if (!GAMECONTROLS) {
            $rand_get = 80;
        }

        if (!empty($this->mUserList[$uid]['client_id'])) {
            $rand_get += $this->mUserList[$uid]['rand_get'];
        }

        if (rand(0, 99) <= $rand_get) {
            if (!empty($fish['fishes'])) {
                $this->finshLife[$fishid][$message['data']['fish']] -= 5;
            } else {
                if (is_numeric($this->finshLife[$fishid])) {
                    $this->finshLife[$fishid] -= 5;
                }
            }
        }

        $data = [
            'seat' => $this->mUserList[$uid]['seat'],
            'bulletid' => $id,
            'gold' => 0,
            'shootid' => $message['data']['fish'],
            'fish' => [],
            'types' => [],
        ];

        $life = !empty($mindex) ? $this->finshLife[$fishid][$mindex] : $this->finshLife[$fishid];
        if ($life <= 0) {
            if ($fish['type'] < 90 || $fish['type'] == 100) {
                if (!empty($mindex)) {
                    //删除鱼阵中鱼
                    $this->curFinshList[$fishid]['fishes'][$mindex] = 0;
                    $data['fish'][$mindex] = $this->GetGolds($fish['type'], $bullet);
                    if (array_sum($fish['fishes']) == 0) {
                        $this->PushFishId($fishid);
                    }
                } else {
                    $data['fish'][$fishid] = $this->GetGolds($fish['type'], $bullet);
                    //删除鱼
                    $this->PushFishId($fishid);
                }
            } elseif ($fish['type'] > 100) {
                //删除鱼
                $this->PushFishId($fishid);
                //特殊种类鱼加成
                if ($fish['type'] > 100) {
                    $data['fish'][$fishid] = $this->GetGolds($fish['type'] % 100, $bullet);
                    $num = intval($fish['type'] / 100);
                    for ($i = 0; $i < $num; $i++) {
                        $data['fish'][$fishid] += $this->GetGolds($fish['couples'][0], $bullet);
                    }
                }
            } else {
                $data['fish'][$fishid] = $this->GetGolds($fish['type'], $bullet);
                //删除鱼
                $this->PushFishId($fishid);
                //炸弹类
                if ($fish['type'] == 99) {
                    //全屏炸弹
                    $fishposition = $this->Algorithm->CountPosition($fish);
                    foreach ($this->curFinshList as $key => $value) {
                        if ($value['type'] > 6) {
                            continue;
                        }

                        if (empty($value['fishes'])) {
                            //局部炸弹
                            $position2 = $this->Algorithm->CountPosition($value);
                            $distance = $this->Algorithm->CountDistance($fishposition, $position2);
                            if ($distance < 300) {
                                $data['fish'][$key] = $this->GetGolds($value['type'], $bullet);
                                $this->PushFishId($key);
                            }
                        } else {
                            foreach ($value['fishes'] as $key1 => $value1) {
                                if ($value1 != 0) {
                                    $position2 = $this->Algorithm->CountPosition($value, $key1 % 100);
                                    $distance = $this->Algorithm->CountDistance($fishposition, $position2);
                                    if ($distance < 200) {
                                        $data['fish'][$key1] = $this->GetGolds($value['type'], $bullet);
                                        $this->curFinshList[$key]['fishes'][$key1] = 0;
                                    }
                                }
                            }

                            if (array_sum($value['fishes']) == 0) {
                                $this->PushFishId($key);
                            }
                        }
                    }
                } elseif ($fish['type'] == 91) {
                    if ($this->fishboomId == 0) {
                        $fishposition = $this->Algorithm->CountPosition($fish);
                        foreach ($this->curFinshList as $key => $value) {
                            if (empty($value['fishes'])) {
                                //局部炸弹
                                $position2 = $this->Algorithm->CountPosition($value);
                                $distance = $this->Algorithm->CountDistance($fishposition, $position2);
                                if ($distance < 200) {
                                    $data['fish'][$key] = $this->GetGolds($value['type'], $bullet);
                                    $this->PushFishId($key);
                                }
                            } else {
                                foreach ($value['fishes'] as $key1 => $value1) {
                                    if ($value1 != 0) {
                                        $position2 = $this->Algorithm->CountPosition($value, $key1 % 100);
                                        $distance = $this->Algorithm->CountDistance($fishposition, $position2);
                                        if ($distance < 200) {
                                            $data['fish'][$key1] = $this->GetGolds($value['type'], $bullet);
                                            $this->curFinshList[$key]['fishes'][$key1] = 0;
                                        }
                                    }
                                }

                                if (array_sum($value['fishes']) == 0) {
                                    $this->PushFishId($key);
                                }
                            }
                        }
                    } else {
                        $_flag = intval($message['data']['fish'] / 100);
                        foreach ($this->curFinshList as $key => $value) {
                            if (intval($key / 100) == $_flag) {
                                $data['fish'][$key] = $this->GetGolds($value['type'], $bullet);
                                $this->PushFishId($key);
                            }
                        }
                    }
                } elseif ($fish['type'] == 90) {
                    //定屏炸弹
                    $this->stopTime = MyTools::GET_MS();
                    foreach ($this->curFinshList as $key => $value) {
                        $starttime = empty($this->curFinshList[$key]['starttime']) ? $this->curFinshList[$key]['createtime'] : $this->curFinshList[$key]['starttime'];
                        $this->curFinshList[$key]['starttime'] = $starttime + 15000;
                        if (is_array($value['endtime'])) {
                            foreach ($value['endtime'] as $key1 => $value1) {
                                if ($this->curFinshList[$key]['endtime'][$key1] > $this->stopTime) {
                                    $this->curFinshList[$key]['endtime'][$key1] += 15000;  //定屏延时
                                }
                            }
                        } else {
                            $this->curFinshList[$key]['endtime'] += 15000;  //定屏延时
                        }
                    }
                }
            }

            $this->ChangeGolds($uid, array_sum($data['fish']));
            $data['gold'] = $this->mUserList[$uid]['gold'];
        }
        unset($this->userBullet[$uid][$id]);
        if (!empty($data['fish'])) {
            /*if (empty($this->mUserList[$uid]['client_id'])) {
                $this->changeLock($uid);
            }*/
            Logic::SendAll('Msg_XLDB_GetFish', $data, $this->roomRule['rid']);
        }
    }

    /**
     * 锁定鱼
     * @param array
     */
    private function Msg_XLDB_Locking($message)
    {
        if (empty($message['data']['fish'])) {
            $this->mUserList[$message['uid']]['locking'] = 0;
            if (isset($message['data']['uid']) && isset($this->robotList[$message['data']['uid']])) {
                $this->robotList[$message['data']['uid']]['lock'] = 0;
            }
            $data = [
                'uid' => $message['data']['uid'] ?? $message['uid'],
                'fish' => 0,
            ];
        } else {
            $this->mUserList[$message['uid']]['locking'] = $message['data']['fish'];
            if (isset($message['data']['uid']) && isset($this->robotList[$message['data']['uid']])) {
                $this->robotList[$message['data']['uid']]['lock'] = $message['data']['fish'];
            }
            $data = [
                'uid' => $message['data']['uid'] ?? $message['uid'],
                'fish' => $message['data']['fish'],
                'time' => MyTools::GET_MS()
            ];
        }
        Logic::SendAll('Msg_XLDB_Locking', $data, $this->roomRule['rid']);
    }

    /**
     * 更换炮台
     * @param array
     */
    private function Msg_XLDB_FishBattery($message)
    {
        $uid = $message['uid'];
        if (DBInstance::CheckMotnly($uid) == 0) {
            Logic::SendError($uid, $message['event'], '仅对月卡玩家开放');
            return;
        }

        DBInstance::UpdateUserString($uid, ['battery' => $message['data']['battery']]);
        $this->mUserList[$uid]['battery'] = $message['data']['battery'];
        $data = [
            'uid' => $message['uid'],
            'battery' => $message['data']['battery'],
        ];
        Logic::SendAll($message['event'], $data, $this->roomRule['rid']);
    }

    /**
     * 回收鱼id
     */
    private function PushFishId($id)
    {
        /*foreach ($this->robotList as $key => $value) {
            if ($value['lock'] == $id) {
                $this->changeLock($key);
            }
        }*/
        if ($this->curFinshList[$id]['type'] == 100) {
            $this->drong = false;
        }
        unset($this->curFinshList[$id]);
        if ($id > 300 && $this->fishBoom == false) {
            $this->Algorithm->PushId($id);
        }
        foreach ($this->mUserList as $key => $value) {
            if ($value['locking'] == $id) {
                $this->mUserList[$key]['locking'] = 0;
            }
        }
    }

    /**
     * 击败鱼计算
     * @param int
     * @param int
     * @return int
     */
    private function GetGolds($type, $double)
    {
        $ret = 0;
        //随机加倍鱼类
        $rands = [28 => [70, 130], 30 => [90, 150], 31 => [100, 160], 32 => [110, 170], 33 => [115, 175], 34 => [140, 200], 35 => [150, 210], 36 => [170, 230], 37 => [190, 250]];
        //$rands = [16 => [1, 10], 17 => [50, 300], 18 => [60, 220], 21 => [80, 150], 23 => [40, 180]];
        if ($type > 100) {
            $type = intval($type / 100) * 100;
        }

        if (isset($rands[$type])) {
            $type_double = rand($rands[$type][0], $rands[$type][1]);
        } else {
            $type_double = StaticValue::$FishList[$type]['double'];
        }

        $ret += $type_double * $double * $this->roomRule['doublescore'];
        return $ret;
    }

    /**
     * 金币变动
     */
    private function ChangeGolds($uid, $gold)
    {
        $this->mUserList[$uid]['gold'] += $gold;

        if (!empty($this->mUserList[$uid]['client_id'])) {
            $this->mUserList[$uid]['win'] += $gold;
            $this->mUserList[$uid]['get'] += $gold;
        }

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementWinPoint($uid, $gold);
            DBInstance::IncrementGolds('gold', $uid, $gold);
            if (intval($gold / $this->roomRule['doublescore']) >= 80 && $gold >= 3000000) {
                Logic::HorseLamp($uid, $gold, intval($gold / $this->roomRule['doublescore']));
            }
        }
    }

    /**
     * 击败鱼计算
     * @param array
     * @param array
     * @param int
     * @return array
     */
    private function Get_Fishes($fishes, $types, $double)
    {
        if ($types[0] >= 300) {
            //闪电鱼
            $index = array_sum(array_keys($fishes));
            foreach ($types as $key => $value) {
                $fishes[$index] += $this->GetGolds($value, $double);
            }
        } else {
            foreach ($this->curFinshList as $key => $value) {
                if (in_array($value['type'], $types)) {
                    if (empty($value['fishes'])) {
                        //普通单个鱼
                        $fishes[$key] = StaticValue::$FishList[$value['type']]['double'] * $double * $this->roomRule['doublescore'];
                    } else {
                        //鱼阵
                        foreach ($value['fishes'] as $key1 => $value1) {
                            if ($value1 != 0) {
                                $fishes[$key1] = StaticValue::$FishList[$value['type']]['double'] * $double * $this->roomRule['doublescore'];
                            }
                        }
                    }
                    $this->PushFishId($key);
                }
            }
        }

        return $fishes;
    }

    /**
     * 更新状态
     */
    private function ChangeRobotStatus()
    {
        foreach ($this->robotList as $key => $value) {
            if ($value['nexttime'] <= $this->recordTime) {
                $res = StaticValue::GetStatus();
                $value['status'] = $res['status'];
                $value['nexttime'] = $res['nexttime'];
            }

            //切换炮台等级
            if ($value['status'] != STAND && $value['berrtytime'] <= $this->recordTime) {
                $res = StaticValue::ChangeBerrty();
                $value['berrtytime'] = $this->recordTime + $res['time'];
                if ($res['berrty'] != 0) {
                    $value['level'] += $res['berrty'];
                }

                if ($value['level'] < 1) {
                    $value['level'] = 1;
                } elseif ($value['level'] > 10) {
                    $value['level'] = 10;
                }

                if ($value['level'] != $this->mUserList[$key]['level']) {
                    $this->Msg_XLDB_ActChange(
                        [
                            'event' => 'Msg_XLDB_ActChange',
                            'uid' => $key,
                            'data' => [
                                'seat' => $this->mUserList[$key]['seat'],
                                'level' => $value['level']
                            ],
                        ]
                    );
                }
            }

            //切换锁定/切换炮台方向
            if ($value['status'] > STAND && ($value['randchange'] <= $this->recordTime || $value['lock'] == 0)) {
                if ($value['status'] == LOCK || $value['status'] == LOCKGET) {
                    /*$value['lock'] = $this->changeLock($key);
                    if ($value['lock'] && $value['endtime'] > time()) {
                        $this->Msg_XLDB_Locking([
                            'uid' => $key,
                            'data' => ['fish' => $this->robotList[$key]['lock']],
                        ]);
                    }*/
                    $value['randchange'] = $this->recordTime + rand(TIME_CHANGETURN[0], TIME_CHANGETURN[1]);
                } elseif ($value['status'] == GETFISH && $value['stoptime'] == 0 && rand(1, 100) <= 70) {
                    $value['angle'] = rand(-85, 85);
                    $value['randchange'] = $this->recordTime + rand(TIME_CHANGETURN[0], TIME_CHANGETURN[1]);
                }
            } elseif ($value['status'] == STAND && $value['lock']) {
                /*$this->Msg_XLDB_Locking([
                    'uid' => $key,
                    'data' => ['fish' => 0],
                ]);*/
            }

            $this->robotList[$key] = $value;
        }
    }

    /**
     * 玩家管理机器人
     */
    private function GetPlayerAgent()
    {
        $cur = $this->playerAgent;
        $robots = [];
        $this->playerAgent = [];
        foreach ($this->mUserList as $key => $value) {
            if (!empty($value['client_id'])) {
                $this->playerAgent[$key] = [];
            } else {
                $robots[] = $key;
            }
        }

        if (count($this->playerAgent) != 0) {
            if (count($this->playerAgent) > 1) {
                $i = 0;
                foreach ($this->playerAgent as $key => $value) {
                    if (empty($robots[$i])) {
                        break;
                    }
                    $this->playerAgent[$key][] = $robots[$i];
                    $i++;
                }
            } else {
                foreach ($this->playerAgent as $key => $value) {
                    $this->playerAgent[$key] = $robots;
                }
            }
        }

        if ($this->playerAgent != $cur) {
            Logic::SendAll('Msg_XLDB_PlayerAgent', $this->playerAgent, $this->roomRule['rid']);
        }
    }

    /**
     * 切换锁定目标
     */
    private function changeLock($uid)
    {
        if (($this->fishboomId && MyTools::GET_MS() - $this->lastFishBoom >= 8000) || $this->fishboomId == 0) {
            if ($this->fishboomId) {
                $this->robotList[$uid]['lock'] = intval((MyTools::GET_MS() - $this->lastFishBoom) / 1000) + rand(-5, 15);
            } else {
                $this->robotList[$uid]['lock'] = StaticValue::LockFish($this->curFinshList);
            }
            $this->robotList[$uid]['randchange'] = rand(2, 6) + $this->recordTime;
        } else {
            $this->robotList[$uid]['lock'] = 0;
        }

        if ($this->robotList[$uid]['lock'] > 0) {
            /*$this->Msg_XLDB_Locking([
                'uid' => $uid,
                'data' => ['fish' => $this->robotList[$uid]['lock']]
            ]);*/
        }
        return $this->robotList[$uid]['lock'];
    }

    /**
     * 玩家退房
     * @param array
     */
    public function Msg_XLDB_Out($message)
    {
        Logic::SendAll('Msg_XLDB_Out', [
            'seat' => $this->mUserList[$message['uid']]['seat'],
            'gold' => DBInstance::GetUserOneWord('gold', $message['uid'])], $this->roomRule['rid']);

        if (!empty($this->mUserList[$message['uid']]['client_id']) && abs($this->mUserList[$message['uid']]['control']) != 2) {
            Logic::InsertProfit($this->roomRule['level'], $this->mUserList[$message['uid']]['get']);
        }

        if ($this->roomRule['level'] != 1 && !empty($this->mUserList[$message['uid']]['client_id'])) {
            DBInstance::IncrementUserGet($message['uid'], $this->mUserList[$message['uid']]['get']);
        }
        unset($this->mSeat[$this->mUserList[$message['uid']]['seat']]);
        unset($this->mUserList[$message['uid']]);

        //删除子弹
        foreach ($this->userBullet[$message['uid']] as $key => $value) {
            $this->Algorithm->PushBulletId($key);
        }
        unset($this->robotList[$message['uid']]);
        unset($this->userBullet[$message['uid']]);
        Logic::QuitRoom([
            'rid' => $this->roomRule['rid'],
            'uid' => $message['uid']
        ]);
        $this->GetPlayerAgent();
    }

    /**
     * 金币变化
     * @param array
     */
    public function ChangeGold($message)
    {
        $uid = $message['uid'];
        $this->mUserList[$uid]['gold'] = DBInstance::GetUserOneWord('gold', $uid);
    }

    /**
     * 解散房间
     * @param array
     */
    public function DisRoom($message)
    {
        Timer::del($this->mTimer);
        Timer::del($this->fishBoomTimer);
        Timer::del($this->outTimer);
        $olddata = [
            'rid' => $this->roomRule['rid'],
            'win' => [],
            'palyers' => $this->mUserList,
            'result' => [
                'uid' => []
            ], //结算消息
            'gtype' => $this->roomRule['gtype'],
        ];
        foreach ($this->mSeat as $key => $value) {
            $this->Msg_XLDB_Out(['uid' => $value]);
        }
        Logic::RoomOld($olddata);
    }

    /**
     * 玩家进房
     * @param array
     */
    public function EnterRoom($message)
    {
        $uid = $message['uid'];
        $this->mUserList[$uid] = $message;
        $this->mUserList[$uid]['level'] = 1;
        $this->mUserList[$uid]['locking'] = 0;  //锁定
        $ret = DBInstance::GetUserOneWord('battery', $uid);//炮台
        $this->mUserList[$uid]['battery'] = $ret == false ? 0 : $ret;  //炮台
        $this->mUserList[$uid]['win'] = 0;  //局内所得
        $this->mUserList[$uid]['get'] = 0;  //局内所得
        $res = DBInstance::GetFishGetRand($this->roomRule['gtype'], $this->roomRule['level'], $uid);
        $this->mUserList[$uid]['rand_get'] = $res['rand_get'];
        $this->mUserList[$uid]['control'] = $res['control'];
        $this->mSeat[$this->mUserList[$uid]['seat']] = $uid;
        $this->userBullet[$uid] = [];   //初始化子弹列表
        Logic::SendRight($uid, 'Msg_XLDB_RoomInfo', [
            'time_ms' => MyTools::GET_MS(),
            'stoptime' => $this->stopTime,
            'fishlist' => $this->curFinshList,
            'players' => $this->mUserList,
            'fishtideid' => $this->fishboomId,
            'lasttidetime' => $this->lastFishBoom,
            'setting' => [
                'doublescore' => $this->roomRule['doublescore'],
                'level' => $this->roomRule['level'],
                'min_gold' => $this->roomRule['min_gold'],
                'max_gold' => $this->roomRule['max_gold'],
            ]
        ]);
        Logic::SendAll('Msg_XLDB_PlayerAct', [
            'seat' => $this->mUserList[$uid]['seat'],
            'player' => $message,
            'battery' => $this->mUserList[$uid]['battery'],
        ], $this->roomRule['rid']);
        if ($message['client_id'] != '') {
            Gateway::joinGroup($message['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->robotList[$uid] = [
                'status' => STAND,
                'nexttime' => time(),
                'berrtytime' => time(),
                'randchange' => time(),
                'stoptime' => time(),
                'angle' => 0,
                'lock' => 0,
                'level' => 1,
                'lastzhen' => 0,
                'endtime' => rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time()
            ];
        }
        $this->GetPlayerAgent();
    }


    /**
     * 玩家重连
     * @param $client_id
     * @param array
     */
    public function UserOnline($client_id, $message)
    {
        if ($client_id != '') {
            Gateway::joinGroup($client_id, 'ROOM:' . $message['data']['rid']);
        }
    }

    /**
     * 玩家离线
     * @param array
     */
    public function UserOff($uid)
    {
        $this->Msg_XLDB_Out(['uid' => $uid]);
    }
}
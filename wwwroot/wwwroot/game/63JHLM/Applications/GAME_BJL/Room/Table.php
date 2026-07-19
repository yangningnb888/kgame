<?php

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('STAGE_BET', 1);
define('STAGE_END', 2);

define('BET_TIME', 21);
define('END_TIME', 22);
define('SYS_TIME', 2);

define('BANKERGOLD', 200000000);

define('BANKERCIRCLE', 10);

require_once __DIR__ . '/Algorithm.php';

class Table
{
    private $roomRule = [];  //房间规则
    private $mUserList = [];  //玩家信息
    private $mRobot = [];   //机器人列表
    private $mTimes = 0;    //定时器开启时间
    private $mTimer = 0;   //定时器id
    private $AITimer = 0;    //下注定时器
    private $bankerTimer = 0;  //上庄定时器
    private $allRobotBanker = [];  //
    private $mGameStatus = 0;  //游戏阶段
    private $allBet = [];    //所有下注
    private $playerBet = [];    //玩家所有下注
    private $bankerList = [];  //上庄列表
    private $cards = [
        'banker' => '',  //庄
        'player' => ''   //闲
    ];    //当局牌值
    private $banker = [   //当前庄家信息
        'nickname' => '',
        'gold' => 0,
        'circle' => 0,
        'uid' => 0,
    ];
    private $history = [];   //历史记录
    private $zhen = 0;
    private $rebate = 0;
    private $bankerturn = false;
    private $gameOut = false;

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->roomRule = $msg;
        $this->rebate = round((100 - $this->roomRule['rebate']) * 0.01, 2);
        foreach ($msg['players'] as $key => $value) {
            $this->EnterRoom($value);
        }
        unset($msg['players']);
    }

    /**
     * 下注阶段
     */
    private function Stage_Bet()
    {
        $this->allBet = [];
        $this->playerBet = [];
        if ($this->gameOut) {
            $this->DisRoom();
            return;
        }

        foreach ($this->mUserList as $key => $value) {
            if ($value['online'] > 0 && time() - $value['online'] >= OFFTIMES && $this->banker['uid'] != $key) {
                $this->Msg_BJL_Out(['uid' => $key]);
            }
        }

        $this->NextBanker();
        $this->banker['circle']++;
        if (empty($this->banker['uid'])) {
            $this->mGameStatus = 0;
            return;
        }
        Logic::SendAll('Msg_BJL_StageBet', ['time' => BET_TIME], $this->roomRule['rid']);
        $this->RobotBet();
        DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet, time() + BET_TIME);
        $this->AITimer = Timer::add(SYS_TIME, function () {
            if ($this->mGameStatus == STAGE_BET) {
                DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet);
                foreach ($this->mRobot as $key => $value) {
                    if ($value <= time() && empty($this->allBet[$key]) && $this->banker['uid'] != $key && !in_array($key, $this->bankerList)) {
                        $this->Msg_BJL_Out(['uid' => $key]);
                    }
                }
                $data = array_fill(1, 8, 0);
                foreach ($data as $key => $value) {
                    $data[$key] = array_sum(array_column($this->allBet, $key));
                }
                Logic::SendAll('Msg_BJL_SysActBet', $data, $this->roomRule['rid']);
                //机器人投注逻辑
                $this->RobotBet();
            }
        });
    }

    /**
     *  结算阶段
     */
    private function Stage_End()
    {
        Timer::del($this->AITimer);
        if (empty($this->banker['uid'])) {
            $this->mGameStatus = 0;
            return;
        }

        //最后同步投注信息
        $data = array_fill(1, 8, 0);
        foreach ($data as $key => $value) {
            $data[$key] = array_sum(array_column($this->allBet, $key));
        }
        Logic::SendAll('Msg_BJL_SysActBet', $data, $this->roomRule['rid']);
        $res = $this->getBankerArea();
        $this->cards = Algorithm::GetCards($res);
        $wins = Algorithm::GetWin($this->cards);
        $this->history[] = $wins;
        if (count($this->history) > 78) {
            for ($i = 0; $i < 6; $i++) {
                array_shift($this->history);
            }
        }
        $gamepoints = $winpoints = $upgold = $allwin = [];
        $allwin[$this->banker['uid']] = 0;
        $data = [
            'result' => $wins,
            'cards' => $this->cards,
            'bigwiner' => [],
            'bet' => 0,
            'win' => 0,
            'gold' => 0,
            'time' => BET_TIME,
        ];

        $gamepoints[$this->banker['uid']] = $winpoints[$this->banker['uid']] = $allwin[$this->banker['uid']] = $upgold[$this->banker['uid']] = 0;
        foreach ($this->mUserList as $key => $value) {
            if (isset($this->allBet[$key])) {
                $allwin[$key] = 0;   //记录实际盈亏
                $upgold[$key] = 0;   //玩家金币变动
                $winpoints[$key] = 0;  //只记录收益
                foreach ($this->allBet[$key] as $key1 => $value1) {
                    if ($value1 > 0) {
                        if ($wins[$key1]) {
                            $allwin[$key] += round(DOUBLE[$key1] * $value1) + $value1;
                            $winpoints[$key] += round(DOUBLE[$key1] * $value1 * $this->rebate);
                            $upgold[$key] += round(DOUBLE[$key1] * $value1 * $this->rebate + $value1);
                            $upgold[$this->banker['uid']] -= round(DOUBLE[$key1] * $value1);
                            $gamepoints[$this->banker['uid']] -= round(DOUBLE[$key1] * $value1);
                            $allwin[$this->banker['uid']] -= round(DOUBLE[$key1] * $value1);
                        } else {
                            if ($wins[5] && ($key1 == 3 || $key1 == 4)) {
                                $upgold[$key] += $value1;  //开出和退还庄闲押注
                                $allwin[$key] += $value1;  //开出和退还庄闲押注
                            } else {
                                $allwin[$this->banker['uid']] += $value1;
                                $winpoints[$this->banker['uid']] += $value1;
                                $upgold[$this->banker['uid']] += $value1;
                                $gamepoints[$this->banker['uid']] += $value1;
                            }
                        }
                    }
                }

                $gamepoints[$key] = $allwin[$key] - array_sum($this->allBet[$key]); //记录未抽水收益
                $allwin[$key] = $upgold[$key] - array_sum($this->allBet[$key]);
                DBInstance::IncrementGolds('gold', $key, $upgold[$key]);
                $this->mUserList[$key]['gold'] += $upgold[$key];
                if (empty($this->mRobot[$key])) {
                    DBInstance::IncrementUserGet($key, $allwin[$key]);
                }
            }
        }

        //增加玩家赢分榜
        foreach ($winpoints as $key => $value) {
            DBInstance::IncrementWinPoint($key, $value);
        }
        DBInstance::IncrementGolds('gold', $this->banker['uid'], $upgold[$this->banker['uid']]);
        $this->mUserList[$this->banker['uid']]['gold'] += $upgold[$this->banker['uid']];
        arsort($allwin);
        $count = 0;
        foreach ($allwin as $key => $value) {
            if ($value < 2000000 && count($data['bigwiner']) >= 3) {
                break;
            }
            $count++;
            if ($value >= 9000000 && $count <= 1) {
                Logic::HorseLamp($key, $value, 0);
            }

            if (count($data['bigwiner']) < 3) {
                $data['bigwiner'][$key] = [
                    'nickname' => $this->mUserList[$key]['nickname'],
                    'win' => $value,
                ];
            }
        }

        $data['bankerwin'] = $upgold[$this->banker['uid']];
        if (empty($this->mRobot[$this->banker['uid']])) {
            DBInstance::IncrementUserGet($key, $upgold[$this->banker['uid']]);
        }
        $gameloss = 0;
        foreach ($this->mUserList as $key => $value) {
            //更新游戏盈亏
            if (empty($this->mRobot[$key]) && !empty($gamepoints[$key])) {
                $gameloss += $gamepoints[$key];
            }

            $send = $data;
            $send['bet'] = isset($this->allBet[$key]) ? array_sum($this->allBet[$key]) : 0;
            $send['win'] = $allwin[$key] ?? 0;
            $send['gold'] = $value['gold'];
            Logic::SendRight($key, 'Msg_BJL_StageEnd', $send);
        }


        Logic::InsertProfit($this->roomRule['level'], $gameloss);
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_BJL_ActBet':
                $this->Msg_BJL_ActBet($message);
                break;
            case 'Msg_BJL_ToBanker':
                $this->Msg_BJL_ToBanker($message);
                break;
            case 'Msg_BJL_GetUserList':
                $this->Msg_BJL_GetUserList($message);
                break;
            case 'Msg_BJL_Out':
                $this->Msg_BJL_Out($message);
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
     * 玩家下注
     * @param array
     */
    private function Msg_BJL_ActBet($message)
    {
        $uid = $message['uid'];
        if ($uid == $this->banker['uid']) {
            Logic::SendError($uid, $message['event'], '庄家不能下注');
            return;
        }

        if ($this->mUserList[$uid]['gold'] < $message['data']['gold']) {
            Logic::SendError($uid, $message['event'], '玩家金币不足');
            return;
        }

        if ($message['data']['region'] <= 0 || $message['data']['region'] > 8 || !is_int($message['data']['region'])) {
            Logic::SendError($uid, $message['event'], '参数错误');
            return;
        }

        if ($this->mGameStatus != STAGE_BET) {
            Logic::SendError($uid, $message['event'], '阶段错误');
            return;
        }

        $curgold = $this->allBet[$uid][$message['data']['region']] ?? 0;
        if ($curgold + $message['data']['gold'] > $this->roomRule['controls']['maxbet']) {
            Logic::SendError($uid, $message['event'], '下注金额过限');
            return;
        }

        $quote = $this->GetOdds();
        if ($message['data']['gold'] > $quote[$message['data']['region']]) {
            Logic::SendError($uid, $message['event'], '庄家赔付额度不足，无法下注');
            return;
        }

        if (empty($this->allBet[$uid])) {
            $this->allBet[$uid] = array_fill(1, 8, 0);
            if (!empty($this->mUserList[$uid]['client_id'])) {
                $this->playerBet[$uid] = array_fill(1, 8, 0);
            }
        }

        $this->allBet[$uid][$message['data']['region']] += $message['data']['gold'];
        if (!empty($this->mUserList[$uid]['client_id'])) {
            $this->playerBet[$uid][$message['data']['region']] += $message['data']['gold'];
        }
        $this->mUserList[$uid]['gold'] -= $message['data']['gold'];
        DBInstance::IncrementGolds('gold', $uid, -$message['data']['gold']);
        $data = $message['data'];
        $data['uid'] = $uid;
        Logic::SendRight($uid, 'Msg_BJL_ActBet', $data);
    }

    /**
     * 玩家上庄
     * @param array
     */
    private function Msg_BJL_ToBanker($message = [])
    {
        if (isset($message['data']['stage'])) {
            $uid = $message['uid'];
            if ($message['data']['stage'] == 1) {
                if ($this->mUserList[$uid]['gold'] < BANKERGOLD || $this->banker['uid'] == $uid || in_array($uid, $this->bankerList)) {
                    Logic::SendError($uid, $message['event'], '玩家无法上庄');
                    return;
                }
                $this->bankerList[] = $uid;
            } else {
                if (!in_array($uid, $this->bankerList) && $this->banker['uid'] != $uid) {
                    Logic::SendError($uid, $message['event'], '玩家无法取消上庄');
                    return;
                } elseif ($this->banker['uid'] == $uid && $this->bankerturn == false) {
                    $this->bankerturn = true;
                } elseif (in_array($uid, $this->bankerList)) {
                    unset($this->bankerList[array_search($uid, $this->bankerList)]);
                }
            }
        } else {
            foreach ($this->bankerList as $key => $value) {
                if ($this->mUserList[$value]['gold'] < BANKERGOLD) {
                    unset($this->bankerList[$key]);
                    //提示玩家金币不足
                    Logic::SendError($value, $message['event'], '当前金币不满足上庄条件');
                }
            }
        }

        $this->bankerList = array_values($this->bankerList);
        Logic::SendAll('Msg_BJL_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
        if ($this->mGameStatus == 0) {
            $uid = array_shift($this->bankerList);
            $this->banker = DBInstance::GetBankerInfo($uid);
            $this->banker['circle'] = 0;
            Logic::SendAll('Msg_BJL_BankerInfo', $this->banker, $this->roomRule['rid']);
            $this->mGameStatus = STAGE_BET;
            $this->mTimes = time();
            $this->Stage_Bet();
            $this->mTimer = Timer::add(BET_TIME, function () {
                $this->mTimes = time();
                $this->mGameStatus = $this->mGameStatus != STAGE_BET ? STAGE_BET : STAGE_END;
                switch ($this->mGameStatus) {
                    case STAGE_END:
                        $this->Stage_End();
                        break;
                    case STAGE_BET:
                        $this->Stage_Bet();
                        break;
                }
            });
        }
    }

    /**
     * 获取庄家信息
     * @param array
     * @return array
     */
    private function Msg_BJL_GetUserList($message)
    {
        $data = [];
        foreach ($this->mUserList as $key => $value) {
            $data[$key] = [
                'headimgurl' => $value['headimgurl'],
                'nickname' => $value['nickname'],
                'gold' => $value['gold'],
            ];
        }
        Logic::SendRight($message['uid'], 'Msg_BJL_GetUserList', $data);
    }

    /**
     * 限制押注配付额度
     * @param int
     * @param int
     * @return array
     */
    private function GetOdds()
    {
        $total = 0;
        foreach (DOUBLE as $key => $value) {
            if ($key != SPACE && $key != BWIN && $key != PWIN && $key != SAMEPOINT) {
                $total += array_sum(array_column($this->allBet, $key)) * DOUBLE[$key];
            }
        }
        $all = [];
        $all[BWIN] = array_sum(array_column($this->allBet, BWIN)) * DOUBLE[BWIN];
        $all[PWIN] = array_sum(array_column($this->allBet, PWIN)) * DOUBLE[PWIN];
        $all[SPACE] = array_sum(array_column($this->allBet, SPACE)) * DOUBLE[SPACE] + array_sum(array_column($this->allBet, SAMEPOINT)) * DOUBLE[SAMEPOINT]
            + array_sum(array_column($this->allBet, BWIN)) + array_sum(array_column($this->allBet, PWIN));
        $allbet = 0;  //总押注金额
        foreach ($this->allBet as $key => $value) {
            $allbet += array_sum($value);
        }

        $Quota = [];
        foreach (DOUBLE as $key => $value) {
            if ($key != SPACE && $key != BWIN && $key != PWIN && $key != SAMEPOINT) {
                $Quota[$key] = intval(($this->mUserList[$this->banker['uid']]['gold'] + $allbet - $total - max($all)) / $value);
            } elseif ($key == BWIN || $key == PWIN) {
                $Quota[$key] = intval(($this->mUserList[$this->banker['uid']]['gold'] + $allbet - $total - $all[$key]) / $value);
            } else {
                $Quota[$key] = intval(($this->mUserList[$this->banker['uid']]['gold'] + $allbet - $total - $all[SPACE]) / $value);
            }
        }
        return $Quota;
    }


    /**
     * 切换庄家
     * @param array
     */
    private function NextBanker()
    {
        $player = 0;
        $num = count($this->bankerList);
        foreach ($this->bankerList as $key => $value) {
            if ($this->mUserList[$value]['gold'] < BANKERGOLD) {
                unset($this->bankerList[$key]);
            } elseif (empty($this->mRobot[$value])) {
                $player++;
            }
        }

        if ($num != count($this->bankerList)) {
            $this->bankerList = array_values($this->bankerList);
            Logic::SendAll('Msg_BJL_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
        }

        if ($player > 0) {
            foreach ($this->bankerList as $key => $value) {
                if (!empty($this->mRobot[$value]) && rand(1, 2) == 1) {
                    $this->allRobotBanker[rand(20, 90) + $this->zhen] = $value;
                    break;
                }
            }
        }

        if (!empty($this->banker['uid'])) {
            $gold = DBInstance::GetUserOneWord('gold', $this->banker['uid']);
            if ($gold < BANKERGOLD || $this->banker['circle'] >= BANKERCIRCLE || $this->bankerturn) {
                if (!empty($this->bankerList)) {
                    $uid = array_shift($this->bankerList);
                    $this->banker = DBInstance::GetBankerInfo($uid);
                    $this->banker['circle'] = 0;
                    Logic::SendAll('Msg_BJL_BankerInfo', $this->banker, $this->roomRule['rid']);
                } else {
                    foreach ($this->mRobot as $key => $value) {
                        if ($this->mUserList[$key]['gold'] >= BANKERGOLD) {
                            $this->RobotToBanker($key);
                            break;
                        }
                    }
                }
                $this->bankerturn = false;
            } else {
                $this->banker['gold'] = $gold;
            }
        }

        if (empty($this->banker['uid'])) {
            foreach ($this->mRobot as $key => $value) {
                if ($this->mUserList[$key]['gold'] >= BANKERGOLD) {
                    $this->RobotToBanker($key);
                    break;
                }
            }
        }

        $num = count($this->bankerList);
        if ($num < 5) {
            $tobanker = [0 => 100, 1 => 100, 2 => 100, 3 => 50, 4 => 20];
            $rand = rand(1, 100);
            if ($tobanker[$num] >= $rand) {
                foreach ($this->mRobot as $key => $value) {
                    if ($this->mUserList[$key]['gold'] >= BANKERGOLD && !in_array($key, $this->bankerList) && !in_array($key, $this->allRobotBanker) && $key != $this->banker['uid']) {
                        $this->allRobotBanker[rand(20, 160) + $this->zhen] = $key;
                        break;
                    }
                }
            }
        }
    }

    /**
     * 机器人上庄
     * @param array
     */
    private function RobotToBanker($banker)
    {
        Timer::del($this->bankerTimer);
        $this->Msg_BJL_ToBanker(
            [
                'event' => 'Msg_BJL_ToBanker',
                'uid' => $banker,
                'data' => ['stage' => 1],
            ]
        );

        $this->bankerTimer = Timer::add(0.1, function () {
            $this->zhen++;
            if (isset($this->allRobotBanker[$this->zhen])) {
                $uid = $this->allRobotBanker[$this->zhen];
                unset($this->allRobotBanker[$this->zhen]);
                if (!isset($this->mUserList[$uid]) || $this->banker['uid'] == $uid) {
                    return;
                }

                $stage = in_array($uid, $this->bankerList) ? 0 : 1;
                $this->Msg_BJL_ToBanker(
                    [
                        'event' => 'Msg_BJL_ToBanker',
                        'uid' => $uid,
                        'data' => ['stage' => $stage],
                    ]
                );
            }
        });
    }

    /**
     * 玩家退房
     * @param array
     */
    public function Msg_BJL_Out($message)
    {
        if (!empty($this->allBet[$message['uid']]) && $this->mGameStatus == STAGE_BET || ($message['uid'] == $this->banker['uid'] && $this->gameOut == false)) {
            Logic::SendError($message['uid'], 'Msg_BJL_Out', '游戏中，无法退出');
            return;
        }

        unset($this->mUserList[$message['uid']]);
        unset($this->mRobot[$message['uid']]);
        if (in_array($message['uid'], $this->bankerList)) {
            unset($this->bankerList[array_search($message['uid'], $this->bankerList)]);
            $this->bankerList = array_values($this->bankerList);
        }

        Logic::SendAll('Msg_BJL_Out', [
            'uid' => $message['uid'],
            'gold' => DBInstance::GetUserOneWord('gold', $message['uid'])
        ], $this->roomRule['rid']);
        Logic::QuitRoom([
            'rid' => $this->roomRule['rid'],
            'uid' => $message['uid']
        ]);
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
    public function DisRoom($message = [])
    {
        if (!empty($message)) {
            $this->gameOut = true;
        } else {
            Timer::del($this->mTimer);
            Timer::del($this->AITimer);
            Timer::del($this->bankerTimer);
            $olddata = [
                'rid' => $this->roomRule['rid'],
                'win' => [],
                'palyers' => $this->mUserList,
                'result' => [
                    'uid' => []
                ], //结算消息
                'gtype' => $this->roomRule['gtype'],
            ];
            foreach ($this->mUserList as $key => $value) {
                $this->Msg_BJL_Out(['uid' => $key]);
            }
            Logic::RoomOld($olddata);
        }
    }

    /**
     * 玩家进房
     * @param array
     */
    public function EnterRoom($player)
    {
        $uid = $player['uid'];
        $this->mUserList[$uid] = $player;
        $this->mUserList[$uid]['online'] = 0;
        Logic::SendRight($uid, 'Msg_BJL_RoomInfo', [
            'history' => $this->history,
            'allbet' => $this->getAllBet(),
            'bankerlist' => $this->bankerList,
            'allnum' => count($this->mUserList),
            'mybet' => $this->allBet[$uid] ?? array_fill(1, 8, 0),
            'time' => $this->mTimes == 0 ? 0 : BET_TIME - time() + $this->mTimes,
            'stage' => $this->mGameStatus,
            'banker' => $this->banker,
            'player' => [
                'uid' => $uid,
                'gold' => $player['gold'],
                'name' => $player['nickname'],
            ],
            'bankerturn' => $this->bankerturn
        ]);
        Logic::SendAll('Msg_BJL_PlayerAct', ['uid' => $uid], $this->roomRule['rid']);
        if ($player['client_id'] != '') {
            Gateway::joinGroup($player['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->mRobot[$uid] = rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time();
        }
        if ($this->mGameStatus == 0 && $player['gold'] >= BANKERGOLD) {
            $this->RobotToBanker($uid);
        }
    }


    /**
     * 玩家重连
     * @param $client_id
     * @param array
     */
    public function UserOnline($client_id, $uid)
    {
        if ($client_id != '') {
            Gateway::joinGroup($client_id, 'ROOM:' . $this->roomRule['rid']);
        }
        $this->mUserList[$uid]['online'] = 0;
        Logic::SendRight($uid, 'Msg_BJL_RoomInfo', [
            'history' => $this->history,
            'allbet' => $this->getAllBet(),
            'bankerlist' => $this->bankerList,
            'allnum' => count($this->mUserList),
            'mybet' => $this->allBet[$uid] ?? array_fill(1, 8, 0),
            'time' => BET_TIME - time() + $this->mTimes,
            'stage' => $this->mGameStatus,
            'banker' => $this->banker,
            'player' => [
                'uid' => $uid,
                'gold' => $this->mUserList[$uid]['gold'],
                'name' => $this->mUserList[$uid]['nickname'],
            ],
            'bankerturn' => $this->bankerturn
        ]);
    }

    /**
     * 玩家离线
     * @param array
     */
    public function UserOff($uid)
    {
        $this->mUserList[$uid]['online'] = time();
        if (in_array($uid, $this->bankerList)) {
            $this->Msg_BJL_ToBanker([
                'event' => 'Msg_BJL_ToBanker',
                'uid' => $uid,
                'data' => ['stage' => 0]
            ]);
        }
        Gateway::leaveGroup($this->mUserList[$uid]['client_id'], 'ROOM:' . $this->roomRule['rid']);
    }


    /**
     *  投注策略
     */
    private function RobotBet()
    {
        $rands = [1000 => 1, 10000 => 1, 100000 => 1, 1000000 => 1, 5000000 => 1, 10000000 => 1];
        $allbets = array_fill(1, 8, 0);
        foreach ($this->allBet as $key => $value) {
            if (!isset($this->mRobot[$this->banker['uid']]) && isset($this->mRobot[$key])) {
                //玩家坐庄，统计机器人下注区域
                foreach ($value as $key1 => $value1) {
                    $allbets[$key1] += $value1;
                }
            }
        }

        $cz = abs($allbets[BWIN] - $allbets[PWIN]) - $allbets[BCOUPLE] - $allbets[PCOUPLE] - $allbets[SPACE] - $allbets[BKING] - $allbets[PKING];
        $time = BET_TIME - (time() - $this->mTimes);
        if (!isset($this->mRobot[$this->banker['uid']]) && $cz > 0 && $time < 4) {
            return;
        }

        foreach ($this->mRobot as $key => $value) {
            if ($key == $this->banker['uid'] || $value - time() <= 5) {
                continue;
            }
            $num = array_rand($rands);
            if ($num >= 5000000 && rand(1, 100) < 15) {
                continue;
            }
            if (rand(1, 2) == 1 && $this->mUserList[$key] >= $num) {
                $msg = ['event' => 'Msg_BJL_ActBet', 'uid' => $key, 'data' => ['region' => 0, 'gold' => $num]];
                if ($num <= 100000 && rand(1, 100) <= 20 && $time > 6) {
                    if (rand(1, 100) <= 40) {
                        $msg['data']['region'] = rand(6, 7);
                    } elseif (rand(1, 100) <= 3) {
                        $msg['data']['region'] = rand(1, 2);
                    }

                    $this->Msg_BJL_ActBet($msg);
                }

                if (rand(1, 100) <= 95) {
                    $rand = rand(1, 100);
                    if ($rand <= 49) {
                        $msg['data']['region'] = 3;
                    } elseif ($rand <= 50) {
                        $msg['data']['region'] = 5;
                    } else {
                        $msg['data']['region'] = 4;
                    }

                    if (!isset($this->mRobot[$this->banker['uid']]) && $cz < 0) {
                        $msg['data']['region'] = $allbets[BWIN] > $allbets[PWIN] ? BWIN : PWIN;
                    }

                    $this->Msg_BJL_ActBet($msg);
                }
            }
        }
    }

    /**
     *  计算总投注
     */
    private function getAllBet()
    {
        $all = array_fill(1, 8, 0);
        foreach ($all as $key => $value) {
            $all[$key] = array_sum(array_column($this->allBet, $key));
        }
        return $all;
    }


    /**
     *  计算庄家各个区域盈亏
     */
    private function getBankerArea()
    {
        $region = DBInstance::GetDuoRenControl($this->roomRule['gtype'], $this->roomRule['level']);
        $rand = DBInstance::GetControlRand($this->roomRule['gtype']);
        $res = $allbets = array_fill(1, 8, 0);

        if (isset($res[$region])) {
            $res[$region] = 1;
            return $res;
        }

        foreach ($this->allBet as $key => $value) {
            if (isset($this->mRobot[$this->banker['uid']]) && !isset($this->mRobot[$key])) {
                //机器人坐庄，统计玩家下注区域
                foreach ($value as $key1 => $value1) {
                    $allbets[$key1] += $value1;
                }
            } elseif (!isset($this->mRobot[$this->banker['uid']]) && isset($this->mRobot[$key])) {
                //玩家坐庄，统计机器人下注区域
                foreach ($value as $key1 => $value1) {
                    $allbets[$key1] += $value1;
                }
            }
        }

        if (rand(1, 100) <= $rand && array_sum($allbets) > 0) {
            //计算各个区域赔付额
            $lose = [];
            foreach ($allbets as $key => $value) {
                $lose[$key] = $value * DOUBLE[$key];
            }

            //计算庄闲开奖区域
            if (isset($this->mRobot[$this->banker['uid']])) {
                //计算玩家投注区域赔付
                if ($allbets[BWIN] == $allbets[PWIN]) {
                    $index = rand(BWIN, PWIN);
                } else {
                    $index = $allbets[BWIN] > $allbets[PWIN] ? PWIN : BWIN;
                }
                $res[$index] = 1;
                $wins = array_sum($allbets) - $lose[$index];
                foreach ($lose as $key => $value) {
                    if ($key != BWIN && $key != PWIN && $key != SPACE && ($value == 0 || $value < $wins)) {
                        $res[$key] = 1;
                        $wins -= $value;
                    }
                }
            } else {
                //计算机器人投注赔付
                if ($allbets[BWIN] == $allbets[PWIN]) {
                    $index = rand(BWIN, PWIN);
                } else {
                    $index = $allbets[BWIN] > $allbets[PWIN] ? BWIN : PWIN;
                }
                $res[$index] = 1;
                $sum = array_sum($allbets) - 2 * $allbets[$index];
                if ($sum > 0) {
                    if ($index == BWIN) {
                        $res[BKING] = 1;
                        $sum -= $allbets[BKING] * 3;
                    } else {
                        $res[PWIN] = 1;
                        $sum -= $allbets[PWIN] * 3;
                    }
                }

                if ($sum > 0) {
                    if ($allbets[BCOUPLE] > $res[PCOUPLE]) {
                        $res[BCOUPLE] = 1;
                    } else {
                        $res[PCOUPLE] = 1;
                    }
                }
            }
        } else {
            $res = [];
        }
        return $res;
    }
}
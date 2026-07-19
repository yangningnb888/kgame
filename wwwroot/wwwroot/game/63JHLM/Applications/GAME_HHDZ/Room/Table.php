<?php

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('STAGE_BET', 1);
define('STAGE_END', 2);

define('BET_TIME', 15);
define('END_TIME', 10);
define('SYS_TIME', 2);

define('BANKERGOLD', 3000000000);
define('BANKERCIRCLE', 10);

define('SETBANKER', false);

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
    private $banker = [   //当前庄家信息
        'nickname' => '',
        'gold' => 0,
        'circle' => 0,
        'uid' => 0,
    ];
    private $history = [];   //历史记录
    private $curUserList = [];   //当前玩家列表
    private $zhen = 0;  //
    private $userCircle = [];  //玩家局数信息
    private $bankerturn = false;
    private $rebate = 0;
    private $gameOut = false;

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->roomRule = $msg;
        $this->rebate = round(1 - $this->roomRule['rebate'] * 0.01, 2);

        unset($msg['players']);
    }

    /**
     * 下注阶段
     */
    private function Stage_Bet()
    {
        if ($this->gameOut) {
            $this->DisRoom();
            return;
        }

        foreach ($this->mUserList as $key => $value) {
            if ($value['online'] > 0 && time() - $value['online'] >= OFFTIMES && $this->banker['uid'] != $key) {
                $this->Msg_HHDZ_Out(['uid' => $key]);
            }
        }

        Timer::del($this->mTimer);
        $this->mGameStatus = STAGE_BET;
        $this->mTimes = time();

        if (SETBANKER) {
            if (empty($this->banker['uid'])) {
                $this->mGameStatus = 0;
                return;
            }
            $this->banker['circle']++;
        }

        $this->allBet = [];
        $this->playerBet = [];
        $this->GetUserList();
        Logic::SendAll('Msg_HHDZ_StageBet', ['time' => BET_TIME], $this->roomRule['rid']);
        Timer::del($this->AITimer);
        DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet, time() + BET_TIME);
        $this->AITimer = Timer::add(SYS_TIME, function () {
            if ($this->mGameStatus == STAGE_BET) {
                DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet);
                //机器人投注逻辑
                $this->RobotBet();
                foreach ($this->mRobot as $key => $value) {
                    if ($value <= time() && empty($this->allBet[$key]) && $this->banker['uid'] != $key && !in_array($key, $this->bankerList)) {
                        $this->Msg_HHDZ_Out(['uid' => $key]);
                    }
                }

                if ($this->zhen % 10 == 0) {
                    $data = $this->getAllBet();
                    Logic::SendAll('Msg_HHDZ_SysActBet', $data, $this->roomRule['rid']);
                }
            }
        });

        $this->mTimer = Timer::add(BET_TIME, function () {
            $this->Stage_End();
        }, [], false);
    }

    /**
     *  结算阶段
     */
    private function Stage_End()
    {
        $uids = array_column($this->curUserList, 'uid');
        Timer::del($this->AITimer);
        Timer::del($this->mTimer);
        $this->mTimes = time();
        $this->mGameStatus = STAGE_END;
        //最后同步投注信息
        $data = $this->getAllBet();
        Logic::SendAll('Msg_HHDZ_SysActBet', ['bets' => $data], $this->roomRule['rid']);
        $res = $this->getBankerArea();
        $cards = Algorithm::GetCards($res);
        $wins = Algorithm::GetWin($cards);
        $this->history[] = $wins;
        if (count($this->history) > 40) {
            array_shift($this->history);
        }
        $gamepoints = $winpoints = $upgold = $allwin = [];
        $alluser = [];
        $data = [
            'result' => $wins,
            'cards' => $cards,
            'userwin' => [],
            'time' => END_TIME,
            'listbet' => []
        ];

        if (SETBANKER) {
            $allwin[$this->banker['uid']] = $gamepoints[$this->banker['uid']] = $winpoints[$this->banker['uid']] = $allwin[$this->banker['uid']] = $upgold[$this->banker['uid']] = 0;
        }

        $px = $wins['result'] % 10;
        $region = intval($wins['result'] / 10);
        foreach ($this->mUserList as $key => $value) {
            if (isset($this->allBet[$key])) {
                $allwin[$key] = 0;   //记录实际盈亏
                $upgold[$key] = 0;   //玩家金币变动
                $winpoints[$key] = 0;  //只记录收益
                $gamepoints[$key] = 0;  //记录未抽水收益
                foreach ($this->allBet[$key] as $key1 => $value1) {
                    if ($key1 == 3) {
                        //幸运一击
                        if ($px != 0) {
                            $_win = DOUBLE[$px] * $value1;
                            $winpoints[$key] += round($_win * $this->rebate);
                            $gamepoints[$key] += $_win + $value1;
                            $upgold[$key] += round($_win * $this->rebate + $value1);
                            if (SETBANKER) {
                                $upgold[$this->banker['uid']] -= $_win;
                                $gamepoints[$this->banker['uid']] -= $_win;
                            }
                        } else {
                            if (SETBANKER) {
                                $allwin[$this->banker['uid']] += $value1;
                                $winpoints[$this->banker['uid']] += $value1;
                                $upgold[$this->banker['uid']] += $value1;
                                $gamepoints[$this->banker['uid']] += $value1;
                            }
                        }
                    } else {
                        if ($key1 == $region) {
                            $_win = DOUBLE[$region] * $value1;
                            $winpoints[$key] += round($_win * $this->rebate);
                            $gamepoints[$key] += $_win + $value1;
                            $upgold[$key] += round($_win * $this->rebate + $value1);
                            if (SETBANKER) {
                                $upgold[$this->banker['uid']] -= DOUBLE[$region] * $value1;
                                $gamepoints[$this->banker['uid']] -= DOUBLE[$region] * $value1;
                            }
                        } else {
                            if (SETBANKER) {
                                $allwin[$this->banker['uid']] += $value1;
                                $winpoints[$this->banker['uid']] += $value1;
                                $upgold[$this->banker['uid']] += $value1;
                                $gamepoints[$this->banker['uid']] += $value1;
                            }
                        }
                    }
                }

                $gamepoints[$key] -= array_sum($this->allBet[$key]);
                $allwin[$key] = $upgold[$key] - array_sum($this->allBet[$key]);
                if (!empty($value['client_id'])) {
                    DBInstance::IncrementUserGet($key, $allwin[$key]);
                }
                DBInstance::IncrementGolds('gold', $key, $upgold[$key]);
                $this->mUserList[$key]['gold'] += $upgold[$key];

                if (in_array($key, $uids)) {
                    foreach ($this->curUserList as $key1 => $value1) {
                        if ($value1['uid'] == $key) {
                            $this->curUserList[$key1]['gold'] += $upgold[$key];
                        }
                    }
                }

                if ($allwin[$key] > 0) {
                    $this->mUserList[$key]['wincircle']++;
                }
                $this->mUserList[$key]['allcircle']++;
                $this->mUserList[$key]['rate'] = intval($this->mUserList[$key]['wincircle'] / $this->mUserList[$key]['allcircle'] * 100);
                if (count($this->userCircle[$key]) >= 20) {
                    array_shift($this->userCircle[$key]);
                }
                $this->userCircle[$key][] = [
                    'bet' => array_sum($this->allBet[$key]),
                    'win' => $allwin[$key] > 0 ? 1 : 0,
                ];
            }
        }

        //列表玩家收益
        foreach ($this->curUserList as $key => $value) {
            $alluser[$value['uid']] = 0;
            if (isset($allwin[$value['uid']])) {
                $alluser[$value['uid']] = $allwin[$value['uid']];
            }
            $data['listbet'][$value['uid']] = isset($this->allBet[$value['uid']]) ? array_sum($this->allBet[$value['uid']]) : 0;
        }

        if (SETBANKER) {
            $alluser[$this->banker['uid']] = $allwin[$this->banker['uid']];
            DBInstance::IncrementGolds('gold', $this->banker['uid'], $upgold[$this->banker['uid']]);
            $this->mUserList[$this->banker['uid']]['gold'] += $upgold[$this->banker['uid']];
        }
        $data['userwin'] = $alluser;
        arsort($allwin);
        foreach ($allwin as $key => $value) {
            Logic::HorseLamp($key, $value, 0);
            break;
        }

        //增加玩家赢分榜
        foreach ($winpoints as $key => $value) {
            DBInstance::IncrementWinPoint($key, $value);
        }
        $gameloss = 0;
        foreach ($this->mUserList as $key => $value) {
            //更新游戏盈亏
            if (empty($this->mRobot[$key]) && !empty($gamepoints[$key])) {
                $gameloss += $gamepoints[$key];
            }
            $send = $data;
            $send['userwin'][$key] = $allwin[$key] ?? 0;
            Logic::SendRight($key, 'Msg_HHDZ_StageEnd', $send);
        }
        Logic::InsertProfit($this->roomRule['level'], $gameloss);
        $this->mTimer = Timer::add(END_TIME, function () {
            $this->NextBanker();
            $this->Stage_Bet();
        }, [], false);
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_HHDZ_ActBet':
                $this->Msg_HHDZ_ActBet($message);
                break;
            case 'Msg_HHDZ_ToBanker':
                $this->Msg_HHDZ_ToBanker($message);
                break;
            case 'Msg_HHDZ_GetUserList':
                $this->Msg_HHDZ_GetUserList($message);
                break;
            case 'Msg_HHDZ_Out':
                $this->Msg_HHDZ_Out($message);
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
    private function Msg_HHDZ_ActBet($message)
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

        if ($message['data']['region'] <= 0 || $message['data']['region'] > 3) {
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

        $quote = $this->GetOdds($message['data']['region']);
        if ($message['data']['gold'] > $quote) {
            Logic::SendError($uid, $message['event'], '庄家赔付额度不足，无法下注');
            return;
        }

        if (empty($this->allBet[$uid])) {
            $this->allBet[$uid] = [1 => 0, 2 => 0, 3 => 0];
            if (!empty($this->mUserList[$uid]['client_id'])) {
                $this->playerBet[$uid] = [1 => 0, 2 => 0, 3 => 0];
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
        $list = array_column($this->curUserList, 'uid');
        if (in_array($uid, $list)) {
            foreach ($this->curUserList as $key => $value) {
                if ($value['uid'] == $uid) {
                    $this->curUserList[$key]['gold'] -= $message['data']['gold'];
                }
            }
            Logic::SendAll('Msg_HHDZ_ActBet', $data, $this->roomRule['rid']);
        } else {
            Logic::SendRight($uid, 'Msg_HHDZ_ActBet', $data);
        }
    }

    /**
     * 玩家上庄
     * @param array
     */
    private function Msg_HHDZ_ToBanker($message = [])
    {
        if (!SETBANKER) {
            Logic::SendError($message['uid'], $message['event'], '消息错误');
            return;
        }


        if (isset($message['data']['stage'])) {
            $uid = $message['uid'];
            if ($message['data']['stage'] == 1) {
                if ($this->mUserList[$uid]['gold'] < BANKERGOLD || in_array($uid, $this->bankerList) || $this->banker['uid'] == $uid) {
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
        Logic::SendAll('Msg_HHDZ_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
        if ($this->mGameStatus == 0 && count($this->mUserList) > 6) {
            $this->NextBanker();
            $this->Stage_Bet();
        }
    }

    /**
     * 获取玩家信息
     * @param array
     * @return array
     */
    private function Msg_HHDZ_GetUserList($message)
    {
        $data = [];
        foreach ($this->mUserList as $key => $value) {
            $data[$key] = [
                'headimgurl' => $value['headimgurl'],
                'nickname' => $value['nickname'],
                'gold' => $value['gold'],
                'allbet' => 0,
                'wincircle' => 0,
            ];

            foreach ($this->userCircle[$key] as $key1 => $value1) {
                $data[$key]['allbet'] += $value1['bet'];
                $data[$key]['wincircle'] += $value1['win'];
            }
        }
        Logic::SendRight($message['uid'], 'Msg_HHDZ_GetUserList', $data);
    }

    /**
     * 限制押注配付额度
     * @param int
     * @param int
     * @return array
     */
    private function GetOdds($region)
    {
        $total = SETBANKER ? $this->banker['gold'] : BANKERGOLD;
        $all = [];
        $all[BLACK] = array_sum(array_column($this->allBet, BLACK)) * DOUBLE[BLACK];
        $all[RED] = array_sum(array_column($this->allBet, RED)) * DOUBLE[RED];
        $max = max([$all[BLACK], $all[RED]]);
        $min = min([$all[BLACK], $all[RED]]);
        $pf = $region < 3 ? 1 : 10;
        $Quota = intval(($total - $max + $min - array_sum(array_column($this->allBet, LUCK)) * 10) / $pf);
        return $Quota;
    }


    /**
     * 切换庄家
     * @param array
     */
    private function NextBanker()
    {
        if (!SETBANKER || empty($this->bankerList)) {
            if (SETBANKER && empty($this->bankerList)) {
                $this->banker = [
                    'nickname' => '',
                    'gold' => 0,
                    'circle' => 0,
                    'uid' => 0,
                ];
                $this->mGameStatus = 0;

                foreach ($this->mRobot as $key => $value) {
                    if ($this->mUserList[$key]['gold'] >= BANKERGOLD) {
                        $this->RobotToBanker($key);
                        break;
                    }
                }
            }
            return;
        }

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
            Logic::SendAll('Msg_HHDZ_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
        }

        if ($player > 0) {
            foreach ($this->bankerList as $key => $value) {
                if (!empty($this->mRobot[$value]) && rand(1, 2) == 1) {
                    $this->allRobotBanker[rand(20, 160) + $this->zhen] = $value;
                    break;
                }
            }
        }

        if (!empty($this->banker['uid'])) {
            $gold = DBInstance::GetUserOneWord('gold', $this->banker['uid']);
            if ($gold < BANKERGOLD || $this->banker['circle'] >= BANKERCIRCLE || $this->bankerturn) {
                $uid = array_shift($this->bankerList);
                $this->banker = DBInstance::GetBankerInfo($uid);
                $this->banker['circle'] = 0;
                Logic::SendAll('Msg_FQZS_BankerInfo', $this->banker, $this->roomRule['rid']);
                $this->bankerturn = false;
            } else {
                $this->banker['gold'] = $gold;
            }
        } elseif (!empty($this->bankerList)) {
            $uid = array_shift($this->bankerList);
            $this->banker = DBInstance::GetBankerInfo($uid);
            $this->banker['circle'] = 0;
            Logic::SendAll('Msg_FQZS_BankerInfo', $this->banker, $this->roomRule['rid']);
        }


        $num = count($this->bankerList);
        if ($num < 5) {
            $tobanker = [0 => 100, 1 => 100, 2 => 100, 3 => 50, 4 => 20];
            $rand = rand(1, 100);
            if ($tobanker[$num] >= $rand) {
                foreach ($this->mRobot as $key => $value) {
                    if ($this->mUserList[$key]['gold'] >= BANKERGOLD && !in_array($key, $this->bankerList) && !in_array($key, $this->allRobotBanker) && $key != $this->banker['uid']) {
                        $this->allRobotBanker[rand(20, 300) + $this->zhen] = $key;
                        break;
                    }
                }
            }
        }
    }

    /**
     * 当前展示玩家
     */
    private function GetUserList()
    {
        $list = $this->mUserList;
        unset($list[$this->banker['uid']]);
        $this->curUserList = [];
        array_multisort(array_column($list, 'gold'), SORT_DESC, $list);
        $i = 0;
        foreach ($list as $key => $value) {
            $i++;
            if ($i > 5) {
                break;
            }
            $this->curUserList[] = [
                'uid' => $value['uid'],
                'nickname' => $value['nickname'],
                'gold' => $value['gold'],
                'headimgurl' => $value['headimgurl'],
            ];
        }

        array_multisort(array_column($list, 'rate'), SORT_DESC, $list);
        $get = array_shift($list);
        $this->curUserList[] = [
            'uid' => $get['uid'],
            'nickname' => $get['nickname'],
            'gold' => $get['gold'],
            'headimgurl' => $get['headimgurl'],
        ];

        Logic::SendAll('Msg_HHDZ_ListInfo', $this->curUserList, $this->roomRule['rid']);
    }

    /**
     * 机器人上庄
     * @param array
     */
    private function RobotToBanker($banker)
    {
        Timer::del($this->bankerTimer);
        $this->Msg_HHDZ_ToBanker(
            [
                'event' => 'Msg_HHDZ_ToBanker',
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
                $this->Msg_HHDZ_ToBanker(
                    [
                        'event' => 'Msg_HHDZ_ToBanker',
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
    public function Msg_HHDZ_Out($message)
    {
        if ((!empty($this->allBet[$message['uid']]) && $this->mGameStatus == STAGE_BET) || ($message['uid'] == $this->banker['uid'] && $this->gameOut == false)) {
            Logic::SendError($message['uid'], 'Msg_HHDZ_Out', '游戏中，无法退出');
            return;
        }

        unset($this->mUserList[$message['uid']]);
        unset($this->mRobot[$message['uid']]);
        if (in_array($message['uid'], $this->bankerList)) {
            unset($this->bankerList[array_search($message['uid'], $this->bankerList)]);
            $this->bankerList = array_values($this->bankerList);
        }

        foreach ($this->curUserList as $key => $value) {
            if ($value['uid'] == $message['uid']) {
                $this->GetUserList();
                break;
            }
        }

        Logic::SendAll('Msg_HHDZ_Out', [
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
                $this->Msg_HHDZ_Out(['uid' => $key]);
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
        $this->mUserList[$uid]['rate'] = 0;
        $this->mUserList[$uid]['allcircle'] = 0;
        $this->mUserList[$uid]['wincircle'] = 0;
        $this->userCircle[$uid] = [];
        $time = $this->mGameStatus == STAGE_BET ? BET_TIME : END_TIME;
        $uids = array_keys($this->curUserList);
        $ssz = empty($uids) ? 0 : end($uids);
        Logic::SendRight($uid, 'Msg_HHDZ_RoomInfo', [
            'history' => $this->history,
            'allbet' => $this->getAllBet(),
            'bankerlist' => $this->bankerList,
            'allnum' => count($this->mUserList),
            'mybet' => $this->allBet[$uid] ?? [],
            'sysbet' => empty($this->allBet[$ssz]) ? [] : $this->allBet[$ssz],
            'time' => $this->mTimes == 0 ? 0 : $time - time() + $this->mTimes,
            'stage' => $this->mGameStatus,
            'banker' => $this->banker,
            'player' => [
                'uid' => $uid,
                'gold' => $player['gold'],
                'name' => $player['nickname'],
            ],
            'playerlist' => $this->curUserList,
            'bankerturn' => $this->bankerturn
        ]);
        Logic::SendAll('Msg_HHDZ_PlayerAct', ['uid' => $uid], $this->roomRule['rid']);
        if ($player['client_id'] != '') {
            Gateway::joinGroup($player['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->mRobot[$uid] = rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time();
        }


        if (SETBANKER == false && count($this->mUserList) > 5 && $this->mGameStatus == 0) {
            $this->Stage_Bet();
        }

        if ($this->mGameStatus == 0 && $player['gold'] >= BANKERGOLD && SETBANKER) {
            $this->RobotToBanker($uid);
        }
        if ($this->mGameStatus == 0 && count($this->mUserList) > 6 && (!empty($this->bankerList)) && SETBANKER) {
            $this->NextBanker();
            $this->Stage_Bet();
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
        $time = $this->mGameStatus == STAGE_BET ? BET_TIME : END_TIME;
        $uids = array_keys($this->curUserList);
        $ssz = empty($uids) ? 0 : end($uids);
        Logic::SendRight($uid, 'Msg_HHDZ_RoomInfo', [
            'history' => $this->history,
            'allbet' => $this->getAllBet(),
            'bankerlist' => $this->bankerList,
            'allnum' => count($this->mUserList),
            'mybet' => $this->allBet[$uid] ?? [],
            'sysbet' => empty($this->allBet[$ssz]) ? [] : $this->allBet[$ssz],
            'time' => $time - time() + $this->mTimes,
            'stage' => $this->mGameStatus,
            'banker' => $this->banker,
            'player' => [
                'uid' => $uid,
                'gold' => $this->mUserList[$uid]['gold'],
                'name' => $this->mUserList[$uid]['nickname'],
            ],
            'playerlist' => $this->curUserList,
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
            $this->Msg_HHDZ_ToBanker([
                'event' => 'Msg_HHDZ_ToBanker',
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
        $uids = array_column($this->curUserList, 'uid');
        $ssz = end($uids);
        foreach ($this->mRobot as $key => $value) {
            if ($key == $this->banker['uid'] || $value - time() <= 5 || rand(1, 10) < 3) {
                continue;
            }
            $num = array_rand($rands);
            if (rand(1, 100) <= 80 && $this->mUserList[$key]['gold'] >= $num) {
                $msg = ['event' => 'Msg_HHDZ_ActBet', 'uid' => $key, 'data' => ['region' => 0, 'gold' => $num]];
                if (rand(1, 100) <= 95) {
                    $rand = rand(1, 100);
                    if ($rand <= 50) {
                        $msg['data']['region'] = 1;
                    } else {
                        $msg['data']['region'] = 2;
                    }

                    if ($ssz != $key || (empty($this->allBet[$key][1]) && $msg['data']['region'] == 2) || (empty($this->allBet[$key][2]) && $msg['data']['region'] == 1)) {
                        $curbet = $this->allBet[$key][$msg['data']['region']] ?? 0;
                        if ($curbet + $num <= $this->roomRule['controls']['maxbet']) {
                            $this->Msg_HHDZ_ActBet($msg);
                        }
                    }
                }

                if (rand(1, 100) <= 10) {
                    $msg['data']['region'] = 3;
                    $curbet = $this->allBet[$key][$msg['data']['region']] ?? 0;
                    if ($curbet + $num <= $this->roomRule['controls']['maxbet']) {
                        $this->Msg_HHDZ_ActBet($msg);
                    }
                }
            }

            if ($this->mUserList[$key]['gold'] < 10000 && rand(1, 100) <= 20) {
                $this->mRobot[$key] = time();
            }
        }
    }

    /**
     *  计算总投注
     */
    private function getAllBet()
    {
        $all = array_fill(1, 3, 0);
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
        $allbets = [1 => 0, 2 => 0, 3 => 0];
        $rand = DBInstance::GetControlRand($this->roomRule['gtype']);
        $px = 0;
        if (rand(1, 100) <= $rand) {
            foreach ($this->allBet as $key => $value) {
                if ((isset($this->mRobot[$this->banker['uid']]) || SETBANKER == false) && !isset($this->mRobot[$key])) {
                    //机器人坐庄，统计玩家下注区域
                    foreach ($value as $key1 => $value1) {
                        $allbets[$key1] += $value1;
                    }
                } elseif (SETBANKER && !isset($this->mRobot[$this->banker['uid']]) && isset($this->mRobot[$key])) {
                    //玩家坐庄，统计机器人下注区域
                    foreach ($value as $key1 => $value1) {
                        $allbets[$key1] += $value1;
                    }
                }
            }

            $allwin = array_sum($allbets);
            if (array_sum($allbets) == 0) {
                return $px;
            }

            if (isset($this->mRobot[$this->banker['uid']]) || SETBANKER == false) {
                if ($allbets[BLACK] > $allbets[RED]) {
                    $px = RED;
                    $allwin -= $allbets[RED] * (DOUBLE[RED] + 1);
                } else {
                    $px = BLACK;
                    $allwin -= $allbets[BLACK] * (DOUBLE[BLACK] + 1);
                }

                if ($allbets[BLACK] == $allbets[RED]) {
                    $px = rand(BLACK, RED);
                }

                $px *= 10;
                for ($i = 4; $i < 10; $i++) {
                    if ($allwin - $allbets[LUCK] * (DOUBLE[$i] + 1) <= 0) {
                        break;
                    }
                }
                $px += $i > 4 ? $i - 1 : 0;
            } else {
                $px = $allbets[BLACK] > $allbets[RED] ? BLACK : RED;
                if ($allbets[BLACK] == $allbets[RED]) {
                    $px = rand(BLACK, RED);
                }
                $px *= 10;
                $px += 9;
            }
        }

        $region = DBInstance::GetDuoRenControl($this->roomRule['gtype'], $this->roomRule['level']);
        if ($region == BLACK || $region == RED) {
            $px = $region * 10;
        }

        return $px;
    }
}
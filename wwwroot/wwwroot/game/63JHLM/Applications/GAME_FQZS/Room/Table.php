<?php

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('STAGE_BET', 1);
define('STAGE_END', 2);

define('BET_TIME', 21);
define('END_TIME', 20);
define('SYS_TIME', 2);

define('BANKERGOLD', 3000000000);
define('BANKERCIRCLE', 10);

define('SETBANKER', false);

require_once __DIR__ . '/Algorithm.php';

class Table
{
    private $roomRule = [];  //房间规则
    private $mUserList = [];  //玩家信息
    private $curUserList = [];   //当前玩家列表
    private $mRobot = [];   //机器人列表
    private $mTimes = 0;    //定时器开启时间
    private $mTimer = 0;   //定时器id
    private $AITimer = 0;    //下注定时器
    private $bankerTimer = 0;  //上庄定时器
    private $allRobotBanker = [];  //上庄机器人列表
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
    private $zhen = 0;
    private $rebate = 0.95;
    private $userCircle = [];  //玩家局数信息
    private $bankerturn = false;
    private $outGame = false;

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->roomRule = $msg;
        $this->rebate = round(1 - $this->roomRule['rebate'] * 0.01, 2);
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
        if ($this->outGame) {
            $this->DisRoom();
            return;
        }
        foreach ($this->mUserList as $key => $value) {
            if ($value['online'] > 0 && time() - $value['online'] >= OFFTIMES && $this->banker['uid'] != $key) {
                $this->Msg_FQZS_Out(['uid' => $key]);
            }
        }

        Timer::del($this->mTimer);
        $this->mTimes = time();
        $this->allBet = [];
        $this->playerBet = [];
        $this->mGameStatus = STAGE_BET;

        if (SETBANKER) {
            $this->banker['circle']++;
        }

        $this->GetUserList();
        if (empty($this->banker['uid']) && SETBANKER) {
            $this->mGameStatus = 0;
            return;
        }

        Logic::SendAll('Msg_FQZS_StageBet', ['time' => BET_TIME], $this->roomRule['rid']);
        DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet, time() + BET_TIME);
        $this->AITimer = Timer::add(SYS_TIME, function () {
            if ($this->mGameStatus == STAGE_BET) {
                DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet);
                //机器人投注逻辑
                $this->RobotBet();
                foreach ($this->mRobot as $key => $value) {
                    if ($value <= time() && empty($this->allBet[$key]) && $this->banker['uid'] != $key && !in_array($key, $this->bankerList) && rand(1, 100) < 50) {
                        $this->Msg_FQZS_Out(['uid' => $key]);
                    }
                }

                //同步当前所有下注信息
                $data = $this->getAllBet();
                Logic::SendAll('Msg_FQZS_SysActBet', ['bets' => $data], $this->roomRule['rid']);
            }
        });

        $this->mTimer = Timer::add(END_TIME, function () {
            $this->Stage_End();
        }, [], false);
    }

    /**
     *  结算阶段
     */
    private function Stage_End()
    {
        Timer::del($this->AITimer);
        Timer::del($this->mTimer);
        $this->mTimes = time();
        $this->mGameStatus = STAGE_END;
        $uids = array_column($this->curUserList, 'uid');
        if (empty($this->banker['uid']) && SETBANKER) {
            $this->mGameStatus = 0;
            return;
        }

        //最后同步投注信息
        $data = $this->getAllBet();
        Logic::SendAll('Msg_FQZS_SysActBet', ['bets' => $data], $this->roomRule['rid']);
        $result = $this->getBankerArea();
        $this->history[] = $result;
        if (count($this->history) > 40) {
            array_shift($this->history);
        }

        $usergold = $userwin = $winpoints = $allwin = [];
        $gameloss = 0;
        $add = $result > 30 ? 1 : rand(1, 3);
        $data = [
            'result' => $result * 10 + $add,
            'bankerwin' => 0,
            'userwin' => [],
            'usergold' => [],
            'time' => END_TIME,
        ];

        if (SETBANKER) {
            $usergold[$this->banker['uid']] = $userwin[$this->banker['uid']] = $winpoints[$this->banker['uid']] = $allwin[$this->banker['uid']] = 0;
        }

        foreach ($this->mUserList as $key => $value) {
            if (isset($this->allBet[$key])) {
                if (SETBANKER) {
                    $winpoints[$this->banker['uid']] += array_sum($this->allBet[$key]);
                    $allwin[$this->banker['uid']] += array_sum($this->allBet[$key]);
                }

                $lx = intval($result / 10);
                $allwin[$key] = 0;
                $winpoints[$key] = 0;
                if (!empty($this->allBet[$key][$result])) {
                    $_win = $this->allBet[$key][$result] * ODDS[$result];
                    $allwin[$key] += $this->allBet[$key][$result] + round($_win * $this->rebate);
                    $winpoints[$key] += round($_win * $this->rebate);

                    if (SETBANKER) {
                        $allwin[$this->banker['uid']] -= $this->allBet[$key][$result] + $_win;
                        $winpoints[$this->banker['uid']] -= $this->allBet[$key][$result] + $_win;
                    }

                    if (empty($this->mRobot[$key]) && (!empty($this->mRobot[$this->banker['uid']]) || !SETBANKER)) {
                        $gameloss += round($_win * (1 - $this->rebate));
                    }
                }

                if (!empty($this->allBet[$key][$lx])) {
                    if ($lx < 3) {
                        $_win = $this->allBet[$key][$lx] * ODDS[$lx];
                        $allwin[$key] += round($_win * $this->rebate) + $this->allBet[$key][$lx];
                        $winpoints[$key] += round($_win * $this->rebate);

                        if (SETBANKER) {
                            $allwin[$this->banker['uid']] -= $this->allBet[$key][$lx] + $_win;
                            $winpoints[$this->banker['uid']] -= $this->allBet[$key][$lx] + $_win;
                        }
                    } else {
                        $_win = $this->allBet[$key][$lx] * ODDS[$result];
                        $allwin[$key] += $this->allBet[$key][$lx] + round($_win * $this->rebate);
                        $winpoints[$key] += round($_win * $this->rebate);
                        if (SETBANKER) {
                            $allwin[$this->banker['uid']] -= $this->allBet[$key][$lx] + $_win;
                            $winpoints[$this->banker['uid']] -= $this->allBet[$key][$lx] + $_win;
                        }
                    }

                    if (empty($this->mRobot[$key]) && (!empty($this->mRobot[$this->banker['uid']]) || !SETBANKER)) {
                        $gameloss += round($_win * (1 - $this->rebate));
                    }
                }

                $userwin[$key] = $allwin[$key] - array_sum($this->allBet[$key]);
            }
        }

        if (SETBANKER) {
            $userwin[$this->banker['uid']] = $allwin[$this->banker['uid']];
        }

        $alluser = [];
        //列表玩家收益
        foreach ($this->curUserList as $key => $value) {
            $alluser[$value['uid']] = 0;
            if (isset($userwin[$value['uid']])) {
                $alluser[$value['uid']] = $userwin[$value['uid']];
            }
        }

        if (SETBANKER) {
            $alluser[$this->banker['uid']] = $userwin[$this->banker['uid']];
        }
        $data['userwin'] = $alluser;
        arsort($userwin);
        $count = 0;
        foreach ($userwin as $key => $value) {
            $count++;
            if ($count <= 1 && $value > 50000000) {
                Logic::HorseLamp($key, $value, 0);
            }
            if ($this->mUserList[$key]['client_id'] != '') {
                $gameloss += $value;
            }
        }

        //增加玩家赢分榜
        foreach ($winpoints as $key => $value) {
            DBInstance::IncrementWinPoint($key, $value);
        }

        foreach ($allwin as $key => $value) {
            if (!empty($this->mUserList[$key]['client_id'])) {
                DBInstance::IncrementUserGet($key, $userwin[$key]);
            }
            DBInstance::IncrementGolds('gold', $key, $value);
            $this->mUserList[$key]['gold'] += $value;
            if (in_array($key, $uids)) {
                foreach ($this->curUserList as $key1 => $value1) {
                    if ($value1['uid'] == $key) {
                        $this->curUserList[$key1]['gold'] += $value;
                    }
                }
                $usergold[$key] = $this->mUserList[$key]['gold'];
            }

            if (count($this->userCircle[$key]) >= 20) {
                array_shift($this->userCircle[$key]);
            }
            $this->userCircle[$key][] = [
                'bet' => array_sum($this->allBet[$key]),
                'win' => $userwin[$key] > 0 ? 1 : 0,
            ];
        }

        $data['usergold'] = $usergold;
        if (SETBANKER) {
            $data['bankerwin'] = $allwin[$this->banker['uid']];
            if (!empty($this->mUserList[$this->banker['uid']]['client_id'])) {
                DBInstance::IncrementUserGet($this->banker['uid'], $allwin[$this->banker['uid']]);
            }
        }
        foreach ($this->mUserList as $key => $value) {
            if (isset($userwin[$key]) && $userwin[$key] > 0) {
                $this->mUserList[$key]['wincircle']++;
            }

            $this->mUserList[$key]['allcircle']++;
            $send = $data;
            $send['bet'] = isset($this->allBet[$key]) ? array_sum($this->allBet[$key]) : 0;
            $send['userwin'][$key] = $userwin[$key] ?? 0;
            $send['usergold'][$key] = $value['gold'];
            Logic::SendRight($key, 'Msg_FQZS_StageEnd', $send);
        }

        if (SETBANKER) {
            $this->banker['gold'] += $userwin[$this->banker['uid']];
        }

        Logic::InsertProfit($this->roomRule['level'], $gameloss);
        $this->mTimer = Timer::add(BET_TIME, function () {
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
            case 'Msg_FQZS_ActBet':
                $this->Msg_FQZS_ActBet($message);
                break;
            case 'Msg_FQZS_ToBanker':
                $this->Msg_FQZS_ToBanker($message);
                break;
            case 'Msg_FQZS_GetUserList':
                $this->Msg_FQZS_GetUserList($message);
                break;
            case 'Msg_FQZS_Out':
                $this->Msg_FQZS_Out($message);
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
    private function Msg_FQZS_ActBet($message)
    {
        $uid = $message['uid'];
        if ($uid == $this->banker['uid']) {
            Logic::SendError($uid, $message['event'], '庄家无法下注');
            return;
        }

        if ($this->mUserList[$uid]['gold'] < $message['data']['gold']) {
            Logic::SendError($uid, $message['event'], '玩家金币不足');
            return;
        }

        if (!isset(ODDS[$message['data']['region']]) || $message['data']['region'] > 30) {
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

        $ret = $this->GetOdds($message['data']['region']);
        if ($message['data']['gold'] > $ret) {
            Logic::SendError($uid, $message['event'], '庄家赔付额度不足，无法下注');
            return;
        }

        if (empty($this->allBet[$uid])) {
            $this->allBet[$uid] = $this->resetBet();
            if (!empty($this->mUserList[$uid]['client_id'])) {
                $this->playerBet[$uid] = $this->allBet[$uid];
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
        $uids = array_column($this->curUserList, 'uid');
        if (in_array($uid, $uids)) {
            foreach ($this->curUserList as $key => $value) {
                if ($value['uid'] == $uid) {
                    $this->curUserList[$key]['gold'] -= $message['data']['gold'];
                }
            }
            Logic::SendAll('Msg_FQZS_ActBet', $data, $this->roomRule['rid']);
        } else {
            Logic::SendRight($uid, 'Msg_FQZS_ActBet', $data);
        }
    }

    /**
     * 玩家上庄
     * @param array
     */
    private function Msg_FQZS_ToBanker($message = [])
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
        Logic::SendAll('Msg_FQZS_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
        if ($this->mGameStatus == 0) {
            $this->NextBanker();
            $this->Stage_Bet();
        }
    }

    /**
     * 获取玩家信息
     * @param array
     * @return array
     */
    private function Msg_FQZS_GetUserList($message)
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
        Logic::SendRight($message['uid'], 'Msg_FQZS_GetUserList', $data);
    }

    /**
     * 限制押注配付额度
     * @return int
     */
    private function GetOdds($region)
    {
        $total = SETBANKER ? $this->banker['gold'] : BANKERGOLD;
        $pf = [];
        foreach (ODDS as $key => $value) {
            $allbet[$key] = array_sum(array_column($this->allBet, $key));
            $pf[$key] = $allbet[$key] * (ODDS[$key] + 1);
        }

        $total += array_sum($allbet);
        if ($region > 30) {
            $total -= $allbet[$region] * (ODDS[$region] + 1);
        } elseif ($region < 10) {
            $_pf = [];
            foreach ($pf as $key => $value) {
                if ($value > 20) {
                    $_pf[$key] = $value;
                }
            }
            $total -= $allbet[$region] * (ODDS[$region] + 1) + empty($_pf) ? 0 : max($_pf);
        } else {
            $arae = intval($region / 10);
            $total -= $allbet[$region] * (ODDS[$region] + 1) + $pf[$arae];
        }

        return intval($total / ODDS[$region]);
    }


    /**
     * 切换庄家
     * @param array
     */
    private function NextBanker()
    {
        if (!SETBANKER) {
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
            Logic::SendAll('Msg_FQZS_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
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
                if (empty($this->bankerList)) {
                    $this->banker = [
                        'nickname' => '',
                        'gold' => 0,
                        'circle' => 0,
                        'uid' => 0,
                    ];
                    $this->mGameStatus = 0;
                } else {
                    $uid = array_shift($this->bankerList);
                    $this->banker = DBInstance::GetBankerInfo($uid);
                    $this->banker['circle'] = 0;
                    Logic::SendAll('Msg_FQZS_BankerInfo', $this->banker, $this->roomRule['rid']);
                }
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

        Logic::SendAll('Msg_FQZS_ListInfo', $this->curUserList, $this->roomRule['rid']);
    }

    /**
     * 机器人上庄
     * @param array
     */
    private function RobotToBanker($banker)
    {
        Timer::del($this->bankerTimer);
        $this->Msg_FQZS_ToBanker(
            [
                'event' => 'Msg_FQZS_ToBanker',
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
                $this->Msg_FQZS_ToBanker(
                    [
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
    public function Msg_FQZS_Out($message)
    {
        if ((!empty($this->allBet[$message['uid']]) && $this->mGameStatus == STAGE_BET) || ($message['uid'] == $this->banker['uid'] && $this->outGame == false)) {
            Logic::SendError($message['uid'], 'Msg_FQZS_Out', '游戏中，无法退出');
            return;
        }

        unset($this->mUserList[$message['uid']]);
        unset($this->mRobot[$message['uid']]);
        if (in_array($message['uid'], $this->bankerList)) {
            unset($this->bankerList[array_search($message['uid'], $this->bankerList)]);
            $this->bankerList = array_values($this->bankerList);
        }

        Logic::SendAll('Msg_FQZS_Out', [
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
            $this->outGame = true;
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
                $this->Msg_FQZS_Out(['uid' => $key]);
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
        Logic::SendRight($uid, 'Msg_FQZS_RoomInfo', [
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
        Logic::SendAll('Msg_FQZS_PlayerAct', ['uid' => $uid], $this->roomRule['rid']);
        if ($player['client_id'] != '') {
            Gateway::joinGroup($player['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->mRobot[$uid] = rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time();
        }

        if (!SETBANKER && count($this->mUserList) >= 5 && $this->mGameStatus == 0) {
            $this->Stage_Bet();
        }

        if ($this->mGameStatus == 0 && $player['gold'] >= BANKERGOLD && SETBANKER) {
            $this->RobotToBanker($uid);
        }
        if ($this->mGameStatus == 0 && count($this->mUserList) > 6 && (!empty($this->bankerList) || SETBANKER == false)) {
            $this->NextBanker();
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
        Logic::SendRight($uid, 'Msg_FQZS_RoomInfo', [
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
            $this->Msg_FQZS_ToBanker([
                'event' => 'Msg_FQZS_ToBanker',
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
        //按照概率随机投注
        $golds = [1000 => 1, 10000 => 1, 100000 => 1, 1000000 => 1, 5000000 => 1, 10000000 => 1];
        foreach ($this->mRobot as $key => $value) {
            if ($this->mUserList[$key]['gold'] < 10000) {
                $this->mRobot[$key] = time();
            }

            $rand = rand(1, array_sum(POSSIBLE));
            $sum = 0;
            foreach (BETPOSSIBLE as $key1 => $value1) {
                $sum += $value1;
                if ($rand <= $sum) {
                    $gold = array_rand($golds);
                    if ($this->mUserList[$key]['gold'] >= $gold) {
                        $this->Msg_FQZS_ActBet([
                            'event' => 'Msg_FQZS_ActBet',
                            'uid' => $key,
                            'data' => [
                                'region' => $key1,
                                'gold' => $gold
                            ],
                        ]);
                    }
                    break;
                }
            }
        }
    }

    /**
     *  计算总投注
     */
    private function getAllBet()
    {
        $all = [];
        foreach (ODDS as $key => $value) {
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
        if ($region > 3 && isset(POSSIBLE[$region])) {
            return $region;
        }

        $allbets = [1 => 0, 2 => 0, 3 => 0, 21 => 0, 22 => 0, 23 => 0, 24 => 0, 11 => 0, 12 => 0, 13 => 0, 14 => 0];
        $rand = DBInstance::GetControlRand($this->roomRule['gtype']);
        $flag = [];
        if (rand(1, 100) <= $rand) {
            foreach ($this->allBet as $key => $value) {
                if ((isset($this->mRobot[$this->banker['uid']]) || !SETBANKER) && !isset($this->mRobot[$key])) {
                    //机器人坐庄，统计玩家下注区域
                    foreach ($value as $key1 => $value1) {
                        $allbets[$key1] += $value1;
                    }
                } elseif (!isset($this->mRobot[$this->banker['uid']]) && SETBANKER && isset($this->mRobot[$key])) {
                    //玩家坐庄，统计机器人下注区域
                    foreach ($value as $key1 => $value1) {
                        $allbets[$key1] += $value1;
                    }
                }
            }

            $allwin = array_sum($allbets);
            foreach (ODDS as $key => $value) {
                if ($key > 10 && $key < 30) {
                    $win = $allwin - $allbets[intval($key / 10)] * 2;
                    $win -= $allbets[$key] * ($value + 1);
                    if ((isset($this->mRobot[$this->banker['uid']]) || !SETBANKER) && $win < 0) {
                        $flag[] = $key;
                    } elseif (!isset($this->mRobot[$this->banker['uid']]) && SETBANKER && $win > 0) {
                        $flag[] = $key;
                    }
                } elseif ($key == 3) {
                    $max = $allbets[3] * 25;
                    if ($allwin < $max && (isset($this->mRobot[$this->banker['uid']]) || !SETBANKER)) {
                        $flag[] = 31;
                    } elseif ($allwin > $max && !isset($this->mRobot[$this->banker['uid']]) && SETBANKER) {
                        $flag[] = 31;
                    }

                    $max = $allbets[3] * 51;
                    if ($max < $allwin && !isset($this->mRobot[$this->banker['uid']]) || !SETBANKER) {
                        $flag[] = 32;
                    } elseif ($allwin > $max && !isset($this->mRobot[$this->banker['uid']]) && SETBANKER) {
                        $flag[] = 32;
                    }
                }
            }
        }

        return Algorithm::GetResult($flag);
    }

    private function resetBet()
    {
        $data = [];
        foreach (BETPOSSIBLE as $key => $value) {
            $data[$key] = 0;
        }
        return $data;
    }
}

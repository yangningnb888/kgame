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
        if ($this->gameOut) {
            $this->DisRoom();
            return;
        }

        foreach ($this->mUserList as $key => $value) {
            if ($value['online'] > 0 &&  time() - $value['online'] >= OFFTIMES && $this->banker['uid'] != $key) {
                $this->Msg_BCBM_Out(['uid' => $key]);
            }
        }

        Timer::del($this->mTimer);
        $this->mTimes = time();
        $this->banker['circle']++;
        $this->allBet = [];
        $this->playerBet = [];
        $this->mGameStatus = STAGE_BET;
        if (empty($this->banker['uid'])) {
            $this->mGameStatus = 0;
            return;
        }

        Logic::SendAll('Msg_BCBM_StageBet', ['time' => BET_TIME], $this->roomRule['rid']);
        Timer::del($this->AITimer);
        DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet, time() + BET_TIME);
        $this->AITimer = Timer::add(SYS_TIME, function () {
            if ($this->mGameStatus == STAGE_BET) {
                DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet);
                foreach ($this->mRobot as $key => $value) {
                    if ($value <= time() && empty($this->allBet[$key]) && $this->banker['uid'] != $key && !in_array($key, $this->bankerList) && rand(1, 100) < 50) {
                        $this->Msg_BCBM_Out(['uid' => $key]);
                    }
                }
                //机器人投注逻辑
                $this->RobotBet();
                //同步当前所有下注信息
                $data = $this->getAllBet();
                Logic::SendAll('Msg_BCBM_SysActBet', ['bets' => $data], $this->roomRule['rid']);
            }
        });
        //初始化
        DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet, time() + BET_TIME);
        $this->mTimer = Timer::add(BET_TIME, function () {
            $this->Stage_End();
        }, [], false);
    }

    /**
     *  结算阶段
     */
    private function Stage_End()
    {
        Timer::del($this->mTimer);
        $this->mTimes = time();
        $this->mGameStatus = STAGE_END;
        if (empty($this->banker['uid'])) {
            $this->mGameStatus = 0;
            return;
        }

        //最后同步投注信息
        $data = $this->getAllBet();
        Logic::SendAll('Msg_BCBM_SysActBet', ['bets' => $data], $this->roomRule['rid']);
        $result = $this->getBankerArea();
        $this->history[] = $result;
        if (count($this->history) > 20) {
            array_shift($this->history);
        }

        $userall = $userwin = $winpoints = $allwin = [];
        $gameloss = 0;
        $data = [
            'result' => $result * 10 + rand(1, 4),
            'bankerwin' => 0,
            'win' => 0,
            'bigwin' => [],
            'gold' => 0,
            'time' => BET_TIME,
        ];

        $userall[$this->banker['uid']] = $userwin[$this->banker['uid']] = $winpoints[$this->banker['uid']] = $allwin[$this->banker['uid']] = 0;

        foreach ($this->mUserList as $key => $value) {
            if (isset($this->allBet[$key])) {
                $winpoints[$this->banker['uid']] += array_sum($this->allBet[$key]);
                $allwin[$this->banker['uid']] += array_sum($this->allBet[$key]);
                $allwin[$key] = 0;
                $winpoints[$key] = 0;
                $userall[$key] = 0;
                if (!empty($this->allBet[$key][$result])) {
                    $_gold = $this->allBet[$key][$result] * ODDS[$result];
                    $userall[$key] += $_gold + $this->allBet[$key][$result];
                    $allwin[$key] += $this->allBet[$key][$result] + $_gold * $this->rebate;
                    $winpoints[$key] += $_gold * $this->rebate;
                    $allwin[$this->banker['uid']] -= $this->allBet[$key][$result] + $_gold;
                    $winpoints[$this->banker['uid']] -= $this->allBet[$key][$result] + $_gold;
                }

                $userwin[$key] = $allwin[$key] - array_sum($this->allBet[$key]);
                $userall[$key] -= array_sum($this->allBet[$key]);
            }
        }

        $userall[$this->banker['uid']] = $allwin[$this->banker['uid']];
        $userwin[$this->banker['uid']] = $allwin[$this->banker['uid']];
        arsort($userwin);
        $count = 0;
        foreach ($userwin as $key => $value) {
            $count++;
            if ($value >= 48000000 && $count <= 1) {
                Logic::HorseLamp($key, $value, 0);
            }

            if ($key != $this->banker['uid'] && count($data['bigwin']) < 5 && $value > 0) {
                $data['bigwin'][$key] = [
                    'nickname' => $this->mUserList[$key]['nickname'],
                    'win' => $value,
                ];
            }
        }

        //增加玩家赢分榜
        foreach ($winpoints as $key => $value) {
            DBInstance::IncrementWinPoint($key, $value);
        }

        $data['bankerwin'] = $allwin[$this->banker['uid']];
        foreach ($this->mUserList as $key => $value) {
            if (isset($allwin[$key])) {
                if (empty($this->mRobot[$key])) {
                    $win = $key == $this->banker['uid'] ? $allwin : $userwin[$key];
                    DBInstance::IncrementUserGet($key, $win);
                }
                DBInstance::IncrementGolds('gold', $key, $allwin[$key]);
                $this->mUserList[$key]['gold'] += $allwin[$key];
            }
            $send = $data;
            $send['bet'] = isset($this->allBet[$key]) ? array_sum($this->allBet[$key]) : 0;

            if (isset($userwin[$key])) {
                if (!empty($value['client_id'])) {
                    $gameloss += $userall[$key];
                }
                $send['win'] = $userwin[$key];
            }

            $send['gold'] = $this->mUserList[$key]['gold'];
            Logic::SendRight($key, 'Msg_BCBM_StageEnd', $send);
        }

        $this->banker['gold'] += $userwin[$this->banker['uid']];
        Logic::InsertProfit($this->roomRule['level'], $gameloss);
        $this->allBet = [];
        $this->playerBet = [];
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
            case 'Msg_BCBM_ActBet':
                $this->Msg_BCBM_ActBet($message);
                break;
            case 'Msg_BCBM_ToBanker':
                $this->Msg_BCBM_ToBanker($message);
                break;
            case 'Msg_BCBM_GetUserList':
                $this->Msg_BCBM_GetUserList($message);
                break;
            case 'Msg_BCBM_Out':
                $this->Msg_BCBM_Out($message);
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
    private function Msg_BCBM_ActBet($message)
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

        if (!isset(ODDS[$message['data']['region']])) {
            Logic::SendError($uid, $message['event'], '参数错误');
            return;
        }

        if ($this->mGameStatus != STAGE_BET) {
            Logic::SendError($uid, $message['event'], '阶段错误');
            return;
        }

        $ret = $this->GetOdds($message['data']['region']);
        if ($message['data']['gold'] > $ret) {
            Logic::SendError($uid, $message['event'], '庄家赔付额度不足，无法下注');
            return;
        }

        $curgold = $this->allBet[$uid][$message['data']['region']] ?? 0;
        if ($curgold + $message['data']['gold'] > $this->roomRule['controls']['maxbet']) {
            Logic::SendError($uid, $message['event'], '下注金额过限');
            return;
        }

        if (empty($this->allBet[$uid])) {
            $this->allBet[$uid] = [];
            if (!empty($this->mUserList[$uid]['client_id'])) {
                $this->playerBet[$uid] = [];
            }
        }

        if (empty($this->allBet[$uid][$message['data']['region']])) {
            $this->allBet[$uid][$message['data']['region']] = 0;
            if (!empty($this->mUserList[$uid]['client_id'])) {
                $this->playerBet[$uid][$message['data']['region']] = 0;
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
        Logic::SendRight($uid, 'Msg_BCBM_ActBet', $data);
    }

    /**
     * 玩家上庄
     * @param array
     */
    private function Msg_BCBM_ToBanker($message = [])
    {
        if (isset($message['data']['stage'])) {
            $uid = $message['uid'];
            if ($message['data']['stage'] == 1) {
                if ($this->mUserList[$uid]['gold'] < BANKERGOLD || $this->banker['uid'] == $uid || in_array($uid, $this->bankerList)) {
                    Logic::SendError($uid, 'Msg_BCBM_ToBanker', '玩家无法上庄');
                    return;
                }
                $this->bankerList[] = $uid;
            } else {
                if (!in_array($uid, $this->bankerList) && $this->banker['uid'] != $uid) {
                    Logic::SendError($uid, 'Msg_BCBM_ToBanker', '玩家无法取消上庄');
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
                    Logic::SendError($value, 'Msg_BCBM_ToBanker', '当前金币不满足上庄条件');
                }
            }
        }

        $this->bankerList = array_values($this->bankerList);
        Logic::SendAll('Msg_BCBM_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
        if ($this->mGameStatus == 0) {
            $this->NextBanker();
            $this->Stage_Bet();
        }
    }

    /**
     * 获取庄家信息
     * @param array
     * @return array
     */
    private function Msg_BCBM_GetUserList($message)
    {
        $data = [];
        foreach ($this->mUserList as $key => $value) {
            $data[$key] = [
                'headimgurl' => $value['headimgurl'],
                'nickname' => $value['nickname'],
                'gold' => $value['gold'],
            ];
        }
        Logic::SendRight($message['uid'], 'Msg_BCBM_GetUserList', $data);
    }

    /**
     * 限制押注配付额度
     * @return int
     */
    private function GetOdds($region)
    {
        $allbet = [];
        foreach (ODDS as $key => $value) {
            $allbet[$key] = array_sum(array_column($this->allBet, $key));
        }

        $pf = $allbet[$region] * (ODDS[$region] + 1);
        $Quota = $this->banker['gold'] + array_sum($allbet) - $pf;
        return intval($Quota / ODDS[$region]);
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
            Logic::SendAll('Msg_BCBM_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
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
                    Logic::SendAll('Msg_BCBM_BankerInfo', $this->banker, $this->roomRule['rid']);
                }

                $this->bankerturn = false;
            } else {
                $this->banker['gold'] = $gold;
            }
        } elseif (!empty($this->bankerList)) {
            $uid = array_shift($this->bankerList);
            $this->banker = DBInstance::GetBankerInfo($uid);
            $this->banker['circle'] = 0;
            Logic::SendAll('Msg_BCBM_BankerInfo', $this->banker, $this->roomRule['rid']);
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
                        $this->allRobotBanker[rand(20, 300) + $this->zhen] = $key;
                        break;
                    }
                }
            }
        }
    }

    private function RobotToBanker($banker)
    {
        Timer::del($this->bankerTimer);
        $this->Msg_BCBM_ToBanker(
            [
                'event' => 'Msg_BCBM_ToBanker',
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
                $this->Msg_BCBM_ToBanker(
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
    public function Msg_BCBM_Out($message)
    {
        if ((!empty($this->allBet[$message['uid']]) && $this->mGameStatus == STAGE_BET) || ($message['uid'] == $this->banker['uid'] && $this->gameOut == false)) {
            Logic::SendError($message['uid'], 'Msg_BCBM_Out', '游戏中，无法退出');
            return;
        }

        unset($this->mUserList[$message['uid']]);
        unset($this->mRobot[$message['uid']]);
        if (in_array($message['uid'], $this->bankerList)) {
            unset($this->bankerList[array_search($message['uid'], $this->bankerList)]);
            $this->bankerList = array_values($this->bankerList);
        }

        Logic::SendAll('Msg_BCBM_Out', [
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
                $this->Msg_BCBM_Out(['uid' => $key]);
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
        $time = $this->mGameStatus == STAGE_BET ? BET_TIME : END_TIME;
        Logic::SendRight($uid, 'Msg_BCBM_RoomInfo', [
            'history' => $this->history,
            'allbet' => $this->getAllBet(),
            'bankerlist' => $this->bankerList,
            'allnum' => count($this->mUserList),
            'mybet' => $this->allBet[$uid] ?? [],
            'time' => $this->mTimes == 0 ? 0 : $time - time() + $this->mTimes,
            'stage' => $this->mGameStatus,
            'banker' => $this->banker,
            'player' => [
                'uid' => $uid,
                'gold' => $player['gold'],
                'name' => $player['nickname'],
            ],
            'bankerturn' => $this->bankerturn
        ]);
        Logic::SendAll('Msg_BCBM_PlayerAct', ['uid' => $uid], $this->roomRule['rid']);
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
        $time = $this->mGameStatus == STAGE_BET ? BET_TIME : END_TIME;
        Logic::SendRight($uid, 'Msg_BCBM_RoomInfo', [
            'history' => $this->history,
            'allbet' => $this->getAllBet(),
            'bankerlist' => $this->bankerList,
            'allnum' => count($this->mUserList),
            'mybet' => $this->allBet[$uid] ?? [],
            'time' => $time - time() + $this->mTimes,
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
            $this->Msg_BCBM_ToBanker([
                'event' => 'Msg_BCBM_ToBanker',
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
            $rand = rand(1, array_sum(POSSIBLE));
            $sum = 0;
            foreach (POSSIBLE as $key1 => $value1) {
                $sum += $value1;
                if ($rand <= $sum) {
                    $gold = array_rand($golds);
                    if ($gold >= 5000000 && rand(1, 5) <= 3) {
                        break;
                    }

                    if ($this->mUserList[$key]['gold'] >= $gold) {
                        $this->Msg_BCBM_ActBet([
                            'event' => 'Msg_BCBM_ActBet',
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
        $duoren = DBInstance::GetDuoRenControl($this->roomRule['gtype'], $this->roomRule['level']);
        if (isset(ODDS[$duoren])) {
            return $duoren;
        }

        $allbets = [11 => 0, 12 => 0, 13 => 0, 14 => 0, 21 => 0, 22 => 0, 23 => 0, 24 => 0];
        $rand = DBInstance::GetControlRand($this->roomRule['gtype']);
        $allwin = 0;
        $flag = [];
        if ($rand >= rand(1, 100)) {
            foreach ($this->allBet as $key => $value) {
                if (isset($this->mRobot[$this->banker['uid']]) && !isset($this->mRobot[$key])) {
                    $allwin += array_sum($value);
                    //机器人坐庄，统计玩家下注区域
                    foreach ($value as $key1 => $value1) {
                        $allbets[$key1] += $value1;
                    }
                } elseif (!isset($this->mRobot[$this->banker['uid']]) && isset($this->mRobot[$key])) {
                    $allwin += array_sum($value);
                    //玩家坐庄，统计机器人下注区域
                    foreach ($value as $key1 => $value1) {
                        $allbets[$key1] += $value1;
                    }
                }
            }

            if (array_sum($allbets) != 0) {
                foreach (ODDS as $key => $value) {
                    $_bet = $allbets[$key] ?? 0;
                    if ($allwin - ($_bet * ($value + 1)) < 0 && isset($this->mRobot[$this->banker['uid']])) {
                        $flag[$key] = $key;
                    } elseif ($allwin - ($_bet * ($value + 1)) > 0 && !isset($this->mRobot[$this->banker['uid']])) {
                        $flag[$key] = $key;
                    }
                }
            }
        }

        return Algorithm::GetResult($flag);
    }
}

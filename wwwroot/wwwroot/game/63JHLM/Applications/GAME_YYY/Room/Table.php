<?php

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('STAGE_BET', 1);
define('STAGE_END', 2);

define('BET_TIME', 21);
define('END_TIME', 16);
define('SYS_TIME', 2);

define('BANKERGOLD', 200000000);
define('BANKERCIRCLE', 10);

require_once __DIR__ . '/Algorithm.php';
const ZHEN = 0.02;

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
    private $points = [];    //当局骰子
    private $banker = [   //当前庄家信息
        'nickname' => '',
        'gold' => 0,
        'circle' => 0,
        'uid' => 0,
    ];
    private $history = [];   //历史记录
    private $zhen = 0;
    private $allCount = [];  //记录大小豹子
    private $allPoint = [];
    private $bankerturn = false;
    private $rebate = 0;
    private $outGame = false;
    private $controlPonins = [13 => [], 14 => [], 23 => [], 24 => []];

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->roomRule = $msg;
        $this->rebate = round(1 - $this->roomRule['rebate'] * 0.01, 2);
        for ($i = 1; $i < 7; $i++) {
            for ($j = 1; $j < 7; $j++) {
                for ($k = 1; $k < 7; $k++) {
                    $this->allPoint[] = [$i, $j, $k];
                }
            }
        }

        foreach ($this->allPoint as $key => $value) {
            if ($value[0] == $value[1] && $value[0] == $value[2]) {
                continue;
            }

            $sum = array_sum($value);
            if ($sum % 2 == 1 && $sum > 10) {
                $this->controlPonins[13][] = $value;
            } elseif ($sum % 2 == 1 && $sum <= 10) {
                $this->controlPonins[14][] = $value;
            } elseif ($sum % 2 == 0 && $sum > 10) {
                $this->controlPonins[23][] = $value;
            } else {
                $this->controlPonins[24][] = $value;
            }
        }

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

        if (empty($this->banker['uid'])) {
            $this->mGameStatus = 0;
            return;
        }

        foreach ($this->mUserList as $key => $value) {
            if ($value['online'] > 0 && time() - $value['online'] >= OFFTIMES && $this->banker['uid'] != $key) {
                $this->Msg_YYY_Out(['uid' => $key]);
            }
        }

        Timer::del($this->mTimer);
        $this->banker['circle']++;
        $this->mGameStatus = STAGE_BET;
        $this->mTimes = time();
        $this->allBet = [];
        $this->playerBet = [];
        if (empty($this->banker['uid'])) {
            $this->mGameStatus = 0;
            return;
        }

        Logic::SendAll('Msg_YYY_StageBet', ['time' => BET_TIME], $this->roomRule['rid']);
        Timer::del($this->AITimer);
        DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet, time() + BET_TIME);
        $this->AITimer = Timer::add(SYS_TIME, function () {
            if ($this->mGameStatus == STAGE_BET) {
                DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet);
                foreach ($this->mRobot as $key => $value) {
                    if ($value <= time() && empty($this->allBet[$key]) && $this->banker['uid'] != $key && !in_array($key, $this->bankerList)) {
                        $this->Msg_YYY_Out(['uid' => $key]);
                    }
                }
                //同步当前所有下注信息
                $data = $this->getAllBet();
                Logic::SendAll('Msg_YYY_SysActBet', $data, $this->roomRule['rid']);
                //机器人投注逻辑
                $this->RobotBet();
            }

            if (empty($this->bankerList)) {
                foreach ($this->mUserList as $key => $value) {
                    if ($value['gold'] >= BANKERGOLD && empty($value['client_id'])) {
                        $this->RobotToBanker($key);
                        break;
                    }
                }
            }
        });
        $this->mTimer = Timer::add(BET_TIME, function () {
            $this->Stage_End();
        });
    }

    /**
     *  结算阶段
     */
    private function Stage_End()
    {
        Timer::del($this->mTimer);
        Timer::del($this->AITimer);
        $this->mTimes = time();
        $this->mGameStatus = STAGE_END;
        if (empty($this->banker['uid'])) {
            $this->mGameStatus = 0;
            return;
        }

        //最后同步投注信息
        $data = $this->getAllBet();
        Logic::SendAll('Msg_YYY_SysActBet', $data, $this->roomRule['rid']);
        $this->points = $this->getBankerArea();
        if (empty($this->points)) {
            $this->points = Algorithm::GetPoint();
            DBInstance::UpdateGameStatus(5, 2);
        }
        $wins = Algorithm::GetResult($this->points);
        $this->history[] = [
            'points' => $this->points,
            'win' => $wins
        ];

        if (count($this->allCount) >= 40) {
            array_shift($this->allCount);
        }

        $this->allCount[] = $wins;

        if (count($this->history) > 6) {
            array_shift($this->history);
        }

        $userwin = $winpoints = $allwin = [];
        $gameloss = 0;
        $allwin[$this->banker['uid']] = 0;
        $data = [
            'result' => $wins,
            'points' => $this->points,
            'bankerwin' => 0,
            'win' => 0,
            'gold' => 0,
            'time' => END_TIME,
        ];

        $userwin[$this->banker['uid']] = $winpoints[$this->banker['uid']] = $allwin[$this->banker['uid']] = 0;

        foreach ($this->mUserList as $key => $value) {
            if (isset($this->allBet[$key])) {
                $allwin[$key] = 0;
                $winpoints[$key] = 0;
                $userwin[$key] = 0;
                foreach ($this->allBet[$key] as $key1 => $value1) {
                    if ($value1 <= 0) {
                        continue;
                    }

                    if (!empty($wins[$key1])) {
                        $odds = $key1 > 10 && $key1 < 20 ? $wins[$key1] : ODDS[$key1];
                        $_win = $value1 * $odds;
                        $userwin[$key] += $_win + $value1;
                        $allwin[$this->banker['uid']] -= $_win;
                        $allwin[$key] += round($_win * $this->rebate) + $value1;
                        $winpoints[$key] += round($_win * $this->rebate);
                    } else {
                        $allwin[$this->banker['uid']] += $value1;
                    }
                }

                if (!isset($this->mRobot[$key])) {
                    $gameloss += $userwin[$key] - array_sum($this->allBet[$key]);
                }

                DBInstance::IncrementGolds('gold', $key, $allwin[$key]);
                $this->mUserList[$key]['gold'] += $allwin[$key];
            }
        }

        DBInstance::IncrementGolds('gold', $this->banker['uid'], $allwin[$this->banker['uid']]);
        if (!isset($this->mRobot[$this->banker['uid']])) {
            $gameloss += $allwin[$this->banker['uid']];
        }
        $this->mUserList[$this->banker['uid']]['gold'] += $allwin[$this->banker['uid']];
        //增加玩家赢分榜
        foreach ($winpoints as $key => $value) {
            DBInstance::IncrementWinPoint($key, $value);
        }
        $data['bankerwin'] = $allwin[$this->banker['uid']];
        $horselamp = [];
        foreach ($this->mUserList as $key => $value) {
            $send = $data;
            $send['bet'] = isset($this->allBet[$key]) ? array_sum($this->allBet[$key]) : 0;
            $send['win'] = isset($this->allBet[$key]) ? $allwin[$key] - array_sum($this->allBet[$key]) : 0;
            $send['gold'] = $value['gold'];
            if ($send['win'] >= 30000000) {
                $horselamp[$key] = $send['win'];
            }
            if (!empty($value['client_id'])) {
                if ($key == $this->banker['uid']) {
                    DBInstance::IncrementUserGet($key, $allwin[$this->banker['uid']]);
                } elseif (!empty($send['win'])) {
                    DBInstance::IncrementUserGet($key, $send['win']);
                }
            }
            Logic::SendRight($key, 'Msg_YYY_StageEnd', $send);
        }

        arsort($horselamp);
        foreach ($horselamp as $key => $value) {
            Logic::HorseLamp($key, $value, 0);
            break;
        }
        Logic::InsertProfit($this->roomRule['level'], $gameloss);
        $this->mTimer = Timer::add(END_TIME - 1, function () {
            $this->NextBanker();
            Timer::add(1, function () {
                $this->mTimes = time();
                $this->mGameStatus = STAGE_BET;
                $this->Stage_Bet();
            }, [], false);
        }, [], false);
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_YYY_ActBet':
                $this->Msg_YYY_ActBet($message);
                break;
            case 'Msg_YYY_ToBanker':
                $this->Msg_YYY_ToBanker($message);
                break;
            case 'Msg_YYY_GetUserList':
                $this->Msg_YYY_GetUserList($message);
                break;
            case 'Msg_YYY_Out':
                $this->Msg_YYY_Out($message);
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
    private function Msg_YYY_ActBet($message)
    {
        $uid = $message['uid'];
        if ($uid == $this->banker['uid']) {
            Logic::SendError($uid, $message['event'], '庄家无法下注');
            return;
        }

        if (!is_array($message['data']['region']) || empty($message['data']['region'])) {
            Logic::SendError($uid, $message['event'], '参数错误');
            return;
        }

        if ($this->mUserList[$uid]['gold'] < array_sum($message['data']['region'])) {
            Logic::SendError($uid, $message['event'], '玩家金币不足');
            return;
        }

        if ($this->mGameStatus != STAGE_BET) {
            Logic::SendError($uid, $message['event'], '阶段错误');
            return;
        }

        foreach ($message['data']['region'] as $key => $value) {
            if (!is_int($value) || !isset(ODDS[$key])) {
                Logic::SendError($uid, $message['event'], '参数错误2');
                return;
            }


            $curgold = $this->allBet[$uid][$key] ?? 0;
            if ($curgold + $value > $this->roomRule['controls']['maxbet']) {
                Logic::SendError($uid, $message['event'], '下注金额过限');
                return;
            }

            $quote = $this->GetOdds();
            $quote = intval($quote / ODDS[$key]);
            if ($value > $quote) {
                Logic::SendError($uid, $message['event'], '庄家赔付额度不足，无法下注');
                return;
            }
        }

        $keys = array_keys($message['data']['region']);
        if (empty($this->allBet[$uid])) {
            $this->allBet[$uid] = [];
        }

        if (!empty($this->mUserList[$uid]['client_id']) && min($keys) <= 4 && empty($this->playerBet[$uid])) {
            $this->playerBet[$uid] = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        }

        foreach ($message['data']['region'] as $key => $value) {
            if (empty($this->allBet[$uid][$key])) {
                $this->allBet[$uid][$key] = 0;
            }
            $this->allBet[$uid][$key] += $value;
            if (!empty($this->mUserList[$uid]['client_id']) && $key > 0 && $key < 5) {
                $this->playerBet[$uid][$key] += $value;
            }
        }

        $this->mUserList[$uid]['gold'] -= array_sum($message['data']['region']);
        DBInstance::IncrementGolds('gold', $uid, -array_sum($message['data']['region']));
        $data = $message['data'];
        $data['uid'] = $uid;
        Logic::SendRight($uid, 'Msg_YYY_ActBet', $data);
    }

    /**
     * 玩家上庄
     * @param array
     */
    private function Msg_YYY_ToBanker($message = [])
    {
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
        Logic::SendAll('Msg_YYY_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
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
    private function Msg_YYY_GetUserList($message)
    {
        $data = [];
        foreach ($this->mUserList as $key => $value) {
            $data[$key] = [
                'headimgurl' => $value['headimgurl'],
                'nickname' => $value['nickname'],
                'gold' => $value['gold'],
            ];
        }
        Logic::SendRight($message['uid'], 'Msg_YYY_GetUserList', $data);
    }

    /**
     * 限制押注配付额度
     * @param int
     * @param int
     * @return array
     */
    private function GetOdds()
    {
        $allbet = [];
        foreach (ODDS as $key => $value) {
            $allbet[$key] = array_sum(array_column($this->allBet, $key));
        }

        $Quota = $this->mUserList[$this->banker['uid']]['gold'] + array_sum($allbet);
        //单双赔付额
        $Quota -= max([$allbet[SINGLE], $allbet[DOUBLE]]) * (ODDS[SINGLE] + 1);
        //大小赔付额
        $Quota -= max([$allbet[BIGPOINT], $allbet[SMALLPOINT]]) * (ODDS[SMALLPOINT] + 1);
        $index = [11, 12, 13, 14, 15, 16];
        $onemax = [];
        $doublemax = [];
        $threemax = [];
        //对子 豹子赔付额
        foreach ($index as $key => $value) {
            $onemax[] = $allbet[$value] * (ODDS[$value] + 1);
            $doublemax[] = $allbet[$value + 10] * (ODDS[$value + 10] + 1);
            $threemax[] = $allbet[$value + 20] * (ODDS[$value + 20] + 1);
        }
        rsort($doublemax);
        rsort($threemax);
        $Quota -= max($onemax);

        for ($i = 0; $i < 3; $i++) {
            $Quota -= $doublemax[$i];
            $Quota -= $threemax[$i];
        }
        $Quota -= $allbet[BAOZI] * ODDS[BAOZI];
        $_all = [];
        $index = [112, 113, 114, 115, 116, 123, 124, 125, 126, 134, 135, 136, 145, 146, 156];
        foreach ($index as $key => $value) {
            $_all[] = $allbet[$value] * (ODDS[$value] + 1);
        }
        rsort($_all);
        for ($i = 0; $i < 3; $i++) {
            $Quota -= $_all[$i];
        }
        $max = 0;
        $index = [304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317];
        foreach ($index as $key => $value) {
            if ($allbet[$value] * ODDS[$value] > $max) {
                $max = $allbet[$value] * ODDS[$value];
            }
        }
        $Quota -= $max;
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
                Logic::SendError($value, 'Msg_YYY_BankerInfo', '当前金币不满足上庄条件');
            }
        }

        if ($num != count($this->bankerList)) {
            $this->bankerList = array_values($this->bankerList);
            Logic::SendAll('Msg_YYY_ToBanker', ['list' => $this->bankerList, 'bankerturn' => $this->bankerturn], $this->roomRule['rid']);
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

                    foreach ($this->mRobot as $key => $value) {
                        if ($this->mUserList[$key]['gold'] >= BANKERGOLD) {
                            $this->RobotToBanker($key);
                            break;
                        }
                    }
                    return;
                }
                $uid = array_shift($this->bankerList);
                $this->banker = DBInstance::GetBankerInfo($uid);
                $this->banker['circle'] = 0;
                Logic::SendAll('Msg_YYY_BankerInfo', $this->banker, $this->roomRule['rid']);
                $this->bankerturn = false;
            } else {
                $this->banker['gold'] = $gold;
            }
        } else {
            $uid = array_shift($this->bankerList);
            $this->banker = DBInstance::GetBankerInfo($uid);
            $this->banker['circle'] = 0;
            Logic::SendAll('Msg_YYY_BankerInfo', $this->banker, $this->roomRule['rid']);
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
     * 机器人上庄
     */
    private function RobotToBanker($banker)
    {
        Timer::del($this->bankerTimer);
        $this->Msg_YYY_ToBanker(
            [
                'event' => 'Msg_YYY_ToBanker',
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
                $this->Msg_YYY_ToBanker(
                    [
                        'event' => 'Msg_YYY_ToBanker',
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
    public function Msg_YYY_Out($message)
    {
        if (!empty($this->allBet[$message['uid']]) && $this->mGameStatus == STAGE_BET || ($message['uid'] == $this->banker['uid'] && $this->outGame == false)) {
            Logic::SendError($message['uid'], 'Msg_YYY_Out', '游戏中，无法退出');
            return;
        }

        unset($this->mUserList[$message['uid']]);
        unset($this->mRobot[$message['uid']]);
        if (in_array($message['uid'], $this->bankerList)) {
            unset($this->bankerList[array_search($message['uid'], $this->bankerList)]);
            $this->bankerList = array_values($this->bankerList);
        }

        Logic::SendAll('Msg_YYY_Out', [
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
        if (!empty($message) && $this->mGameStatus != 0) {
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
                $this->Msg_YYY_Out(['uid' => $key]);
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
        $data = [
            'history' => $this->history,
            'allcount' => $this->allCount,
            'allbet' => $this->getAllBet(),
            'bankerlist' => $this->bankerList,
            'allnum' => count($this->mUserList),
            'mybet' => $this->allBet[$uid] ?? [],
            'time' => $this->mGameStatus == STAGE_BET ? BET_TIME - time() + $this->mTimes : END_TIME - time() + $this->mTimes,
            'stage' => $this->mGameStatus,
            'banker' => $this->banker,
            'player' => [
                'uid' => $uid,
                'gold' => $player['gold'],
                'name' => $player['nickname'],
            ],
            'bankerturn' => $this->bankerturn
        ];

        if ($data['time'] < 0) {
            $data['time'] = 0;
        }

        Logic::SendRight($uid, 'Msg_YYY_RoomInfo', $data);
        Logic::SendAll('Msg_YYY_PlayerAct', ['uid' => $uid], $this->roomRule['rid']);
        if ($player['client_id'] != '') {
            Gateway::joinGroup($player['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->mRobot[$uid] = rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time();
            if ($this->mGameStatus == 0 && $player['gold'] >= BANKERGOLD) {
                $this->RobotToBanker($uid);
            }
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
        Logic::SendRight($uid, 'Msg_YYY_RoomInfo', [
            'history' => $this->history,
            'allcount' => $this->allCount,
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
            $this->Msg_YYY_ToBanker([
                'event' => 'Msg_YYY_ToBanker',
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
        if ($this->mGameStatus != STAGE_BET) {
            return;
        }

        //机器人坐庄 按照概率随机投注
        $golds = [1000 => 1, 10000 => 1, 100000 => 1, 1000000 => 1, 5000000 => 1, 10000000 => 1];
        foreach ($this->mRobot as $key => $value) {
            $rand = rand(1, 100);
            $sum = 0;
            foreach (AREAPOS as $key1 => $value1) {
                $sum += $value1['pos'];
                if ($rand <= $sum) {
                    $rand = rand(1, 100);
                    $sum = 0;
                    foreach ($value1['rand'] as $key2 => $value2) {
                        $sum += $value2;
                        if ($rand <= $sum) {
                            $gold = array_rand($golds);
                            if ($this->mUserList[$key]['gold'] >= $gold) {
                                $this->Msg_YYY_ActBet([
                                    'event' => 'Msg_YYY_ActBet',
                                    'uid' => $key,
                                    'data' => [
                                        'region' => [$key2 => $gold]
                                    ],
                                ]);
                            }
                            break 2;
                        }
                    }
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
            if (array_sum(array_column($this->allBet, $key)) > 0) {
                $all[$key] = array_sum(array_column($this->allBet, $key));
            }
        }
        return $all;
    }


    /**
     *  计算庄家各个区域盈亏
     */
    private function getBankerArea()
    {
        $region = DBInstance::GetDuoRenControl($this->roomRule['gtype'], $this->roomRule['level']);
        if (!empty($this->controlPonins[$region])) {
            $rand = array_rand($this->controlPonins[$region]);
            return $this->controlPonins[$region][$rand];
        } else {
            $rand = DBInstance::GetControlRand($this->roomRule['gtype']);
            if (rand(1, 100) <= $rand) {
                $all = [];
                foreach ($this->allBet as $key => $value) {
                    if (isset($this->mRobot[$this->banker['uid']]) && !isset($this->mRobot[$key])) {
                        foreach ($value as $key1 => $value1) {
                            if (!isset($all[$key1])) {
                                $all[$key1] = 0;
                            }
                            $all[$key1] += $value1;
                        }
                    } elseif (!isset($this->mRobot[$this->banker['uid']]) && isset($this->mRobot[$key])) {
                        foreach ($value as $key1 => $value1) {
                            if (!isset($all[$key1])) {
                                $all[$key1] = 0;
                            }
                            $all[$key1] += $value1;
                        }
                    }
                }

                $get = [];
                foreach ($this->allPoint as $key => $value) {
                    $ret = $this->getScorce($all, $value);
                    if (isset($this->mRobot[$this->banker['uid']]) && $ret >= 0) {
                        $get[$key] = $key;
                    } elseif (!isset($this->mRobot[$this->banker['uid']]) && $ret <= 0) {
                        $get[$key] = $key;
                    }
                }

                if (!empty($get)) {
                    $rand = array_rand($get);
                    return $this->allPoint[$rand];
                }
            }
        }

        $res = Algorithm::GetPoint();
        return $res;
    }


    /**
     *  计算盈亏
     */
    private function getScorce($allbet, $points)
    {
        $get = array_sum($allbet);
        $wins = Algorithm::GetResult($points);
        foreach ($allbet as $key => $value) {
            if (!empty($wins[$key])) {
                $odds = $key > 10 && $key < 20 ? $wins[$key] : ODDS[$key];
                $get -= $value * ($odds + 1);
            }
        }

        return $get;
    }
}
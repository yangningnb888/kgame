<?php

date_default_timezone_set("Asia/Shanghai");
require_once __DIR__ . '/Algorithm.php';

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('STAGE_BET', 1);   //下注阶段
define('STAGE_FA', 2);    //发牌阶段
define('STAGE_KEEP', 3);  //买保险阶段
define('STAGE_ACT', 4);   //玩家说话
define('STAGE_BANKERCARD', 5);  //庄家发牌阶段
define('STAGE_END', 6);    //结算阶段

define('TIME_BET', 10);  //下注时间
define('TIME_FA', 3);   //发牌动画时间
define('TIME_KEEP', 10);  //买保险时间
define('TIME_CALL', 10);  //操作时间
define('TIME_END', 15);   //结算时间
define('TIME_FUNC', 2);   //动画时间

define('ALLBET', [
    1 => [60000, 120000, 240000, 480000, 600000, 1200000],
    2 => [10000, 20000, 40000, 80000, 100000, 200000],
    3 => [60000, 120000, 240000, 480000, 600000, 1200000],
    4 => [300000, 600000, 1200000, 2400000, 3000000, 6000000],
    5 => [1000000, 2000000, 4000000, 6000000, 8000000, 10000000],
]);

class Table
{
    private $roomRule = [];  //房间规则
    private $mUserList = [];  //玩家信息
    private $mSeat = [];      //玩家座位
    private $mTimes = 0;    //定时器开启时间
    private $mTimer = 0;   //定时器id
    private $mGameStatus = 0;  //游戏阶段
    private $userCards = [];  //公共牌

    private $userAct = [];   //玩家动作
    private $cards = [];   //牌堆
    private $mTouch = 10;   //操作座位id
    private $bankerCards = [];      //庄家手牌
    private $startSeat = 1;    //开局座位号
    private $countTouch = 0;   //记录操作次数
    private $rebate = 0;      //抽水比例
    private $outGame = false;  //房间解散标志
    private $control = false;   //场控标注
    private $playerContrl = [];  //玩家场控等级
    private $robotTimer = 0;      //机器人行为定时器

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->mUserList = $msg['players'];
        unset($msg['players']);
        $this->roomRule = $msg;
        $this->rebate = (100 - $this->roomRule['rebate']) * 0.01;
        foreach ($this->mUserList as $key => $value) {
            $this->mUserList[$key]['seat']++;
            $this->mSeat[$value['seat'] + 1] = $key;
            $this->mUserList[$key]['win'] = 0;
            $this->mUserList[$key]['endtime'] = 0;
            $this->mUserList[$key]['online'] = ONLINE;
            $this->getRoomInfo($key);
            $this->UserInRoom($key);
            if ($value['client_id'] != '') {
                Gateway::joinGroup($value['client_id'], 'ROOM:' . $this->roomRule['rid']);
            } else {
                $this->mUserList[$key]['endtime'] = rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time();
            }
        }

        if (count($this->mUserList) >= 1 && $this->mGameStatus == 0) {
            //游戏开局
            $this->Init();
        }
        $this->robotTimer = Timer::add(1, function () {
            if (($this->mGameStatus == STAGE_BET || $this->mGameStatus == STAGE_KEEP || $this->mGameStatus == STAGE_ACT) && time() - $this->mTimes >= 1) {
                $this->RobotAllActions();
            } elseif ($this->mGameStatus == 0 || $this->mGameStatus == STAGE_END) {
                foreach ($this->mUserList as $key => $value) {
                    if ($value['client_id'] == '' && $value['endtime'] < time() && rand(1, 4) == 1) {
                        $this->Msg_ESYD_Out([
                            'event' => 'Msg_ESYD_Out',
                            'uid' => $key
                        ]);
                    }
                }
            }
        });
    }

    /**
     * 进房初始化
     */
    private function getRoomInfo($uid)
    {
        $data = [
            'stage' => $this->mGameStatus,
            'players' => [],
            'cards' => $this->userCards,
            'notice' => $this->userCall($uid),
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'acts' => $this->userAct,
            'bankercards' => $this->mGameStatus < STAGE_BANKERCARD && $this->mGameStatus > 0 ? [$this->bankerCards[0]] : $this->bankerCards,
            'startseat' => $this->startSeat,
            'time' => 0,
            'chip' => ALLBET[$this->roomRule['level']],
        ];

        if ($this->mGameStatus == STAGE_BET) {
            $data['time'] = TIME_BET - time() + $this->mTimes;
        } elseif ($this->mGameStatus == STAGE_END) {
            $data['time'] = TIME_END - time() + $this->mTimes;
        } elseif ($this->mGameStatus == STAGE_KEEP) {
            $data['time'] = TIME_KEEP - time() + $this->mTimes;
        }

        if ($this->mGameStatus < STAGE_FA) {
            foreach ($data['acts'] as $key => $value) {
                if (isset($data['acts'][$key][0]['cards'])) {
                    $data['acts'][$key][0]['cards'] = [];
                }
            }
        }

        foreach ($this->mUserList as $key => $value) {
            $data['players'][] = [
                'uid' => $key,
                'nickname' => $value['nickname'],
                'gold' => $value['gold'],
                'headimgurl' => $value['headimgurl'],
                'seat' => $value['seat'],
            ];
        }

        Logic::SendRight($uid, 'Msg_ESYD_RoomInfo', $data);
    }


    /**
     * 游戏初始化
     */
    private function Init()
    {
        if ($this->outGame) {
            $this->DisRoom();
            return;
        }
        Timer::del($this->mTimer);
        $this->mTimes = time();
        $this->userCards = [];
        $this->userAct = [];
        $this->startSeat--;
        $this->countTouch = 0;
        $this->mGameStatus = 0;
        $players = [];
        if ($this->startSeat <= 0) {
            $this->startSeat = 5;
        }

        $start = 0;
        foreach ($this->mUserList as $key => $value) {
            if ($value['gold'] < $this->roomRule['doublescore'] || $value['online'] == OFFLINE) {
                $this->Msg_ESYD_Out([
                    'event' => 'Msg_ESYD_Out',
                    'uid' => $key
                ]);
            } else {
                $start++;
                if (!empty($value['client_id'])) {
                    $players[] = $key;
                }
            }
        }

        $this->playerContrl = DBInstance::GetChessCardControl($this->roomRule['gtype'], $this->roomRule['level'], $players);
        if (in_array(-1, $this->playerContrl) || in_array(-2, $this->playerContrl)) {
            $this->control = true;
        } else {
            $this->control = false;
        }

        if ($start <= 0) {
            $this->DisRoom();
            return;
        }

        if (count($this->cards) < 30) {
            $this->cards = Algorithm::GetAllCards();
        }

        $this->mGameStatus = STAGE_BET;
        $this->bankerCards = $this->GetCards(2);
        $usergold = [];
        foreach ($this->mUserList as $key => $value) {
            $this->useGold($key, -$this->roomRule['doublescore']);
            $cards = $this->GetCards(2);
            //判断场控是否需要重新生成牌组
            if (isset($this->playerContrl[$key]) && $this->playerContrl[$key] < 0) {
                $cards = $this->GetPointCards($cards);
            }
            $this->userAct[$value['seat']] = [
                [
                    'bet' => $this->roomRule['doublescore'],
                    'bx' => -1,
                    'cards' => $cards,
                    'uid' => $key
                ]
            ];
            $usergold[$key] = $value['gold'];
        }

        Logic::SendAll('Msg_ESYD_GameStart', ['time' => TIME_BET, 'startseat' => $this->startSeat, 'usergold' => $usergold], $this->roomRule['rid']);
        $this->mTimes = time();
        $this->mTimer = Timer::add(TIME_BET, function () {
            $this->StageFa();
        }, [], false);
    }

    /**
     * 补牌逻辑
     */
    private function AddCard ($uid, $cards)
    {
        if (empty($this->playerContrl[$uid])) {
            return array_shift($this->cards);
        } elseif ($this->playerContrl[$uid] > 0) {
            if (count($cards) >= 2) {
                foreach ($this->cards as $key => $value) {
                    $_cards = $cards;
                    $_cards[] = $value;
                    $point = Algorithm::GetPX($_cards);
                    if (($point > 18 || $point < 14) && $point > 0) {
                        unset($this->cards[$key]);
                        $this->cards = array_values($this->cards);
                        return  $value;
                    }
                }
            }

            return array_shift($this->cards);
        } else {
            if (count($cards) >= 2) {
                foreach ($this->cards as $key => $value) {
                    $_cards = $cards;
                    $_cards[] = $value;
                    $point = Algorithm::GetPX($_cards);
                    if ($point < 0 || ($point < 17 && count($_cards) < 5)) {
                        unset($this->cards[$key]);
                        $this->cards = array_values($this->cards);
                        return  $value;
                    }
                }
            }

            foreach ($this->cards as $key => $value) {
                $_cards = $cards;
                $_cards[] = $value;
                $point = Algorithm::GetPX($_cards);
                if ($point <= 17 && $point >= 12) {
                    unset($this->cards[$key]);
                    $this->cards = array_values($this->cards);
                    return  $value;
                }
            }
        }
    }

    /**
     * 牌堆
     */
    private function GetCards($num = 0)
    {
        if ($num > 0) {
            $cards = [];
            for ($i = 0; $i < $num; $i++) {
                $cards[] = array_shift($this->cards);
            }
            return $cards;
        }
    }

    /**
     * 通知下注
     */
    private function StageFa()
    {
        $this->mGameStatus = STAGE_FA;
        $data = [
            'banker' => $this->bankerCards[0],
            'player' => [],
        ];
        foreach ($this->userAct as $key => $value) {
            foreach ($value as $key1 => $value1) {
                $data['player'][$key] = $value1['cards'];
            }
        }

        Logic::SendAll('Msg_ESYD_FaCards', $data, $this->roomRule['rid']);
        $this->mTimes = time();
        $this->mTimer = Timer::add(TIME_FUNC, function () {
            if ($this->bankerCards[0] > 1400) {
                //买保险
                $this->mGameStatus = STAGE_KEEP;
                Logic::SendAll('Msg_ESYD_CallKeep', ['time' => TIME_KEEP], $this->roomRule['rid']);
                $this->mTimes = time();
                $this->mTimer = Timer::add(TIME_KEEP, function () {
                    foreach ($this->userAct as $key => $value) {
                        if ($value[0]['bx'] < 0) {
                            $this->Msg_ESYD_BuyKeep([
                                'event' => 'Msg_ESYD_BuyKeep',
                                'uid' => $value[0]['uid'],
                                'data' => [
                                    'seat' => $key,
                                    'buy' => 0
                                ]
                            ]);
                        }
                    }
                }, [], false);
            } elseif ($this->bankerCards[0] > 1000) {
                $this->checkHJK();
            } else {
                $this->mGameStatus = STAGE_ACT;
                $this->userCall(0, true);
            }
        }, [], false);
    }

    /**
     * 通知下注
     */
    private function checkHJK()
    {
        $res = Algorithm::GetPX($this->bankerCards);
        if ($res == PX_HJK) {
            $this->mGameStatus = STAGE_BANKERCARD;
            Logic::SendAll('Msg_ESYD_BankerCards', ['banker' => $this->bankerCards], $this->roomRule['rid']);
            $this->mTimes = time();
            $this->mTimer = Timer::add(TIME_FUNC, function () {
                Timer::del($this->mTimer);
                $this->StageEnd();
            }, [], false);
        } else {
            Logic::SendAll('Msg_ESYD_BankerCards', ['banker' => [$this->bankerCards[0]]], $this->roomRule['rid']);
            $this->mTimer = Timer::add(TIME_FUNC, function () {
                $this->mGameStatus = STAGE_ACT;
                $this->userCall(0, true);
            }, [], false);
        }
    }


    /**
     * 通知下注
     */
    private function userCall($uid = 0, $turn = false)
    {
        if ($this->mGameStatus != STAGE_ACT || ($this->mTouch <= 0 && $uid != 0)) {
            return [];
        }

        if ($uid == 0) {
            $this->NextTouch($turn);
            if ($this->mTouch <= 0) {
                $this->AddBankerCards();
                return;
            }
            $this->mTimes = time();
        }

        $data = [
            'seat' => $this->mTouch,
            'uid' => $this->userAct[intval($this->mTouch / 10)][0]['uid'],
            'time' => TIME_CALL - time() + $this->mTimes
        ];

        if ($uid == 0) {
            Logic::SendAll('Msg_ESYD_CallUserAct', $data, $this->roomRule['rid']);
            $this->mTimer = Timer::add(TIME_CALL, function () {
                $this->userCall();
            }, [], false);
        } else {
            return $data;
        }
    }

    /**
     * 庄家加牌
     * */
    private function AddBankerCards()
    {
        $this->mGameStatus = STAGE_BANKERCARD;
        if ($this->control) {
            $res = Algorithm::GetPX($this->bankerCards);
            $point = $this->CheckWin();
            if ($res < $point) {
                array_pop($this->bankerCards);
                $allcards = [];
                for ($j = 2; $j < 15; $j++) {
                    for ($k = 1; $k <= 4; $k++) {
                        $allcards[] = $j * 100 + $k;
                    }
                }

                shuffle($allcards);
                $_cards = $this->bankerCards;
                for ($i = 0; $i < 4; $i++) {
                    foreach ($allcards as $key => $value) {
                        $_cards[] = $value;
                        $res = Algorithm::GetPX($_cards);
                        if ($res >= $point && $res != PX_HJK) {
                            break 2;
                        } elseif (($res >= 17 && $res < $point) || $res < 0) {
                            array_pop($_cards);
                        }
                    }
                }

                $this->bankerCards = $_cards;
            }
        }

        for ($i = 0; $i < 3; $i++) {
            $res = Algorithm::GetPX($this->bankerCards);
            if ($res < 17 && $res > 0) {
                $this->bankerCards = array_merge($this->bankerCards, $this->GetCards(1));
            } elseif ($res < 0 || $res >= 17) {
                break;
            }
        }

        $res = Algorithm::GetPX($this->bankerCards);
        Logic::SendAll('Msg_ESYD_BankerAllCards', ['cards' => $this->bankerCards, 'point' => $res], $this->roomRule['rid']);
        $this->mTimes = time();
        $time = count($this->bankerCards) - 1;
        $this->mTimer = Timer::add($time, function () {
            $this->StageEnd();
        }, [], false);
    }

    /**
     * 结算阶段
     * */
    private function StageEnd()
    {
        $this->mGameStatus = STAGE_END;
        $res = Algorithm::GetPX($this->bankerCards);  //庄家牌型
        $playeruse = [];   //uid => 下注花费
        $player = [];
        $data = [
            'seatwin' => [],
            'userwin' => [],
            'factwin' => [],
        ];

        foreach ($this->userAct as $key => $value) {
            $data['seatwin'][$key] = 0;
            $data['factwin'][$key] = 0;
            $bet = 0;
            if (!isset($player[$value[0]['uid']])) {
                $player[$value[0]['uid']] = 0;
            }

            foreach ($value as $key1 => $value1) {
                if (!isset($playeruse[$value1['uid']])) {
                    $playeruse[$value1['uid']] = $value1['bet'];
                } else {
                    $playeruse[$value1['uid']] += $value1['bet'];
                }

                $bet += $value1['bet'];
                $playeruse[$value1['uid']] += $value1['bx'] > 0 ? $value1['bx'] : 0;

                if ($value1['bx'] > 0) {
                    if ($res == PX_HJK) {
                        $data['factwin'][$key] += 3 * $value1['bx'];
                        $data['seatwin'][$key] += 2 * $value1['bx'] * $this->rebate + $value1['bx'];
                    }
                    $bet += $value1['bx'];
                }

                $px = Algorithm::GetPX($value1['cards']);
                if ($px > 0 && $px > $res) {
                    if ($px >= PX_WXL) {
                        $data['factwin'][$key] += 2.5 * $value1['bet'];
                        $data['seatwin'][$key] += round(1.5 * $value1['bet'] * $this->rebate) + $value1['bet'];
                    } else {
                        $data['factwin'][$key] += 2 * $value1['bet'];
                        $data['seatwin'][$key] += $value1['bet'] + $value1['bet'] * $this->rebate;
                    }
                } elseif ($px == $res && $px > 0) {
                    $data['factwin'][$key] += $value1['bet'];
                    $data['seatwin'][$key] += $value1['bet'];
                }
            }

            $data['factwin'][$key] -= $bet;
            $data['seatwin'][$key] = $data['seatwin'][$key] - $bet;
            $player[$value[0]['uid']] += $data['seatwin'][$key];
            if (isset($data['factwin'][$key]) && $this->mUserList[$value[0]['uid']]['client_id'] != '' && $this->roomRule['level'] != 1) {
                if (abs($this->playerContrl[$value[0]['uid']]) != 2) {
                    Logic::InsertProfit($this->roomRule['level'], $data['factwin'][$key]);
                }
                DBInstance::IncrementUserGet($value[0]['uid'], $player[$value[0]['uid']]);
            }
        }

        foreach ($player as $key => $value) {
            $this->useGold($key, $value + $playeruse[$key]);
        }

        $data['userwin'] = $player;
        $data['time'] = TIME_END;
        Logic::SendAll('Msg_ESYD_Result', $data, $this->roomRule['rid']);
        $this->mTimes = time();
        $this->mTimer = Timer::add(TIME_END, function () {
            $this->Init();
        }, [], false);
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_ESYD_UserBet':
                $this->Msg_ESYD_UserBet($message);
                break;
            case 'Msg_ESYD_BuyKeep':
                $this->Msg_ESYD_BuyKeep($message);
                break;
            case 'Msg_ESYD_UserTouch':
                $this->Msg_ESYD_UserTouch($message);
                break;
            case 'Msg_ESYD_Out':
                $this->Msg_ESYD_Out($message);
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
     * @param bool
     */
    public function Msg_ESYD_UserBet($message)
    {
        $uid = $message['uid'];
        $seat = $message['data']['seat'];
        $gold = $message['data']['gold'];
        if (empty($this->userAct[$this->mUserList[$uid]['seat']])) {
            Logic::SendError($uid, $message['event'], '玩家未参与当前游戏');
            return;
        }

        if ($this->mUserList[$uid]['seat'] != $seat && !empty($this->userAct[$seat]) && $this->userAct[$seat][0]['uid'] != $uid) {
            Logic::SendError($uid, $message['event'], '玩家不能下注该位置');
            return;
        }

        if ($this->mUserList[$uid]['gold'] < $gold || (!empty($this->userAct[$seat]) && $gold + $this->userAct[$seat][0]['bet'] > max(ALLBET[$this->roomRule['level']]))) {
            Logic::SendError($uid, $message['event'], '下注金额超限');
            return;
        }

        if (empty($this->userAct[$seat])) {
            $this->userAct[$seat] = [
                [
                    'bet' => 0,
                    'bx' => -1,
                    'cards' => $this->GetCards(2),
                    'uid' => $uid
                ]
            ];
        }

        $message['data']['uid'] = $uid;
        $this->userAct[$seat][0]['bet'] += $gold;
        $this->useGold($uid, -$gold);
        Logic::SendAll('Msg_ESYD_UserBet', $message['data'], $this->roomRule['rid']);
    }

    /**
     * 玩家下注
     * @param bool
     */
    public function Msg_ESYD_BuyKeep($message)
    {
        $uid = $message['uid'];
        $seat = $message['data']['seat'];
        if (empty($this->userAct[$seat]) || $this->userAct[$seat][0]['uid'] != $uid || $this->userAct[$seat][0]['bx'] > 0) {
            Logic::SendError($uid, $message['event'], '非法操作');
            return;
        }

        if ($this->mGameStatus != STAGE_KEEP) {
            Logic::SendError($uid, $message['event'], '阶段错误');
            return;
        }

        if ($message['data']['buy']) {
            $gold = $this->userAct[$seat][0]['bet'] * 0.5;
            if ($this->mUserList[$uid]['gold'] < $gold) {
                Logic::SendError($uid, $message['event'], '金币不足');
                return;
            }
            $this->userAct[$seat][0]['bx'] = $gold;
            $this->useGold($uid, -$gold);
        } else {
            $this->userAct[$seat][0]['bx'] = 0;
        }

        Logic::SendAll('Msg_ESYD_BuyKeep', $message['data'], $this->roomRule['rid']);
        foreach ($this->userAct as $key => $value) {
            if ($value[0]['bx'] < 0) {
                return;
            }
        }
        $this->checkHJK();
    }

    /**
     * 玩家下注
     * @param bool
     */
    public function Msg_ESYD_UserTouch($message)
    {
        $uid = $message['uid'];
        $act = $message['data']['act'];
        $seat = intval($this->mTouch / 10);
        $index = $this->mTouch % 10;
        if ($this->userAct[$seat][$index]['uid'] != $uid || count($this->userAct[$seat][$index]['cards']) >= 5) {
            Logic::SendError($uid, $message['event'], '非法操作');
            return;
        }

        $data = $message['data'];
        $data['card'] = 0;
        $data['allcards'] = [];
        if ($act > 1) {
            $gold = $this->userAct[$seat][$index]['bet'];
            if ($this->mUserList[$uid]['gold'] < $gold) {
                Logic::SendError($uid, $message['event'], '金币不足');
                return;
            }
            if ($act == 2) {
                $this->userAct[$seat][$index]['bet'] += $gold;
            }
            $this->useGold($uid, -$gold);
        }

        if ($act >= 3) {
            //分牌
            if (count($this->userAct[$seat]) > 1 || count($this->userAct[$seat][$index]['cards']) != 2) {
                Logic::SendError($uid, $message['event'], '玩家不能分牌');
                return;
            }

            $points = [];
            foreach ($this->userAct[$seat][$index]['cards'] as $key => $value) {
                $_point = intval($value / 100);
                $points[] = $_point < 14 && $_point > 10 ? 10 : $_point;
            }

            if ($points[0] != $points[1]) {
                Logic::SendError($uid, $message['event'], '手牌不能分牌');
                return;
            }

            $new = [];
            for ($i = 0; $i < 2; $i++) {
                $cards = [$this->userAct[$seat][$index]['cards'][$i]];
                $cards[] = $this->AddCard($uid, $cards);
                $data['allcards'][] = $cards;
                $new[] = [
                    'bet' => $this->userAct[$seat][0]['bet'],
                    'bx' => $i == 0 ? $this->userAct[$seat][0]['bx'] : 0,
                    'cards' => $cards,
                    'uid' => $uid
                ];
            }
            $this->userAct[$seat] = $new;
            $this->mTouch -= 9;
            $this->countTouch--;
        } elseif ($act == 2) {
            if (count($this->userAct[$seat][$index]['cards']) == 4) {
                $minpoint = Algorithm::GetMinPX($this->userAct[$seat][$index]['cards']);
                if ($minpoint <= 10) {
                    Logic::SendError($uid, $message['event'], '牌型不能双倍下注！');
                    return;
                }
            }
            $this->userAct[$seat][$index]['cards'][] = $this->AddCard($uid, $this->userAct[$seat][$index]['cards']);
            $data['card'] = end($this->userAct[$seat][$index]['cards']);
        } elseif ($act == 1) {
            $this->userAct[$seat][$index]['cards'][] = $this->AddCard($uid, $this->userAct[$seat][$index]['cards']);
            $data['card'] = end($this->userAct[$seat][$index]['cards']);
        }

        $point = Algorithm::GetPX($this->userAct[$seat][$index]['cards']);
        $data['point'] = $point;
        Logic::SendAll('Msg_ESYD_UserTouch', $data, $this->roomRule['rid']);

        if ($point < 0 || count($this->userAct[$seat][$index]['cards']) >= 5 || $act == 0 || $act >= 2) {
            Timer::del($this->mTimer);
            $this->userCall();
        }
    }

    /**
     * 玩家退房
     * @param array
     */
    public function Msg_ESYD_Out($message)
    {
        if ($this->mGameStatus != 0 && $this->mGameStatus != STAGE_END) {
            foreach ($this->userAct as $key => $value) {
                if ($value[0]['uid'] == $message['uid']) {
                    Logic::SendError($message['uid'], 'Msg_ESYD_Out', '游戏中，不能退出');
                    return;
                }
            }
        }

        Logic::SendAll('Msg_ESYD_Out', [
            'uid' => $message['uid'],
            'gold' => DBInstance::GetUserOneWord('gold', $message['uid'])], $this->roomRule['rid']);
        unset($this->mSeat[$this->mUserList[$message['uid']]['seat']]);
        unset($this->mUserList[$message['uid']]);
        Logic::QuitRoom([
            'rid' => $this->roomRule['rid'],
            'uid' => $message['uid']
        ]);

        if (empty($this->mUserList)) {
            $this->DisRoom();
        }
    }

    /**
     * 玩家重连
     * @param string
     * @param int
     */
    public function UserOnline($client_id, $uid)
    {
        $this->mUserList[$uid]['client_id'] = $client_id;
        $this->mUserList[$uid]['online'] = ONLINE;
        Gateway::joinGroup($client_id, 'ROOM:' . $this->roomRule['rid']);
        $this->getRoomInfo($uid);
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
        $this->mUserList[$uid]['online'] = OFFLINE;
        Gateway::leaveGroup($this->mUserList[$uid]['client_id'], 'ROOM:' . $this->roomRule['rid']);
    }

    /**
     * 新增玩家
     *
     * @param [type] $msg
     * @return void
     */
    public function EnterRoom($msg)
    {
        $this->mUserList[$msg['uid']] = $msg;
        $this->mUserList[$msg['uid']]['seat']++;
        $this->mUserList[$msg['uid']]['win'] = 0;
        $this->mUserList[$msg['uid']]['online'] = ONLINE;
        $this->mSeat[$msg['seat']] = $msg['uid'];
        $this->UserInRoom($msg['uid']);
        $this->getRoomInfo($msg['uid']);
        if ($msg['client_id'] != '') {
            Gateway::joinGroup($msg['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->mUserList[$msg['uid']]['endtime'] = rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time();
        }

        if ($this->mGameStatus == 0) {
            $this->Init();
        }
    }

    public function UserInRoom($uid)
    {
        Logic::SendAll('Msg_ESYD_PlayerAct', [
            'nickname' => $this->mUserList[$uid]['nickname'],
            'gold' => $this->mUserList[$uid]['gold'],
            'uid' => $uid,
            'headimgurl' => $this->mUserList[$uid]['headimgurl'],
            'seat' => $this->mUserList[$uid]['seat'],
        ], $this->roomRule['rid']);
    }

    /**
     * 玩家金钱变化
     * @param [type] $msg
     * @return void
     */
    public function ChangeGold($msg)
    {
        if ($this->roomRule['level'] != 1) {
            $uid = $msg['uid'];
            $this->mUserList[$uid]['gold'] = DBInstance::GetUserOneWord('gold', $uid);
            Logic::SendAll('Msg_ESYD_ChangGold', ['uid' => $uid, 'gold' => $this->mUserList[$uid]['gold']], $this->roomRule['rid']);
            if ($this->mGameStatus == 0) {
                $this->Init();
            }
        }
    }

    /**
     * 玩家金钱变化
     * @param [type] $msg
     * @return void
     */
    private function useGold($uid, $gold)
    {
        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $uid, $gold);
        }
        $this->mUserList[$uid]['gold'] += $gold;
    }


    /**
     * 强制解散房间
     *
     * @param [type] $msg
     * @return void
     */
    public function DisRoom($msg = [])
    {
        if (!empty($msg) && $this->mGameStatus != 0) {
            $this->outGame = true;
        } else {
            Timer::del($this->mTimer);
            Timer::del($this->robotTimer);
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
                $this->Msg_ESYD_Out(['uid' => $key]);
            }
            Logic::RoomOld($olddata);
        }
    }

    /**
     * 操纵座位号
     */
    private function NextTouch($turn)
    {
        $num = 0;
        for (; ;) {
            if ($turn && $num == 0) {
                $this->mTouch = $this->startSeat * 10;
            } elseif ($this->mTouch % 10 != 0) {
                $this->mTouch = (intval($this->mTouch / 10) + 1) * 10;
            } else {
                $this->mTouch++;
            }

            if ($this->mTouch > 51) {
                $this->mTouch = 10;
            }

            $num++;
            $this->countTouch++;
            $seat = intval($this->mTouch / 10);
            $index = $this->mTouch % 10;

            $start = intval($this->mTouch / 10);
            if ($num >= 10 || ($turn == false && $start == $this->startSeat && $this->countTouch > 2)) {
                $this->mTouch = -1;
                return;
            }

            if (!empty($this->userAct[$seat][$index])) {
                $point = Algorithm::GetPX($this->userAct[$seat][$index]['cards']);
                if ($point <= 21) {
                    return;
                }
            }
        }
    }

    /**
     * 计算本局输赢
     */
    private function CheckWin()
    {
        $arr = [17, 18, 19, 20, 21, PX_WXL];
        foreach ($arr as $key => $value) {
            $bankerwin = 0;
            foreach ($this->userAct as $key1 => $value1) {
                foreach ($value1 as $key2 => $value2) {
                    $px = Algorithm::GetPX($value2['cards']);
                    if ($px > 0 && $px > $value) {
                        if ($px >= PX_WXL) {
                            $bankerwin -= 1.5 * $value2['bet'];
                        } else {
                            $bankerwin -= $value2['bet'];
                        }
                    } elseif ($px < 0 || $px < $value) {
                        $bankerwin += $value2['bet'];
                    }
                }
            }

            if ($bankerwin >= 0) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * 限定发牌
     */
    private function GetPointCards ($cards)
    {
        $point = Algorithm::GetPX($cards);
        $flag = false;
        for ($i = 1; $i <= 4; $i++) {
            if (in_array(1400 + $i, $cards)) {
                $flag = true;
            }
        }

        if ($point >= 12 && $point <= 17 && !$flag) {
            return $cards;
        } else {
            //将牌组放回牌堆
            $this->cards = array_merge($this->cards, $cards);
            $cards = [];
            foreach ($this->cards as $key => $value) {
                if (intval($value / 100) != 14 && count($cards) == 0) {
                    $cards[] = $value;
                    unset($this->cards[$key]);
                } elseif (!empty($cards)) {
                    $_cards = $cards;
                    $_cards[] = $value;
                    $point = Algorithm::GetPX($_cards);
                    if ($point >= 12 && $point <= 17) {
                        $cards = $_cards;
                        unset($this->cards[$key]);
                        break;
                    }
                }
            }

            $this->cards = array_values($this->cards);
            $num = count($cards);
            for ($i = $num; $i < 2; $i++) {
                MyTools::msg('========================*******===============');
                MyTools::msg(json_encode($cards));
                MyTools::msg(json_encode($this->cards));
                $cards = array_shift($this->cards);
            }
        }

        return $cards;
    }

    //机器人行为判断
    private function RobotAllActions ()
    {
        $empty = [];
        for ($i = 1; $i <= 5; $i++) {
            if (empty($this->userAct[$i])) {
                $empty[] = $i;
            }
        }

        foreach ($this->mUserList as $key => $value) {
            if (rand(1, 2) == 1 || !empty($value['client_id'])) {
                continue;
            }

            if ($this->mGameStatus == STAGE_BET && empty($value['client_id'])) {
                $ret = Algorithm::RobotBet($value['seat'], ALLBET[$this->roomRule['level']], $empty);
                $max = max(ALLBET[$this->roomRule['level']]);
                if ($ret['gold']) {
                    if ($value['gold'] > $ret['gold'] && (empty($this->userAct[$ret['seat']]) || ($this->userAct[$ret['seat']][0]['uid'] == $key
                                && $this->userAct[$ret['seat']][0]['bet'] + $ret['gold'] <= $max))) {
                        $this->Msg_ESYD_UserBet([
                            'event' => 'Msg_ESYD_UserBet',
                            'uid' => $key,
                            'data' => $ret
                        ]);
                    }
                }
            } elseif ($this->mGameStatus == STAGE_KEEP && rand(1, 10) == 2) {
                $seats = [];
                foreach ($this->userAct as $key1 => $value1) {
                    if ($value1[0]['uid'] == $key && $value1[0]['bx'] < 0) {
                        $seats[$key1] = $key1;
                    }
                }

                if (!empty($seats)) {
                    $this->Msg_ESYD_BuyKeep([
                        'event' => 'Msg_ESYD_BuyKeep',
                        'uid' => $key,
                        'data' => [
                            'seat' => array_rand($seats),
                            'buy' => rand(1, 4) == 1 ? 1 : 0,
                        ]
                    ]);
                }
            } else {
                $seat = intval($this->mTouch / 10);
                $index = $this->mTouch % 10;
                if (!empty($this->userAct[$seat]) && $this->userAct[$seat][0]['uid'] == $key) {
                    if (count($this->userAct[$seat][$index]['cards']) == 2 && count($this->userAct[$seat]) == 1 && $value['gold'] >= $this->userAct[$seat][0]['bet']) {
                        $fen = true;
                        $points = [];
                        foreach ($this->userAct[$seat][$index]['cards'] as $key1 => $value1) {
                            $_point = intval($value1 / 100);
                            $points[] = $_point < 14 && $_point > 10 ? 10 : $_point;
                        }

                        if ($points[0] != $points[1]) {
                            $fen = false;
                        }
                    } else {
                        $fen = false;
                    }

                    $act = Algorithm::RobotAct($this->userAct[$seat][$index]['cards'], $fen);
                    if ($act == 2 && $value['gold'] < $this->userAct[$seat][0]['bet']) {
                        $act = 1;
                    }

                    $this->Msg_ESYD_UserTouch([
                        'event' => 'Msg_ESYD_UserTouch',
                        'uid' => $key,
                        'data' => [
                            'act' => $act
                        ]
                    ]);
                }
            }
        }
    }
}

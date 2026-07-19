<?php

date_default_timezone_set("Asia/Shanghai");
require_once __DIR__ . '/AI.php';

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('STAGE_FA', 1);
define('STAGE_BET', 2);
define('STAGE_END', 3);

define('TIME_FA', 2);
define('TIME_BET', 60);
define('TIME_END', 15);

define('ACT_XIAOMANG', 1);
define('ACT_DAMANG', 2);
define('ACT_ADDBET', 3);
define('ACT_GENBET', 4);
define('ACT_GIVEUP', 5);
define('ACT_ALLIN', 6);

class Table
{
    private $roomRule = [];  //房间规则
    private $mUserList = [];  //玩家信息
    private $mSeat = [];      //玩家座位
    private $mTimes = 0;    //定时器开启时间
    private $mTimer = 0;   //定时器id
    private $AITimer = 0;   //机器人定时器
    private $mGameStatus = 0;  //游戏阶段
    private $publicCards = [];  //公共牌

    private $inGame = [];   //游戏中uid
    private $outTime = [];  //充值缓冲玩家
    private $allBets = 0;   //底池
    private $userAct = [];   //玩家动作
    private $userAllBet = [];  //玩家总下注
    private $cards = [];   //牌堆
    private $mTouch = 0;   //操作座位id
    private $startUser = 0;  //庄家座位
    private $px = [];      //
    private $outGame = false;
    private $curCards = [
        'public' => [],
        'user' => [],
    ];
    private $robotAction = [];
    private $controlUid = [];   //玩家场控值


    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->mUserList = $msg['players'];
        unset($msg['players']);
        $this->roomRule = $msg;
        if ($this->roomRule['level'] == 1) {
            $this->roomRule['min_gold'] = 250000;
        }

        foreach ($this->mUserList as $key => $value) {
            if ($value['gold'] > $this->roomRule['max_gold']) {
                $this->mUserList[$key]['gold'] = $this->roomRule['max_gold'];
                $this->mUserList[$key]['allgold'] = $value['gold'] - $this->roomRule['max_gold'];
            } else {
                $this->mUserList[$key]['allgold'] = 0;
            }

            $this->mSeat[$value['seat']] = $key;
            $this->mUserList[$key]['win'] = 0;
            $this->mUserList[$key]['online'] = ONLINE;
            $this->getRoomInfo($key);
            $this->UserInRoom($key);
            if ($value['client_id'] != '') {
                Gateway::joinGroup($value['client_id'], 'ROOM:' . $this->roomRule['rid']);
            } else {
                $this->robotAction[$key] = new AI($key, $value['gold'], rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time(), $this->roomRule['doublescore']);
            }
        }

        if (count($this->mUserList) - count($this->outTime) > 2) {
            //游戏开局
            $this->Init();
        }

        $this->AITimer = Timer::add(1, function () {
            foreach ($this->robotAction as $key => $value) {
                $time = rand(1, 5);
                if ($this->mGameStatus == STAGE_BET && $this->mSeat[$this->mTouch] == $key) {
                    if (time() - $this->mTimes >= $time) {
                        $min = $this->GetMinBet($key);
                        $this->robotAction[$key]->cards = $this->inGame[$key];
                        $this->robotAction[$key]->publicCards = $this->publicCards;
                        $this->robotAction[$key]->allbet = $this->userAllBet[$key] ?? 0;
                        $this->robotAction[$key]->userAct = $this->userAct;
                        $message = $value->RobotActions($this->mGameStatus, $min);
                        if ($message['data']['gold'] > 0 && $time == 1 && rand(1, 30) == 1) {
                            $message['data']['gold'] = $this->robotAction[$key]->gold;
                        }
                    }
                } elseif (empty($this->inGame[$key])) {
                    $message = $value->RobotActions();
                }

                if (!empty($message)) {
                    $this->All_RECV($message);
                    break;
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
            'curbet' => $this->userAct,
            'publiccards' => $this->publicCards,
            'notice' => $this->userCall($uid),
            'allbet' => $this->allBets,
            'bankeruid' => $this->mGameStatus == 0 ? 0 : $this->mSeat[$this->startUser],
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'px' => $this->px,
            'allbets' => $this->userAllBet
        ];

        foreach ($this->mUserList as $key => $value) {
            if (empty($value['nickname'])) {
                var_dump($key);
            }
            $data['players'][] = [
                'uid' => $key,
                'nickname' => $value['nickname'],
                'gold' => $value['gold'],
                'headimgurl' => $value['headimgurl'],
                'seat' => $value['seat'],
                'cards' => isset($this->inGame[$key]) ? $key == $uid ? $this->inGame[$key] : [1, 1] : []
            ];
        }

        Logic::SendRight($uid, 'Msg_DZPK_RoomInfo', $data);
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
        $this->allBets = 0;
        $this->inGame = [];
        $this->publicCards = [];
        $this->cards = [];
        $this->userAct = [];
        $this->userAllBet = [];
        $this->mGameStatus = 0;
        $this->px = [];
        $this->curCards['public'] = $this->GetCards(5);
        $this->curCards['user'] = [];
        $uids = [];
        foreach ($this->mUserList as $key => $value) {
            if ($value['online'] == OFFLINE) {
                $this->Msg_DZPK_Out(['uid' => $key]);
                continue;
            }

            if ($value['gold'] < $this->roomRule['min_gold'] && $value['allgold'] + $value['gold'] >= $this->roomRule['min_gold']) {
                if ($value['gold'] + $value['allgold'] >= $this->roomRule['max_gold']) {
                    $this->mUserList[$key]['allgold'] -= ($this->roomRule['max_gold'] - $value['gold']);
                    $this->mUserList[$key]['gold'] = $this->roomRule['max_gold'];
                } else {
                    $this->mUserList[$key]['gold'] += $this->mUserList[$key]['allgold'];
                    $this->mUserList[$key]['allgold'] = 0;
                }

                Logic::SendAll('Msg_DZPK_ChangGold', ['uid' => $key, 'gold' => $this->mUserList[$key]['gold']], $this->roomRule['rid']);
                if (isset($this->robotAction[$key])) {
                    $this->robotAction[$key]->gold = $this->mUserList[$key]['gold'];
                }
            }

            if ($this->mUserList[$key]['gold'] >= $this->roomRule['min_gold']) {
                $uids[] = $key;
                $cards = $this->GetCards(2);
                $val = Algorithm::GetPX(array_merge($cards, $this->curCards['public']));
                $this->curCards['user'][] = [
                    'card' => $cards,
                    'val' => $val['value']
                ];
                $this->userAllBet[$key] = 0;
                unset($this->outTime[$key]);
            } else {
                $this->Msg_DZPK_Out(['uid' => $key]);
            }
        }

        array_multisort(array_column($this->curCards['user'], 'val'), SORT_DESC, $this->curCards['user']);
        $max = max(array_column($this->curCards['user'], 'val'));
        $usercards = [];
        foreach ($this->curCards['user'] as $key => $value) {
            if ($value['val'] == $max) {
                $usercards[] = [
                    'key' => $key,
                    'val' => Algorithm::ColorValue($value['card'])
                ];
            }
        }

        if (count($usercards) > 1) {
            //交换最大牌型值
            array_multisort(array_column($usercards, 'val'), SORT_DESC, $usercards);
            $first = current($usercards);
            $temp = $this->curCards['user'][0];
            $this->curCards['user'][0] = $this->curCards['user'][$first['key']];
            $this->curCards['user'][$first['key']] = $temp;
        }

        $this->controlUid = $turn = DBInstance::GetChessCardControl($this->roomRule['gtype'], $this->roomRule['level'], $uids);
        arsort($turn);
        $control = 0;
        //处理只有一个赢家的牌
        foreach ($turn as $key => $value) {
            if (!$control) {
                $control = $key;
            } else {
                $turn[$key] = rand(-1, -10);
            }
        }

        arsort($turn);

        foreach ($turn as $key => $value) {
            if (isset($this->robotAction[$key])) {
                $this->robotAction[$key]->controlUid = $control;
            }
            $cards = array_shift($this->curCards['user']);
            $this->inGame[$key] = $cards['card'];
        }

        if (count($this->inGame) < 3) {
            $this->mGameStatus = 0;
            $this->inGame = [];
            return;
        }

        Logic::TableStatus($this->roomRule['rid'], 1);
        $this->mGameStatus = STAGE_FA;
        $this->NextBankerUid();

        foreach ($this->mUserList as $key => $value) {
            //发送手牌
            Logic::SendRight($key, 'Msg_DZPK_FaCards', ['cards' => $this->inGame[$key] ?? [], 'ingame' => array_keys($this->inGame), 'bankeruid' => $this->mSeat[$this->startUser]]);
        }

        $this->mTimer = Timer::add(TIME_FA, function () {
            $data = ['bets' => []];
            for ($i = 0; $i < 2; $i++) {
                if ($i == 0) {
                    $this->NextTouch(true);
                    $this->userAct[$this->mSeat[$this->mTouch]] = [
                        'act' => ACT_XIAOMANG,
                        'gold' => $this->roomRule['doublescore'] * 0.5
                    ];
                    $data['bets'][$this->mSeat[$this->mTouch]] = $this->roomRule['doublescore'] * 0.5;
                    $this->userAllBet[$this->mSeat[$this->mTouch]] = $this->roomRule['doublescore'] * 0.5;
                    $this->allBets += $this->roomRule['doublescore'] * 0.5;
                    $this->useGold($this->mSeat[$this->mTouch], -$this->roomRule['doublescore'] * 0.5);
                } else {
                    $this->NextTouch();
                    $this->userAct[$this->mSeat[$this->mTouch]] = [
                        'act' => ACT_DAMANG,
                        'gold' => $this->roomRule['doublescore']
                    ];
                    $data['bets'][$this->mSeat[$this->mTouch]] = $this->roomRule['doublescore'];
                    $this->userAllBet[$this->mSeat[$this->mTouch]] = $this->roomRule['doublescore'];
                    $this->allBets += $this->roomRule['doublescore'];
                    $this->useGold($this->mSeat[$this->mTouch], -$this->roomRule['doublescore']);
                }
            }

            Logic::SendAll('Msg_DZPK_StageBet', $data, $this->roomRule['rid']);
            $this->mGameStatus = STAGE_BET;
            $this->userCall();
        }, [], false);
    }

    /**
     * 牌堆
     */
    private function GetCards($num = 0, $public = false)
    {
        if (empty($this->cards)) {
            for ($i = 2; $i < 15; $i++) {
                for ($j = 1; $j < 5; $j++) {
                    $this->cards[] = $i * 100 + $j;
                }
            }
            shuffle($this->cards);
        }

        if ($num > 0) {
            $cards = [];
            for ($i = 0; $i < $num; $i++) {
                if ($public) {
                    $cards[] = array_shift($this->curCards['public']);
                } else {
                    $cards[] = array_shift($this->cards);
                }
            }
            return $cards;
        }
    }

    /**
     * 通知下注
     */
    private function userCall($uid = 0)
    {
        if ($this->mGameStatus != STAGE_BET) {
            return [];
        }

        if ($uid == 0) {
            $count = count($this->inGame);
            $change = true;
            $gold = 0;
            $allin = 0;
            $giveup = 0;
            $gen = 0;

            foreach ($this->userAct as $key => $value) {
                if ($value['gold'] > $gold) {
                    $gold = $value['gold'];
                }
            }

            foreach ($this->userAct as $key => $value) {
                if ($value['act'] == ACT_GIVEUP || $value['act'] == ACT_ALLIN || ($value['act'] >= ACT_ADDBET && $value['gold'] >= $gold)) {
                    if ($value['act'] == ACT_GIVEUP) {
                        $giveup++;  //弃牌人数
                    } elseif ($value['act'] == ACT_ALLIN) {
                        $allin++;
                    } else {
                        $gen++;
                    }
                }
            }

            if ($count != $allin + $giveup + $gen && $count - $giveup > 1) {
                $change = false;
            }

            //跳阶段
            if ($change) {
                if (count($this->publicCards) >= 5 || $count - $giveup <= 1) {
                    $_giveup = $count - $giveup <= 1;
                    $this->StageEnd($_giveup);
                    return;
                }

                if ($count - $giveup - $allin <= 1) {
                    $num = 5 - count($this->publicCards);
                } else {
                    //保留弃牌状态
                    foreach ($this->userAct as $key => $value) {
                        if ($value['act'] != ACT_GIVEUP && $value['act'] != ACT_ALLIN) {
                            unset($this->userAct[$key]);
                        } else {
                            $this->userAct[$key]['gold'] = 0;
                        }
                    }

                    if (count($this->publicCards) == 0) {
                        $num = 3;
                    } else {
                        $num = 1;
                    }
                }

                $cards = $this->GetCards($num, true);
                $this->publicCards = array_merge($this->publicCards, $cards);
                $this->px = [];

                foreach ($this->inGame as $key => $value) {
                    $_cards = array_merge($value, $this->publicCards);
                    $_px = Algorithm::GetPX($_cards);
                    $this->px[$key] = $_px['value'];
                }

                Logic::SendAll('Msg_DZPK_PublicCards', ['cards' => $cards, 'px' => $this->px], $this->roomRule['rid']);

                if ($count - $giveup - $allin <= 1) {
                    //结算阶段
                    $_giveup = $count - $giveup <= 1;
                    $this->StageEnd($_giveup);
                    return;
                } else {
                    $this->mTouch = $this->startUser;
                }
            }
        }

        if ($uid == 0) {
            $this->NextTouch();
        }

        $touch = $this->mSeat[$this->mTouch];
        $minbet = $this->GetMinBet($touch);
        if ($uid == 0) {
            $this->mTimes = time();
            Logic::SendAll('Msg_DZPK_CallUserAct', ['minbet' => $minbet, 'uid' => $touch, 'time' => TIME_BET], $this->roomRule['rid']);
            $this->mTimer = Timer::add(TIME_BET, function () {
                //超时操纵
                $this->GameAct();
            }, [], false);
        } else {
            return [
                'uid' => $touch,
                'minbet' => $minbet,
                'time' => TIME_BET - time() + $this->mTimes < 0 ? 0 : TIME_BET - time() + $this->mTimes,
            ];
        }
    }

    /**
     * 结算阶段
     **/
    private function StageEnd($giveup = false)
    {
        Timer::del($this->mTimer);
        Logic::TableStatus($this->roomRule['rid'], 0);
        $this->mGameStatus = STAGE_END;
        $this->mTimes = time();
        $uservalues = [];

        foreach ($this->inGame as $key => $value) {
            if (!empty($this->userAct[$key]) && $this->userAct[$key]['act'] == ACT_GIVEUP) {
                $uservalues[$key] = ['cards' => [], 'value' => 0];
            } else {
                $cards = array_merge($this->inGame[$key], $this->publicCards);
                $uservalues[$key] = Algorithm::GetPX($cards);
            }
        }

        foreach ($uservalues as $key => $value) {
            $uservalues[$key]['hcards'] = $giveup ? [] : $this->inGame[$key];
        }

        $wins = [];
        $maxvalue = max(array_column($uservalues, 'value'));
        foreach ($uservalues as $key => $value) {
            if ($value['value'] == $maxvalue) {
                $wins[$key] = Algorithm::ColorValue($this->inGame[$key]);
            }
        }

        $winner = array_search(max($wins), $wins);
        if ($giveup) {
            foreach ($uservalues as $key => $value) {
                $uservalues[$key] = ['cards' => [], 'value' => 0];
            }
        }
        $data = [
            'winner' => $winner,
            'wingold' => $this->allBets - $this->userAllBet[$winner],
            'upgold' => [],
            'usergold' => [],
            'cards' => $uservalues,
            'time' => TIME_END
        ];

        foreach ($this->userAllBet as $key => $value) {
            if ($value > $this->userAllBet[$winner]) {
                $data['wingold'] -= $value - $this->userAllBet[$winner];
                $data['upgold'][$key] = $value - $this->userAllBet[$winner];
            }
        }

        $data['upgold'][$winner] = round($data['wingold'] * (1 - $this->roomRule['rebate'] * 0.01)) + $this->userAllBet[$winner];
        foreach ($data['upgold'] as $key => $value) {
            $this->useGold($key, $value);
        }

        foreach ($this->inGame as $key => $value) {
            if (isset($this->mUserList[$key])) {
                $data['usergold'][$key] = $this->mUserList[$key]['gold'];
            }
            if (!empty($this->userAllBet[$key]) && $this->roomRule['level'] != 1) {
                $type = empty($this->mUserList[$key]['client_id']) ? 1 : 0;
                if (empty($this->mUserList[$key])) {
                    $type = DBInstance::GetUserOneWord('type', $key);
                }
                if ($type == 0) {
                    if ($key == $winner) {
                        $_win = $data['wingold'];
                    } else {
                        $_win = isset($data['upgold'][$key]) ? $data['upgold'][$key] - $this->userAllBet[$key] : -$this->userAllBet[$key];
                    }
                    if (abs($this->controlUid[$key]) != 2) {
                        Logic::InsertProfit($this->roomRule['level'], $_win);
                    }
                    DBInstance::IncrementUserGet($key, $_win);
                }
            }
        }

        $data['wingold'] *= 1 - $this->roomRule['rebate'] * 0.01;
        $data['wingold'] = round($data['wingold']);
        Logic::SendAll('Msg_DZPK_Result', $data, $this->roomRule['rid']);
        $this->mTimer = Timer::add(TIME_END, function () {
            $this->Init();
        }, [], false);
    }

    /**
     * 获取最小下注额
     * */
    private function GetMinBet($uid)
    {
        $minbet = 0;
        if (!empty($this->userAct)) {
            $minbet = max(array_column($this->userAct, 'gold'));
        }

        if (!empty($this->userAct[$uid])) {
            $minbet -= $this->userAct[$uid]['gold'];
        }

        if ($minbet > $this->mUserList[$uid]['gold']) {
            $minbet = $this->mUserList[$uid]['gold'];
        }

        return $minbet;
    }

    /**
     * 默认操纵
     * */
    private function GameAct()
    {
        $minbet = $this->GetMinBet($this->mSeat[$this->mTouch]);
        $gold = 0;
        if ($minbet > 0) {
            $gold = -1;
        }
        $this->Msg_DZPK_ActBet(
            [
                'event' => 'Msg_DZPK_ActBet',
                'uid' => $this->mSeat[$this->mTouch],
                'data' => ['gold' => $gold]
            ]
        );
    }

    /**
     * 所有消息回调
     * @param array
     */

    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_DZPK_ActBet':
                $this->Msg_DZPK_ActBet($message);
                break;
            case 'Msg_DZPK_Out':
                $this->Msg_DZPK_Out($message);
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
    public function Msg_DZPK_ActBet($message)
    {
        $uid = $message['uid'];
        $gold = $message['data']['gold'];

        if ($uid != $this->mSeat[$this->mTouch] || empty($this->inGame[$uid])) {
            MyTools::msg(json_encode($message));
            MyTools::msg($this->mSeat[$this->mTouch] . '------------------------------------111');
            Logic::SendError($uid, $message['event'], '非操纵玩家');
            return;
        }

        if ($gold < 0) {
            if (empty($this->userAct[$uid])) {
                $this->userAct[$uid] = [
                    'act' => 0,
                    'gold' => 0,
                ];
            }
            $this->userAct[$uid]['act'] = ACT_GIVEUP;
        } else {
            $min = $this->GetMinBet($uid);
            if ($this->mUserList[$uid]['gold'] < $gold) {
                MyTools::msg(json_encode($message));
                MyTools::msg('------------------------------------222');
                var_dump($this->robotAction[$uid]->gold);
                var_dump($this->mUserList[$uid]['gold']);
                Logic::SendError($uid, $message['event'], '玩家金币不足');
                return;
            } elseif ($gold < $min && $this->mUserList[$uid]['gold'] > $gold) {
                MyTools::msg(json_encode($message));
                MyTools::msg('------------------------------------333');
                Logic::SendError($uid, $message['event'], '低于最小下注额');
                return;
            }

            if (empty($this->userAct[$uid])) {
                $this->userAct[$uid] = [
                    'act' => 0,
                    'gold' => 0,
                ];
            }

            if ($gold == $this->mUserList[$uid]['gold']) {
                $this->userAct[$uid]['act'] = ACT_ALLIN;
            } elseif ($min == $gold) {
                $this->userAct[$uid]['act'] = ACT_GENBET;
            } elseif ($min == 0 && $gold >= $this->roomRule['doublescore'] || intval($gold / $min) >= 2 && $min > 0) {
                $this->userAct[$uid]['act'] = ACT_ADDBET;
            } else {
                MyTools::msg(json_encode($message));
                MyTools::msg('------------------------------------444');
                Logic::SendError($uid, $message['event'], '金额错误');
                if ($this->userAct[$uid]['act'] == 0) {
                    unset($this->userAct[$uid]);
                }
                return;
            }

            $this->userAct[$uid]['gold'] += $gold;
            $this->allBets += $gold;
            $this->userAllBet[$uid] += $gold;
            if ($this->mUserList[$uid]['gold'] == 0) {
                $this->userAct[$uid]['act'] = ACT_ALLIN;
            }
            $this->useGold($this->mSeat[$this->mTouch], -$gold);
        }

        Timer::del($this->mTimer);
        Logic::SendAll('Msg_DZPK_ActBet', ['act' => $this->userAct[$uid]['act'], 'gold' => $gold], $this->roomRule['rid']);
        $this->userCall();
    }

    /**
     * 玩家退房
     * @param array
     */
    public function Msg_DZPK_Out($message)
    {
        if (isset($this->inGame[$message['uid']]) && (empty($this->userAct[$message['uid']]) || $this->userAct[$message['uid']]['act'] != 5) && ($this->mGameStatus == STAGE_BET || $this->mGameStatus == STAGE_FA)) {
            Logic::SendError($message['uid'], 'Msg_DZPK_Out', '游戏中，不能退出');
            return;
        }

        Logic::SendAll('Msg_DZPK_Out', [
            'uid' => $message['uid'],
            'gold' => DBInstance::GetUserOneWord('gold', $message['uid'])], $this->roomRule['rid']);
        unset($this->mSeat[$this->mUserList[$message['uid']]['seat']]);
        unset($this->mUserList[$message['uid']]);
        unset($this->robotAction[$message['uid']]);
        Logic::QuitRoom([
            'rid' => $this->roomRule['rid'],
            'uid' => $message['uid']
        ]);

        if (empty($this->mSeat)) {
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
        $this->mUserList[$uid]['online'] = ONLINE;
        $this->mUserList[$uid]['client_id'] = $client_id;
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
        if ($msg['gold'] > $this->roomRule['max_gold']) {
            $this->mUserList[$msg['uid']]['gold'] = $this->roomRule['max_gold'];
            $this->mUserList[$msg['uid']]['allgold'] = $msg['gold'] - $this->roomRule['max_gold'];
        } else {
            $this->mUserList[$msg['uid']]['allgold'] = 0;
        }

        $this->mUserList[$msg['uid']]['win'] = 0;
        $this->mUserList[$msg['uid']]['online'] = ONLINE;
        $this->mSeat[$msg['seat']] = $msg['uid'];
        $this->UserInRoom($msg['uid']);
        $this->getRoomInfo($msg['uid']);
        if ($msg['client_id'] != '') {
            Gateway::joinGroup($msg['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->robotAction[$msg['uid']] = new AI($msg['uid'], $this->mUserList[$msg['uid']]['gold'], rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]) + time(), $this->roomRule['doublescore']);
        }

        if ($this->mGameStatus == 0) {
            $this->Init();
        }
    }

    public function UserInRoom($uid)
    {
        Logic::SendAll('Msg_DZPK_PlayerAct', [
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
        $uid = $msg['uid'];
        $this->mUserList[$uid]['gold'] = DBInstance::GetUserOneWord('gold', $uid);
        if ($this->mUserList[$uid]['gold'] > $this->roomRule['max_gold']) {
            $this->mUserList[$uid]['gold'] = $this->roomRule['max_gold'];
            $this->mUserList[$uid]['allgold'] = $this->mUserList[$uid]['gold'] - $this->roomRule['max_gold'];
        } else {
            $this->mUserList[$uid]['allgold'] = 0;
        }

        Logic::SendAll('Msg_DZPK_ChangGold', [
            'uid' => $uid,
            'gold' => $this->mUserList[$uid]['gold']
        ], $this->roomRule['rid']);
        if ($this->mGameStatus == 0) {
            $this->Init();
        }
    }

    /**
     * 玩家金钱变化
     * @param [type] $msg
     * @return void
     */
    private function useGold($uid, $gold)
    {
        if ($gold != 0) {
            if ($this->roomRule['level'] != 1) {
                DBInstance::IncrementGolds('gold', $uid, $gold);
            }

            if (isset($this->robotAction[$uid])) {
                $this->robotAction[$uid]->gold += $gold;
            }
            $this->mUserList[$uid]['gold'] += $gold;
        }
    }


    /**
     * 强制解散房间
     * @param [type] $msg
     * @return void
     */
    public function DisRoom($msg = [])
    {
        if (!empty($msg) && $this->mGameStatus != 0) {
            $this->outGame = true;
        } else {
            Timer::del($this->mTimer);
            Timer::del($this->AITimer);
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
                $this->Msg_DZPK_Out(['uid' => $value]);
            }
            Logic::RoomOld($olddata);
        }
    }

    /**
     * 操纵座位号
     */
    private function NextTouch($reset = false)
    {
        $num = 0;
        if ($reset) {
            $this->mTouch = $this->startUser;
        }

        for (; ;) {
            if ($num > 8) {
                return;
            }
            $num++;
            $this->mTouch = $this->mTouch + 1;
            if ($this->mTouch >= 6) {
                $this->mTouch -= 6;
            }

            if (!empty($this->mSeat[$this->mTouch])) {
                $uid = $this->mSeat[$this->mTouch];
                if (!empty($this->inGame[$uid]) && (empty($this->userAct[$uid]) || $this->userAct[$uid]['act'] < ACT_GIVEUP)) {
                    return;
                }
            }
        }
    }

    /**
     * 获取庄家uid
     */
    private function NextBankerUid()
    {
        $num = 0;
        if ($this->mGameStatus) {
            $bseat = $this->startUser;
            for (; ;) {
                if ($num > 8) {
                    return;
                }
                $num++;
                $bseat--;
                if ($bseat < 0) {
                    $bseat += 6;
                }
                if (!empty($this->mSeat[$bseat]) && !empty($this->inGame[$this->mSeat[$bseat]])) {
                    $this->startUser = $bseat;
                    return;
                }
            }
        } else {
            return 0;
        }
    }
}

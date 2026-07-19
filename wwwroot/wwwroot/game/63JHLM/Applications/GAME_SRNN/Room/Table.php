<?php

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

require_once __DIR__ . '/Cards.php';
require_once __DIR__ . '/AI.php';
define("STAGE_WAIT", 0);  //等待开始
define("STAGE_CALL_BANKER", 1);  //叫庄
define("STAGE_BET", 2);  //下注
define("STAGE_FA", 3);  //发牌
define("STAGE_RES", 4);  //结算

define('TIEM_CALL_BANKER', 5); //叫庄
define('TIEM_BET', 5); //下注时间
define('TIEM_FA', 5); //发牌时间
define('TIEM_RES', 20); //结算时间
define('TIEM_TOU', 2); //托管
define('TIEM_RES_TUO', 4);

define('BET_ARR', [2, 4, 8]);

define('OUT_BEI', 5);

define('SRNN_PNUM', 2); //玩家人数

define('AI_ACT_TIME', [2, 4]); //机器人操作时间

class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $gameState = STAGE_WAIT; //游戏状态

    private $banker = 0; //庄家

    private  $timer = 0; //游戏流程时间戳

    private $timeStamp = 0; //时间戳

    private $seatUid = [];

    private $curseat = 0; //当前正操作的玩家

    private $hands = []; //牌堆

    private $userTimer = [];

    private $pnum = 0;

    private $fristbanker = 0; //下局叫庄家

    private $disRoom = 0;

    private $AiTimer = [];

    private $playercontr = [];

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->roomRule = [
            'rid' => $msg['rid'],
            'gtype' => $msg['gtype'],
            'level' => $msg['level'],
            'min_gold' => $msg['min_gold'],
            'max_gold' => $msg['max_gold'],
            'doublescore' => $msg['doublescore'],
            'vals' => $msg['vals'],
            'rebate' => (100 - $msg['rebate']) / 100
        ];
        $this->userEnter($msg['players']);
    }

    /**
     * 玩家进房
     * @param int
     * */
    private function userEnter($PlayerInfo)
    {
        foreach ($PlayerInfo as $key => $val) {
            $this->addPlayer($key, $val);
        }
    }

    /**
     * 整理房间进房消息
     * @param [type] $uid
     * @param [type] $info
     * @return void
     */
    private function addPlayer($uid, $info)
    {
        $this->seatUid[$info['seat']] = $uid;

        $this->PlayerInfo[$uid] = [
            'allWinScore' => 0, //总共赢了多少
            'winScore' => 0, //当局赢了多少         
            'nickname' => $info['nickname'], //昵称
            'gold' => $info['gold'], //金币
            'headimgurl' => $info['headimgurl'], //头像
            'client_id' => $info['client_id'], //套接字
            'seat' => $info['seat'], //座位号
            'hands' => [], //手牌
            'istuo' => 0, //托管状态 1=>0.5 2=>0.01 3=>0.05 4=>0.025 
            'handsid' => 0, //牌id
            'type' => 0, //牌型
            'callbanker' => 0, //0没有操作 叫庄状态 1没叫 2叫了
            'ishow' => 0, //是否看牌 1看牌
            'ready' => 0, //是否准备 1 准备
            'bet' => 0,
            'betArr' => [],
            'isrealy' => 0,
            'lifeTime' => 0,
        ];

        if ($info['client_id'] != '') {
            Gateway::joinGroup($info['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->PlayerInfo[$uid]['lifeTime'] = time() + rand($this->roomRule['vals']['lifeTime'][0], $this->roomRule['vals']['lifeTime'][1]);
        }

        $this->roomInfo($uid);
        if ($this->gameState == STAGE_WAIT || $this->gameState == STAGE_RES) {
            if ($info['client_id'] == '') {
                $this->AiTimer[$uid] = Timer::add(rand(AI_ACT_TIME[0], AI_ACT_TIME[1]), function () use ($uid) {
                    Timer::del($this->AiTimer[$uid]);
                    $data = AI::Msg_SRNN_Res($uid, $this->PlayerInfo[$uid]['gold'], $this->roomRule['doublescore'], $this->PlayerInfo[$uid]['lifeTime']);

                    $this->All_RECV($data);
                }, [], false);
            }
            $this->ResStateTimer($uid, TIEM_RES, 0);
        }
    }

    /**
     *  刷新房间
     *
     * @param integer $uid
     * @return void
     */
    private function roomInfo($uid)
    {
        $players = [];
        foreach ($this->PlayerInfo as $key => $val) {
            $players[$key] = [
                'gold' => $val['gold'],
                'nickname' => $val['nickname'],
                'headimgurl' => $val['headimgurl'],
                'istuo' => $val['istuo'],
                'handsnum' => count($val['hands']),
                'callbanker' => $val['callbanker'], //叫庄状态
                'ishow' => $val['ishow'], //是否看牌 1看牌
                'ready' => $val['ready'], //是否准备 1 准备
                'allWinScore' => $val['allWinScore'], //总共赢了多少
                'winScore' => $val['winScore'], //当局赢了多少 
                'seat' => $val['seat'],
                'isrealy' => $val['isrealy'],
                'bet' => $val['bet']
            ];
        }

        /**
         * 阶段消息
         */
        $msg = [];
        if ($this->gameState == STAGE_CALL_BANKER) {
            $time = TIEM_CALL_BANKER - (time() - $this->timeStamp);
            $msg = [
                'time' => $time,
                'uid' => $this->seatUid[$this->curseat],
            ];
        } elseif ($this->gameState == STAGE_BET) {
            $time = TIEM_BET - (time() - $this->timeStamp);
            $msg = [
                'time' => $time,
                'bet' => $this->PlayerInfo[$uid]['betArr'],
                'banker' => $this->banker
            ];
        } elseif ($this->gameState == STAGE_FA) {
            $time = TIEM_FA - (time() - $this->timeStamp);
            $_players = [];
            foreach ($this->PlayerInfo as $key  => $val) {
                if (!empty($val['hands'])) {
                    $_players[$key] = [
                        'hands' => $val['hands'],
                        'type' => $val['type']
                    ];
                }
            }

            $msg = [
                'time' => $time,
                'players' => $_players
            ];
        } else {
            $msg['time'] = TIEM_RES;
        }

        $res = [
            'players' => $players,
            'doublescore' => $this->roomRule['doublescore'],
            'banker' => $this->banker,
            'gameState' => $this->gameState,
            'msg' => $msg,
            'hands' => $this->PlayerInfo[$uid]['hands'],
            'level' => $this->roomRule['level'],
        ];

        Logic::SendRight($uid, 'Msg_SRNN_RoomInfo', $res);
    }

    /**
     * 游戏流程
     *
     * @param [type] $gameState
     * @return void
     */
    private function procedure($gameState, $code = true)
    {
        $this->gameState = $gameState;
        switch ($this->gameState) {
            case STAGE_CALL_BANKER:
                $this->callBankerState();
                break;
            case STAGE_BET:
                $this->BetState();
                break;
            case STAGE_FA:
                $this->FaState();
                break;
            case STAGE_RES:
                $this->ResState($code);
                break;
        }
    }

    /**
     *初始化
     * @return void
     */
    private function Init()
    {
        $this->hands = Cards::InitCard();
        $this->pnum = 0;
        $player = [];
        foreach ($this->PlayerInfo as $key => $val) {
            $this->PlayerInfo[$key]['hands'] = [];
            $this->PlayerInfo[$key]['handsid'] = 0;
            $this->PlayerInfo[$key]['type'] = 0;
            $this->PlayerInfo[$key]['callbanker'] = 0;
            $this->PlayerInfo[$key]['ishow'] = 0;
            if ($val['ready'] == 1) {
                $player[] = $key;
                $this->PlayerInfo[$key]['isrealy'] = 1;
            }

            $this->PlayerInfo[$key]['ready'] = 0;
            $this->PlayerInfo[$key]['bet'] = 0;
            $this->PlayerInfo[$key]['winScore'] = 0;
            $this->PlayerInfo[$key]['betArr'] = [];
            $this->pnum++;
        }

        $this->resdata = [];
        $this->curseat = $this->fristbanker;
        $this->playercontr =  DBInstance::GetChessCardControl($this->roomRule['gtype'], $this->roomRule['level'], $player);

        Logic::TableStatus($this->roomRule['rid'], 1);
    }

    /**
     * 通知玩家叫庄
     * @return void
     */
    private function callBankerState($code = true)
    {
        if ($code) {
            $this->Init();
        }

        $this->timeStamp = time();
        $time = TIEM_CALL_BANKER;

        if (!isset($this->seatUid[$this->curseat])) {
            $this->nextPlayer();
        }

        Logic::SendAll(
            'Msg_SRNN_CallBanker',
            [
                'time' => $time,
                'uid' => $this->seatUid[$this->curseat]
            ],
            $this->roomRule['rid']
        );

        if ($this->PlayerInfo[$this->seatUid[$this->curseat]]['client_id'] == '') {
            $this->AiTimer[$this->seatUid[$this->curseat]] = Timer::add(rand(AI_ACT_TIME[0], AI_ACT_TIME[1]), function () {
                Timer::del($this->AiTimer[$this->seatUid[$this->curseat]]);
                $data = AI::Msg_SRNN_CallBanker($this->seatUid[$this->curseat]);
                $this->All_RECV($data);
            }, [], false);
        }

        if ($this->PlayerInfo[$this->seatUid[$this->curseat]]['istuo'] > 0) {
            $time = TIEM_TOU;
        }

        $this->timer = Timer::add($time, function () {
            Timer::del($this->timer);
            $this->All_RECV(['event' => 'Msg_SRNN_Act_CallBanker', 'uid' => $this->seatUid[$this->curseat], 'data' => ['type' => 1]]);
        }, [], false);
    }

    /**
     * 下注
     *
     * @return void
     */
    private function BetState()
    {
        $this->timeStamp = time();
        $gold = $this->PlayerInfo[$this->banker]['gold'];
        $data = ['bet' => [], 'time' => TIEM_BET, 'banker' => $this->banker];
        foreach ($this->PlayerInfo as $key => $val) {
            $score = $val['gold'] > $gold ? $gold : $val['gold'];
            $_score = intval($score / ($this->pnum - 1) / 5);
            $arr = [$_score];
            foreach (BET_ARR as $key1 => $val1) {
                $arr[] = intval($_score / $val1);
            }
            $data['bet'] = $arr;

            Logic::SendRight($key, 'Msg_SRNN_Bet', $data);
            if ($key == $this->banker || $val['isrealy'] == 0) {
                continue;
            }
            if ($val['client_id'] == '') {
                $this->AiTimer[$key] = Timer::add(rand(AI_ACT_TIME[0], AI_ACT_TIME[1]), function () use ($key, $data) {
                    Timer::del($this->AiTimer[$key]);
                    $res = AI::Msg_SRNN_Bet($key, $data);
                    $this->All_RECV($res);
                }, [], false);
            }

            $this->PlayerInfo[$key]['betArr'] = $arr;

            $time = $val['istuo'] == 0 ? TIEM_BET : TIEM_TOU;
            $this->BetStateTimer($key, $time);
        }
    }
    /**
     * 下注定时器
     * @param [type] $uid
     * @param [type] $time
     * @return void
     */
    private function BetStateTimer($uid, $time)
    {
        $this->userTimer[$uid] = Timer::add($time, function () use ($uid) {
            Timer::del($this->userTimer[$uid]);
            if ($this->PlayerInfo[$uid]['istuo'] == 0) {
                $bet = min($this->PlayerInfo[$uid]['betArr']);
            } else {
                $bet = $this->PlayerInfo[$uid]['betArr'][$this->PlayerInfo[$uid]['istuo'] - 1];
            }

            $this->All_RECV(['event' => 'Msg_SRNN_Act_Bet', 'uid' => $uid, 'data' => ['bet' => $bet]]);
        }, [], false);
    }
    /**
     * 发牌
     * 
     * @return void
     */
    private function FaState()
    {
        $this->timeStamp = time();

        $players = $this->controlFa();

        Logic::SendAll(
            'Msg_SRNN_FaCards',
            [
                'players' => $players,
                'time' => TIEM_FA,
            ],
            $this->roomRule['rid']
        );

        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isrealy'] == 0) {
                continue;
            }

            if ($val['client_id'] == '') {
                $this->AiTimer[$key] = Timer::add(rand(AI_ACT_TIME[0], AI_ACT_TIME[1]), function () use ($key) {
                    Timer::del($this->AiTimer[$key]);
                    $res = AI::Msg_SRNN_FaCards($key);
                    $this->All_RECV($res);
                }, [], false);
            }

            $time = TIEM_FA;
            if ($val['istuo'] != 0) {

                $time = TIEM_TOU;
            }
            $this->FaStateTimer($key, $time);
        }
    }

    private function controlFa()
    {
        $res = [];
        $aiarr = [];
        $max = 0;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isrealy'] == 0) {
                continue;
            }
            $hands = [];
            for ($i = 0; $i < HANDS_NUM; $i++) {
                $hands[] = array_shift($this->hands);
            }

            $data = Cards::checkCattle($hands);

            $res[$data['id']] = $data;
            if ($val['client_id'] == '' && (empty($aiarr) || $aiarr['bet'] < $val['bet'])) {
                $aiarr = ['uid' => $key, 'bet' => $val['bet']];
            }

            $max = $max > $data['id'] ? $max : $data['id'];
        }
        krsort($res);
        $playerarr = $this->playercontr;
        while (!empty($data)) {
            $max = max($playerarr);
            $uids = [];
            foreach ($playerarr as $key => $val) {
                if ($val == $max) {
                    $uids[$key] = $val;
                }
            }
            $uid = array_rand($uids);
            unset($playerarr[$uid]);
            foreach ($res as $key => $val) {

                $this->PlayerInfo[$uid]['hands'] = $val['hands'];
                $this->PlayerInfo[$uid]['handsid'] = $val['id'];
                $this->PlayerInfo[$uid]['type'] = $val['type'];
                unset($res[$key]);
                break;
            }
            if (empty($res)) {
                break;
            }
        }

        $players = [];
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isrealy'] == 0) {
                continue;
            }

            $players[$key] = [
                'hands' => $this->PlayerInfo[$key]['hands'],
                'type' => $this->PlayerInfo[$key]['type']
            ];
        }
        return $players;
    }

    /**
     * 发牌定时器
     * @param [type] $uid
     * @param [type] $time
     * @return void
     */
    private function FaStateTimer($uid, $time)
    {
        $this->userTimer[$uid] = Timer::add($time, function () use ($uid) {
            Timer::del($this->userTimer[$uid]);
            $this->All_RECV(['event' => 'Msg_SRNN_Act_Show', 'uid' => $uid, 'data' => []]);
        }, [], false);
    }

    /**
     * 结算
     *
     * @return void
     */
    private function ResState()
    {
        $this->timeStamp = time();

        $bankerid = $this->PlayerInfo[$this->banker]['handsid'];

        $players = [];
        foreach ($this->PlayerInfo as $key => $val) {
            if ($key == $this->banker || $val['isrealy'] == 0) {
                continue;
            }

            $type = $this->PlayerInfo[$this->banker]['type'];
            if ($val['handsid'] > $bankerid) {
                $type = $val['type'];
            }

            $score = $val['bet'] * BEISHU_CATTLE[$type];

            if ($val['handsid'] < $bankerid) {
                $_score = -$score;

                $score = -$score;

                $score = intval($score * $this->roomRule['rebate']);
            } else {
                $_score = intval($score * $this->roomRule['rebate']);
            }

            $this->changScore($key, $_score);

            $players[$key] = [
                'score' => $_score,
                'type' => $val['type'],
                'gold' => $this->PlayerInfo[$key]['gold']
            ];

            if ($this->roomRule['level'] != 1) {
                DBInstance::IncrementUserGet($key, $_score);
            }

            $this->changScore($this->banker, -$score);
        }

        $players[$this->banker] = [
            'score' => $this->PlayerInfo[$this->banker]['winScore'],
            'type' => $this->PlayerInfo[$this->banker]['type'],
            'gold' => $this->PlayerInfo[$this->banker]['gold'],
        ];
        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($this->banker,  $this->PlayerInfo[$this->banker]['winScore']);
        }

        $data = [
            'players' => $players,
            'time' => TIEM_RES
        ];

        Logic::SendAll('Msg_SRNN_Res', $data, $this->roomRule['rid']);

        $this->resdata = $data;
        $code = true;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['client_id'] != '') {
                $code = false;
                break;
            }
        }

        foreach ($this->PlayerInfo as $key => $val) {
            $time = TIEM_RES;
            if ($val['istuo'] != 0) {
                $time = TIEM_RES_TUO;
            }

            if ($val['client_id'] == '') {
                $this->AiTimer[$key] = Timer::add(rand(5, 8), function () use ($key, $code) {
                    Timer::del($this->AiTimer[$key]);
                    if (!isset($this->PlayerInfo[$key])) {
                        return;
                    }

                    if ($code) {
                        $data = ['event' => 'Msg_SRNN_Out', 'uid' => $key, 'data' => []];
                    } else {
                        $data = AI::Msg_SRNN_Res($key, $this->PlayerInfo[$key]['gold'], $this->roomRule['doublescore'], $this->PlayerInfo[$key]['lifeTime']);
                    }

                    $this->All_RECV($data);
                }, [], false);
            }
            $this->ResStateTimer($key, $time, $val['istuo']);
        }
        // $this->nextPlayer();

        for ($i = 0; $i >= 0; $i++) {
            $this->fristbanker++;
            $count = max(array_keys($this->seatUid)) + 1;
            $this->fristbanker = $this->fristbanker >= $count ? $this->fristbanker -= $count : $this->fristbanker;
            if (isset($this->seatUid[$this->fristbanker]) && isset($this->PlayerInfo[$this->seatUid[$this->fristbanker]])) {
                break;
            }
        }

        Logic::TableStatus($this->roomRule['rid'], 0);
    }

    /**
     * 结算定时器
     * @param [type] $uid
     * @param [type] $time
     * @return void
     */
    private function ResStateTimer($uid, $time, $istuo)
    {
        $this->userTimer[$uid] = Timer::add($time, function () use ($uid, $istuo) {
            Timer::del($this->userTimer[$uid]);
            if ($istuo == 0 || $this->PlayerInfo[$uid]['gold'] < $this->roomRule['doublescore'] * OUT_BEI) {
                $this->gameState = STAGE_WAIT;
                $this->All_RECV(['event' => 'Msg_SRNN_Out', 'uid' => $uid, 'data' => []]);
            } else {
                if ($istuo > 0 && $this->PlayerInfo[$uid]['istuo'] == 0) {
                    $time = TIEM_RES - TIEM_RES_TUO;
                    $this->ResStateTimer($uid, $time, $this->PlayerInfo[$uid]['istuo']);
                } else {
                    $this->All_RECV(['event' => 'Msg_SRNN_Ready', 'uid' => $uid, 'data' => []]);
                }
            }
        }, [], false);
    }
    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_SRNN_Act_CallBanker':
                $this->Msg_SRNN_Act_CallBanker($message);
                break;
            case 'Msg_SRNN_Act_Bet':
                $this->Msg_SRNN_Act_Bet($message);
                break;
            case 'Msg_SRNN_Out':
                $this->Msg_SRNN_Out($message);
                break;
            case 'Msg_SRNN_Act_Show':
                $this->Msg_SRNN_Act_Show($message);
                break;
            case 'Msg_SRNN_Ready':
                $this->Msg_SRNN_Ready($message);
                break;
            case 'Msg_SRNN_Act_Tuo':
                $this->Msg_SRNN_Act_Tuo($message);
                break;
            default: {
                    Logic::SendError($message['uid'], $message['event'], '');
                    MyTools::msg('uid:' . $message['uid'] . '-----UnKnown!!!!!!!--------' . $message['event'], true);
                    break;
                }
        }
    }

    /**
     * 叫庄
     * @param [type] $msg
     * @return void
     */
    private function Msg_SRNN_Act_CallBanker($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['isrealy']  != 1) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if ($this->gameState != STAGE_CALL_BANKER) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if ($msg['uid'] != $this->seatUid[$this->curseat]) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if (!isset($msg['data']['type'])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据错误');
            return;
        }
        Timer::del($this->timer);

        $this->PlayerInfo[$msg['uid']]['callbanker'] = $msg['data']['type'];
        $code = false;
        Logic::SendAll('Msg_SRNN_Act_CallBanker', ['uid' => $msg['uid'], 'type' => $msg['data']['type']], $this->roomRule['rid']);

        if ($msg['data']['type'] == 1) {
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['isrealy'] == 0) {
                    continue;
                }

                if ($val['callbanker'] == 0) {
                    $code = true;
                    break;
                }
            }
        }

        if ($msg['data']['type'] != 1 || !$code) {
            $banker = $msg['uid'];
            if ($msg['data']['type'] == 1) {
                $this->nextPlayer();
                $banker = $this->seatUid[$this->curseat];
            }
            $this->banker = $banker;

            $this->procedure(STAGE_BET);
        } else {
            $this->nextPlayer();
            $this->callBankerState(false);
        }
    }

    /**
     * 玩家下注
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_SRNN_Act_Bet($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['isrealy']  != 1) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if ($this->gameState != STAGE_BET) {
            Logic::SendError($msg['uid'], 'Msg_SRNN_Act_Bet', '阶段错误');
            return;
        }

        if ($msg['uid'] == $this->banker || !isset($msg['data']['bet'])) {
            Logic::SendError($msg['uid'], 'Msg_SRNN_Act_Bet', '庄家不能下注或数据格式错误');
            return;
        }

        if (!in_array($msg['data']['bet'], $this->PlayerInfo[$msg['uid']]['betArr'])) {
            Logic::SendError($msg['uid'], 'Msg_SRNN_Act_Bet', '下注金额错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['bet'] > 0) {
            Logic::SendError($msg['uid'], 'Msg_SRNN_Act_Bet', '重复操作');
            return;
        }

        Timer::del($this->userTimer[$msg['uid']]);
        $this->PlayerInfo[$msg['uid']]['bet'] = $msg['data']['bet'];
        Logic::SendAll('Msg_SRNN_Act_Bet', ['uid' => $msg['uid'], 'bet' => $msg['data']['bet']], $this->roomRule['rid']);
        $code = true;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($this->banker == $key || $val['isrealy'] == 0) {
                continue;
            }

            if ($val['bet'] == 0) {
                $code = false;
                break;
            }
        }

        if ($code) {
            $this->procedure(STAGE_FA);
        }
    }

    /**
     * 看牌
     * @param [type] $msg
     * @return void
     */
    private function Msg_SRNN_Act_Show($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['isrealy']  != 1) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if ($this->gameState != STAGE_FA) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['ishow'] != 0) {
            Logic::SendError($msg['uid'], $msg['event'], '操作错误');
            return;
        }

        $this->PlayerInfo[$msg['uid']]['ishow'] = 1;
        $code = false;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isrealy'] == 0) {
                continue;
            }

            if ($val['ishow'] == 0) {
                $code = true;
                break;
            }
        }
        Timer::del($this->userTimer[$msg['uid']]);

        Logic::SendAll('Msg_SRNN_Act_Show', ['uid' => $msg['uid']], $this->roomRule['rid']);

        if (!$code) {
            $this->procedure(STAGE_RES);
        }
    }

    /**
     * 准备
     */
    private function Msg_SRNN_Ready($msg)
    {
        if ($this->gameState != STAGE_RES && $this->gameState != STAGE_WAIT) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['ready'] != 0) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['gold'] < $this->roomRule['doublescore'] * OUT_BEI) {
            Logic::SendError($msg['uid'], $msg['event'], '金币不足准备失败');
            $this->Msg_SRNN_Out(['uid' => $msg['uid']]);
            return;
        }

        $this->PlayerInfo[$msg['uid']]['ready'] = 1;

        Timer::del($this->userTimer[$msg['uid']]);

        Logic::SendAll('Msg_SRNN_Ready', ['uid' => $msg['uid']], $this->roomRule['rid']);

        $code = false;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['ready'] == 0) {
                $code = true;
                break;
            }
        }

        if (!$code && count($this->PlayerInfo) >= SRNN_PNUM ||$this->disRoom == 1) {
            if ($this->disRoom == 1) {
                foreach ($this->PlayerInfo as $key => $val) {
                    if (isset($this->AiTimer[$key])) {
                        Timer::del($this->AiTimer[$key]);
                    }

                    if (isset($this->userTimer[$key])) {
                        Timer::del($this->userTimer[$key]);
                    }
                    $this->All_RECV(['event' => 'Msg_SRNN_Out', 'uid' => $key]);
                }

                return;
            }
            $this->procedure(STAGE_CALL_BANKER);
        }
    }

    /**
     * 托管
     */
    private function Msg_SRNN_Act_Tuo($msg)
    {
        if (!isset($msg['data']['istuo'])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }

        $arr = [0, 1, 2, 3, 4];
        if (!in_array($msg['data']['istuo'], $arr)) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }

        if (!isset($msg['data']['istuo'])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['istuo'] == 0) {
            if ($msg['data']['istuo'] == 0) {
                Logic::SendError($msg['uid'], $msg['event'], '当前并未托管');
                return;
            }
        } else {
            if ($msg['data']['istuo'] > 0) {
                Logic::SendError($msg['uid'], $msg['event'], '当前正在托管');
                return;
            }
        }

        $this->PlayerInfo[$msg['uid']]['istuo'] = $msg['data']['istuo'];
        Logic::SendAll($msg['event'], ['uid' => $msg['uid'], 'istuo' => $msg['data']['istuo']], $this->roomRule['rid']);

        if ($msg['data']['istuo'] > 0) {
            if ($this->gameState == STAGE_CALL_BANKER && $this->PlayerInfo[$msg['uid']]['isrealy'] == 1) {
                $this->All_RECV(['event' => 'Msg_SRNN_Act_CallBanker', 'uid' => $msg['uid'], 'data' => ['type' => 1]]);
            } elseif ($this->gameState == STAGE_BET && $msg['uid'] != $this->banker && $this->PlayerInfo[$msg['uid']]['isrealy'] == 1) {
                $bet = $this->PlayerInfo[$msg['uid']]['betArr'][$msg['data']['istuo'] - 1];
                $this->All_RECV(['event' => 'Msg_SRNN_Act_Bet', 'uid' => $msg['uid'], 'data' => ['bet' => $bet]]);
            } elseif ($this->gameState == STAGE_FA && $this->PlayerInfo[$msg['uid']]['ishow'] == 0 && $this->PlayerInfo[$msg['uid']]['isrealy'] == 1) {
                $this->All_RECV(['event' => 'Msg_SRNN_Act_Show', 'uid' => $msg['uid'], 'data' => []]);
            } elseif (($this->gameState == STAGE_RES || $this->gameState == STAGE_WAIT) && $this->PlayerInfo[$msg['uid']]['ready'] == 0) {
                $this->All_RECV(['event' => 'Msg_SRNN_Ready', 'uid' => $msg['uid'], 'data' => []]);
            }
        }
    }
    /**
     * 玩家退出房间
     * @param [type] $msg
     * @return void
     */
    private function Msg_SRNN_Out($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['isrealy'] == 1 && $this->gameState != STAGE_RES && $this->gameState != STAGE_WAIT) {
            Logic::SendError($msg['uid'], 'Msg_SRNN_Out', '正在游戏中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_SRNN_Out', ['uid' => $msg['uid'], 'gold' => $gold], $this->roomRule['rid']);

        if (isset($this->userTimer[$msg['uid']])) {
            Timer::del($this->userTimer[$msg['uid']]);
        }

        unset($this->seatUid[$this->PlayerInfo[$msg['uid']]['seat']]);
        unset($this->PlayerInfo[$msg['uid']]);

        $data = ['uid' => $msg['uid'], 'rid' => $this->roomRule['rid']];
        Logic::QuitRoom($data);
        if (count($this->PlayerInfo) <= 0) {
            $this->OldRoom();
        }

        if ($this->gameState == STAGE_WAIT || $this->gameState == STAGE_RES) {
            $code = false;
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['ready'] == 0) {
                    $code = true;
                    break;
                }
            }

            if (!$code && count($this->PlayerInfo) >= SRNN_PNUM) {
                if ($this->disRoom == 1) {
                    // foreach ($this->PlayerInfo as $key => $val) {
                    //     if (isset($this->AiTimer[$key])) {
                    //         Timer::del($this->AiTimer[$key]);
                    //     }

                    //     if (isset($this->userTimer[$key])) {
                    //         Timer::del($this->userTimer[$key]);
                    //     }
                    //     $this->All_RECV(['event' => 'Msg_SRNN_Out', 'uid' => $key]);
                    // }
                    return;
                }
                $this->procedure(STAGE_CALL_BANKER);
            }
        }
    }

    /**
     * 分数变化
     * @param int $uid
     * @param int $score
     * @return void
     */
    private function changScore($uid, $score)
    {
        $this->PlayerInfo[$uid]['winScore'] += $score;
        $this->PlayerInfo[$uid]['allWinScore'] += $score;
        $this->PlayerInfo[$uid]['gold'] += $score;

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $uid, $score);
        }

        if ($score > 0) {
            DBInstance::IncrementWinPoint($uid, $score);
        }

        if ($this->PlayerInfo[$uid]['client_id'] != '' && abs($this->playercontr[$uid]) != 2) {
            Logic::InsertProfit($this->roomRule['level'], $score);
        }
    }

    /**
     * 下一个玩家
     * @return void
     */
    private function nextPlayer()
    {
        for ($i = 0; $i >= 0; $i++) {
            $this->curseat++;
            $count = max(array_keys($this->seatUid)) + 1;
            $this->curseat = $this->curseat >= $count ? $this->curseat -= $count : $this->curseat;
            if (isset($this->seatUid[$this->curseat]) && isset($this->PlayerInfo[$this->seatUid[$this->curseat]])  && $this->PlayerInfo[$this->seatUid[$this->curseat]]['isrealy'] == 1) {
                break;
            }
        }
    }

    /**
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        $olddata = [
            'rid' =>  $this->roomRule['rid'],
            'win' => [],
            'palyers' => $this->PlayerInfo,
            'result' => [
                'uid' => []
            ], //结算消息
            'gtype' => $this->roomRule['gtype'],
        ];
        Logic::RoomOld($olddata);
        return;
    }
    /**
     * 玩家重连
     * @param string
     * @param int
     */
    public function UserOnline($client_id, $uid)
    {
        if ($client_id != '') {
            $this->PlayerInfo[$uid]['client_id'] = $client_id;
            Gateway::joinGroup($client_id, 'ROOM:' . $this->roomRule['rid']);

            $this->roomInfo($uid);
        }
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
        if ($this->PlayerInfo[$uid]['isrealy'] == 0 || $this->gameState == STAGE_RES || $this->gameState == STAGE_WAIT) {
            $this->All_RECV(['event' => 'Msg_SRNN_Out', 'uid' => $uid, 'data' => []]);
        }
        return;
    }

    /**
     * 新增玩家
     *
     * @param [type] $msg
     * @return void
     */
    public function EnterRoom($msg)
    {
        Logic::SendAll('Msg_SRNN_Add', [
            'uid' => $msg['uid'],
            'nickname' => $msg['nickname'],
            'headimgurl' => $msg['headimgurl'],
            'gold' => $msg['gold'],
            'seat' => $msg['seat']
        ], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);
    }


    /**
     * 玩家金钱变化
     *
     * @param [type] $msg
     * @return void
     */
    public function ChangeGold($msg)
    {
        $this->PlayerInfo[$msg['uid']]['gold'] = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_SRNN_ChangGold', ['uid' => $msg['uid'], 'gold' => $this->PlayerInfo[$msg['uid']]['gold']], $this->roomRule['rid']);
    }

    /**
     * 强制解散房间
     *
     * @param [type] $msg
     * @return void
     */
    public function DisRoom($msg)
    {
        $this->disRoom = 1;
        if ($this->gameState == STAGE_WAIT || $this->gameState == STAGE_RES) {
            Timer::del($this->timer);
            foreach ($this->PlayerInfo as $key => $val) {
                Timer::del($this->userTimer[$key]);
            }

            foreach ($this->PlayerInfo as $key => $val) {
                if (isset($this->AiTimer[$key])) {
                    Timer::del($this->AiTimer[$key]);
                }

                if (isset($this->userTimer[$key])) {
                    Timer::del($this->userTimer[$key]);
                }
                $this->All_RECV(['event' => 'Msg_SRNN_Out', 'uid' => $key]);
            }
        }
    }
}

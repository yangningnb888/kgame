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
define('TIEM_RES_TUO', 4); //结算时间

define('BET_ARR', [0.2, 0.1, 0.05, 0.025]);

define('OUT_BEI', 5);

define('ERNN_PNUM', 2); //玩家人数

define('HANDS_NUM', 5); //手牌张数

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

    private $disRoom = 0;

    private $hands = []; //牌堆

    private $userTimer = [];

    private $bet = 0; //当前下注数

    private $betArr = []; //下注数组

    private $fristbanker = 0; //下局叫庄家

    private $AItimer = []; //AI定时器

    private $winneruid = 0;
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
            'lifeTime' => 0,
            'control' => 0
        ];

        if ($info['client_id'] != '') {
            Gateway::joinGroup($info['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->PlayerInfo[$uid]['lifeTime'] = time() + rand($this->roomRule['vals']['lifeTime'][0], $this->roomRule['vals']['lifeTime'][1]);
        }

        $this->roomInfo($uid);

        if ($this->gameState == STAGE_WAIT || $this->gameState == STAGE_RES) {
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
                'bet' => $this->betArr,
                'banker' => $this->banker
            ];
        } elseif ($this->gameState == STAGE_FA) {
            $time = TIEM_FA - (time() - $this->timeStamp);
            $_players = [];
            foreach ($this->PlayerInfo as $key  => $val) {
                $_players[$key] = [
                    'hands' => $val['hands']
                ];
            }

            $msg = [
                'time' => $time,
                'players' => $_players
            ];
        } else {
            $msg['time'] = TIEM_RES;
        }

        // elseif ($this->gameState == STAGE_RES) {
        //     $time = TIEM_RES - (time() - $this->timeStamp);
        //     $msg =  $this->resdata;
        //     $msg['time'] = $time;
        // }

        $res = [
            'players' => $players,
            'doublescore' => $this->roomRule['doublescore'],
            'banker' => $this->banker,
            'gameState' => $this->gameState,
            'msg' => $msg,
            'hands' => $this->PlayerInfo[$uid]['hands'],
            'bet' => $this->bet,
            'level' => $this->roomRule['level'],
        ];

        Logic::SendRight($uid, 'Msg_ERNN_RoomInfo', $res);
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
        foreach ($this->userTimer as $key => $val) {
            Timer::del($this->userTimer[$key]);
        }
        Timer::del($this->timer);

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
        $player = [];
        foreach ($this->PlayerInfo as $key => $val) {
            $this->PlayerInfo[$key]['hands'] = [];
            $this->PlayerInfo[$key]['handsid'] = 0;
            $this->PlayerInfo[$key]['type'] = 0;
            $this->PlayerInfo[$key]['callbanker'] = 0;
            $this->PlayerInfo[$key]['ishow'] = 0;
            $this->PlayerInfo[$key]['ready'] = 0;
            $this->PlayerInfo[$key]['control'] = 0;
            $player[] = $key;
        }

        $this->curseat = $this->fristbanker;
        $this->banker = $this->seatUid[$this->curseat];
        $this->resdata = [];
        $players =  DBInstance::GetChessCardControl($this->roomRule['gtype'], $this->roomRule['level'], $player);
        $max = max($players);
        $uids = [];
        foreach ($players as $key => $val) {
            if ($val == $max) {
                $uids[$key] = $val;
            }
            $this->PlayerInfo[$key]['control'] = $val;
        }

        $this->winneruid = array_rand($uids);

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
        Logic::SendAll(
            'Msg_ERNN_CallBanKer',
            [
                'time' => $time,
                'uid' => $this->seatUid[$this->curseat]
            ],
            $this->roomRule['rid']
        );

        if ($this->PlayerInfo[$this->seatUid[$this->curseat]]['client_id'] == '') {
            $this->AItimer[$this->seatUid[$this->curseat]] = Timer::add(rand(1, 2), function () {
                Timer::del($this->AItimer[$this->seatUid[$this->curseat]]);
                $data = AI::Msg_ERNN_CallBanKer($this->seatUid[$this->curseat]);
                $this->All_RECV($data);
            }, [], false);
        }

        if ($this->PlayerInfo[$this->seatUid[$this->curseat]]['istuo'] > 0) {
            $time = TIEM_TOU;
        }

        $this->timer = Timer::add($time, function () {
            Timer::del($this->timer);
            $this->All_RECV(['event' => 'Msg_ERNN_Act_CallBanker', 'uid' => $this->seatUid[$this->curseat], 'data' => ['type' => 1]]);
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

        $arr = [];
        $tuo = 0;
        $uid = 0;
        foreach ($this->PlayerInfo as $key => $val) {
            $arr[] = $val['gold'];
            if ($key != $this->banker) {
                $tuo = $val['istuo'];
                $uid = $key;
            }
        }

        $min = min($arr);
        $this->betArr = [];

        foreach (BET_ARR as $key => $val) {
            $this->betArr[] = intval($val * $min);
        }

        $time = TIEM_BET;
        Logic::SendAll('Msg_ERNN_Bet', ['bet' =>  $this->betArr, 'time' => $time, 'banker' => $this->banker], $this->roomRule['rid']);
        if ($tuo != 0) {
            $time = TIEM_TOU;
        }
        foreach ($this->PlayerInfo as $key => $val) {
            if ($key != $this->banker && $val['client_id'] == '') {
                $this->AItimer[$key] = Timer::add(rand(1, 2), function () use ($key) {
                    Timer::del($this->AItimer[$key]);
                    $data = AI::Msg_ERNN_Bet(['bet' => $this->betArr, 'banker' => $this->banker], $key);
                    $this->All_RECV($data);
                }, [], false);
            }
        }

        $this->timer = Timer::add($time, function () use ($tuo, $uid) {
            Timer::del($this->timer);
            $score = min($this->betArr);

            if ($tuo != 0) {
                $score  = $this->betArr[$tuo - 1];
            }
            // 调用下注            
            $this->All_RECV(['event' => 'Msg_ERNN_Act_Bet', 'uid' => $uid, 'data' => ['bet' => $score]]);
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
        // foreach ($this->PlayerInfo as $key => $val) {
        //     for ($i = 0; $i < HANDS_NUM; $i++) {
        //         $this->PlayerInfo[$key]['hands'][] = array_shift($this->hands);
        //     }
        //     $data = Cards::checkCattle($this->PlayerInfo[$key]['hands']);
        //     $this->PlayerInfo[$key]['hands'] = $data['hands'];
        //     $this->PlayerInfo[$key]['type'] = $data['type'];

        //     $players[$key] = [
        //         'hands' => $this->PlayerInfo[$key]['hands'],
        //     ];
        // }

        Logic::SendAll(
            'Msg_ERNN_FaCards',
            [
                'players' => $players,
                'time' => TIEM_FA,
            ],
            $this->roomRule['rid']
        );
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['client_id'] == '') {
                $this->AItimer[$key] = Timer::add(rand(1, 2), function () use ($key) {
                    Timer::del($this->AItimer[$key]);
                    $data = AI::Msg_ERNN_FaCards($key);
                    $this->All_RECV($data);
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
        $data = [];
        $max = 0;
        foreach ($this->PlayerInfo as $key => $val) {
            $hands = [];
            for ($i = 0; $i < HANDS_NUM; $i++) {
                $hands[] = array_shift($this->hands);
            }

            $_data = Cards::checkCattle($hands);
            if ($_data['id'] > $max) {
                $max = $_data['id'];
            }

            $data[$_data['id']] =  ['hands' => $_data['hands'], 'type' => $_data['type']];
        }

        $players = [];
        $this->PlayerInfo[$this->winneruid]['handsid'] = $max;
        $this->PlayerInfo[$this->winneruid]['hands'] = $data[$max]['hands'];
        $this->PlayerInfo[$this->winneruid]['type'] = $data[$max]['type'];
        unset($data[$max]);

        foreach ($this->PlayerInfo as $key => $val) {
            if ($key != $this->winneruid) {
                foreach ($data as $key1 => $val1) {
                    $this->PlayerInfo[$key]['handsid'] = $key1;
                    $this->PlayerInfo[$key]['hands'] = $val1['hands'];
                    $this->PlayerInfo[$key]['type'] = $val1['type'];
                    unset($data[$key1]);
                }
            }
            $players[$key] = [
                'hands' => $this->PlayerInfo[$key]['hands'],
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
            $this->All_RECV(['event' => 'Msg_ERNN_Act_Show', 'uid' => $uid, 'data' => []]);
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
        $res = ['uid' => 0, 'type' => 0, 'id' => 0];
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['handsid'] > $res['id']) {
                $res = [
                    'uid' => $key,
                    'type' => $val['type'],
                    'id' => $val['handsid']
                ];
            }
        }
        $this->winner = $this->PlayerInfo[$res['uid']]['seat'];

        $beishu = BEISHU_CATTLE[$res['type']];
        $score = $beishu * $this->bet;
        $players = [];

        foreach ($this->PlayerInfo as $key => $val) {
            $_score = -$score;
            if ($key == $res['uid']) {
                $_score = $score;
                if ($val['client_id'] != '' && abs($val['control']) != 2) {
                    Logic::InsertProfit($this->roomRule['level'], $_score);
                }
                $_score = intval($_score * $this->roomRule['rebate']);
            }

            if ($_score < 0 && $val['client_id'] != '' && abs($val['control']) != 2) {
                Logic::InsertProfit($this->roomRule['level'], $_score);
            }

            $this->changScore($key, $_score);

            $players[$key] = [
                'score' => $_score,
                'type' => $val['type'],
                'gold' => $this->PlayerInfo[$key]['gold'],
            ];

            if ($this->roomRule['level'] != 1) {
                DBInstance::IncrementUserGet($key, $_score);
            }
        }

        $data = [
            'players' => $players,
            'time' => TIEM_RES
        ];
        Logic::SendAll('Msg_ERNN_Res', $data, $this->roomRule['rid']);

        $this->resdata = $data;

        foreach ($this->PlayerInfo as $key => $val) {
            $time = TIEM_RES;
            if ($val['istuo'] != 0) {
                $time = TIEM_RES_TUO;
            }
            if ($val['gold'] < $this->roomRule['doublescore'] * OUT_BEI) {
                $time = 4;
            }

            if ($val['client_id'] == '') {
                $this->AItimer[$key] = Timer::add(rand(4, 6), function () use ($key) {
                    Timer::del($this->AItimer[$key]);
                    if (count($this->PlayerInfo) == 1 || $this->PlayerInfo[$key]['lifeTime'] < time()) {
                        $data =  AI::Msg_ERNN_Out($key);
                    } else {
                        $data =  AI::Msg_ERNN_Add($key);
                    }
                    $this->All_RECV($data);
                }, [], false);
            }

            $this->ResStateTimer($key, $time, $val['istuo']);
        }

        $this->fristbanker++;
        $count = count($this->PlayerInfo);
        $this->fristbanker = $this->fristbanker >= $count ? $this->fristbanker -= $count : $this->fristbanker;

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
                $this->All_RECV(['event' => 'Msg_ERNN_Out', 'uid' => $uid, 'data' => []]);
            } else {
                if ($istuo > 0 && $this->PlayerInfo[$uid]['istuo'] == 0) {
                    $time = TIEM_RES - TIEM_RES_TUO;
                    $this->ResStateTimer($uid, $time, $this->PlayerInfo[$uid]['istuo']);
                } else {
                    $this->All_RECV(['event' => 'Msg_ERNN_Ready', 'uid' => $uid, 'data' => []]);
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
            case 'Msg_ERNN_Act_CallBanker':
                $this->Msg_ERNN_Act_CallBanker($message);
                break;
            case 'Msg_ERNN_Act_Bet':
                $this->Msg_ERNN_Act_Bet($message);
                break;
            case 'Msg_ERNN_Out':
                $this->Msg_ERNN_Out($message);
                break;
            case 'Msg_ERNN_Act_Show':
                $this->Msg_ERNN_Act_Show($message);
                break;
            case 'Msg_ERNN_Ready':
                $this->Msg_ERNN_Ready($message);
                break;
            case 'Msg_ERNN_Act_Tuo':
                $this->Msg_ERNN_Act_Tuo($message);
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
    private function Msg_ERNN_Act_CallBanker($msg)
    {
        if ($this->gameState != STAGE_CALL_BANKER) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if ($msg['uid'] != $this->seatUid[$this->curseat]) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if (!isset($msg['data']['type'])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }
        Timer::del($this->timer);

        $this->PlayerInfo[$msg['uid']]['callbanker'] = $msg['data']['type'];
        $code = false;
        Logic::SendAll('Msg_ERNN_Act_CallBanker', ['uid' => $msg['uid'], 'type' => $msg['data']['type']], $this->roomRule['rid']);

        if ($msg['data']['type'] == 1) {
            foreach ($this->PlayerInfo as $key => $val) {
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
    private function Msg_ERNN_Act_Bet($msg)
    {
        if ($this->gameState != STAGE_BET) {
            Logic::SendError($msg['uid'], 'Msg_ERNN_Act_Bet', '阶段错误');
            return;
        }

        if ($msg['uid'] == $this->banker || !isset($msg['data']['bet'])) {
            Logic::SendError($msg['uid'], 'Msg_ERNN_Act_Bet', '庄家不能下注或者数据格式错误');
            return;
        }

        if (!in_array($msg['data']['bet'], $this->betArr)) {
            Logic::SendError($msg['uid'], 'Msg_ERNN_Act_Bet', '下注金额错误');
            return;
        }

        $this->bet = $msg['data']['bet'];
        Logic::SendAll('Msg_ERNN_Act_Bet', ['uid' => $msg['uid'], 'bet' => $msg['data']['bet']], $this->roomRule['rid']);

        Timer::del($this->timer);
        $this->procedure(STAGE_FA);
    }

    /**
     * 看牌
     * @param [type] $msg
     * @return void
     */
    private function Msg_ERNN_Act_Show($msg)
    {
        if ($this->gameState != STAGE_FA) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['ishow'] != 0) {
            Logic::SendError($msg['uid'], $msg['event'], '已经看过牌');
            return;
        }
        $this->PlayerInfo[$msg['uid']]['ishow'] = 1;
        $code = false;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['ishow'] == 0) {
                $code = true;
                break;
            }
        }
        Timer::del($this->userTimer[$msg['uid']]);

        Logic::SendAll('Msg_ERNN_Act_Show', ['uid' => $msg['uid']], $this->roomRule['rid']);

        if (!$code) {
            $this->procedure(STAGE_RES);
        }
    }

    private function Msg_ERNN_Ready($msg)
    {
        if ($this->gameState != STAGE_RES && $this->gameState != STAGE_WAIT) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['ready'] != 0) {
            Logic::SendError($msg['uid'], $msg['event'], '已经准备');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['gold'] < $this->roomRule['doublescore'] * OUT_BEI) {
            Logic::SendError($msg['uid'], $msg['event'], '金币不足');
            $this->Msg_ERNN_Out(['uid' => $msg['uid']]);
            return;
        }

        $this->PlayerInfo[$msg['uid']]['ready'] = 1;
        if (isset($this->userTimer[$msg['uid']])) {
            Timer::del($this->userTimer[$msg['uid']]);
        }

        Logic::SendAll('Msg_ERNN_Ready', ['uid' => $msg['uid']], $this->roomRule['rid']);

        $code = false;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['ready'] == 0) {
                $code = true;
                break;
            }
        }

        if (!$code && count($this->PlayerInfo) >= ERNN_PNUM || $this->disRoom == 1) {
            if ($this->disRoom == 1) {
                foreach ($this->PlayerInfo as $key => $val) {
                    $this->All_RECV(['event' => 'Msg_ERNN_Out', 'uid' => $key]);
                }
                $this->OldRoom();
                return;
            }
            $this->procedure(STAGE_CALL_BANKER);
        }
    }
    /**
     * 玩家退出房间
     * @param [type] $msg
     * @return void
     */
    private function Msg_ERNN_Out($msg)
    {
        if ($this->gameState != STAGE_RES && $this->gameState != STAGE_WAIT) {
            Logic::SendError($msg['uid'], 'Msg_ERNN_Out', '游戏正在进行中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_ERNN_Out', ['uid' => $msg['uid'], 'gold' => $gold], $this->roomRule['rid']);

        unset($this->PlayerInfo[$msg['uid']]);

        if (isset($this->userTimer[$msg['uid']])) {
            Timer::del($this->userTimer[$msg['uid']]);
        }

        if (isset($this->AItimer[$msg['uid']])) {
            Timer::del($this->AItimer[$msg['uid']]);
        }

        $data = ['uid' => $msg['uid'], 'rid' => $this->roomRule['rid']];
        Logic::QuitRoom($data);

        if (count($this->PlayerInfo) <= 0) {
            $this->OldRoom();
        }
    }

    private function Msg_ERNN_Act_Tuo($msg)
    {
        if (!isset($msg['data']['istuo'])) {
            Logic::SendError($msg['uid'], $msg['event'], '缺少参数');
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
            if ($this->gameState == STAGE_CALL_BANKER &&  $this->PlayerInfo[$msg['uid']]['callbanker'] < 0) {
                $this->All_RECV(['event' => 'Msg_ERNN_Act_CallBanker', 'uid' => $this->seatUid[$this->curseat], 'data' => ['type' => 1]]);
            } elseif ($this->gameState == STAGE_BET && $msg['uid'] != $this->banker && $this->bet <= 0) {
                $score  = $this->betArr[$msg['data']['istuo'] - 1];
                $this->All_RECV(['event' => 'Msg_ERNN_Act_Bet', 'uid' => $msg['uid'], 'data' => ['bet' => $score]]);
            } elseif ($this->gameState == STAGE_FA && $this->PlayerInfo[$msg['uid']]['ishow'] == 0) {
                $this->All_RECV(['event' => 'Msg_ERNN_Act_Show', 'uid' => $msg['uid'], 'data' => []]);
            } elseif (($this->gameState == STAGE_RES || $this->gameState == STAGE_WAIT) && $this->PlayerInfo[$msg['uid']]['ready'] == 0) {
                $this->All_RECV(['event' => 'Msg_ERNN_Ready', 'uid' => $msg['uid'], 'data' => []]);
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
        $this->PlayerInfo[$uid]['winScore'] = $score;
        $this->PlayerInfo[$uid]['allWinScore'] += $score;
        $this->PlayerInfo[$uid]['gold'] += $score;

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $uid, $score);
        }

        if ($score > 0) {
            DBInstance::IncrementWinPoint($uid, $score);
        }
    }

    /**
     * 下一个玩家
     * @return void
     */
    private function nextPlayer()
    {
        $this->curseat++;
        $count = count($this->PlayerInfo);
        $this->curseat = $this->curseat >= $count ? $this->curseat -= $count : $this->curseat;
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
        if ($this->gameState == STAGE_RES || $this->gameState == STAGE_WAIT) {
            $this->All_RECV(['event' => 'Msg_ERNN_Out', 'uid' => $uid, 'data' => []]);
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
        Logic::SendAll('Msg_ERNN_Add', [
            'uid' => $msg['uid'],
            'nickname' => $msg['nickname'],
            'headimgurl' => $msg['headimgurl'],
            'gold' => $msg['gold']
        ], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);
        if ($msg['client_id'] == '') {
            $this->AItimer[$msg['uid']] = Timer::add(rand(1, 2), function () use ($msg) {
                Timer::del($this->AItimer[$msg['uid']]);
                $data =  AI::Msg_ERNN_Add($msg['uid']);
                // if (count($this->PlayerInfo) == 1) {
                //     $data =  AI::Msg_ERNN_Out($msg['uid']);
                // } else {
                //     $data =  AI::Msg_ERNN_Add($msg['uid']);
                // }
                $this->All_RECV($data);
            }, [], false);
        }
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
        Logic::SendAll('Msg_ERNN_ChangGold', ['uid' => $msg['uid'], 'gold' => $this->PlayerInfo[$msg['uid']]['gold']], $this->roomRule['rid']);
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
                $this->All_RECV(['event' => 'Msg_ERNN_Out', 'uid' => $key]);
            }
            $this->OldRoom();
        }
    }
}

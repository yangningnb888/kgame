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
define('TIEM_RES', 10); //结算时间
define('TIEM_TOU', 2); //托管

define('QZNN_PNUM', 2); //玩家人数

define('BET_ALL_ARR', [5, 10, 15, 20]); //下注数组

define('CALL_BANKER_ARR', [0, 1, 2, 4]); //叫庄数组

class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $gameState = STAGE_WAIT; //游戏状态

    private $banker = 0; //庄家

    private  $timer = 0; //游戏流程时间戳

    private $timeStamp = 0; //时间戳

    private $seatUid = [];

    private $hands = []; //牌堆

    private $userTimer = [];

    private $disRoom = 0;

    private $playercontr = [];

    private $AiTimer = [];
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
            'istuo' => 0, //没有操作的次数托管
            'handsid' => 0, //牌id
            'type' => 0, //牌型
            'callbanker' => -1, //-1没有操作 0不叫  1一倍 2两倍 4 四倍
            'ishow' => 0, //是否看牌 1看牌
            'bet' => 0,
            'isrealy' => 0,
            'lifeTime' => 0
        ];

        if ($info['client_id'] != '') {
            Gateway::joinGroup($info['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->PlayerInfo[$uid]['lifeTime'] = time() + rand($this->roomRule['vals']['lifeTime'][0], $this->roomRule['vals']['lifeTime'][1]);
        }

        $this->roomInfo($uid);
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
                'handsnum' => count($val['hands']),
                'callbanker' => $val['callbanker'], //叫庄状态
                'ishow' => $val['ishow'], //是否看牌 1看牌
                'allWinScore' => $val['allWinScore'], //总共赢了多少
                'winScore' => $val['winScore'], //当局赢了多少
                'isrealy' => $val['isrealy'],
                'bet' => $val['bet'],
                'seat' => $val['seat']
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
                'CallBankerArr' => CALL_BANKER_ARR
            ];
        } elseif ($this->gameState == STAGE_BET) {
            $time = TIEM_BET - (time() - $this->timeStamp);
            $msg = [
                'time' => $time,
                'banker' => $this->banker
            ];
        } elseif ($this->gameState == STAGE_FA) {
            $time = TIEM_FA - (time() - $this->timeStamp);
            $_players = [];
            foreach ($this->PlayerInfo as $key  => $val) {
                if ($val['isrealy'] == 0) {
                    continue;
                }

                $_players[$key] = [
                    'hands' => $val['hands'],
                    'type' => $val['type']
                ];
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
            'hands' => $this->PlayerInfo[$uid]['hands']
        ];

        Logic::SendRight($uid, 'Msg_QZNN_RoomInfo', $res);
    }

    /**
     * 游戏流程
     *
     * @param [type] $gameState
     * @return void
     */
    private function procedure($gameState)
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
                $this->ResState();
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
            $this->PlayerInfo[$key]['callbanker'] = -1;
            $this->PlayerInfo[$key]['ishow'] = 0;
            $this->PlayerInfo[$key]['bet'] = 0;
            $this->PlayerInfo[$key]['winScore'] = 0;
            $this->PlayerInfo[$key]['istuo'] = 0;
            $this->PlayerInfo[$key]['isrealy'] = 1;
            $player[] = $key;
        }

        $this->banker = 0;

        $this->playercontr =  DBInstance::GetChessCardControl($this->roomRule['gtype'], $this->roomRule['level'], $player);
        Logic::TableStatus($this->roomRule['rid'], 1);
    }

    /**
     * 通知玩家叫庄
     * @return void
     */
    private function callBankerState()
    {
        $this->Init();

        $this->timeStamp = time();
        Logic::SendAll('Msg_QZNN_CallBanker', ['time' => TIEM_CALL_BANKER, 'CallBankerArr' => CALL_BANKER_ARR], $this->roomRule['rid']);
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['client_id'] != '' || $val['isrealy'] == 0) {
                continue;
            }
            $this->AiTimer[$key] = Timer::add(rand(1, 3), function () use ($key) {
                Timer::del($this->AiTimer[$key]);

                $data = AI::Msg_QZNN_CallBanker($key);
                $this->All_RECV($data);
            }, [], false);
        }
        $this->timer = Timer::add(TIEM_CALL_BANKER, function () {
            Timer::del($this->timer);
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['isrealy'] == 0) {
                    continue;
                }
                if ($val['callbanker'] < 0) {
                    $this->PlayerInfo[$key]['istuo']++;

                    $this->All_RECV(['event' => 'Msg_QZNN_Act_CallBanker', 'uid' => $key, 'data' => ['type' => 0]]);
                }
            }
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
        $data = ['bet' => BET_ALL_ARR, 'time' => TIEM_BET, 'banker' => $this->banker];

        Logic::SendAll('Msg_QZNN_Bet', $data, $this->roomRule['rid']);
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['client_id'] != '' || $val['isrealy'] == 0 || $key == $this->banker) {
                continue;
            }
            $this->AiTimer[$key] = Timer::add(rand(1, 3), function () use ($key) {
                Timer::del($this->AiTimer[$key]);

                $data = AI::Msg_QZNN_Bet($key);
                $this->All_RECV($data);
            }, [], false);
        }

        $this->timer = Timer::add(TIEM_BET, function () {
            Timer::del($this->timer);
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['isrealy'] == 0 || $this->banker == $key) {
                    continue;
                }

                if ($val['bet'] <= 0) {
                    $this->PlayerInfo[$key]['istuo']++;
                    $this->All_RECV(['event' => 'Msg_QZNN_Act_Bet', 'uid' => $key, 'data' => ['bet' => min(BET_ALL_ARR)]]);
                }
            }
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
        $this->controlFa();

        $players = [];
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isrealy'] == 0) {
                continue;
            }

            $players[$key] = [
                'hands' => $val['hands'],
                'type' => $val['type']
            ];
        }

        Logic::SendAll('Msg_QZNN_FaCards', ['players' => $players, 'time' => TIEM_FA], $this->roomRule['rid']);

        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['client_id'] != '' || $val['isrealy'] == 0) {
                continue;
            }
            $this->AiTimer[$key] = Timer::add(rand(1, 3), function () use ($key) {
                Timer::del($this->AiTimer[$key]);

                $data = AI::Msg_QZNN_FaCards($key);
                $this->All_RECV($data);
            }, [], false);
        }

        $this->timer = Timer::add(TIEM_FA, function () {
            Timer::del($this->timer);

            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['isrealy'] == 0) {
                    continue;
                }

                if ($val['ishow'] == 0) {
                    $this->PlayerInfo[$key]['istuo']++;
                    $this->All_RECV(['event' => 'Msg_QZNN_Act_Show', 'uid' => $key, 'data' => []]);
                }
            }
        }, [], false);
    }

    /**
     * 场控发牌
     * @return void
     */
    private function controlFa()
    {
        $res = [];
        $aiarr = ['uid' => 0, 'bet' => 0];
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isrealy'] == 0) {
                continue;
            }

            if ($val['client_id'] == '' && $val['bet'] > $aiarr['bet']) {
                $aiarr = ['uid' => $key, 'bet' => $val['bet']];
            }

            $hands = [];
            for ($i = 0; $i < HANDS_NUM; $i++) {
                $hands[] = array_shift($this->hands);
            }
            $data = Cards::checkCattle($hands);
            $res[$data['id']] = $data;
        }

        krsort($res);
        $playerarr = $this->playercontr;

        while (!empty($res)) {
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
    }

    /**
     * 结算
     *
     * @return void
     */
    private function ResState()
    {
        $this->timeStamp = time();

        $players = $this->checkres();

        $data = [
            'players' => $players,
            'time' => TIEM_RES
        ];

        Logic::SendAll('Msg_QZNN_Res', $data, $this->roomRule['rid']);

        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['istuo'] >= 2 || $val['gold'] < max(BET_ALL_ARR) * $this->roomRule['doublescore'] * max(BEISHU_CATTLE)) {
                $time = TIEM_RES;
                if ($val['istuo'] >= 2) {
                    Logic::SendRight($key, 'Msg_QZNN_DelayOut', ['time' => TIEM_RES]);
                }
                $this->ResStateTimer($key, $time);
            }

            if ($val['client_id'] == '' && $val['lifeTime'] < time()) {
                $this->AiTimer[$key] =  Timer::add(rand(3, 5), function () use ($key) {
                    Timer::del($this->AiTimer[$key]);
                    $this->All_RECV(['event' => 'Msg_QZNN_Out', 'uid' => $key]);
                }, [], false);
            }
        }

        $this->timer = Timer::add(TIEM_RES, function () {
            Timer::del($this->timer);

            if (count($this->PlayerInfo) >= QZNN_PNUM || $this->disRoom == 1) {
                if ($this->disRoom == 1) {
                    $this->disroomfun();
                    return;
                }

                $this->procedure(STAGE_CALL_BANKER);
            } else {
                $this->gameState = STAGE_WAIT;
            }
        }, [], false);
        Logic::TableStatus($this->roomRule['rid'], 0);
    }

    private function checkres()
    {
        $winscore = 0;
        $transcore = 0;

        $players = ['win' => [], 'tran' => []];
        $bankerid = $this->PlayerInfo[$this->banker]['handsid'];
        $bankerscore = $this->PlayerInfo[$this->banker]['gold'];

        foreach ($this->PlayerInfo as $key => $val) {
            if ($key == $this->banker || $val['isrealy'] == 0) {
                continue;
            }

            $type = $this->PlayerInfo[$this->banker]['type'];

            if ($val['handsid'] > $bankerid) {
                $type = $val['type'];
            }

            $score =  BEISHU_CATTLE[$type] * $this->roomRule['doublescore'] * $val['bet'] * $this->PlayerInfo[$this->banker]['callbanker'];

            if ($val['handsid'] > $bankerid) {
                $transcore += $score;
                $players['tran'][$key] = $score;
            } else {
                $winscore += $score;
                $players['win'][$key] = $score;
            }
        }
        $res = [];
        $bankerWinScore = 0;
        $InsertProfit = 0;
        foreach ($players['win'] as $key => $val) {
            $score = $val;
            if ($winscore > $bankerscore) {
                $print = $val / $winscore;
                $score = intval($print * $bankerscore);
            }

            if ($score > $this->PlayerInfo[$key]['gold']) {
                $score = $this->PlayerInfo[$key]['gold'];
            }
            if ($this->PlayerInfo[$key]['client_id'] != '' && abs($this->playercontr[$key]) != 2) {
                Logic::InsertProfit($this->roomRule['level'], -$score);
            }

            $this->changScore($key, -$score);

            if ($this->roomRule['level'] != 1) {
                DBInstance::IncrementUserGet($key, -$score);
            }
            $res[$key] = [
                'score' => -$score,
                'gold' => $this->PlayerInfo[$key]['gold'],
            ];
            $bankerWinScore += intval($score * $this->roomRule['rebate']);
            $InsertProfit += $score;
        }

        foreach ($players['tran'] as $key => $val) {
            $score = $val;
            if ($transcore > $bankerscore) {
                $print = $val / $transcore;
                $score = intval($print * $bankerscore);
            }
            if ($score > $this->PlayerInfo[$key]['gold']) {
                $score = $this->PlayerInfo[$key]['gold'];
            }

            if ($this->PlayerInfo[$key]['client_id'] != '' && abs($this->playercontr[$key]) != 2) {
                Logic::InsertProfit($this->roomRule['level'], $score);
            }

            $this->changScore($key, intval($score * $this->roomRule['rebate']));

            if ($this->roomRule['level'] != 1) {
                DBInstance::IncrementUserGet($key, intval($score * $this->roomRule['rebate']));
            }

            $res[$key] = [
                'score' => intval($score * $this->roomRule['rebate']),
                'gold' => $this->PlayerInfo[$key]['gold'],

            ];
            $bankerWinScore -= $score;
            $InsertProfit -= $score;
        }

        if ($this->PlayerInfo[$this->banker]['client_id'] != '' && abs($this->playercontr[$this->banker]) != 2) {
            Logic::InsertProfit($this->roomRule['level'], $InsertProfit);
        }

        $this->changScore($this->banker, $bankerWinScore);

        $res[$this->banker] = [
            'score' => $this->PlayerInfo[$this->banker]['winScore'],
            'gold' => $this->PlayerInfo[$this->banker]['gold'],
        ];

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($this->banker,  $this->PlayerInfo[$this->banker]['winScore']);
        }

        return $res;
    }
    /**
     * 结算定时器
     * @param [type] $uid
     * @param [type] $time
     * @return void
     */
    private function ResStateTimer($uid, $time)
    {
        $this->userTimer[$uid] = Timer::add($time, function () use ($uid) {
            Timer::del($this->userTimer[$uid]);
            $this->gameState = STAGE_WAIT;
            $this->All_RECV(['event' => 'Msg_QZNN_Out', 'uid' => $uid, 'data' => []]);
        }, [], false);
    }
    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_QZNN_Act_CallBanker':
                $this->Msg_QZNN_Act_CallBanker($message);
                break;
            case 'Msg_QZNN_Act_Bet':
                $this->Msg_QZNN_Act_Bet($message);
                break;
            case 'Msg_QZNN_Out':
                $this->Msg_QZNN_Out($message);
                break;
            case 'Msg_QZNN_Act_Show':
                $this->Msg_QZNN_Act_Show($message);
                break;
            case 'Msg_QZNN_ACT_Delay':
                $this->Msg_QZNN_ACT_Delay($message);
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
    private function Msg_QZNN_Act_CallBanker($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['isrealy']  != 1) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if ($this->gameState != STAGE_CALL_BANKER) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['callbanker'] != -1) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if (!isset($msg['data']['type'])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }

        $this->PlayerInfo[$msg['uid']]['callbanker'] = $msg['data']['type'];

        Logic::SendAll('Msg_QZNN_Act_CallBanker', ['uid' => $msg['uid'], 'type' => $msg['data']['type']], $this->roomRule['rid']);

        $code = false;
        $players = [];
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isrealy'] == 0) {
                continue;
            }
            if ($val['callbanker'] == -1) {
                $code = true;
                break;
            }
            $players[$key] = $val['callbanker'];
        }

        if (!$code) {
            Timer::del($this->timer);

            $max = max($players);
            $bankers = [];
            $sum = 0;
            foreach ($players as $key => $val) {
                if ($val == $max) {
                    $sum += $this->PlayerInfo[$key]['gold'];
                    $bankers[$key] = $this->PlayerInfo[$key]['gold'];
                }
            }
            $rand = rand(0, $sum);

            $_sum = 0;
            foreach ($bankers  as $key  => $val) {
                $_sum += $val;
                if ($rand <= $_sum) {
                    $this->banker = $key;
                    if ($this->PlayerInfo[$key]['callbanker'] <= 0) {
                        $this->PlayerInfo[$key]['callbanker'] = 1;
                    }
                    break;
                }
            }

            $this->procedure(STAGE_BET);
        }
    }

    /**
     * 玩家下注
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_QZNN_Act_Bet($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['isrealy']  != 1) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if ($this->gameState != STAGE_BET) {
            Logic::SendError($msg['uid'], 'Msg_QZNN_Act_Bet', '阶段错误');
            return;
        }

        if ($msg['uid'] == $this->banker || !isset($msg['data']['bet'])) {
            Logic::SendError($msg['uid'], 'Msg_QZNN_Act_Bet', '庄家不能下注或数据格式错误');
            return;
        }

        if (!in_array($msg['data']['bet'], BET_ALL_ARR)) {
            Logic::SendError($msg['uid'], 'Msg_QZNN_Act_Bet', '数据格式错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['bet'] > 0) {
            Logic::SendError($msg['uid'], 'Msg_QZNN_Act_Bet', '重复操作');
            return;
        }

        $this->PlayerInfo[$msg['uid']]['bet'] = $msg['data']['bet'];
        Logic::SendAll('Msg_QZNN_Act_Bet', ['uid' => $msg['uid'], 'bet' => $msg['data']['bet']], $this->roomRule['rid']);

        $code = true;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isrealy'] == 0 || $this->banker == $key) {
                continue;
            }
            if ($val['bet'] == 0) {
                $code = false;
                break;
            }
        }

        if ($code) {
            Timer::del($this->timer);
            $this->procedure(STAGE_FA);
        }
    }

    /**
     * 看牌
     * @param [type] $msg
     * @return void
     */
    private function Msg_QZNN_Act_Show($msg)
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
            Logic::SendError($msg['uid'], $msg['event'], '已经看过牌');
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

        Logic::SendAll('Msg_QZNN_Act_Show', ['uid' => $msg['uid']], $this->roomRule['rid']);

        if (!$code) {
            Timer::del($this->timer);
            $this->procedure(STAGE_RES);
        }
    }

    /**
     * 托管次数多了后玩家选择是否退出房间
     */
    private function Msg_QZNN_ACT_Delay($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['isrealy']  != 1) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if ($this->gameState != STAGE_RES && $this->gameState != STAGE_WAIT) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if (!isset($this->userTimer[$msg['uid']])) {
            Logic::SendError($msg['uid'], $msg['event'], '操作错误');
            return;
        }

        if (!isset($msg['data']['type']) || ($msg['data']['type'] != 0 && $msg['data']['type'] != 1)) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }

        Timer::del($this->userTimer[$msg['uid']]);

        Logic::SendRight($msg['uid'], $msg['event'], $msg['data']);

        if ($msg['data']['type'] == 0) {
            $this->Msg_QZNN_Out(['uid' => $msg['uid']]);
        }
    }

    /**
     * 玩家退出房间
     * @param [type] $msg
     * @return void
     */
    private function Msg_QZNN_Out($msg)
    {
        if ($this->gameState != STAGE_RES && $this->gameState != STAGE_WAIT) {
            Logic::SendError($msg['uid'], 'Msg_QZNN_Out', '正在游戏中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_QZNN_Out', ['uid' => $msg['uid'], 'gold' => $gold], $this->roomRule['rid']);
        if (isset($this->userTimer[$msg['uid']])) {
            Timer::del($this->userTimer[$msg['uid']]);
        }

        if (isset($this->AiTimer[$msg['uid']])) {
            Timer::del($this->AiTimer[$msg['uid']]);
        }
        unset($this->PlayerInfo[$msg['uid']]);

        $data = ['uid' => $msg['uid'], 'rid' => $this->roomRule['rid']];
        Logic::QuitRoom($data);

        if (count($this->PlayerInfo) <= 0) {
            $this->OldRoom();
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
    }

    private function disroomfun()
    {
        foreach ($this->PlayerInfo as $key => $val) {
            if (isset($this->userTimer[$key])) {
                Timer::del($this->userTimer[$key]);
            }

            $this->All_RECV(['event' => 'Msg_QZNN_Out', 'uid' => $key]);
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
        if ($this->gameState == STAGE_RES || $this->gameState == STAGE_WAIT) {
            $this->All_RECV(['event' => 'Msg_QZNN_Out', 'uid' => $uid, 'data' => []]);
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
        Logic::SendAll('Msg_QZNN_Add', [
            'uid' => $msg['uid'],
            'nickname' => $msg['nickname'],
            'headimgurl' => $msg['headimgurl'],
            'gold' => $msg['gold'],
            'seat' => $msg['seat']
        ], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);
        if ($this->gameState == STAGE_WAIT && count($this->PlayerInfo) >= QZNN_PNUM || $this->disRoom == 1) {
            if ($this->disRoom == 1) {
                $this->disroomfun();
                return;
            }
            $this->procedure(STAGE_CALL_BANKER);
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
        Logic::SendAll('Msg_QZNN_ChangGold', ['uid' => $msg['uid'], 'gold' => $this->PlayerInfo[$msg['uid']]['gold']], $this->roomRule['rid']);
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
            $this->disroomfun();
        }
    }
}

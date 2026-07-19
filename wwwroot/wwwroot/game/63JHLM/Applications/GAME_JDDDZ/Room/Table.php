<?php

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

require_once __DIR__ . '/Cards.php';

define('DDZ_STATE_WAIT', 0); //等待
define('DDZ_STATE_FA', 1); //发牌
define('DDZ_STATE_BANKER', 2); //抢庄
define('DDZ_STATE_DOUBLE', 3); //加倍
define('DDZ_STATE_DA', 4); //打牌
define('DDZ_STATE_RES', 5); //结算
define('DDZ_STATE_OLD', 6); //解散房间

define('DDZ_ALL_NUM', 3); //游戏人数

define('DDZ_TIME_FA', 2); //发牌时间
define('DDZ_TIME_CALLBANKER', 15); //叫地主
define('DDZ_TIME_DOUBLE', 15); //加倍
define('DDZ_TIME_PASS', 5); //过时间
define('DDZ_TIME_TUO', 1); //托管时间
define('DDZ_TIME_DA', 15); //打牌时间15
define('DDZ_TIME_RES', 30); //结算时间

define('DDZ_BANRE_ARR', [1, 2, 3]); //地主叫分

define('DDZ_TUO_NUM', 3); //托管次数

define('DDZ_MAX_BEISHU', 100); //退出房间的倍数
class Table
{
    private $roomRule = []; //房间信息

    private $PlayerInfo = []; //玩家信息

    private $gameState = DDZ_STATE_WAIT; //游戏阶段

    private $banker = 0; //庄家uid

    private $tally = []; //记牌器

    private $bankerCards = []; //地主牌

    private $timer = 0; //定时器

    private $seatUid = [];

    private $current = -1; //当前正操作玩家seat

    private $disRoom = -1; //退出房间方式

    private $beishu = [
        'bankerCards' => 0, //地主牌的分数
        'boom' => 1, //炸弹分数
    ]; //倍数

    private $last = [
        'type' => 0, //类型
        'hands' => [], //牌
        'min' => 0, //最小牌
        'seat' => -1, //座位号
        'length' => 0, //长度
    ]; //上一手牌

    private $winner = 0;

    private $bankernum = 0; //地主出牌次数

    private $timeTemp = 0;

    private $resData = [];

    private $nobankernum = 0;

    private $bankerscore = -1;

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
        $this->roomInfo();

        if (count($this->PlayerInfo) >= DDZ_ALL_NUM) {
            $this->procedure(DDZ_STATE_FA);
        }
    }

    /**
     * 玩家进房
     * @param int
     * */
    private function userEnter($PlayerInfo)
    {
        foreach ($PlayerInfo as $key => $val) {
            if ($this->current < 0) {
                $this->current = $val['seat'];
            }

            if ($this->winner == 0) {
                $this->winner = $key;
            }

            $this->addPlayer($key, $val);
        }
    }

    /**
     * 刷新房间
     * @param integer $uid
     * @return void
     */
    private function roomInfo($uid = 0)
    {
        $players = [];
        foreach ($this->PlayerInfo as $key => $val) {
            $players[$key] = [
                'gold' => $val['gold'],
                'nickname' => $val['nickname'],
                'cardsNum' => count($val['hands']),
                'multiple' => $val['multiple'],
                'ready' => $val['ready'],
                'istuo' => $val['istuo'],
                'online' => $val['online'],
                'sex' => $val['sex'],
                'seat' => $val['seat'],
                'callbanker' => $val['callbanker'],
                'lastCards' => $val['lastCards']
            ];
        }

        $data = [
            'players' => $players,
            'gameState' => $this->gameState,
            'hands' => [],
            'banker' => $this->banker,
            'recorder' => $this->tally,
            'bankerCards' => $this->gameState <= DDZ_STATE_BANKER ? [] : $this->bankerCards,
            'doublescore' => $this->roomRule['doublescore'],
            'rid' => $this->roomRule['rid'],
            'beishu' => $this->beishu,
        ];

        if ($uid != 0) {
            $data['hands'] = $this->PlayerInfo[$uid]['hands'];

            Logic::SendRight($uid, 'Msg_JDDDZ_RoomInfo', $data);
        } else {
            Logic::SendAll('Msg_JDDDZ_RoomInfo', $data, $this->roomRule['rid']);
        }
    }
    /**
     * 玩家在进入房间
     * @param [type] $uid
     * @param [type] $data
     * @return void
     */
    private function addPlayer($uid, $data)
    {
        $this->seatUid[$data['seat']] = $uid;

        $this->PlayerInfo[$uid] = [
            'nickname' => $data['nickname'], //昵称
            'gold' => $data['gold'], //金币
            'headimgurl' => $data['headimgurl'], //头像框
            'client_id' => $data['client_id'], //套节字
            'seat' => $data['seat'], //座位号
            'multiple' => -1,
            'hands' => [], //手牌
            'callbanker' => -1, // -1没有操作 0过 1一分 2两分 3三分
            'tuonum' => 0, //托管次数
            'istuo' => 0, //0不托管 1托管
            'score' => 0, //
            'tips' => [], //提示
            'ready' => 1, //是否准备
            'online' => 1, //1在线 0离线
            'sendData' => [], //通知消息
            'sex' => $data['sex'], //性别
            'lastCards' => [
                'cards' => [],
                'type' => 0
            ]
        ];

        if ($data['client_id'] != '') {
            Gateway::joinGroup($data['client_id'], 'ROOM:' . $this->roomRule['rid']);
        }
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
        Timer::del($this->timer);

        switch ($this->gameState) {
            case DDZ_STATE_FA:
                $this->StageFa();
                break;
            case DDZ_STATE_BANKER:
                $this->stageBanker();
                break;
            case DDZ_STATE_DOUBLE:
                $this->stageDouble();
                break;
            case DDZ_STATE_DA:
                $this->stageDa();
                break;
            case DDZ_STATE_RES:
                $this->StageRes();
                break;
            case DDZ_STATE_OLD:
                $this->OldRoom();
                break;
        }
    }


    /**
     * 初始化
     *
     * @return void
     */
    private function Init()
    {
        Cards::InitCards();
        $_recorder = Cards::getprintHands(Cards::$CardsArr);

        foreach ($this->PlayerInfo as $key => $val) {
            $this->PlayerInfo[$key]['ready'] = 0;
            $this->PlayerInfo[$key]['multiple'] = -1;
            $this->PlayerInfo[$key]['hands'] = [];
            $this->PlayerInfo[$key]['sendData'] = [];
            $this->PlayerInfo[$key]['callbanker'] = -1;
            $this->PlayerInfo[$key]['istuo'] = 0;
            $this->PlayerInfo[$key]['tips'] = [];
            $this->PlayerInfo[$key]['lastCards'] = [
                'cards' => [],
                'type' => 0
            ];
        }
        $this->tally = array_count_values($_recorder);
        $this->bankerscore = -1;

        $this->bankerCards = [];

        $this->last = [
            'type' => 0, //类型
            'hands' => [], //牌
            'min' => 0, //最小牌
            'seat' => -1, //座位号
            'length' => 0, //长度
        ];

        $this->beishu = [
            'bankerCards' => 0,
            'boom' => 1,
        ]; //倍数

        $this->bankernum = 0;
        Logic::TableStatus($this->roomRule['rid'], 1);
    }

    /**
     * 发牌
     * @return void
     */
    private function StageFa()
    {
        if ($this->disRoom == 1) {
            $this->OldRoom();
            return;
        }

        $this->Init();
        $players = [];
        $this->timeTemp = time();

        foreach ($this->PlayerInfo as $key => $val) {
            $this->PlayerInfo[$key]['hands'] = Cards::FaCards();
            $players[$key]['cardsNum'] = DDZ_HANDS_NUM;
        }

        $data = [
            'player' => $players,
            'hands' => [],
        ];
        foreach ($this->PlayerInfo as $key => $val) {
            $data['hands'] = $val['hands'];
            Logic::SendRight($key, 'Msg_JDDDZ_Fa', $data);
        }


        $this->timer = Timer::add(DDZ_TIME_FA, function () {
            $this->procedure(DDZ_STATE_BANKER);
        }, [], false);
    }

    /**
     * 通知玩家抢地主
     * @return void
     */
    private function stageBanker()
    {
        $data = [
            'time' => DDZ_TIME_CALLBANKER,
            'uid' => $this->seatUid[$this->current]
        ];
        $this->timeTemp = time();

        Logic::SendAll('Msg_JDDDZ_CallBanker', $data, $this->roomRule['rid']);

        $this->timer = Timer::add(DDZ_TIME_CALLBANKER, function () {
            Timer::del($this->timer);
            $this->All_RECV(['event' => 'Msg_JDDDZ_Act_CallScore', 'uid' => $this->seatUid[$this->current], 'data' => ['score' => 0]]);
        }, [], false);
    }

    /**
     * 加倍
     * @return void
     */
    private function stageDouble()
    {
        $this->current = $this->PlayerInfo[$this->banker]['seat'];

        $this->bankerCards = array_values(Cards::$CardsArr);

        $this->PlayerInfo[$this->banker]['hands'] = array_merge($this->PlayerInfo[$this->banker]['hands'], $this->bankerCards);
        $this->timeTemp = time();

        Logic::SendAll('Msg_JDDDZ_Double', ['time' => DDZ_TIME_DOUBLE, 'banker' => $this->banker, 'tally' => $this->tally, 'bankerCards' =>  $this->bankerCards], $this->roomRule['rid']);
        $this->timer = Timer::add(DDZ_TIME_DOUBLE, function () {
            Timer::del($this->timer);
            $this->current = $this->PlayerInfo[$this->banker]['seat'];
            foreach ($this->PlayerInfo as $key => $val) {
                if ($key == $this->banker) {
                    $this->PlayerInfo[$key]['multiple'] = 1;
                } elseif ($val['multiple'] <= 0) {
                    $this->All_RECV(['event' => 'Msg_JDDDZ_Act_Beishu', 'uid' => $key, 'data' => ['beishu' => 0]]);
                }
            }
        }, [], false);
    }

    /**
     * 通知玩家打牌
     * @return void
     */
    private function stageDa()
    {
        $this->timeTemp = time();
        $ispass = 1;
        $time = DDZ_TIME_DA;
        $_data = [];
        $uid = $this->seatUid[$this->current];

        $this->PlayerInfo[$uid]['tips'] = $this->PlayerInfo[$uid]['hands'];

        if ($this->last['type'] >= DDZ_TYPE_ONE) { //跟牌
            $_data = Cards::tips($this->last, $this->PlayerInfo[$uid]['hands']);
            $this->PlayerInfo[$uid]['tips'] = $_data;

            if (empty($_data)) {
                $ispass = 0;
                $time = DDZ_TIME_PASS;
            }
        }

        $data = ['time' => DDZ_TIME_DA, 'type' => $this->last, 'ispass' => 1, 'uid' => $uid];
        foreach ($this->PlayerInfo as $key => $val) {
            if ($key == $uid) {
                continue;
            }

            $this->PlayerInfo[$key]['sendData'] = $data;

            Logic::SendRight($key, 'Msg_JDDDZ_Da', $data);
        }

        $data['time'] = $time;
        $istuo = $this->PlayerInfo[$uid]['istuo'];
        if ($istuo == 1) {
            $time = DDZ_TIME_TUO;
        }
        $data['ispass'] = $ispass;

        $this->PlayerInfo[$uid]['sendData'] = $data;
        Logic::SendRight($uid, 'Msg_JDDDZ_Da', $data);
        $this->timer = Timer::add($time, function () use ($ispass, $_data, $istuo) {
            Timer::del($this->timer);
            if ($ispass == 0 || ($istuo == 0 && $this->last['type'] != 0)) {
                $this->All_RECV(['event' => 'Msg_JDDDZ_Act_Pass', 'uid' => $this->seatUid[$this->current]]);
            } else {
                if ($istuo == 0) {
                    $this->PlayerInfo[$this->seatUid[$this->current]]['tuonum']++;
                    if ($this->PlayerInfo[$this->seatUid[$this->current]]['tuonum'] > DDZ_TUO_NUM) {
                        $this->All_RECV(['event' => 'Msg_JDDDZ_Act_Tuo', 'uid' => $this->seatUid[$this->current]]);
                        return;
                    } elseif ($this->last['type'] != 0) {
                        $this->All_RECV(['event' => 'Msg_JDDDZ_Act_Pass', 'uid' => $this->seatUid[$this->current]]);
                        return;
                    }
                }

                $cards = $this->recommendCards($_data);

                $this->All_RECV(['event' => 'Msg_JDDDZ_Act_Da', 'uid' => $this->seatUid[$this->current], 'data' => ['cards' => $cards]]);
            }
        }, [], false);
    }

    /**
     * 通知玩家解散房间
     * @return void
     */
    private function StageRes()
    {
        $this->timeTemp = time();
        $beishu = 1;
        foreach ($this->beishu as $key => $val) {
            if ($val > 0) {
                $beishu *= $val;
            }
        }

        $spring =  true;
        $antispring = false;
        $code = true;
        if ($this->winner == $this->banker) {
            $code = false;
            foreach ($this->PlayerInfo as $key => $val) {
                if ($key == $this->banker) {
                    continue;
                }

                if (count($val['hands']) != DDZ_HANDS_NUM) {
                    $spring = false;
                    break;
                }
            }
        } else {
            $spring = false;
            if ($this->bankernum < 2) {
                $antispring = true;
                $beishu *= DDZ_BEISHU_ANTISPRING;
            }
        }

        if ($spring) {
            $beishu *= DDZ_BEISHU_SPRING;
        }

        $players = [];
        $countScore = 0;
        $countbeishu = 0;
        foreach ($this->PlayerInfo as $key => $val) {
            $this->PlayerInfo[$key]['ready'] = 0;
            $this->PlayerInfo[$key]['multiple'] = -1;
            $this->PlayerInfo[$key]['sendData'] = [];
            $this->PlayerInfo[$key]['callbanker'] = -1;
            $this->PlayerInfo[$key]['istuo'] = 0;
            $this->PlayerInfo[$key]['tips'] = [];
            $this->PlayerInfo[$key]['lastCards'] = [
                'cards' => [],
                'type' => 0
            ];

            if ($key == $this->banker) {
                continue;
            }

            $_score = 0;
            $_beishu = $beishu;
            if ($val['multiple'] > 0) {
                $_beishu *=  $val['multiple'];

                $_score = $this->roomRule['doublescore'] * $_beishu;

                if (!$code) {
                    $countScore -= $_score;
                    $_score = -$_score;
                } else {
                    $countScore += $_score;
                    $_score *= $this->roomRule['rebate'];
                }
            }

            $this->changScore($key, $_score);

            $players[$key] = [
                'score' => $_score,
                'gold' => $this->PlayerInfo[$key]['gold'],
                'hands' => array_values($val['hands']),
                'beishu' => $_beishu,
            ];

            if ($this->roomRule['level'] != 1) {
                DBInstance::IncrementUserGet($key, $_score);
            }

            $countbeishu += $_beishu;

            $this->PlayerInfo[$key]['hands'] = [];
        }

        if ($this->PlayerInfo[$this->banker]['gold'] < $countScore) {
            $countScore = $this->PlayerInfo[$this->banker]['gold'];
        }

        if ($countScore < 0) {
            $countScore *= $this->roomRule['rebate'];
        }
        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($this->banker, -$countScore);
        }
        $this->changScore($this->banker, -$countScore);

        $players[$this->banker] = [
            'score' => -$countScore,
            'gold' => $this->PlayerInfo[$this->banker]['gold'],
            'hands' => array_values($this->PlayerInfo[$this->banker]['hands']),
            'beishu' => $countbeishu,
        ];


        $data = [
            'players' => $players,
            'spring' => $spring,
            'antispring' => $antispring,
            'time' => DDZ_TIME_RES
        ];

        Logic::SendAll('Msg_JDDDZ_Res', $data, $this->roomRule['rid']);
        $this->resData = $data;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['gold'] < DDZ_MAX_BEISHU * $this->roomRule['doublescore'] || $val['online'] == 0) {
                $this->Msg_JDDDZ_Out(['uid' => $key]);
            }
        }

        $this->tally = [];

        $this->bankerCards = [];

        $this->last = [
            'type' => 0, //类型
            'hands' => [], //牌
            'min' => 0, //最小牌
            'seat' => -1, //座位号
            'length' => 0, //长度
        ];

        $this->beishu = [
            'bankerCards' => 0,
            'boom' => 1,
        ]; //倍数

        $this->timer = Timer::add(DDZ_TIME_RES, function () {
            Timer::del($this->timer);
            $this->gameState = DDZ_STATE_WAIT;
            foreach ($this->PlayerInfo as $key => $val) {
                $this->PlayerInfo[$key]['ready'] = 1;
            }

            if (count($this->PlayerInfo) == DDZ_ALL_NUM) {
                $this->procedure(DDZ_STATE_FA);
            }
        }, [], false);

        Logic::TableStatus($this->roomRule['rid'], 0);
    }

    /**
     * 推荐出牌
     * @param [type] $data
     * @return void
     */
    private function recommendCards($data)
    {
        $cards = [];
        if (empty($data)) {
            $min =  min($this->PlayerInfo[$this->seatUid[$this->current]]['hands']);
            $printMin = Cards::GET_P($min);

            foreach ($this->PlayerInfo[$this->seatUid[$this->current]]['hands'] as $key => $val) {
                $print = Cards::GET_P($val);
                if ($print == $printMin) {
                    $cards[] = $val;
                }
            }
        } else {
            foreach ($data as $key => $val) {
                $cards = $val;
                if (!is_array($cards)) {
                    $cards[] = $cards;
                }
                break;
            }
        }

        return $cards;
    }
    /**
     *下一个玩家
     * @return void
     */
    private function nextPlayer()
    {
        $this->current++;
        if ($this->current >= DDZ_ALL_NUM) {
            $this->current -= DDZ_ALL_NUM;
        }
        if ($this->last['seat'] == $this->current) {
            $this->last = [
                'hands' => [],
                'min' => 0,
                'type' => 0,
                'length' => 0,
                'seat' => $this->current,
            ];
        }
    }
    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_JDDDZ_Act_CallScore':
                $this->Msg_JDDDZ_Act_CallScore($message);
                break;
            case 'Msg_JDDDZ_Act_Beishu':
                $this->Msg_JDDDZ_Act_Beishu($message);
                break;
            case 'Msg_JDDDZ_Act_Da':
                $this->Msg_JDDDZ_Act_Da($message);
                break;
            case 'Msg_JDDDZ_Act_Pass':
                $this->Msg_JDDDZ_Act_Pass($message);
                break;
            case 'Msg_JDDDZ_Act_Tuo':
                $this->Msg_JDDDZ_Act_Tuo($message);
                break;

            case 'Msg_JDDDZ_Act_Ready':
                $this->Msg_JDDDZ_Act_Ready($message);
                break;

            case 'Msg_JDDDZ_Out':
                $this->Msg_JDDDZ_Out($message);
                break;

            default: {
                    Logic::SendError($message['uid'], $message['event'], '');

                    MyTools::msg('uid:' . $message['uid'] . '-----UnKnown!!!!!!!--------' . $message['event'], true);
                    break;
                }
        }
    }

    /**
     * 叫地主
     */
    private function Msg_JDDDZ_Act_CallScore($msg)
    {
        if ($this->gameState != DDZ_STATE_BANKER) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_CallScore', '阶段错误');
            return;
        }

        if ($this->seatUid[$this->current] != $msg['uid']) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_CallScore', '操作失败');
            return;
        }

        if ($msg['data']['score'] <= $this->bankerscore && $msg['data']['score'] != 0) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_CallScore', '数据格式错误');
            return;
        }

        Timer::del($this->timer);
        $this->PlayerInfo[$msg['uid']]['callbanker'] = $msg['data']['score'];
        if ($msg['data']['score'] > 0) {
            $this->bankerscore = $msg['data']['score'];
        }

        Logic::SendAll('Msg_JDDDZ_Act_CallScore', ['uid' => $msg['uid'], 'score' => $msg['data']['score']], $this->roomRule['rid']);

        if ($msg['data']['score'] == max(DDZ_BANRE_ARR)) {
            $this->banker = $msg['uid'];
            $this->procedure(DDZ_STATE_DOUBLE);
            $this->bankernum = 0;
            $this->beishu['bankerCards'] = $msg['data']['score'];
        } else {
            $this->checkBanker();
        }
    }

    /**
     * 加倍
     * @param [type] $msg
     * @return void
     */
    private function Msg_JDDDZ_Act_Beishu($msg)
    {
        if ($this->gameState != DDZ_STATE_DOUBLE) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Beishu', '阶段错误');
            return;
        }

        if ($msg['uid'] == $this->banker) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Beishu', '地主不能加倍');
            return;
        }

        if (!isset($msg['data']['beishu'])) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Beishu', '数据格式错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['multiple'] >= 0) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Beishu', '重复操作');
            return;
        }

        if ($msg['data']['beishu'] == 0) {
            $this->PlayerInfo[$msg['uid']]['multiple'] = 1;
        } else {
            $this->PlayerInfo[$msg['uid']]['multiple'] = 2;
        }
        Logic::SendAll('Msg_JDDDZ_Act_Beishu', ['uid' => $msg['uid'], 'beishu' => $msg['data']['beishu']], $this->roomRule['rid']);
        $code = true;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($key == $this->banker) {
                continue;
            }

            if ($val['multiple'] < 0) {
                $code = false;
                break;
            }
        }

        if ($code) {
            $this->PlayerInfo[$this->banker]['multiple'] = 1;
            $this->procedure(DDZ_STATE_DA);
        }
    }

    /**
     * 打牌
     * @param [type] $msg
     * @return void
     */
    private function Msg_JDDDZ_Act_Da($msg)
    {
        if ($this->gameState != DDZ_STATE_DA) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Da', '阶段错误');
            return;
        }

        if ($msg['uid'] != $this->seatUid[$this->current]) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Da', '操作失败');
            return;
        }

        if (empty($this->PlayerInfo[$msg['uid']]['tips'])) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Da', '请选择正确的牌型');
            return;
        }

        if (!isset($msg['data']['cards']) || !is_array($msg['data']['cards']) || empty($msg['data']['cards'])) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Da', '数据格式错误');
            return;
        }

        foreach ($msg['data']['cards'] as $key => $val) {
            if (!in_array($val, $this->PlayerInfo[$msg['uid']]['hands']) || !is_int($val)) {
                Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Da', '没有此牌');
                return;
            }
        }

        $type = Cards::checkType($msg['data']['cards']);

        $data = $this->checklengthmin($type, $msg['data']['cards']);

        if ($type == 0) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Da', '请选择正确的牌型');
            return;
        }

        if ($this->last['type'] != 0) {
            if ($this->last['type'] < DDZ_TYPE_BOOM  && $type < DDZ_TYPE_BOOM) {
                if ($data['min'] <= $this->last['min'] || $this->last['length'] != $data['length']) {
                    Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Da', '请选择正确的牌型');
                    return;
                }
            } elseif ($this->last['type'] == DDZ_TYPE_BOOM) {
                if ($data['min'] <= $this->last['min'] || $type < DDZ_TYPE_BOOM) {
                    Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Da', '请选择正确的牌型');
                    return;
                }
            }
        }
        Timer::del($this->timer);

        $this->last = [
            'hands' => $msg['data']['cards'],
            'min' => $data['min'],
            'type' => $type,
            'length' => $data['length'],
            'seat' => $this->current,
        ];

        $this->PlayerInfo[$msg['uid']]['lastCards'] = [
            'cards' => $msg['data']['cards'],
            'type' => $type,
        ];

        Logic::SendAll('Msg_JDDDZ_Act_Da', ['uid' => $msg['uid'], 'cards' => $msg['data']['cards'], 'type' => $type], $this->roomRule['rid']);

        if ($type >= DDZ_TYPE_BOOM) {
            $this->beishu['boom'] *= DDZ_BEISHU_BOOM;
        }

        foreach ($msg['data']['cards'] as $key => $val) {
            $print = Cards::GET_P($val);
            $this->tally[$print]--;
        }

        $this->PlayerInfo[$msg['uid']]['hands'] = array_diff($this->PlayerInfo[$msg['uid']]['hands'], $msg['data']['cards']);

        if (empty($this->PlayerInfo[$msg['uid']]['hands'])) {
            $this->winner = $msg['uid'];
            $this->procedure(DDZ_STATE_RES);
            return;
        }

        if ($this->banker == $msg['uid']) {
            $this->bankernum++;
        }

        $this->nextPlayer();
        $this->stageDa();
    }
    /**
     * 过
     * @param [type] $msg
     * @return void
     */
    private function Msg_JDDDZ_Act_Pass($msg)
    {
        if ($this->gameState != DDZ_STATE_DA) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Pass', '阶段错误');
            return;
        }

        if ($msg['uid'] != $this->seatUid[$this->current] || $this->last['type'] == 0) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Pass', '请出牌');
            return;
        }

        Timer::del($this->timer);

        Logic::SendAll('Msg_JDDDZ_Act_Pass', ['uid' => $msg['uid']], $this->roomRule['rid']);

        $this->PlayerInfo[$msg['uid']]['lastCards'] = [
            'cards' => [],
            'type' => 0,
        ];

        $this->nextPlayer();
        $this->stageDa();
    }

    /**
     * 托管
     * @param [type] $msg
     * @return void
     */
    private function Msg_JDDDZ_Act_Tuo($msg)
    {
        if ($this->gameState != DDZ_STATE_DA) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Tuo', '阶段错误');
            return;
        }
        $this->PlayerInfo[$msg['uid']]['istuo'] = $msg['data']['istuo'];
        Logic::SendAll('Msg_JDDDZ_Act_Tuo', ['uid' => $msg['uid'], 'istuo' => $msg['data']['istuo']], $this->roomRule['rid']);

        if ($msg['data']['istuo'] == 1 && $this->PlayerInfo[$msg['uid']]['seat'] == $this->current && $this->gameState == DDZ_STATE_DA) {
            if (!empty($this->PlayerInfo[$msg['uid']]['tips'])) {
                $data = [];
                if ($this->last['type'] >= DDZ_TYPE_ONE) {
                    $data = $this->PlayerInfo[$msg['uid']]['tips'];
                }

                $cards = $this->recommendCards($data);
                $this->All_RECV(['event' => 'Msg_JDDDZ_Act_Da', 'uid' => $msg['uid'], 'data' => ['cards' => $cards]]);
            } else {
                $this->All_RECV(['event' => 'Msg_JDDDZ_Act_Pass', 'uid' => $msg['uid'], 'data' => []]);
            }
        } elseif ($this->gameState == DDZ_STATE_BANKER && $this->PlayerInfo[$msg['uid']]['seat'] == $this->current) {
            $this->All_RECV(['event' => 'Msg_JDDDZ_Act_CallScore', 'uid' => $this->seatUid[$this->current], 'data' => ['score' => 0]]);
        } elseif ($this->gameState == DDZ_STATE_DOUBLE && $this->PlayerInfo[$msg['uid']]['beishu'] == -1) {
            if ($msg['uid'] == $this->banker) {
                $this->PlayerInfo[$msg['uid']]['multiple'] = 1;
            } else {
                $this->All_RECV(['event' => 'Msg_JDDDZ_Act_Beishu', 'uid' => $msg['uid'], 'data' => ['beishu' => 0]]);
            }
        }
    }

    /**
     * 准备
     * @param [type] $msg
     * @return void
     */
    private function Msg_JDDDZ_Act_Ready($msg)
    {
        if ($this->gameState != DDZ_STATE_RES) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Ready', '阶段错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['ready'] != 0) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Act_Ready', '已经准备');
            return;
        }

        $this->PlayerInfo[$msg['uid']]['ready'] = 1;

        Logic::SendAll('Msg_JDDDZ_Act_Ready', ['uid' => $msg['uid']], $this->roomRule['rid']);

        $code = true;

        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['ready'] == 0) {
                $code = false;
                break;
            }
        }

        if ($code && count($this->PlayerInfo) == DDZ_TUO_NUM) {
            $this->procedure(DDZ_STATE_FA);
        }
    }

    /**
     * 庄家检测 问题
     * @return void
     */
    private function checkBanker()
    {
        $code = true;
        $seatUid = ['seat' => -1, 'callbanker' => -1];
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['callbanker'] == -1) {
                $code = false;
                break;
            }

            if ($val['callbanker'] != 0 && $val['callbanker'] > $seatUid['callbanker']) {
                $seatUid['seat']  = $val['seat'];
                $seatUid['callbanker'] = $val['callbanker'];
            }
        }

        if ($code) {
            if ($seatUid['seat'] != -1) {
                $this->banker = $this->seatUid[$seatUid['seat']];
                $this->procedure(DDZ_STATE_DOUBLE);
            } else {
                $this->nobankernum++;

                if ($this->nobankernum >= 3) {
                    $this->beishu['bankerCards'] = 1;
                    $this->banker = $this->winner;
                    $this->procedure(DDZ_STATE_DOUBLE);
                } else {
                    $this->nextPlayer();
                    $this->StageFa(false);
                }
            }
        } else {
            $this->nextPlayer();
            $this->stageBanker();
        }
    }

    /**
     * 玩家退出房间
     * @param [type] $msg
     * @return void
     */
    private function Msg_JDDDZ_Out($msg)
    {
        if ($this->gameState != DDZ_STATE_RES && $this->gameState != DDZ_STATE_WAIT) {
            Logic::SendError($msg['uid'], 'Msg_JDDDZ_Out', '正在游戏中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_JDDDZ_Out', ['gold' => $gold, 'uid' => $msg['uid']], $this->roomRule['rid']);

        if (isset($this->resData['players'][$msg['uid']])) {
            unset($this->resData['players'][$msg['uid']]);
        }

        unset($this->PlayerInfo[$msg['uid']]);

        $data = ['uid' => $msg['uid'], 'rid' => $this->roomRule['rid']];

        Logic::QuitRoom($data);

        if (empty($this->PlayerInfo)) {
            $this->OldRoom();
        }
        if ($msg['uid'] == $this->winner) {
            foreach ($this->PlayerInfo as $key => $val) {
                $this->winner = $key;
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
        Timer::del($this->timer);
        foreach ($this->PlayerInfo as $key => $val) {
            $this->Msg_JDDDZ_Out(['uid' => $key]);
        }

        $olddata = [
            'rid' =>  $this->roomRule['rid'],
            'win' => [],
            'palyers' =>  $this->PlayerInfo,
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
        $this->PlayerInfo[$uid]['client_id'] = $client_id;

        Gateway::joinGroup($client_id, 'ROOM:' . $this->roomRule['rid']);
        $this->PlayerInfo[$uid]['online'] = 1;

        $this->roomInfo($uid);
        Logic::SendAll('Msg_JDDDZ_UserState', ['uid' => $uid, 'online' => 1], $this->roomRule['rid']);

        if ($this->gameState == DDZ_STATE_BANKER) {
            $time = DDZ_TIME_CALLBANKER;
            $time = $time - (time() - $this->timeTemp);
            Logic::SendRight($uid, 'Msg_JDDDZ_CallBanker', [
                'time' => $time <= 0 ? 1 : $time,
                'uid' => $this->seatUid[$this->current],
            ]);
        } elseif ($this->gameState == DDZ_STATE_DOUBLE) {
            $time = DDZ_TIME_DOUBLE;
            $time = $time - (time() - $this->timeTemp);
            Logic::SendRight($uid, 'Msg_JDDDZ_Double', [
                'time' =>  $time <= 0 ? 1 : $time,
                'banker' => $this->banker,
                'tally' => $this->tally,
                'bankerCards' =>  $this->bankerCards
            ]);
        } elseif ($this->gameState == DDZ_STATE_DA) {
            $data = $this->PlayerInfo[$uid]['sendData'];
            $data['time'] =  $data['time'] - (time() - $this->timeTemp) <= 0 ? 1 : $data['time'] - (time() - $this->timeTemp);
            Logic::SendRight($uid, 'Msg_JDDDZ_Da', $data);
        } elseif ($this->gameState == DDZ_STATE_RES) {
            $data = $this->resData;
            $data['time'] =  $data['time'] - (time() - $this->timeTemp) <= 0 ? 1 : $data['time'] - (time() - $this->timeTemp);
            Logic::SendRight($uid, 'Msg_JDDDZ_Res', $data);
        }
    }

    /**
     * 检测出牌的长度
     * @param int $type
     * @param [] $cards
     * @return void
     */
    private function checklengthmin($type, $cards)
    {
        $length = 1;

        if ($type == DDZ_TYPE_FEIJ_BELT1) {
            $length = count($cards) / 4;
        } elseif ($type == DDZ_TYPE_FEIJ_BELT2) {
            $length = count($cards) / 5;
        } elseif ($type == DDZ_TYPE_LAINDUI) {
            $length = count($cards) / 2;
        } elseif ($type == DDZ_TYPE_FENJI) {
            $length = count($cards) / 3;
        } elseif ($type == DDZ_TYPE_SHUNZI) {
            $length = count($cards);
        }

        $min = Cards::Mincards($cards, $type, $length);

        return ['min' => $min, 'length' => $length];
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
        $this->PlayerInfo[$uid]['online'] = 0;
        Logic::SendAll('Msg_JDDDZ_UserState', ['uid' => $uid, 'online' => 0], $this->roomRule['rid']);

        if ($this->gameState == DDZ_STATE_RES || $this->gameState == DDZ_STATE_WAIT) {
            $this->Msg_JDDDZ_Out(['uid' => $uid]);
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
        Logic::SendAll('Msg_JDDDZ_Add', [
            'uid' => $msg['uid'],
            'nickname' => $msg['nickname'],
            'headimgurl' => $msg['headimgurl'],
            'gold' => $msg['gold'],
            'seat' => $msg['seat'],
            'sex' => $msg['sex']
        ], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);

        $this->roomInfo($msg['uid']);

        if (count($this->PlayerInfo) == DDZ_ALL_NUM) {
            $num = 0;
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['ready'] == 1) {
                    $num++;
                }
            }

            if ($num == DDZ_ALL_NUM) {
                $this->procedure(DDZ_STATE_FA);
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
        $this->PlayerInfo[$uid]['score'] += $score;
        $this->PlayerInfo[$uid]['gold'] += $score;

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $uid, $score);
        }

        if ($score > 0) {
            DBInstance::IncrementWinPoint($uid, $score);
        }

        Logic::InsertProfit($this->roomRule['level'], -$score);
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
        Logic::SendAll('Msg_JDDDZ_ChangGold', ['uid' => $msg['uid'], 'gold' => $this->PlayerInfo[$msg['uid']]['gold']], $this->roomRule['rid']);
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
        if ($this->gameState == DDZ_STATE_WAIT || $this->gameState == DDZ_STATE_RES) {
            Timer::del($this->timer);

            foreach ($this->PlayerInfo as $key => $val) {
                Timer::del($this->userTimer[$key]);
            }
            $this->OldRoom();
        }
    }
}

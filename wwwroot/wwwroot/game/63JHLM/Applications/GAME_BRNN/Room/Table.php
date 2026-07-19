<?php
date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

require_once __DIR__ . '/Cards.php';
require_once __DIR__ . '/AI.php';

define("STAGE_WAIT", 0);  //等待开始
define("STAGE_START", 1);  //下注
define("STAGE_RES", 2);  //结算
define("STAGE_OLD", 3);  //解散

define('BAT_TYPE_TIAN', 1); //天
define('BAT_TYPE_DI', 2); //地
define('BAT_TYPE_XUAN', 3); //玄
define('BAT_TYPE_HUANG', 4); //黄

define('TIEM_BET', 15); //下注时间
define('TIEM_RES', 20); //结算时间

define('BANKER_GOLD_BRNN', 600000000); //庄家上庄分数
define('BANKER_GOLD_BRNN_DOW', 200000000); //庄家下庄分数

define('BANKER_NUM_BRNN', 10); //庄家最大连庄数

define('PLAYER_TIME', 300); //玩家离线好久踢出房间

define('HORSE_LAMO', 15000000); //跑马灯

class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $gameState = STAGE_WAIT; //游戏状态

    private $disRoom = -1;

    private $hands = [];

    private $banker = ['uid' => 0, 'num' => 0, 'gold' => 0, 'nickname' => '', 'headimgurl' => 1, 'isbanker' => false]; //庄家

    private $applyBanker = []; //申请的庄家

    private $circle = 0; //本日局数

    private $curtime = 0;

    private $hall = [
        BAT_TYPE_TIAN => ['score' => 0, 'hands' => []], //天
        BAT_TYPE_DI => ['score' => 0, 'hands' => []], //地
        BAT_TYPE_XUAN => ['score' => 0, 'hands' => []], //玄
        BAT_TYPE_HUANG => ['score' => 0, 'hands' => []] //黄
    ]; //堂子

    private $timer = 0; //游戏流程时间戳

    private $history = [
        BAT_TYPE_TIAN => [
            'info' => [],
            'win' => 0,
            'tran' => 0
        ], //天
        BAT_TYPE_DI => [
            'info' => [],
            'win' => 0,
            'tran' => 0
        ], //地
        BAT_TYPE_XUAN => [
            'info' => [],
            'win' => 0,
            'tran' => 0
        ], //玄
        BAT_TYPE_HUANG => [
            'info' => [],
            'win' => 0,
            'tran' => 0
        ] //黄
    ]; //历史记录

    private $curbat = [];

    private $playerBet = [];    //玩家所有下注

    private $timeStamp = 0; //时间戳 

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
            'controls' => $msg['controls'],
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
            $this->roomInfo($key);
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
        $this->PlayerInfo[$uid] = [
            'allWinScore' => 0, //总共赢了多少
            'winScore' => 0, //当局赢了多少            
            'win' => 0, //赢得次数
            'playnum' => 0, //下注把数
            'nickname' => $info['nickname'],
            'gold' => $info['gold'],
            'headimgurl' => $info['headimgurl'],
            // 'robot' => $info['robot'],
            'client_id' => $info['client_id'],
            'robotfun' => null,
            'gamenum' => 0, //游戏总局数
            'seat' => $info['seat'],
            'hall' => [
                BAT_TYPE_TIAN => 0, //天
                BAT_TYPE_DI => 0, //地
                BAT_TYPE_XUAN => 0, //玄
                BAT_TYPE_HUANG => 0 //黄 
            ],
            'onlinetime' => 0 //1在线0不在线
        ];

        if ($info['client_id'] == '') {
            $this->PlayerInfo[$uid]['robotfun'] = new AI($this->roomRule['vals']);

            if ($this->banker['uid'] == 0 && $info['gold'] >= BANKER_GOLD_BRNN) {
                if ($this->banker['uid'] == 0) {
                    $this->banker = [
                        'uid' => $uid,
                        'num' => 0,
                        'gold' => $this->PlayerInfo[$uid]['gold'], //新增
                        'nickname' => $this->PlayerInfo[$uid]['nickname'], //新增
                        'headimgurl' => $this->PlayerInfo[$uid]['headimgurl'], //新增
                        'isbanker' => false
                    ];
                } else {
                    $this->Msg_BRNN_Act_Banker(['event' => 'Msg_BRNN_Act_Banker', 'uid' => $uid]);
                }
            }
        } else {
            Gateway::joinGroup($info['client_id'], 'ROOM:' . $this->roomRule['rid']);
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
        $time = TIEM_RES;
        if ($this->gameState == STAGE_START) {
            $time = TIEM_BET;
        }

        $res = [
            // 'players' => $players,
            'doublescore' => $this->roomRule['doublescore'],
            'history' => $this->history,
            'banker' => $this->banker,
            'circle' => $this->circle,
            'time' => $time - (time() - $this->timeStamp),
            'gameState' => $this->gameState,
            'applyBanker' => array_values($this->applyBanker),
            'hall' => $this->hall,
            'mybat' => $this->PlayerInfo[$uid]['hall'],
            'playerCount' => count($this->PlayerInfo),
        ];

        //判断断线重连
        Logic::SendRight($uid, 'Msg_BRNN_RoomInfo', $res);
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
            case STAGE_START:
                $this->BetState();
                break;
            case STAGE_RES:
                $this->ResState($code);
                break;
            case STAGE_OLD:
                $this->OldRoom();
                break;
        }
    }

    /**
     *初始化
     * @return void
     */
    private function Init()
    {
        $this->hands = [];
        $this->playerBet = [];
        $date = date('Y-m-d', time());
        if ($this->curtime != $date) {
            $this->curtime = $date;
            $this->circle = 0;
            $this->history = [
                BAT_TYPE_TIAN => [
                    'info' => [],
                    'win' => 0,
                    'tran' => 0
                ], //天
                BAT_TYPE_DI => [
                    'info' => [],
                    'win' => 0,
                    'tran' => 0
                ], //地
                BAT_TYPE_XUAN => [
                    'info' => [],
                    'win' => 0,
                    'tran' => 0
                ], //玄
                BAT_TYPE_HUANG => [
                    'info' => [],
                    'win' => 0,
                    'tran' => 0
                ] //黄
            ]; //历史记录
        }
        $this->hall = [
            BAT_TYPE_TIAN => ['score' => 0, 'hands' => []], //天
            BAT_TYPE_DI => ['score' => 0, 'hands' => []], //地
            BAT_TYPE_XUAN => ['score' => 0, 'hands' => []], //玄
            BAT_TYPE_HUANG => ['score' => 0, 'hands' => []] //黄
        ];

        $this->circle++;

        if ($this->banker['num'] >= BANKER_NUM_BRNN || $this->PlayerInfo[$this->banker['uid']]['gold'] < BANKER_GOLD_BRNN_DOW || $this->banker['isbanker']) {
            if (empty($this->applyBanker)) {
                foreach ($this->PlayerInfo as $key => $val) {
                    if ($val['client_id'] == '' && $val['gold'] >= BANKER_GOLD_BRNN) {
                        $this->All_RECV(['uid' => $key, 'event' => 'Msg_BRNN_Act_Banker']);
                    }
                }
            }

            foreach ($this->applyBanker as $key => $val) {
                unset($this->applyBanker[$key]);
                if ($this->PlayerInfo[$val]['gold'] >= BANKER_GOLD_BRNN) {
                    $uid = $val;
                    break;
                }
            }

            if (!isset($uid)) {
                foreach ($this->PlayerInfo as $key => $va1) {
                    if ($va1['client_id'] == '' && $val['gold'] >= BANKER_GOLD_BRNN) {
                        $uid = $key;
                        break;
                    }
                }
            }

            $this->banker = [
                'num' => 0,
                'uid' => $uid,
                'gold' => $this->PlayerInfo[$uid]['gold'], //新增
                'nickname' => $this->PlayerInfo[$uid]['nickname'], //新增
                'headimgurl' => $this->PlayerInfo[$uid]['headimgurl'], //新增
                'isbanker' => false
            ];

            $this->applyBanker = array_values($this->applyBanker);
        }

        foreach ($this->PlayerInfo as $key => $val) {
            $this->PlayerInfo[$key]['hall'] = [
                BAT_TYPE_TIAN => 0, //天
                BAT_TYPE_DI => 0, //地
                BAT_TYPE_XUAN => 0, //玄
                BAT_TYPE_HUANG => 0 //黄 
            ];
        }

        $this->hands = Cards::InitCard();
    }

    /**
     * 下注
     *
     * @return void
     */
    private function BetState()
    {
        $this->Init();

        if ($this->disRoom == 1) {
            $this->OldRoom();
            return;
        }

        $this->timeStamp = time();
        $this->banker['num']++;
        if ($this->banker['uid'] == null) {
            MyTools::msg("==not banker=======================" . json_encode($this->banker) . json_encode($this->PlayerInfo));
            return;
        }

        $data = [
            'banker' => $this->banker,
            'circle' => $this->circle,
            'applyBanker' => array_values($this->applyBanker),
            'time' => TIEM_BET
        ];

        Logic::SendAll('Msg_BRNN_Bet', $data, $this->roomRule['rid']);
        //DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet, time() + TIEM_BET);
        $this->timer = Timer::add(TIEM_BET, function () {
            Timer::del($this->timer);
            MyTools::log('-----------------------------------------bet4');
            if (!empty($this->curbat)) {
                Logic::SendAll('Msg_BRNN_Table', $this->curbat, $this->roomRule['rid']);
            }

            $this->procedure(STAGE_RES);
        }, [], false);
    }

    /**
     * 结算
     *
     * @return void
     */
    private function ResState($code)
    {
        $this->timeStamp = time();
        $data = $this->controlHand();
        $hall = [];

        foreach ($data['table'] as $key => $val) {
            $hall[$key] = ['type' => $val['type'], 'id' => $val['id'], 'win' => 1, 'hands' => $val['hands'], 'score' => 0];
            if ($data['banker']['id'] > $val['id']) {
                $hall[$key]['win'] = 0;
                $this->history[$key]['win']++;
            } else {
                $this->history[$key]['tran']++;
            }

            if (count($this->history[$key]['info']) >= 10) {
                array_shift($this->history[$key]['info']);
                $this->history[$key]['info'] = array_values($this->history[$key]['info']);
            }

            $this->history[$key]['info'][] = $hall[$key]['win'];
        }

        $bankerscore = 0;
        $players = [];
        $sendHorse = [];
        foreach ($this->PlayerInfo as $key => $val) {
            $sum = array_sum($val['hall']);

            if ($sum <= 0 || $key == $this->banker['uid']) {
                continue;
            }

            $_score = 0;
            $fall = 0;
            $this->changScore($key, $sum);

            foreach ($val['hall'] as $key1 => $val1) {
                if ($val1 <= 0) {
                    continue;
                }

                if ($hall[$key1]['win'] == 1) {
                    $_score += BEISHU_CATTLE[$hall[$key1]['type']] * $val1;

                    $hall[$key1]['score'] += BEISHU_CATTLE[$hall[$key1]['type']] * $val1;
                } else {
                    $fall -= BEISHU_CATTLE[$data['banker']['type']] * $val1;

                    $hall[$key1]['score'] -= BEISHU_CATTLE[$data['banker']['type']] * $val1;
                }
            }

            if ($val['client_id'] != '') {
                Logic::InsertProfit($this->roomRule['level'], ($_score + $fall));
            }
            $bankerscore -= $_score + $fall;

            if ($_score > 0) {
                $_score = round($_score * $this->roomRule['rebate']);
            }

            $_score += $fall;

            $this->changScore($key, $_score);
            if ($_score >= HORSE_LAMO) {
                // Logic::HorseLamp($key, $_score, 0);
                $sendHorse[$key] = $_score;
            }

            $players[$key] = [
                'gold' => $this->PlayerInfo[$key]['gold'],
                'score' => $_score,
                'nickname' => $val['nickname']
            ];

            if ($val['client_id'] != '') {
                DBInstance::IncrementUserGet($key, $_score);
            }
        }

        if ($this->PlayerInfo[$this->banker['uid']]['client_id'] != '') {
            Logic::InsertProfit($this->roomRule['level'], $bankerscore);
        }

        if ($bankerscore >= HORSE_LAMO) {
            // Logic::HorseLamp($this->banker['uid'], $_score, 0);
            $sendHorse[$this->banker['uid']] = $bankerscore;
        }

        $this->changScore($this->banker['uid'], $bankerscore);

        if ($this->PlayerInfo[$this->banker['uid']]['client_id'] != '') {
            DBInstance::IncrementUserGet($this->banker['uid'], $bankerscore);
        }

        $players[$this->banker['uid']] = [
            'gold' => $this->PlayerInfo[$this->banker['uid']]['gold'],
            'score' => $bankerscore,
            'nickname' => $this->banker['nickname']
        ];

        Logic::SendAll('Msg_BRNN_Res', [
            'hall' => $hall,
            'banker' => $data['banker'],
            'player' => $players,
            'time' => TIEM_RES,
            'isadvance' => $code
        ], $this->roomRule['rid']);
        $this->banker['gold'] = $this->PlayerInfo[$this->banker['uid']]['gold'];

        $this->timer = Timer::add(TIEM_RES, function () {
            Timer::del($this->timer);
            MyTools::log('-----------------------------------------bet2');
            $this->procedure(STAGE_START);
        }, [], false);

        $this->sendHorse($sendHorse);
    }

    private function sendHorse($players)
    {
        if (empty($players)) {
            return;
        }

        arsort($players);
        $i = 1;
        foreach ($players as $key => $val) {
            $i--;
            Logic::HorseLamp($key, $val, 0);
            if ($i <= 0) {
                break;
            }
        }
    }

    /**
     * 根据场控得牌
     */
    private function controlHand()
    {
        //$region = DBInstance::GetDuoRenControl($this->roomRule['gtype'], $this->roomRule['level']);
        $region = 0;
        if ($region <= 0) {
            $controlnum = DBInstance::GetControlRand($this->roomRule['gtype']);
            if (rand(0, 100) <= $controlnum) {
                if ($this->PlayerInfo[$this->banker['uid']]['client_id'] == '') {
                    $control = 2;
                } else {
                    $control = 1; //庄家输
                }
            }
        }

        $data = [];

        for ($i = 0; $i < 5; $i++) {
            $hands = [];
            for ($j = 0; $j < HANDS_ALL_HANDS; $j++) {
                $hands[] = array_shift($this->hands);
            }

            $type = Cards::checkCattle($hands);
            $id = Cards::GetId($type['type'], $hands);

            $data[$i] = [
                'hands' => $type['cards'],
                'type' => $type['type'],
                'id' => $id
            ];
        }

        $temp = [];
        $banker = [];
        if (isset($control)) {
            $hall = $this->hall;
            foreach ($this->PlayerInfo as $key => $val) {
                if (($this->PlayerInfo[$this->banker['uid']]['client_id'] != '' && $val['client_id'] != '') || ($this->PlayerInfo[$this->banker['uid']]['client_id'] == '' && $val['client_id'] == '')) {
                    foreach ($val['hall'] as $key1 => $val1) {
                        $hall[$key1]['score'] -= $val1;
                    }
                }
            }

            foreach ($data as $key => $val) {
                $_temp = [];
                foreach ($hall as $key1 => $va1l) {
                    if ($key1 == $key) {
                        $_temp[$key1] = $data[0];
                        continue;
                    }

                    $_temp[$key1] = $data[$key1];
                }

                $score = 0;
                foreach ($_temp as $key1 => $val1) {
                    if ($val['id'] > $val1['id']) {
                        $score += BEISHU_CATTLE[$val['type']] * $hall[$key1]['score'];
                    } else {
                        $score -= BEISHU_CATTLE[$val['type']] * $hall[$key1]['score'];
                    }
                }

                if (($control == 1 && $score <= 0) || ($control == 2 && $score >= 0)) {
                    $temp = $_temp;
                    $banker = $val;
                    break;
                }
            }
        } elseif ($region > 0) {
            $win = intval($region / 10);
            $lose = $region % 10;
            if ($win >= 0 && $win < 5 && $lose >= 0 && $lose < 5 && $win != $lose) {
                $list = array_column($data, 'id');
                $_data = [];
                foreach ($data as $key => $value) {
                    if ($value['id'] == max($list)) {
                        $_data[$win] = $value;
                        unset($data[$key]);
                    } elseif ($value['id'] == min($list)) {
                        $_data[$lose] = $value;
                        unset($data[$key]);
                    }
                }

                for ($i = 0; $i < 5; $i++) {
                    if (!isset($_data[$i])) {
                        $_data[$i] = array_shift($data);
                    }
                }
                $data = $_data;
            }
        }

        if (empty($banker) || empty($temp)) {
            $banker = $data[0];
            unset($data[0]);
            $temp = $data;
        }

        return ['table' => $temp, 'banker' => $banker];
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_BRNN_Act_Bet':
                $this->Msg_BRNN_Act_Bet($message);
                break;
            case 'Msg_BRNN_Act_Banker':
                $this->Msg_BRNN_Act_Banker($message);
                break;
            case 'Msg_BRNN_Out':
                $this->Msg_BRNN_Out($message);
                break;
            case 'Msg_BRNN_Act_BankerOut':
                $this->Msg_BRNN_Act_BankerOut($message);
                break;
            case 'Msg_BRNN_GetUserList':
                $this->Msg_BRNN_GetUserList($message);
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
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_BRNN_Act_Bet($msg)
    {
        if ($this->gameState != STAGE_START) {
            Logic::SendError($msg['uid'], 'Msg_BRNN_Act_Bet', '阶段错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['gold'] + array_sum($this->PlayerInfo[$msg['uid']]['hall']) < ($msg['data']['bat'] + array_sum($this->PlayerInfo[$msg['uid']]['hall'])) * max(BEISHU_CATTLE)) {
            Logic::SendError($msg['uid'], 'Msg_BRNN_Act_Bet', '携带金币须大于等于最大赔付倍数');
            return;
        }

        if ($msg['data']['bat'] % 1000 != 0 || $msg['uid'] == $this->banker['uid']) {
            Logic::SendError($msg['uid'], 'Msg_BRNN_Act_Bet', '下注金额错误');
            return;
        }

        if ($msg['data']['bat'] + $this->PlayerInfo[$msg['uid']]['hall'][$msg['data']['code']] > $this->roomRule['controls']['maxbet']) {
            Logic::SendError($msg['uid'], 'Msg_BRNN_Act_Bet', '单区域最高下注1000万');
            return;
        }

        $code = $this->checkbankerscore($msg['data']['bat']);
        if ($code == 3) {
            Logic::SendError($msg['uid'], 'Msg_BRNN_Act_Bet', '庄家不够赔付');
            return;
        }

        $this->hall[$msg['data']['code']]['score'] += $msg['data']['bat'];

        $this->changScore($msg['uid'], -$msg['data']['bat']);
        $data = [
            'code' => $msg['data']['code'],
            'bat' => $msg['data']['bat'],
            'gold' => $this->PlayerInfo[$msg['uid']]['gold'],
        ];
        if (!isset($this->curbat[$msg['uid']])) {
            $this->curbat[$msg['uid']] = [
                BAT_TYPE_TIAN => 0, //天
                BAT_TYPE_DI => 0, //地
                BAT_TYPE_XUAN => 0, //玄
                BAT_TYPE_HUANG => 0 //黄
            ];
        }
        $this->curbat[$msg['uid']][$msg['data']['code']] += $msg['data']['bat'];

        $this->PlayerInfo[$msg['uid']]['hall'][$msg['data']['code']] += $msg['data']['bat'];
        //缓存下注信息
        if (!empty($this->PlayerInfo[$msg['uid']]['client_id'])) {
            if (empty($this->playerBet[$msg['uid']])) {
                $this->playerBet[$msg['uid']] = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            }
            $this->playerBet[$msg['uid']][$msg['data']['code']] += $msg['data']['bat'];
        }

        Logic::SendRight($msg['uid'], 'Msg_BRNN_Act_Bet', $data);

        if ($code == 2) {
            Timer::del($this->timer);
            MyTools::log('-----------------------------------------bet3');
            if (!empty($this->curbat)) {
                $data = [];
                foreach ($this->hall as $key => $val) {
                    $data[$key] = $val['score'];
                }
                Logic::SendAll('Msg_BRNN_Table', $data, $this->roomRule['rid']);
            }

            $this->procedure(STAGE_RES, false);
        }
    }

    private function checkbankerscore($bat)
    {
        $count = $bat;
        foreach ($this->hall as $key => $val) {
            $count += $val['score'];
        }
        $code = 1;
        $score = $count * max(BEISHU_CATTLE);

        if ($this->PlayerInfo[$this->banker['uid']]['gold'] < $score) {
            $code = 3;
        }

        return $code;
    }

    /**
     * 玩家申请当庄
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_BRNN_Act_Banker($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['gold'] < BANKER_GOLD_BRNN) {
            Logic::SendError($msg['uid'], 'Msg_BRNN_Act_Banker', '上庄金币不足');
            return;
        }

        if (in_array($msg['uid'], $this->applyBanker) || $this->banker['uid'] == $msg['uid']) {
            Logic::SendError($msg['uid'], 'Msg_BRNN_Act_Banker', '已经申请上庄');
            return;
        }

        $this->applyBanker[] = $msg['uid'];
        Logic::SendAll('Msg_BRNN_Act_Banker', ['uid' => $msg['uid']], $this->roomRule['rid']);
    }

    /**
     * 玩家放弃当庄
     * @param [type] $msg
     * @return void
     */
    private function Msg_BRNN_Act_BankerOut($msg)
    {
        if ($msg['uid'] != $this->banker['uid'] && !in_array($msg['uid'], $this->applyBanker)) {
            Logic::SendError($msg['uid'], 'Msg_BRNN_Act_BankerOut', '正在游戏中');
            return;
        }

        if ($this->banker['uid'] == $msg['uid']) {
            $this->banker['isbanker'] = true;
        } else {
            $this->applyBanker = array_diff($this->applyBanker, [$msg['uid']]);
            $this->applyBanker = array_values($this->applyBanker);
        }

        Logic::SendAll('Msg_BRNN_Act_BankerOut', ['uid' => $msg['uid']], $this->roomRule['rid']);
    }

    /**
     * 玩家退出房间
     * @param [type] $msg
     * @return void
     */
    private function Msg_BRNN_Out($msg)
    {
        if (($this->gameState != STAGE_RES && array_sum($this->PlayerInfo[$msg['uid']]['hall']) > 0) || ($msg['uid'] == $this->banker['uid'] && $this->disRoom == 0)) {
            Logic::SendError($msg['uid'], 'Msg_BRNN_Out', '正在游戏中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_BRNN_Out', ['uid' => $msg['uid'], 'gold' => $gold], $this->roomRule['rid']);

        if (in_array($msg['uid'], $this->applyBanker)) {
            $this->applyBanker = array_diff($this->applyBanker, [$msg['uid']]);
        }

        unset($this->PlayerInfo[$msg['uid']]);

        $data = ['uid' => $msg['uid'], 'rid' => $this->roomRule['rid']];
        Logic::QuitRoom($data);
    }

    /**
     * 玩家信息
     * @param [type] $msg
     * @return void
     */
    private function Msg_BRNN_GetUserList($msg)
    {
        $players = [];
        foreach ($this->PlayerInfo as $key => $val) {
            $players[$key] = [
                'nickname' => $val['nickname'],
                'gold' => $val['gold'],
                'headimgurl' => $val['headimgurl']
            ];
        }

        Logic::SendRight($msg['uid'], 'Msg_BRNN_GetUserList', ['players' => $players]);
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

    /**
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['client_id'] != '') {
                $this->All_RECV(['event' => 'Msg_BRNN_Out', 'uid' => $key]);
            }
        }
        MyTools::log('---------------------------------------------deltimer');
        Timer::del($this->aiActTimer);
        Timer::del($this->timer);

        $olddata = [
            'rid' => $this->roomRule['rid'],
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
        $this->PlayerInfo[$uid]['onlinetime'] = 0;

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
        $this->PlayerInfo[$uid]['onlinetime'] = time() + OUT_TIME;
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
        Logic::SendAll('Msg_BRNN_Add', [
            // 'uid' => $msg['uid'],
            // 'nickname' => $msg['nickname'],
            // 'headimgurl' => $msg['headimgurl'],
            // 'gold' => $msg['gold']
        ], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);

        $this->roomInfo($msg['uid']);

        if ($this->banker['uid'] != 0 && $this->circle < 1) {
            MyTools::log('-----------------------------------------bet1');
            $this->procedure(STAGE_START);

            $this->AItimer();
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

        // $this->OldRoom();
    }

    private function AItimer()
    {
        $this->aiActTimer = Timer::add(2, function () {
            if ($this->gameState == STAGE_START) {
                $num = 0;
                $playercount = count($this->PlayerInfo);
                //DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet);
                foreach ($this->PlayerInfo as $key => $val) {
                    if ($key == $this->banker['uid']) {
                        continue;
                    }

                    $rand = rand(0, 100);

                    if ($val['client_id'] == '' && $rand <= 50) {
                        $count = array_sum($val['hall']);
                        if ($count <= 0 && $playercount >= $this->roomRule['vals']['min']) {
                            if (!method_exists($this->PlayerInfo[$key]['robotfun'], 'QuitRoom')) {
                                MyTools::msg($key . "=========================" . json_encode($this->PlayerInfo[$key]));
                            } else {
                                $code = $this->PlayerInfo[$key]['robotfun']->QuitRoom($val['gold']);
                                if ($code) {
                                    $this->Msg_BRNN_Out(['uid' => $key]);
                                    continue;
                                }
                            }
                        }

                        $num++;

                        $data = $val['robotfun']->bat($val['gold'] * max(BEISHU_CATTLE));

                        $this->All_RECV(['uid' => $key, 'event' => 'Msg_BRNN_Act_Bet', 'data' => $data]);

                        if (in_array($key, $this->applyBanker) && $val['gold'] < BANKER_GOLD_BRNN) {
                            $this->All_RECV(['uid' => $key, 'event' => 'Msg_BRNN_Act_BankerOut']);
                        }

                        if ((count($this->applyBanker) < 5 && $val['gold'] >= BANKER_GOLD_BRNN && !in_array($key, $this->applyBanker))) {

                            $this->All_RECV(['uid' => $key, 'event' => 'Msg_BRNN_Act_Banker']);
                        }
                    }
                }
                if (!empty($this->curbat)) {
                    Logic::SendAll('Msg_BRNN_Table', $this->curbat, $this->roomRule['rid']);
                }

                $this->curbat = [];
            } else {
                foreach ($this->PlayerInfo as $key => $val) {
                    if ($val['onlinetime'] == 0) {
                        continue;
                    }

                    if ($val['onlinetime'] < time()) {
                        $this->Msg_BRNN_Out(['uid' => $key]);
                    }
                }
            }
        });
    }
}

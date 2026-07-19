<?php

date_default_timezone_set("Asia/Shanghai");

require_once __DIR__ . '/Cards.php';
require_once __DIR__ . '/AI.php';

use app\pay\controller\Time;
use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('ZJH_STATE_WAIT', 0); //等待
define('ZJH_STATE_FA', 1); //发牌
define('ZJH_STATE_BET', 2); //下注
define('ZJH_STATE_RES', 3); //结算
define('ZJH_STATE_OLD', 4); //解散房间

define('ZJH_TIME_FA', 0.8); //发牌时间 1
define('ZJH_TIME_BET', 15); //下注时间 15
define('ZJH_TIME_RES', 15); //结算时间 15
define('ZJH_TIME_COMPARE', 7); //比牌时间 15

define('ZJH_CIRCLE', 20); //结束局数

define('ZJH_PNUM_MIN', 2); //最小人数
define('ZJH_OUT_BEISHU', 100); //最小倍数
class Table
{
    private $roomRule = []; //房间信息

    private $PlayerInfo = []; //玩家信息

    private $gameState = ZJH_STATE_WAIT; //游戏阶段

    private $banker = 0; //庄家uid

    private $timer = 0; //定时器

    private $seatUid = [];

    private $current = -1; //当前正操作玩家seat

    private $winner = -1; //赢得玩家

    private $timeTemp = 0; //定时器

    private $Cards = []; //牌堆

    private $table = 0; //桌子上的钱

    private $curbet = 0; //没有看牌当前下注金额

    private $circle = 1; //当前局数

    private $actNum = 0; //当有效玩家人数

    private $resData = []; //结算消息

    private $beishuarr = [];

    private $betAll = 0; //全下

    private $betAllinfo = []; //全下的两个人的情况[$uid=>['ishow'=>0看牌情况,'launch'=>0发起人]]

    private $compare = 0; //比牌状态

    private $compareInfo = []; //比牌信息

    private $disRoom = 0;

    private $AiData = [];

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

        $this->roomInfo();

        for ($i = 0; $i <= 5; $i++) {
            if ($i == 0) {
                $score = $msg['doublescore'];
            } else {
                $score = $i * 2 * $msg['doublescore'];
            }
            $this->beishuarr[] = $score;
        }

        if (count($this->PlayerInfo) >= ZJH_PNUM_MIN) {
            $count = 0;
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['gold'] >= $this->roomRule['doublescore'] * ZJH_OUT_BEISHU) {
                    $count++;
                }
            }

            if ($count >= ZJH_PNUM_MIN) {
                if ($this->disRoom == 1) {
                    $this->disroomfun();
                    return;
                }

                $this->procedure(ZJH_STATE_FA);
            }
        }
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
                'online' => $val['online'],
                'ishow' => $val['ishow'], //0没有看牌 1 看牌
                'seat' => $val['seat'],
                'headimgurl' => $val['headimgurl'],
                'bat' => $val['bat'],
                'ispass' => $val['ispass'],
                'isfail' => $val['isfail'],
                'brightCard' => $val['brightCard'],
            ];
        }

        $msg = [];

        if ($this->gameState == ZJH_STATE_RES) {
            $msg = $this->resData;
            $msg['time'] =  $this->resData['time'] - (time() - $this->timeTemp);
        } elseif ($this->gameState == ZJH_STATE_BET) {
            if ($this->compare == 0) {
                $time = ZJH_TIME_BET -  (time() - $this->timeTemp);
                $time = $time < 0 ? 1 : $time;
                $msg = [
                    'uid' => $this->seatUid[$this->current],
                    'curbet' => $this->curbet,
                    'circle' => $this->circle,
                    'time' => $time,
                ];
            } else {
                $msg = $this->compareInfo;
            }
        } elseif ($this->gameState == ZJH_STATE_FA) {
            $time = ZJH_TIME_FA * $this->actNum -  (time() - $this->timeTemp);
            $msg = [
                'time' => $time
            ];
        }

        $data = [
            'players' => $players,
            'gameState' => $this->gameState,
            'hands' => [],
            'curbet' =>  $this->curbet,
            'table' => $this->table,
            'doublescore' => $this->roomRule['doublescore'],
            'banker' => $this->banker,
            'circle' => $this->circle,
            'msg' => $msg,
            'betAll' => $this->betAll,
            'compare' => $this->compare,
        ];

        if ($uid != 0) {
            $data['hands'] = $this->PlayerInfo[$uid]['ishow'] == 0 ? [] : $this->PlayerInfo[$uid]['hands'];
            Logic::SendRight($uid, 'Msg_ZJH_RoomInfo', $data);
        } else {
            Logic::SendAll('Msg_ZJH_RoomInfo', $data, $this->roomRule['rid']);
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

        if ($this->winner <= 0 && $data['gold'] > $this->roomRule['doublescore'] * ZJH_OUT_BEISHU) {
            $this->winner = $uid;
        }

        $this->PlayerInfo[$uid] = [
            'nickname' => $data['nickname'],
            'gold' => $data['gold'],
            'headimgurl' => $data['headimgurl'],
            'client_id' => $data['client_id'],
            'seat' => $data['seat'],
            'hands' => [], //手牌
            'online' => 1, //1在线 0离线
            'ishow' => 0, //0没有看牌 1 看牌
            'ispass' => 0, //0没有丢牌 1 丢牌
            'ompare' => [], //比牌玩家
            'bat' => 0, //下注金额
            'isfail' => 0, //比牌失败 1失败
            'brightCard' => 0, //亮牌
            'handsid' => 0,
            'lifeTime' => 0,
            'type' => 0
        ];

        if ($data['client_id'] != '') {
            Gateway::joinGroup($data['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->PlayerInfo[$uid]['lifeTime'] = time() + rand($this->roomRule['vals']['lifeTime'][0], $this->roomRule['vals']['lifeTime'][1]);
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
        switch ($this->gameState) {
            case ZJH_STATE_FA:
                $this->StageFa();
                break;
            case ZJH_STATE_BET:
                $this->StageBet();
                break;
            case ZJH_STATE_RES:
                $this->StageRes();
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
        $this->Cards = Cards::InitCards();

        $this->actNum = 0;
        $this->AiData = [];
        $player = [];
        foreach ($this->PlayerInfo as $key => $val) {
            $this->PlayerInfo[$key]['brightCard'] = 0;
            $this->PlayerInfo[$key]['ishow'] = 0;
            $this->PlayerInfo[$key]['hands'] = [];
            $this->PlayerInfo[$key]['handsid'] = 0;
            $this->PlayerInfo[$key]['type'] = 0;

            $this->PlayerInfo[$key]['ispass'] = 0;
            $this->PlayerInfo[$key]['ompare'] = [];
            $this->PlayerInfo[$key]['bat'] = 0;

            if ($val['gold'] >= $this->roomRule['doublescore'] * ZJH_OUT_BEISHU) {
                if ($this->winner == 0) {
                    $this->winner = $key;
                }

                $this->actNum++;
                $player[] = $key;
            }
        }

        $this->playercontr =  DBInstance::GetChessCardControl($this->roomRule['gtype'], $this->roomRule['level'], $player);

        $this->controlFa();

        $this->current =  $this->PlayerInfo[$this->winner]['seat'];

        $this->banker = $this->winner;

        $this->circle = 1;

        $this->compare = 0;

        $this->betAll = 0;

        $this->table = 0;

        $this->resData = [];

        $this->compareInfo = [];

        $this->betAllinfo = [];

        Logic::TableStatus($this->roomRule['rid'], 1);
    }

    /**
     * 场控发牌
     * @return void
     */
    private function controlFa()
    {
        $res = [];
        $maxid = 0;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['gold'] >= $this->roomRule['doublescore'] * ZJH_OUT_BEISHU) {
                $hands = [];
                for ($i = 0; $i < ZJH_HANDS_NUM; $i++) {
                    $hands[] = array_shift($this->Cards);
                }
                $data = Cards::ChechType($hands);
                $res[$data['id']] = ['hands' => $hands, 'type' => $data['type']];
                if ($data['id'] > $maxid) {
                    $maxid = $data['id'];
                }
            }
        }

        $playerarr = $this->playercontr;
        $max = max($playerarr);
        $uids = [];
        foreach ($playerarr as $key => $val) {
            if ($val == $max) {
                $uids[$key] = $val;
            }
        }

        $uid = array_rand($uids);
        $this->PlayerInfo[$uid]['hands'] = $res[$maxid]['hands'];
        $this->PlayerInfo[$uid]['handsid'] = $maxid;
        $this->PlayerInfo[$uid]['type'] = $res[$maxid]['type'];
        $this->AiData[$uid] = $maxid;
        unset($res[$maxid]);
        unset($playerarr[$uid]);

        foreach ($playerarr as $key => $val) {
            foreach ($res as $key1 => $val1) {
                $this->PlayerInfo[$key]['hands'] = $val1['hands'];
                $this->PlayerInfo[$key]['handsid'] = $key1;
                $this->PlayerInfo[$key]['type'] = $val1['type'];
                $this->AiData[$key] = $key1;
                unset($res[$key1]);
                break;
            }
        }
    }

    /**
     * 发牌
     * @return void
     */
    private function StageFa()
    {
        $this->Init();

        $this->timeTemp = time();

        $players = [];

        foreach ($this->PlayerInfo as $key => $val) {
            if (!empty($val['hands'])) {
                $this->changScore($key, -$this->roomRule['doublescore']);
                $this->PlayerInfo[$key]['bat'] += $this->roomRule['doublescore'];
                $this->table += $this->roomRule['doublescore'];
                $players[$key] = [
                    'cardsNum' => ZJH_HANDS_NUM,
                    'gold' => $this->PlayerInfo[$key]['gold'],
                    'curscore' => $this->roomRule['doublescore']
                ];
            }
        }

        $this->curbet = $this->roomRule['doublescore'];
        $time = ZJH_TIME_FA * $this->actNum;
        $data = [
            'player' => $players,
            'table' =>   $this->table,
            'time' => $time,
            'banker' => $this->banker
        ];

        Logic::SendAll('Msg_ZJH_Fa', $data, $this->roomRule['rid']);

        $this->timer = Timer::add($time, function () {
            Timer::del($this->timer);
            $this->procedure(ZJH_STATE_BET);
        }, [], false);
    }

    /**
     * 通知玩家下注
     * @return void
     */
    private function StageBet()
    {
        $this->timeTemp = time();
        $data = [
            'uid' => $this->seatUid[$this->current],
            'curbet' => $this->curbet,
            'circle' => $this->circle,
            'time' => ZJH_TIME_BET,
            'betAll' => $this->betAll
        ];
        Logic::SendAll('Msg_ZJH_Bet', $data, $this->roomRule['rid']);

        if ($this->PlayerInfo[$this->seatUid[$this->current]]['client_id'] == '') {
            $this->AIAct($data);
        }

        if ($this->actNum == 2) {
            $score = $this->PlayerInfo[$this->seatUid[$this->current]]['gold'];
            if ($this->PlayerInfo[$this->seatUid[$this->current]]['ishow'] == 1) {
                $score *= 0.5;
            }
            if ($this->curbet > $score) {
                $this->nextPlayer(true);
                return;
            }
        }

        $this->timer = Timer::add(ZJH_TIME_BET, function () {
            //丢牌
            Timer::del($this->timer);

            $this->All_RECV(['event' => 'Msg_ZJH_Act_Discard', 'uid' => $this->seatUid[$this->current]]);
        }, [], false);
    }

    /**
     * 通知玩家解散房间
     * @return void
     */
    private function StageRes()
    {
        $this->timeTemp = time();
        $players = [];
        foreach ($this->PlayerInfo as $key => $val) {
            if (empty($val['hands'])) {
                continue;
            }

            $score = -$val['bat'];
            if ($key == $this->winner) {
                $score = $this->table;
                $this->changScore($key, $val['bat']);

                $score -= $val['bat'];
                if ($val['client_id'] != '' && abs($this->playercontr[$key]) != 2) {
                    Logic::InsertProfit($this->roomRule['level'], $score);
                }

                $score *= $this->roomRule['rebate'];
                $score = intval($score);

                $this->changScore($key, $score);
            }

            if ($val['client_id'] != '' && $score < 0 && abs($this->playercontr[$key]) != 2) {
                Logic::InsertProfit($this->roomRule['level'], $score);
            }

            $players[$key] = [
                'score' => $score,
                'ompare' => array_values($val['ompare']),
                'hands' => $val['hands'],
                'gold' => $this->PlayerInfo[$key]['gold']
            ];

            if ($this->roomRule['level'] != 1) {
                DBInstance::IncrementUserGet($key, $score);
            }

            $this->PlayerInfo[$key]['ishow'] = 0;
            $this->PlayerInfo[$key]['ispass'] = 0;
            $this->PlayerInfo[$key]['ompare'] = [];
            $this->PlayerInfo[$key]['bat'] = 0;
            $this->PlayerInfo[$key]['isfail'] = 0;
            $this->PlayerInfo[$key]['hands'] = [];
            if ($val['client_id'] == '') {
                $this->AiTimer[$key] = Timer::add(rand(1, 2), function () use ($key) {
                    Timer::del($this->AiTimer[$key]);
                    $res = AI::Msg_ZJH_Res($key, $this->PlayerInfo[$key]['lifeTime']);
                    if (!empty($res)) {
                        $this->All_RECV($res);
                    }
                }, [], false);
            }
        }

        $data = [
            'players' => $players, 'time' => ZJH_TIME_RES
        ];

        Logic::SendAll('Msg_ZJH_Res',  $data, $this->roomRule['rid']);

        $this->resData = $data;

        $this->timer = Timer::add(ZJH_TIME_RES, function () {
            Timer::del($this->timer);
            $this->gameState = ZJH_STATE_WAIT;

            $this->table = 0;

            $this->curbet = 0;

            $this->circle = 1;

            $this->betAll = 0;

            $this->resData = [];

            $this->compareInfo = [];

            $count = 0;
            $aicont = 0;
            foreach ($this->PlayerInfo as $key => $val) {
                $this->PlayerInfo[$key]['brightCard'] = 0;
                if ($val['gold'] < $this->roomRule['doublescore'] || $val['online'] == 0 || ($val['client_id'] == '' && $val['gold'] < $this->roomRule['doublescore'] * ZJH_OUT_BEISHU)) {
                    $this->Msg_ZJH_Out(['uid' => $key]);
                    continue;
                }

                if ($val['gold'] >= $this->roomRule['doublescore'] * ZJH_OUT_BEISHU) {
                    $count++;
                    if ($val['client_id'] == '') {
                        $aicont++;
                    }
                }
            }

            if ($count >= ZJH_PNUM_MIN || $this->disRoom == 1) {
                // if (count($this->PlayerInfo) == $aicont && $aicont == $count && $this->disRoom == 0) {
                //     foreach ($this->PlayerInfo as $key => $val) {
                //         $this->Msg_ZJH_Out(['uid' => $key]);
                //     }
                // } else {
                if ($this->disRoom == 1) {
                    $this->disroomfun();
                    return;
                }

                $this->procedure(ZJH_STATE_FA);
                // }
            }
        }, [], false);

        Logic::TableStatus($this->roomRule['rid'], 0);
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_ZJH_Act_Discard':
                $this->Msg_ZJH_Act_Discard($message);
                break;
            case 'Msg_ZJH_Act_Bet':
                $this->Msg_ZJH_Act_Bet($message);
                break;
            case 'Msg_ZJH_Act_Compare':
                $this->Msg_ZJH_Act_Compare($message);
                break;
            case 'Msg_ZJH_Act_Show':
                $this->Msg_ZJH_Act_Show($message);
                break;
            case 'Msg_ZJH_Act_BetAll':
                $this->Msg_ZJH_Act_BetAll($message);
                break;
            case 'Msg_ZJH_Out':
                $this->Msg_ZJH_Out($message);
                break;
            case 'Msg_ZJH_Act_BrightCard':
                $this->Msg_ZJH_Act_BrightCard($message);
                break;
            default: {
                    Logic::SendError($message['uid'], $message['event'], '');

                    MyTools::msg('uid:' . $message['uid'] . '-----UnKnown!!!!!!!--------' . $message['event'], true);
                    break;
                }
        }
    }

    /**
     * 玩家下注
     * @param [type] $msg
     * @return void
     */
    private function Msg_ZJH_Act_Bet($msg)
    {
        if ($this->seatUid[$this->current] != $msg['uid']) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if ($this->gameState != ZJH_STATE_BET) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if ($this->compare == 1) {
            Logic::SendError($msg['uid'], $msg['event'], '下注失败');
            return;
        }

        $score = $msg['data']['bet'];
        if ($this->PlayerInfo[$msg['uid']]['ishow'] == 1) {
            $score *= 0.5;
        }

        if (!in_array($score, $this->beishuarr)) {
            Logic::SendError($msg['uid'], $msg['event'], '分数错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['gold'] < $msg['data']['bet'] || $this->curbet > $score) {
            Logic::SendError($msg['uid'], $msg['event'], '下注失败');
            return;
        }

        if ($this->circle > ZJH_CIRCLE) {
            Logic::SendError($msg['uid'], $msg['event'], '下注失败');
            return;
        }

        $this->curbet =  $score;

        Timer::del($this->timer);

        $this->changScore($msg['uid'], -$msg['data']['bet']);

        Logic::SendAll('Msg_ZJH_Act_Bet', [
            'uid' => $msg['uid'],
            'bet' => $msg['data']['bet'],
            'gold' => $this->PlayerInfo[$msg['uid']]['gold'],
            'curbet' => $score
        ], $this->roomRule['rid']);

        $this->table +=  $msg['data']['bet'];
        $this->PlayerInfo[$msg['uid']]['bat'] += $msg['data']['bet'];

        if ($this->betAll > 0) {
            $this->nextPlayer(true);
            return;
        }

        $this->nextPlayer();
        if ($this->circle <= ZJH_CIRCLE) {
            $this->StageBet();
        }
    }

    //全下
    private function Msg_ZJH_Act_BetAll($msg)
    {
        if ($this->actNum != 2 || $this->compare == 1) {
            Logic::SendError($msg['uid'], 'Msg_ZJH_Act_BetAll', '全下失败，游戏中玩家必须为2人');
            return;
        }

        if ($this->seatUid[$this->current] != $msg['uid']) {
            Logic::SendError($msg['uid'], $msg['event'], '操作失败');
            return;
        }

        if ($this->gameState != ZJH_STATE_BET) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        $scores = $this->betAll;
        $_score = 0;
        if ($scores <= 0) {
            $this->betAllinfo[$msg['uid']] = [
                'ishow' => $this->PlayerInfo[$msg['uid']]['ishow'],
                'launch' => 1,
            ];

            $score = 0;
            $ishow = -1;
            $gold = 0;
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['ispass'] == 0 && $val['isfail'] == 0) {
                    if ($score == 0 || $val['gold'] < $score) {
                        $score = $val['gold'];
                    }

                    if (!isset($this->betAllinfo[$key])) {
                        $ishow = $val['ishow'];
                        $gold = $val['gold'];
                        $this->betAllinfo[$key] = [
                            'ishow' => $val['ishow'],
                            'launch' => 0,
                        ];
                    }
                }
            }

            $uids = array_keys($this->betAllinfo);
            foreach ($this->betAllinfo as $key => $val) {
                $_uids = array_diff($uids, [$key]);
                $ompare = $this->PlayerInfo[$key]['ompare'];
                foreach ($_uids as $key1 => $val1) {
                    $ompare[] = $val1;
                }
                $this->PlayerInfo[$key]['ompare'] = $ompare;
            }

            $_score = $score;
            $this->betAll = $score;
            if ($this->betAllinfo[$msg['uid']]['ishow'] == $ishow) {
                if ($ishow == 0) {
                    $this->betAll = intval($score * 0.5);
                    $_score = $this->betAll;
                }
            } elseif ($this->betAllinfo[$msg['uid']]['ishow'] == 1 && $ishow == 0) {
                if ($gold == $score) {
                    if ($score * 2 > $this->PlayerInfo[$msg['uid']]['ishow']) {
                        $_score = $this->PlayerInfo[$msg['uid']]['gold'];
                    }
                }

                $this->betAll = intval($_score * 0.5);
            } else {
                if ($score != $gold && $score * 2 < $gold) {
                    $_score = $score * 2;
                    $this->betAll = $score * 2;
                }

                $_score = intval($_score * 0.5);
            }
        }

        if ($_score == 0) {
            $_score = $this->betAll;
        }
        $this->changScore($msg['uid'], -$_score);

        $this->table +=  $_score;
        $this->PlayerInfo[$msg['uid']]['bat'] += $_score;

        Logic::SendAll('Msg_ZJH_Act_BetAll', ['score' => $_score, 'uid' => $msg['uid']], $this->roomRule['rid']);
        if ($scores > 0) {
            $this->nextPlayer(true);
        } else {
            $this->nextPlayer();
            $this->StageBet();
        }
    }
    /**
     * 玩家比牌
     * @param [type] $msg
     * @return void
     */
    private function Msg_ZJH_Act_Compare($msg)
    {
        if ($this->seatUid[$this->current] != $msg['uid']) {
            Logic::SendError($msg['uid'], $msg['event'], '比牌失败');
            return;
        }

        if ($this->gameState != ZJH_STATE_BET) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }
        
        $bet = $this->curbet;
        if ($this->PlayerInfo[$msg['uid']]['ishow'] == 1) {
            $bet *= 2;
        }

        if ($bet > $this->PlayerInfo[$msg['uid']]['gold'] || empty($this->PlayerInfo[$msg['data']['uid']]['hands']) || $this->compare == 1) {
            Logic::SendError($msg['uid'], $msg['event'], '比牌失败');
            return;
        }

        if (empty($this->PlayerInfo[$msg['data']['uid']]['hands']) || $this->betAll > 0) {
            Logic::SendError($msg['uid'], $msg['event'], '比牌失败');
            return;
        }

        Timer::del($this->timer);
        $this->changScore($msg['uid'], -$bet);

        $myid = $this->PlayerInfo[$msg['uid']]['handsid'];
        $otherid = $this->PlayerInfo[$msg['data']['uid']]['handsid'];

        $uid = $msg['uid'];
        if ($otherid > $myid) {
            $uid = $msg['data']['uid'];
        }
        $isfail = $uid == $msg['uid'] ? $msg['data']['uid'] : $msg['uid'];

        $data = [
            'winuid' => $uid,
            'failuid' => $isfail,
            'uid' => $msg['uid'],
            'bet' => $bet,
            'gold' => $this->PlayerInfo[$msg['uid']]['gold']
        ];

        unset($this->AiData[$isfail]);

        Logic::SendAll('Msg_ZJH_Act_Compare', $data, $this->roomRule['rid']);

        $this->table +=  $bet;
        $this->PlayerInfo[$msg['uid']]['bat'] += $bet;
        $this->PlayerInfo[$isfail]['isfail'] = 1;
        $this->PlayerInfo[$msg['uid']]['ompare'][] = $msg['data']['uid'];
        $this->PlayerInfo[$msg['data']['uid']]['ompare'][] = $msg['uid'];

        $this->compare = 1;

        $this->compareInfo = $data;

        Timer::add(ZJH_TIME_COMPARE, function () {
            Timer::del($this->timer);
            $this->compare = 0;
            $this->compareInfo = [];
            $this->checkWin();
        }, [], false);
    }
    /**
     * 玩家丢牌
     * @param [type] $msg
     * @return void
     */
    private function Msg_ZJH_Act_Discard($msg)
    {
        if ($this->gameState != ZJH_STATE_BET) {
            Logic::SendError($msg['uid'], $msg['event'], '弃牌失败');
            return;
        }

        if (empty($this->PlayerInfo[$msg['uid']]['hands']) ||  ($this->betAll > 0 && $this->seatUid[$this->current] != $msg['uid']) || $this->PlayerInfo[$msg['uid']]['ispass'] == 1 ||  $this->PlayerInfo[$msg['uid']]['isfail'] == 1) {
            Logic::SendError($msg['uid'], $msg['event'], '弃牌失败');
            return;
        }

        if ($this->compare == 1 && ($this->seatUid[$this->current] == $msg['uid'] || in_array($msg['uid'], $this->PlayerInfo[$this->seatUid[$this->current]]['ompare']))) {
            Logic::SendError($msg['uid'], $msg['event'], '正在比牌');
            return;
        }

        Timer::del($this->timer);

        unset($this->AiData[$msg['uid']]);

        $this->PlayerInfo[$msg['uid']]['ispass'] = 1;

        Logic::SendAll('Msg_ZJH_Act_Discard', ['uid' => $msg['uid']], $this->roomRule['rid']);

        $this->checkWin();
    }

    //看牌
    private function Msg_ZJH_Act_Show($msg)
    {
        if ($this->gameState != ZJH_STATE_BET) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if (
            empty($this->PlayerInfo[$msg['uid']]['hands']) || $this->PlayerInfo[$msg['uid']]['ishow'] == 1 || $this->circle <= 1 ||
            ($this->betAll > 0 && $this->seatUid[$this->current] != $msg['uid']) ||
            $this->PlayerInfo[$msg['uid']]['ispass'] == 1 ||  $this->PlayerInfo[$msg['uid']]['isfail'] == 1
        ) {
            Logic::SendError($msg['uid'], $msg['event'], '已经弃牌');
            return;
        }

        if ($this->compare == 1 && ($this->seatUid[$this->current] == $msg['uid'] || in_array($msg['uid'], $this->PlayerInfo[$this->seatUid[$this->current]]['ompare']))) {
            Logic::SendError($msg['uid'], $msg['event'], '正在比牌');
            return;
        }

        $this->PlayerInfo[$msg['uid']]['ishow'] = 1;
        Logic::SendRight($msg['uid'], 'Msg_ZJH_Act_Show', ['cards' => $this->PlayerInfo[$msg['uid']]['hands'], 'uid' => $msg['uid']]);
        foreach ($this->PlayerInfo as $key => $val) {
            if ($key == $msg['uid']) {
                continue;
            }
            Logic::SendRight($key, 'Msg_ZJH_Act_Show', ['cards' => [], 'uid' => $msg['uid']]);
        }

        if ($this->betAll > 0) {
            if ($this->current == $this->PlayerInfo[$msg['uid']]['seat']) {
                if ($this->betAllinfo[$msg['uid']]['ishow'] == 0) {
                    $this->betAll *= 2;
                }
            }
        }
    }

    //亮牌
    private function Msg_ZJH_Act_BrightCard($msg)
    {
        if ($this->gameState != ZJH_STATE_RES) {
            Logic::SendError($msg['uid'], $msg['event'], '阶段错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['brightCard'] == 1) {
            Logic::SendError($msg['uid'], $msg['event'], '亮牌失败');
            return;
        }

        $this->PlayerInfo[$msg['uid']]['brightCard'] = 1;

        Logic::SendAll('Msg_ZJH_Act_BrightCard', ['uid' => $msg['uid']], $this->roomRule['rid']);
    }

    //退出房间
    private function Msg_ZJH_Out($msg)
    {
        if ($this->gameState != ZJH_STATE_RES && $this->gameState != ZJH_STATE_WAIT && !empty($this->PlayerInfo[$msg['uid']]['hands'])) {
            if ($this->PlayerInfo[$msg['uid']]['ispass'] == 0 &&  $this->PlayerInfo[$msg['uid']]['isfail'] == 0) {
                Logic::SendError($msg['uid'], 'Msg_ZJH_Out', '正在游戏中');
                return;
            }
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_ZJH_Out', ['gold' => $gold, 'uid' => $msg['uid']], $this->roomRule['rid']);

        if ($this->PlayerInfo[$msg['uid']]['client_id'] != '' && $this->PlayerInfo[$msg['uid']]['bat'] > 0 && abs($this->playercontr[$msg['uid']]) != 2) {
            Logic::InsertProfit($this->roomRule['level'], -$this->PlayerInfo[$msg['uid']]['bat']);
        }

        if (isset($this->resData['players'][$msg['uid']])) {
            unset($this->resData['players'][$msg['uid']]);
        }

        unset($this->seatUid[$this->PlayerInfo[$msg['uid']]['seat']]);
        unset($this->PlayerInfo[$msg['uid']]);
        $data = ['uid' => $msg['uid'], 'rid' => $this->roomRule['rid']];

        Logic::QuitRoom($data);

        if (empty($this->PlayerInfo)) {
            $this->OldRoom();
        }

        if ($this->winner == $msg['uid']) {
            $this->winner = 0;
            foreach ($this->PlayerInfo as $key => $val) {
                if ($this->winner == 0 && $val['gold'] >= $this->roomRule['doublescore'] * ZJH_OUT_BEISHU) {
                    $this->winner = $key;
                }
                $val['ompare'] = array_diff($val['ompare'], [$msg['uid']]);
                break;
            }
        }
    }

    //下一个玩家
    private function nextPlayer($code = false)
    {
        Timer::del($this->timer);

        $count = max(array_keys($this->seatUid)) + 1;
        $num = 0;
        for ($i = 0;; $i++) {
            $num++;

            $this->current++;
            if ($this->current >= $count) {
                $this->current -= $count;
            }

            if (isset($this->seatUid[$this->current]) && $this->seatUid[$this->current] == $this->banker) {
                $this->circle++;
            }

            if ($num > 20) {
                break;
            }

            if (isset($this->seatUid[$this->current]) && !empty($this->PlayerInfo[$this->seatUid[$this->current]]['hands']) && $this->PlayerInfo[$this->seatUid[$this->current]]['ispass'] == 0 && $this->PlayerInfo[$this->seatUid[$this->current]]['isfail'] == 0) {
                break;
            }
        };

        if ($this->circle > ZJH_CIRCLE || $code) {
            $data = ['uids' => [], 'win' => []];
            foreach ($this->PlayerInfo as $key => $val) {
                if (!empty($val['hands']) && $val['ispass'] == 0 && $val['isfail'] == 0) {
                    $data['uids'][] = $key;
                    if (empty($data['win']) || $val['handsid'] > $data['win']['id']) {
                        $data['win'] = [
                            'uid' => $key,
                            'id' => $val['handsid']
                        ];
                    }
                }
            }

            $this->winner = $data['win']['uid'];

            foreach ($this->PlayerInfo as $key => $val) {
                if (!empty($val['hands']) && $val['ispass'] == 0 && $val['isfail'] == 0) {
                    $this->PlayerInfo[$key]['isfail'] = 1;
                    $_data = array_diff($data['uids'], [$key]);
                    $this->PlayerInfo[$key]['ompare'] = $_data;
                }
            }

            $this->PlayerInfo[$this->winner]['isfail'] = 0;

            $this->procedure(ZJH_STATE_RES);
            return;
        }
    }

    /**
     * 判断赢家是谁
     * @return void
     */
    private function checkWin()
    {
        $this->actNum--;
        if ($this->actNum <= 1) {
            foreach ($this->PlayerInfo as $key => $val) {
                if (!empty($val['hands']) && $val['ispass'] == 0 && $val['isfail'] == 0) {
                    $this->winner = $key;
                    break;
                }
            }
            $this->procedure(ZJH_STATE_RES);
        } else {
            $this->nextPlayer();
            $this->StageBet();
        }
    }

    private function disroomfun()
    {
        foreach ($this->PlayerInfo as $key => $val) {
            if (isset($this->AiTimer[$key])) {
                Timer::del($this->AiTimer[$key]);
            }
            $this->All_RECV(['event' => 'Msg_ZJH_Out', 'uid' => $key]);
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

        $this->PlayerInfo[$uid]['online'] = 1;

        $this->roomInfo($uid);

        Logic::SendAll('Msg_ZJH_UserState', ['uid' => $uid, 'online' => 1], $this->roomRule['rid']);
        if ($client_id != '') {
            Gateway::joinGroup($client_id, 'ROOM:' . $this->roomRule['rid']);
        }
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
        $this->PlayerInfo[$uid]['online'] = 0;

        Logic::SendAll('Msg_ZJH_UserState', ['uid' => $uid, 'online' => 0], $this->roomRule['rid']);

        if ($this->gameState == ZJH_STATE_RES || empty($this->PlayerInfo[$uid]['hands'])) {
            $this->Msg_ZJH_Out(['uid' => $uid]);
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
        Logic::SendAll('Msg_ZJH_Add', [
            'uid' => $msg['uid'],
            'nickname' => $msg['nickname'],
            'headimgurl' => $msg['headimgurl'],
            'gold' => $msg['gold'],
            'seat' => $msg['seat'],
            'online' => 1,
            'cardsNum' => 0,
        ], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);

        $this->roomInfo($msg['uid']);
        if ($this->gameState == ZJH_STATE_WAIT || $this->disRoom == 1) {
            $count = 0;
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['gold'] >= $this->roomRule['doublescore'] * ZJH_OUT_BEISHU) {
                    $count++;
                }
            }
            if ($count >= ZJH_PNUM_MIN || $this->disRoom == 1) {
                if ($this->disRoom == 1) {
                    $this->disroomfun();
                    return;
                }

                $this->procedure(ZJH_STATE_FA);
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
        $this->PlayerInfo[$uid]['gold'] += $score;

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $uid, $score);
        }

        if ($score > 0) {
            DBInstance::IncrementWinPoint($uid, $score);
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
        $this->PlayerInfo['gold'] = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);
        Logic::SendAll('Msg_ZJH_ChangGold', ['uid' => $msg['uid'], 'gold' => $this->PlayerInfo[$msg['uid']]['gold']], $this->roomRule['rid']);
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
        if ($this->gameState == ZJH_STATE_WAIT || $this->gameState == ZJH_STATE_RES) {
            Timer::del($this->timer);
            $this->disroomfun();
        }
    }

    private function AIAct($data)
    {
        $robotData = [
            'player' => [],
            'circle' => $this->circle,
            'data' => $this->AiData,
            'control' => $this->roomRule['vals']['control'],
            'beishuarr' => $this->beishuarr,
        ];

        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isfail'] == 1 || $val['ispass'] == 1) {
                continue;
            }

            $robotData['player'][$key] = [
                'ishow' => $val['ishow'],
                'client_id' => $val['client_id'],
                'type' => $val['type']
            ];
        }

        $res = AI::Msg_ZJH_Bet($data, $robotData);
        foreach ($res as $key => $val) {
            unset($res[$key]);
            $this->AiTimer[$val['uid']] = Timer::add(rand(1, 2), function () use ($val, $res) {
                Timer::del($this->AiTimer[$val['uid']]);
                if ($this->gameState != ZJH_STATE_BET) {
                    return;
                }
                $this->All_RECV($val);
                foreach ($res as $key => $val1) {
                    $this->AiTimer[$val1['uid']] = Timer::add(rand(1, 2), function () use ($val1) {
                        Timer::del($this->AiTimer[$val1['uid']]);
                        if ($this->gameState != ZJH_STATE_BET) {
                            return;
                        }
                        $this->All_RECV($val1);
                    }, [], false);
                    break;
                }
            }, [], false);
            break;
        }
    }
}

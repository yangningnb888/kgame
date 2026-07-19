<?php

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

require_once __DIR__ . '/Cards.php';
require_once __DIR__ . '/AI.php';

define("STAGE_WAIT", 0);  //等待开始
define("STAGE_FA", 1);  //发牌
define("STAGE_RES", 2);  //结算

define('TIEM_FA', 5); //发牌时间
define('TIEM_RES', 20); //结算时间
define('TIEM_TOU', 2); //托管
define('TIEM_RES_TUO', 5);

define('OUT_BEI', 5);

define('TBNN_PNUM', 2); //玩家人数
class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $gameState = STAGE_WAIT; //游戏状态

    private  $timer = 0; //游戏流程时间戳

    private $timeStamp = 0; //时间戳

    private $seatUid = [];

    private $hands = []; //牌堆

    private $userTimer = [];

    private $pnum = 0;

    private $playershandsid = [];

    private $disRoom = -1;

    private $Aitimer = []; //机器人定时器

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
            'rebate' => (100 - $msg['rebate']) / 100,
            'controls' => $msg['controls']
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
            'istuo' => 0, //托管状态 1 托管
            'handsid' => 0, //牌id
            'type' => 0, //牌型
            'ishow' => 0, //是否看牌 1看牌
            'ready' => 0, //是否准备 1 准备
            'isrealy' => 0,
            'lifeTime' => 0,
            'control' => -999
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
                'ishow' => $val['ishow'], //是否看牌 1看牌
                'ready' => $val['ready'], //是否准备 1 准备
                'allWinScore' => $val['allWinScore'], //总共赢了多少
                'winScore' => $val['winScore'], //当局赢了多少 
                'seat' => $val['seat'],
                'isrealy' => $val['isrealy']
            ];
        }

        /**
         * 阶段消息
         */
        $msg = [];
        if ($this->gameState == STAGE_FA) {
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

        $jackpot = $this->SaveJackpot(0, 1);
        $res = [
            'players' => $players,
            'doublescore' => $this->roomRule['doublescore'],
            'gameState' => $this->gameState,
            'msg' => $msg,
            'hands' => $this->PlayerInfo[$uid]['hands'],
            'level' => $this->roomRule['level'],
            'jackpost' =>  $jackpot['jackpot']
        ];

        Logic::SendRight($uid, 'Msg_TBNN_RoomInfo', $res);
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
            // $this->PlayerInfo[$key]['istuo'] = 0;
            if ($val['ready'] == 1) {
                $this->PlayerInfo[$key]['isrealy'] = 1;
                $player[] = $key;
            }

            $this->PlayerInfo[$key]['handsid'] = 0;
            $this->PlayerInfo[$key]['type'] = 0;
            $this->PlayerInfo[$key]['ishow'] = 0;
            $this->PlayerInfo[$key]['ready'] = 0;
            $this->PlayerInfo[$key]['winScore'] = 0;
            $this->pnum++;
        }

        $players =  DBInstance::GetChessCardControl($this->roomRule['gtype'], $this->roomRule['level'], $player);
        $max = max($players);
        $winner = [];
        foreach ($players as $key => $val) {
            $this->PlayerInfo[$key]['control'] = $val;
            if ($val == $max) {
                $winner[$key] = 1;
            }
        }
        $this->winneruid = array_rand($winner);

        $this->playershandsid = [];
        $this->resdata = [];
        Logic::TableStatus($this->roomRule['rid'], 1);
    }

    /**
     * 发牌
     * 
     * @return void
     */
    private function FaState()
    {
        $this->Init();
        $this->timeStamp = time();

        $players = $this->controlFa();

        Logic::SendAll(
            'Msg_TBNN_FaCards',
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

            $time = TIEM_FA;
            if ($val['istuo'] != 0) {
                $time = TIEM_TOU;
            }

            if ($val['client_id'] == '') {
                $this->Aitimer[$key] = Timer::add(rand(1, 3), function () use ($key) {
                    Timer::del($this->Aitimer[$key]);
                    $data = AI::Msg_TBNN_FaCards($key);
                    $this->All_RECV($data);
                }, [], false);
            }

            $this->FaStateTimer($key, $time);
        }
    }

    private function controlFa()
    {
        $player = [];
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

            $player[$data['id']] = [
                'hands' => $data['hands'],
                'type' => $data['type'],
            ];
            if ($data['id'] > $max) {
                $max = $data['id'];
            }
        }
        $this->PlayerInfo[$this->winneruid]['id'] = $max;
        $this->PlayerInfo[$this->winneruid]['hands'] = $player[$max]['hands'];
        $this->PlayerInfo[$this->winneruid]['type'] =  $player[$max]['type'];
        unset($player[$max]);
        $res = [];
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['isrealy'] == 0) {
                continue;
            }

            if (empty($val['hands'])) {
                foreach ($player as $key1 => $val1) {
                    $this->PlayerInfo[$key]['id']  = $key1;
                    $this->PlayerInfo[$key]['hands']  = $val1['hands'];
                    $this->PlayerInfo[$key]['type']  =  $val1['type'];
                    unset($player[$key1]);
                    break;
                }
            }
            $res[$key] = [
                'hands' => $this->PlayerInfo[$key]['hands'],
                'type' => $this->PlayerInfo[$key]['type'],
            ];
            $this->playershandsid[$key] = $this->PlayerInfo[$key]['id'];
        }

        return $res;
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
            $this->All_RECV(['event' => 'Msg_TBNN_Act_Show', 'uid' => $uid, 'data' => []]);
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
        $players = [];
        $max = max($this->playershandsid);
        $winner = array_search($max, $this->playershandsid);
        // MyTools::msg(json_encode($this->playershandsid));
        $type = $this->PlayerInfo[$winner]['type'];

        $score = BEISHU_CATTLE[$type] * $this->roomRule['doublescore'];
        $count = 0;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($key == $winner || $val['isrealy'] == 0) {
                continue;
            }

            $count++;
            if ($val['client_id'] != '' && abs($val['control']) != 2) {
                Logic::InsertProfit($this->roomRule['level'], -$score);
            }

            $this->changScore($key, -$score);

            $jackpot = 0;
            $spetype = Cards::SpeType($val['hands']);
            if (isset(SPE_TYPE_BEISHU[$spetype])) {
                $jackpot = $this->SaveJackpot(0, 1);
                $jackpot = intval($jackpot['jackpot'] * SPE_TYPE_BEISHU[$spetype]);
                $this->SaveJackpot(-$jackpot, 2);
                $this->changScore($key, $jackpot);
                $res = [
                    'type' => 0,
                    'nickname' => $val['nickname'],
                    'gtype' => $this->roomRule['gtype'],
                    'level' => $this->roomRule['level'],
                    'vals' => json_encode([
                        'score' => $jackpot,
                        'type' => $spetype,
                        'hands' => $val['hands'],
                        'nickname' =>  $val['nickname'],
                    ]),
                    'score' => $score,
                    'created' => MyTools::GET_NOW(),
                    'playnum' => 0
                ];
                DBInstance::SaveData('game_back', $res);
            }

            $players[$key] = [
                'score' => -$score,
                'gold' => $this->PlayerInfo[$key]['gold'],
                'type' => $val['type'],
                'jackpost' => $jackpot
            ];

            if ($this->roomRule['level'] != 1) {
                DBInstance::IncrementUserGet($key, -$score);
            }
        }

        $countScore = intval($count * $score * $this->roomRule['rebate']);

        if ($this->PlayerInfo[$winner]['client_id'] != '' && abs($this->PlayerInfo[$winner]['control']) != 2) {
            Logic::InsertProfit($this->roomRule['level'], $count * $score);
        }

        $this->changScore($winner, $countScore);
        $jackpot = 0;
        $spetype = Cards::SpeType($this->PlayerInfo[$winner]['hands']);
        if (isset(SPE_TYPE_BEISHU[$spetype])) {
            $jackpot = $this->SaveJackpot(0, 1);
            $jackpot = intval($jackpot['jackpot'] * SPE_TYPE_BEISHU[$spetype]);
            $this->SaveJackpot(-$jackpot, 2);
            $res = [
                'type' => 0,
                'nickname' => $this->PlayerInfo[$winner]['nickname'],
                'gtype' => $this->roomRule['gtype'],
                'level' => $this->roomRule['level'],
                'vals' => json_encode([
                    'score' => $jackpot,
                    'type' => $spetype,
                    'hands' => $this->PlayerInfo[$winner]['hands'],
                    'nickname' => $this->PlayerInfo[$winner]['nickname'],
                ]),
                'score' => $countScore,
                'created' => MyTools::GET_NOW(),
                'playnum' => 0
            ];
            DBInstance::SaveData('game_back', $res);
            $this->changScore($winner, $jackpot);
        }

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($winner, $countScore);
        }

        $players[$winner] = [
            'score' => $countScore,
            'gold' => $this->PlayerInfo[$winner]['gold'],
            'type' => $type,
            'jackpost' => $jackpot
        ];

        $jackpots = $this->SaveJackpot(0, 1);
        $data = [
            'players' => $players,
            'jackpost' => $jackpots['jackpot'],
            'time' => TIEM_RES
        ];

        Logic::SendAll('Msg_TBNN_Res', $data, $this->roomRule['rid']);

        $jackpot = intval(($count * $score - $countScore) * $jackpots['probability']);
        $this->SaveJackpot($jackpot, 2);

        $this->resdata = $data;

        foreach ($this->PlayerInfo as $key => $val) {
            $time = TIEM_RES;
            if ($val['istuo'] != 0) {
                $time = TIEM_RES_TUO;
            }

            if ($val['client_id'] == '' &&  $val['gold'] > $this->roomRule['doublescore'] * OUT_BEI) {
                $this->Aitimer[$key] = Timer::add(rand(3, 5), function () use ($key) {
                    Timer::del($this->Aitimer[$key]);

                    if (!isset($this->PlayerInfo[$key])) {
                        return;
                    }

                    if ($this->PlayerInfo[$key]['lifeTime'] <= time()) {
                        $this->All_RECV(['event' => 'Msg_TBNN_Out', 'uid' => $key, 'data' => []]);
                        return;
                    }

                    $data = AI::Msg_TBNN_Res($key);
                    $this->All_RECV($data);
                }, [], false);
            }
            $this->ResStateTimer($key, $time, $val['istuo']);
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
                $this->All_RECV(['event' => 'Msg_TBNN_Out', 'uid' => $uid, 'data' => []]);
                if (count($this->PlayerInfo) < TBNN_PNUM) {
                    $this->gameState = STAGE_WAIT;
                }
            } else {
                $this->All_RECV(['event' => 'Msg_TBNN_Ready', 'uid' => $uid, 'data' => []]);
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
            case 'Msg_TBNN_Out':
                $this->Msg_TBNN_Out($message);
                break;
            case 'Msg_TBNN_Act_Show':
                $this->Msg_TBNN_Act_Show($message);
                break;
            case 'Msg_TBNN_Ready':
                $this->Msg_TBNN_Ready($message);
                break;
            case 'Msg_TBNN_Act_Tuo':
                $this->Msg_TBNN_Act_Tuo($message);
                break;
            case 'Msg_TBNN_WinJPList':
                $this->Msg_TBNN_WinJPList($message);
                break;
            default: {
                    Logic::SendError($message['uid'], $message['event'], '');
                    MyTools::msg('uid:' . $message['uid'] . '-----UnKnown!!!!!!!--------' . $message['event'], true);
                    break;
                }
        }
    }

    /**
     * 看牌
     * @param [type] $msg
     * @return void
     */
    private function Msg_TBNN_Act_Show($msg)
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
        Timer::del($this->userTimer[$msg['uid']]);

        Logic::SendAll('Msg_TBNN_Act_Show', ['uid' => $msg['uid']], $this->roomRule['rid']);

        if (!$code) {
            $this->timer = Timer::add(1, function () {
                Timer::del($this->timer);
                $this->procedure(STAGE_RES);
            }, [], false);
        }
    }

    /**
     * 准备
     */
    private function Msg_TBNN_Ready($msg)
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
            Logic::SendError($msg['uid'], $msg['event'], '金币不足');
            Timer::del($this->userTimer[$msg['uid']]);
            $this->All_RECV(['event' => 'Msg_TBNN_Out', 'uid' => $msg['uid'], 'data' => []]);
            return;
        }

        $this->PlayerInfo[$msg['uid']]['ready'] = 1;
        $this->PlayerInfo[$msg['uid']]['isrealy'] = 1;

        Timer::del($this->userTimer[$msg['uid']]);

        Logic::SendAll('Msg_TBNN_Ready', ['uid' => $msg['uid']], $this->roomRule['rid']);

        $code = false;
        foreach ($this->PlayerInfo as $key => $val) {
            if ($val['ready'] == 0) {
                $code = true;
                break;
            }
        }

        if (!$code && count($this->PlayerInfo) >= TBNN_PNUM || $this->disRoom == 1) {
            if ($this->disRoom == 0) {
                $this->gameState = STAGE_FA;
            }
            $this->timer = Timer::add(1, function () {
                Timer::del($this->timer);
                if ($this->disRoom == 1) {
                    $this->disroomfun();
                    return;
                }
                $this->procedure(STAGE_FA);
            }, [], false);
        }
    }

    /**
     * 特殊牌型列表
     *  */
    private function Msg_TBNN_WinJPList($msg)
    {
        $data = DBInstance::$db->table('game_back')->select('vals')->where(['gtype' => $this->roomRule['gtype']])->limit(20)->asArray()->all();
        foreach ($data as $key => $val) {
            $data[$key] = json_decode($val, true);
        }

        Logic::SendRight($msg['uid'], 'Msg_TBNN_WinJPList', $data);
    }

    /**
     * 托管
     */
    private function Msg_TBNN_Act_Tuo($msg)
    {
        if (!isset($msg['data']['istuo'])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }

        $arr = [0, 1];
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
            if ($this->gameState == STAGE_FA && $this->PlayerInfo[$msg['uid']]['ishow'] == 0 && $this->PlayerInfo[$msg['uid']]['isrealy'] == 1 && !empty($this->PlayerInfo[$msg['uid']]['hands'])) {
                $this->All_RECV(['event' => 'Msg_TBNN_Act_Show', 'uid' => $msg['uid'], 'data' => []]);
            } elseif (($this->gameState == STAGE_RES || $this->gameState == STAGE_WAIT) && $this->PlayerInfo[$msg['uid']]['ready'] == 0) {
                $this->All_RECV(['event' => 'Msg_TBNN_Ready', 'uid' => $msg['uid'], 'data' => []]);
            }
        }
    }

    /**
     * 玩家退出房间
     * @param [type] $msg
     * @return void
     */
    private function Msg_TBNN_Out($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['isrealy'] == 1 && $this->gameState != STAGE_RES && $this->gameState != STAGE_WAIT) {
            Logic::SendError($msg['uid'], 'Msg_TBNN_Out', '正在游戏中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_TBNN_Out', ['uid' => $msg['uid'], 'gold' => $gold], $this->roomRule['rid']);

        unset($this->PlayerInfo[$msg['uid']]);
        if (isset($this->userTimer[$msg['uid']])) {
            Timer::del($this->userTimer[$msg['uid']]);
        }

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

            if (!$code && count($this->PlayerInfo) >= TBNN_PNUM) {
                if ($this->disRoom == 0) {
                    $this->gameState = STAGE_FA;
                }

                $this->timer = Timer::add(1, function () {
                    Timer::del($this->timer);
                    $this->procedure(STAGE_FA);
                }, [], false);
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
    }

    private function disroomfun()
    {
        foreach ($this->PlayerInfo as $key => $val) {
            if (isset($this->userTimer[$key])) {
                Timer::del($this->userTimer[$key]);
            }
            if (isset($this->Aitimer[$key])) {
                Timer::del($this->Aitimer[$key]);
            }
            $this->All_RECV(['event' => 'Msg_TBNN_Out', 'uid' => $key]);
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
        if ($this->PlayerInfo[$uid]['isrealy'] == 0 || ($this->PlayerInfo[$uid]['isrealy'] == 1 && $this->gameState == STAGE_RES || $this->gameState == STAGE_WAIT)) {
            $this->All_RECV(['event' => 'Msg_TBNN_Out', 'uid' => $uid, 'data' => []]);
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
        Logic::SendAll('Msg_TBNN_Add', [
            'uid' => $msg['uid'],
            'nickname' => $msg['nickname'],
            'headimgurl' => $msg['headimgurl'],
            'gold' => $msg['gold'],
            'seat' => $msg['seat']
        ], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);

        if ($msg['client_id'] == '') {
            $this->Aitimer[$msg['uid']] = Timer::add(rand(3, 5), function () use ($msg) {
                Timer::del($this->Aitimer[$msg['uid']]);
                $data = AI::Msg_TBNN_Res($msg['uid']);
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
        Logic::SendAll('Msg_TBNN_ChangGold', ['uid' => $msg['uid'], 'gold' => $this->PlayerInfo[$msg['uid']]['gold']], $this->roomRule['rid']);
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
    /**
     *对奖池的操作
     *
     * @param int $score
     * @param int $code  1查询 2修改
     * @return void
     */
    private function SaveJackpot($score, $code)
    {
        $res = DBInstance::GetGameJackpot(['gtype' => $this->roomRule['gtype'], 'level' => $this->roomRule['level']]);

        if ($code == 2) {
            DBInstance::SaveJackpot($this->roomRule['gtype'], $this->roomRule['level'], $score);
        }
        return $res;
    }
}

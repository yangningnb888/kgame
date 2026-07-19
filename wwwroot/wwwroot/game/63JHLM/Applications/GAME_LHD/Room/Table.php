<?php

date_default_timezone_set("Asia/Shanghai");
require_once  __DIR__ . "/AI.php";

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

use function PHPSTORM_META\type;

define('SEAT_OFFLINE', 0); //离线
define('SEAT_ONLINE', 1); //在线

define("STAGE_WAIT", 0);  //等待开始
define("STAGE_BET", 1);  //下注
define("STAGE_OPEN", 2);  //开奖
define("STAGE_OLD", 3);  //解散

define('TIME_STAR', 0.1); //开局动画时间
define('TIME_BET', 15); //下注时间
define('TIME_OPEN', 10); //开奖时间

define('BEISHU_LOONG', 1);
define('BEISHU_TIGER', 1);
define('BEISHU_FLAT', 8);

define('WIN_SHOWE_NUM', 1); //钱最多、赢得概率的人数
define('SCORE_SHOWE_NUM', 5); //钱最多、赢得概率的人数

define('BANKER_NUM', 10);
define('BANKER_SCORE', 200000000); //庄家单局分数
define('APPLICATION_CONDITION_SCORE', 60000000); //庄家申请分数

define('LHD_LOONG_TYPE', 1);
define('LHD_FLAT_TYPE', 2);
define('LHD_TIGER_TYPE', 3);

define('AI_BANKER_NUM', 1);
define('AI_BAT_NUM', 5);

define('BAT_HISTORY_PLAYER', 20);
define('BAT_HISTORY_GAME', 66);
define('BAT_HISTORY_GAME_ROW', 6);

define('SCORE_BAT_LHD', [1000, 10000, 100000, 1000000, 10000000, 50000000]);

define('PLAYER_TIME', 300); //玩家离线好久踢出房间

define('HORSE_LAMO', 8000000); //跑马的分数
class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $playerInfo = []; //玩家信息

    private $gameState = STAGE_WAIT; //游戏状态

    private $disRoom = -1;

    private $userWin = []; //玩家uid=>赢得概率
    private $userScore = []; //玩家uid => 分数

    private $prize = [
        LHD_LOONG_TYPE => [
            'card' => 0,
            'score' => 0,
        ],
        LHD_TIGER_TYPE => [
            'card' => 0,
            'score' => 0,
        ],
        LHD_FLAT_TYPE => [
            'card' => 0,
            'score' => 0,
        ]
    ]; //奖池数据

    private $timer = 0; //定时器id

    private $cards = []; //手牌

    private $banker = ['num' => 0, 'uid' => 0, 'nickname' => '', 'gold' => 0, 'isbanker' => false]; //庄家['uid'=>0,'num'=>次数]

    private $applybanker = [];

    private $WennerUid = 0; //神算子

    private $aiActTimer = 0; //ai操作定时器

    private $history = [];

    private $cri = 0; //当前局数

    private $curbat = []; //['玩家uid'=>[[1=>分数，2=>分数，3=>分数]] 1龙 2平 3 虎

    private $playerBet = [];    //玩家所有下注

    private $winnerFrist = 0;

    private $moreScore = []; //最多的

    private $moreWin = []; //胜利率最高的

    private $timestamp = 0;

    private $curtime = 0;

    private $circle = 0;
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
            'rebate' => (100 - $msg['rebate']) / 100,
            'vals' => $msg['vals'],
            'controls' => $msg['controls'],
        ];

        $this->aiTimer();
        $this->userEnter($msg['players']);
    }

    /**
     * 玩家进房
     * @param int
     * */
    private function userEnter($playerInfo)
    {
        foreach ($playerInfo as $key => $val) {
            if ($this->WennerUid == 0) {
                $this->WennerUid = $key;
            }
            $this->addPlayer($key, $val);
            $this->roomInfo($key);
        }
        arsort($this->userScore);
    }

    private function addPlayer($uid, $info)
    {
        $this->playerInfo[$uid] = [
            'allWinScore' => 0, //总共赢了多少
            'winScore' => 0, //当局赢了多少
            'Loongbet' => 0, // 龙下注分数
            'tigerbet' => 0, // 虎下注分数
            'flatbet' => 0, //平下注分数
            'win' => 0, //赢得次数
            'playnum' => 0, //下注把数
            'nickname' => $info['nickname'],
            'gold' => $info['gold'],
            'headimgurl' => $info['headimgurl'],
            'client_id' => $info['client_id'],
            'robotfun' => null,
            'history' => [],
            'gamenum' => 0, //游戏总局数
            'onlinetime' => 0,
        ];

        if ($info['client_id'] != '') {
            Gateway::joinGroup($info['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->playerInfo[$uid]['robotfun'] = new AI($this->roomRule['vals']);
            if ($info['gold'] >= APPLICATION_CONDITION_SCORE) {
                if ($this->banker['uid'] == 0) {
                    $this->banker = [
                        'uid' => $uid,
                        'num' => 0,
                        'nickname' => $this->playerInfo[$uid]['nickname'],
                        'gold' => $this->playerInfo[$uid]['gold'],
                        'isbanker' => false
                    ];
                }
            }
        }

        $this->userWin[$uid] = 0; //玩家uid=>赢得次数
        $this->userScore[$uid] = $info['gold']; //玩家uid => 分数 
    }

    /**
     * 初始化牌堆
     *
     * @return void
     */
    private function InitCards()
    {
        $this->cards = [];
        for ($k = 0; $k < 8; $k++) {
            for ($i = 1; $i <= 13; $i++) {
                for ($j = 1; $j <= 4; $j++) {
                    $this->cards[] = $i * 100 + $j;
                }
            }
        }

        for ($i = 0; $i < 10; $i++) {
            shuffle($this->cards);
        }
    }

    /**
     * 刷新房间
     *
     * @param integer $uid
     * @return void
     */
    private function roomInfo($uid = 0)
    {
        $time = TIME_OPEN;
        if ($this->gameState == STAGE_BET) {
            $time = TIME_BET;
        }

        $res = [
            'history' => $this->history,
            'prize' => $this->prize,
            'banker' => $this->banker,
            'applyNum' => array_values($this->applybanker),
            'moreScore' => $this->moreScore,
            'WennerUid' => [
                'uid' => $this->WennerUid,
                'Loongbet' => isset($this->playerInfo[$this->WennerUid]) ? $this->playerInfo[$this->WennerUid]['Loongbet'] : 0,
                'tigerbet' =>  isset($this->playerInfo[$this->WennerUid]) ? $this->playerInfo[$this->WennerUid]['tigerbet'] : 0,
                'flatbet' =>  isset($this->playerInfo[$this->WennerUid]) ? $this->playerInfo[$this->WennerUid]['flatbet'] : 0,
                'win' =>  isset($this->userWin[$this->WennerUid]) ? $this->userWin[$this->WennerUid] : 0,
            ],
            'moreWin' =>  $this->moreWin,
            'gameState' => $this->gameState,
            'Loongbet' => $this->playerInfo[$uid]['Loongbet'], // 龙下注分数
            'tigerbet' => $this->playerInfo[$uid]['tigerbet'], // 虎下注分数
            'flatbet' => $this->playerInfo[$uid]['flatbet'], //平下注分数
            'time' => $this->timestamp <= 0 ? 0 : $time - (time() - $this->timestamp),
            'circle' => $this->circle,
            'playerCount' => count($this->playerInfo),
        ];

        Logic::SendRight($uid, 'Msg_LHD_RoomInfo', $res);
    }

    /**
     * 开始
     *
     * @return void
     */
    private function starGame()
    {
        $this->cri++;
        $this->changeBanker();

        $this->maxWinScore();
        foreach ($this->playerInfo as $key => $val) {
            $this->playerInfo[$key]['winScore'] = 0;
            $this->playerInfo[$key]['Loongbet'] = 0;
            $this->playerInfo[$key]['tigerbet'] = 0;
            $this->playerInfo[$key]['flatbet'] = 0;
        }

        $date =  date('Y-m-d', time());
        if ($this->curtime != $date) {
            $this->curtime = $date;
            $this->circle = 0;
        }
        $this->circle++;
        $this->banker['gold'] = $this->playerInfo[$this->banker['uid']]['gold'];

        if ($this->disRoom == 1) {
            foreach ($this->playerInfo as $key => $val) {
                if ($val['client_id'] != '') {
                    $this->All_RECV(['event' => 'Msg_LHD_Out', 'uid' => $key]);
                }
            }
            $this->OldRoom();

            return;
        }

        DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet, time() + TIME_BET);
        $this->timer = Timer::add(TIME_STAR, function () {
            Timer::del($this->timer);
            $this->procedure(STAGE_BET);
        }, [], false);
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
            case STAGE_BET:
                $this->bet(); //下注
                break;
            case STAGE_OPEN:
                $this->openstate(); //开奖
                break;
            case STAGE_OLD:
                $this->OldRoom();
                break;
        }
    }

    /**
     * 通知玩家下注
     *
     * @return void
     */
    private function bet()
    {
        $this->prize = [
            LHD_LOONG_TYPE => [
                'card' => 0,
                'score' => 0,
            ],
            LHD_TIGER_TYPE => [
                'card' => 0,
                'score' => 0,
            ],
            LHD_FLAT_TYPE => [
                'card' => 0,
                'score' => 0,
            ]
        ];

        $this->banker['num']++;

        $data = [
            'banker' => $this->banker,
            'moreScore' => $this->moreScore,
            'moreWin' =>  $this->moreWin,
            'time' => TIME_BET,
            'WennerUid' => [
                'uid' => $this->WennerUid,
                'win' => isset($this->userWin[$this->WennerUid]) ? $this->userWin[$this->WennerUid] : 0,
            ],
            'applybanker' => array_values($this->applybanker),
            'circle' => $this->circle
        ];

        Logic::SendAll('Msg_LHD_State_Stake', $data, $this->roomRule['rid']);

        $this->timestamp = time();
        $this->playerBet = [];
        $this->timer = Timer::add(TIME_BET, function () {
            Timer::del($this->timer);
            if (!empty($this->curbat)) {
                Logic::SendAll('Msg_LHD_Act_Table', $this->curbat, $this->roomRule['rid']);
                $this->curbat = [];
            }

            $this->procedure(STAGE_OPEN);
        }, [], false);
    }

    /**
     *yingde 
     * @return void
     */
    private function maxWinScore()
    {
        arsort($this->userScore);
        arsort($this->userWin);

        $moreWin = [];
        $moreScore = [];
        $i = WIN_SHOWE_NUM;
        foreach ($this->userWin as $key => $val) {
            if ($this->banker['uid'] == $key) {
                continue;
            }

            $moreWin[$key] = [
                'victory' => $val,
                'nickname' => $this->playerInfo[$key]['nickname'],
                'gold' => $this->playerInfo[$key]['gold'],
                'headimgurl' => $this->playerInfo[$key]['headimgurl'],
            ];

            if ($i == WIN_SHOWE_NUM) {
                $this->WennerUid = $key;
            }

            $i--;
            if ($i <= 0) {
                break;
            }
        }

        $i = SCORE_SHOWE_NUM;
        foreach ($this->userScore as $key => $val) {
            if ($this->banker['uid'] == $key || isset($moreWin[$key])) {
                continue;
            }

            $moreScore[$key] = [
                'gold' => $val,
                'nickname' => $this->playerInfo[$key]['nickname'],
                'headimgurl' => $this->playerInfo[$key]['headimgurl'],
            ];

            $i--;
            if ($i <= 0) {
                break;
            }
        }

        $this->moreScore = $moreScore;
        $this->moreWin = $moreWin;
        $this->winnerFrist = 0;
        return ['moreScore' => $moreScore, 'moreWin' => $moreWin];
    }
    /**
     * 是否换庄
     *
     * @return void
     */
    private function changeBanker()
    {
        $bankerCode = true;

        if ($this->banker['num'] >= BANKER_NUM) {
            $bankerCode = false;
        }

        if ($this->playerInfo[$this->banker['uid']]['gold'] < BANKER_SCORE) {
            $bankerCode = false;
        }

        if ($this->banker['isbanker']) {
            $bankerCode = false;
        }

        if (!$bankerCode) {
            foreach ($this->applybanker as $key => $val) {
                unset($this->applybanker[$key]);
                if (!isset($this->playerInfo[$key]) || $this->playerInfo[$key]['gold'] < APPLICATION_CONDITION_SCORE) {
                    continue;
                }

                $uid = $key;
                break;
            }

            if (!isset($uid)) {
                foreach ($this->playerInfo as $key => $val) {
                    if ($val['client_id'] == '' && $val['gold'] >= APPLICATION_CONDITION_SCORE) {
                        $uid = $key;
                        break;
                    }
                }
            }

            $this->banker = [
                'uid' => $uid,
                'num' => 0,
                'nickname' => $this->playerInfo[$uid]['nickname'],
                'gold' => $this->playerInfo[$uid]['gold'],
                'isbanker' => false
            ];
        }
    }

    /**
     * 开奖
     *
     * @return void
     */
    private function openstate()
    {
        $this->timestamp = time();
        if (count($this->cards) < 2) {
            $this->InitCards();
        }

        $this->controlPrint();
        $Loong = intval($this->prize[LHD_LOONG_TYPE]['card'] / 100);
        $tiger = intval($this->prize[LHD_TIGER_TYPE]['card'] / 100);

        $bankerTransport = 0; //庄家输的钱
        $bankerWin = 0; //庄家赢得钱
        $type = 0;
        $beishu = 0;
        $player = [];
        if ($Loong > $tiger) {
            $type = LHD_LOONG_TYPE;
            $beishu = BEISHU_LOONG;
        } elseif ($Loong == $tiger) {
            $type = LHD_FLAT_TYPE;
            $beishu = BEISHU_FLAT;
        } else {
            $type = LHD_TIGER_TYPE;
            $beishu = BEISHU_TIGER;
        }

        if (count($this->history) < BAT_HISTORY_GAME) {
            $this->history[] = $type;
        } else {
            for ($i = 0; $i < BAT_HISTORY_GAME_ROW; $i++) {
                array_shift($this->history);
            }

            $_data = [];
            foreach ($this->history as $key => $val) {
                $_data[] = $val;
            }
            $_data[] = $type;

            $this->history = $_data;
        }
        $sendHorse = [];

        foreach ($this->playerInfo as $key => $val) {
            $this->playerInfo[$key]['gamenum']++;
            if ($key == $this->banker['uid']) {
                continue;
            }

            $_countScore = $val['Loongbet'] + $val['tigerbet'] + $val['flatbet'];
            if ($_countScore > 0) {
                $this->playerInfo[$key]['playnum']++;
                $score = 0;
                $winscore = 0;
                if ($type == LHD_FLAT_TYPE) {
                    $this->changScore($key, $_countScore);
                    $winscore  = $val['flatbet'];
                } else {
                    if ($type == LHD_TIGER_TYPE) {
                        $winscore = $val['tigerbet'];
                        $bankerWin +=  $val['Loongbet'] + $val['flatbet'];
                    } else {
                        $winscore = $val['Loongbet'];
                        $bankerWin +=  $val['tigerbet'] + $val['flatbet'];
                    }
                }

                $score = $winscore * $beishu;

                $bankerTransport += $score;

                // $score = intval($winscore) * $this->roomRule['rebate']  + ($score - $winscore);
                $score = intval($score * $this->roomRule['rebate']) + $winscore;

                $this->changScore($key, $score);

                $_score = $score;
                if ($type != LHD_FLAT_TYPE) {
                    $_score = $score - $_countScore;
                }

                if ($_score > 0) {
                    $this->playerInfo[$key]['win']++;
                }
            } else {
                $_score = 0;
            }

            $count = count($this->playerInfo[$key]['history']);
            if ($count >= BAT_HISTORY_PLAYER) {
                array_shift($this->playerInfo[$key]['history']);
            }

            $this->playerInfo[$key]['history'][$this->cri] = [
                'batGold' => $_countScore,
                'batVictory' => $_score > 0 ? 1 : 0
            ];

            $_res = $this->historyinfo($key);

            if ($_countScore > 0) {
                $player[$key] = [
                    'score' => $_score,
                    'gold' => $this->playerInfo[$key]['gold'],
                    'batGold' => $_res['batGold'],
                    'batVictory' => $_res['batVictory'],
                ];

                if ($val['client_id'] != '') {
                    DBInstance::IncrementUserGet($key, $_score);
                }

                if ($val['client_id'] != '') {
                    $winscores = $winscore;
                    if ($type == LHD_FLAT_TYPE) {
                        $winscores = $_countScore;
                    }
                    $InsertProfit = ($winscore * $beishu) - ($_countScore - $winscores);
                    Logic::InsertProfit($this->roomRule['level'], $InsertProfit);
                }
            }
            
            if ($_score >= HORSE_LAMO) {
                // Logic::HorseLamp($key, $score, 0);
                $sendHorse[$key] = $_score;
            }

            $_num = $this->playerInfo[$key]['win'] / $this->playerInfo[$key]['gamenum'];
            $_num = sprintf("%.2f", $_num);
            $this->userWin[$key] = $_num; //玩家概率
            $this->userScore[$key] = $this->playerInfo[$key]['gold']; //玩家分数
        }

        $bankerScores =  $bankerWin - $bankerTransport;

        if ($this->playerInfo[$this->banker['uid']]['client_id'] != '') {
            Logic::InsertProfit($this->roomRule['level'], $bankerScores);
        }

        if ($bankerScores >= HORSE_LAMO) {
            // Logic::HorseLamp($this->banker['uid'], $bankerScores, 0);
            $sendHorse[$this->banker['uid']] = $bankerScores;
        }

        $this->changScore($this->banker['uid'], $bankerScores);

        if ($this->playerInfo[$this->banker['uid']]['client_id'] != '') {
            DBInstance::IncrementUserGet($this->banker['uid'], $bankerScores);
        }

        if ($bankerScores > 0) {
            $this->playerInfo[$this->banker['uid']]['win']++;
        }

        $_num = $this->playerInfo[$this->banker['uid']]['win'] / $this->playerInfo[$this->banker['uid']]['gamenum'];
        $_num = sprintf("%.2f", $_num);
        $this->userWin[$this->banker['uid']] = $_num; //玩家概率
        $this->userScore[$this->banker['uid']] = $this->playerInfo[$this->banker['uid']]['gold']; //玩家分数

        $_res = $this->historyinfo($this->banker['uid']);

        $player[$this->banker['uid']] = [
            'score' => $bankerScores,
            'gold' => $this->playerInfo[$this->banker['uid']]['gold'],
            'batGold' => $_res['batGold'],
            'batVictory' => $_res['batVictory'],
        ];

        $res = [
            'player' => $player,
            'Loong' => $this->prize[LHD_LOONG_TYPE]['card'],
            'tiger' => $this->prize[LHD_TIGER_TYPE]['card'],
            'time' => TIME_OPEN
        ];

        Logic::SendAll('Msg_LHD_State_Open', $res, $this->roomRule['rid']);

        $this->timer = Timer::add(TIME_OPEN, function () {
            Timer::del($this->timer);
            $this->starGame();
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

    private function controlPrint()
    {
        $region = DBInstance::GetDuoRenControl($this->roomRule['gtype'], $this->roomRule['level']);
        if ($region < 0 || $region > 3) {
            $controlnum = DBInstance::GetControlRand($this->roomRule['gtype']);
            if (rand(0, 100) <= $controlnum) {
                if ($this->playerInfo[$this->banker['uid']]['client_id'] == '') {
                    $control = 2;
                } else {
                    $control = 1; //庄家输
                }
            }

            $data = [LHD_LOONG_TYPE, LHD_TIGER_TYPE];
            $_data = [];
            $hands = [];
            $allcards = $this->cards;
            foreach ($data as $key => $val) {
                $pid = array_shift($this->cards);
                $_data[$pid] = intval($pid / 100);
                $hands[] = $pid;
            }
        } else {
            $hands = $this->Z_Control($region);
        }

        if (isset($control)) {
            $prize = $this->prize;
            foreach ($this->playerInfo as $key => $val) {
                if (($this->playerInfo[$this->banker['uid']]['client_id'] != '' && $val['client_id'] != '') || ($this->playerInfo[$this->banker['uid']]['client_id'] == '' && $val['client_id'] == '')) {
                    $prize[LHD_LOONG_TYPE]['score'] -= $val['Loongbet'];
                    $prize[LHD_TIGER_TYPE]['score'] -= $val['tigerbet'];
                    $prize[LHD_FLAT_TYPE]['score'] -= $val['flatbet'];
                }
            }

            $type = 0;
            foreach ($data as $key => $val) {
                $score = 0;
                foreach ($prize as $key1 => $val1) {
                    if ($key1 == $val || $val == LHD_FLAT_TYPE) {
                        if ($key1 == LHD_FLAT_TYPE) {
                            $score -= $val1['score'] * BEISHU_FLAT;
                        } else {
                            $score -= $val1['score'];
                        }
                        continue;
                    }
                    $score += $val1['score'];
                }

                if ($control == 2 && $score > 0) {
                    $type = $val;
                    break;
                } elseif ($control == 1 && $score < 0) {
                    $type = $val;
                    break;
                }
            }

            if ($type == LHD_LOONG_TYPE) {
                $card = max($hands);
            } elseif ($type == LHD_TIGER_TYPE) {
                $card = min($hands);
            } else {
                $card = $hands[0];
            }

            $this->prize[LHD_LOONG_TYPE]['card'] = $card;
            $keys = array_search($card, $hands);
            foreach ($hands as $key => $val) {
                if ($keys != $key) {
                    $this->prize[LHD_TIGER_TYPE]['card'] = $val;
                }
            }
        } else {
            $this->prize[LHD_LOONG_TYPE]['card'] = $hands[0];
            $this->prize[LHD_TIGER_TYPE]['card'] = $hands[1];
        }

        /**
         *  测试
         */

        if ($this->prize[LHD_TIGER_TYPE]['card'] == 0) {
            MyTools::msg('handsall:::' . json_encode($allcards), 'hands::' . json_encode($hands), true);
        }
    }

    /**
     *最近下注20把的输赢

     * @param [type] $uid
     * @return void
     */
    private function historyinfo($uid)
    {
        $batGold = 0;
        $batVictory = 0;

        foreach ($this->playerInfo[$uid]['history'] as $key => $val) {
            $batGold += $val['batGold'];
            $batVictory += $val['batVictory'];
        }

        return ['batVictory' => $batVictory, 'batGold' => $batGold];
    }

    /**
     * 分数变化
     *
     * @param int $uid
     * @param int $score
     * @return void
     */
    private function changScore($uid, $score)
    {
        $this->playerInfo[$uid]['winScore'] += $score;
        $this->playerInfo[$uid]['allWinScore'] += $score;
        $this->playerInfo[$uid]['gold'] += $score;

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $uid, $score);
        }

        if ($score > 0) {
            DBInstance::IncrementWinPoint($uid, $score);
        }
    }

    /**
     * 所有消息回调
     * @param array
     */

    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_LHD_Act_Bet':
                $this->Msg_LHD_Act_Bet($message);
                break;
            case 'Msg_LHD_Out':
                $this->QuitRoom($message);
                break;
            case 'Msg_LHD_Act_Banker':
                $this->Msg_LHD_Act_Banker($message);
                break;
            case 'Msg_LHD_Act_BackBanker':
                $this->Msg_LHD_Act_BackBanker($message);
                break;
            case 'Msg_LHD_GetUserList';
                $this->Msg_LHD_GetUserList($message);
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
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_LHD_Act_Bet($msg)
    {
        $uid = $msg['uid'];
        if ($uid == $this->banker['uid']) {
            Logic::SendError($uid, 'Msg_LHD_Act_Bet', '庄家不能下注');
            return;
        }

        if ($this->gameState != STAGE_BET) {
            Logic::SendError($uid, 'Msg_LHD_Act_Bet', '阶段错误');
            return;
        }

        $data = $msg['data'];

        if (!isset($data['code']) || !isset($data['bat']) || $data['bat'] % 1000 != 0 || $data['bat'] <= 0) {
            Logic::SendError($uid, 'Msg_LHD_Act_Bet', '数据错误');
            return;
        }

        if ($this->playerInfo[$msg['uid']]['gold'] < $data['bat']) {
            Logic::SendError($uid, 'Msg_LHD_Act_Bet', '金币不足');
            return;
        }

        $betCode = $this->checkBet($data['code'], $data['bat']);

        if (!$betCode) {
            Logic::SendError($uid, 'Msg_LHD_Act_Bet', '庄家不够赔付');
            return;
        }

        $name = [LHD_LOONG_TYPE => 'Loongbet', LHD_TIGER_TYPE => 'tigerbet', LHD_FLAT_TYPE => 'flatbet'];
        if ($this->playerInfo[$uid][$name[$data['code']]] + $data['bat'] > $this->roomRule['controls']['maxbet']) {
            Logic::SendError($uid, 'Msg_LHD_Act_Bet', '单区域最高下注1000万');
            return;
        }

        $this->playerInfo[$uid][$name[$data['code']]] += $data['bat'];

        $this->prize[$data['code']]['score'] += $data['bat'];

        $isWenner = 1;
        if ($uid != $this->WennerUid) {
            $isWenner = 0;
        }

        if (!isset($this->curbat[$msg['uid']])) {
            $this->curbat[$msg['uid']] = [
                LHD_LOONG_TYPE => [
                    'score' => 0,
                ],
                LHD_TIGER_TYPE => [
                    'score' => 0,
                ],
                LHD_FLAT_TYPE => [
                    'score' => 0,
                ]
            ];
        }

        if (!empty($this->playerInfo[$msg['uid']]['client_id']) && empty($this->playerBet[$msg['uid']])) {
            $this->playerBet[$msg['uid']] = [LHD_LOONG_TYPE => 0, LHD_TIGER_TYPE => 0, LHD_FLAT_TYPE => 0];
        }
        $this->changScore($uid, -$data['bat']);

        $this->curbat[$msg['uid']][$data['code']]['score'] +=  $data['bat'];
        if (!empty($this->playerInfo[$msg['uid']]['client_id'])) {
            $this->playerBet[$msg['uid']][$data['code']] += $data['bat'];
        }

        if ($this->playerInfo[$msg['uid']]['client_id'] != '') {
            Logic::SendRight($msg['uid'], 'Msg_LHD_Act_Bet', $data);
        }

        if ($isWenner == 1 && $this->winnerFrist == 0) {
            $this->winnerFrist = $data['code'];
        }
    }

    /**
     * 申请庄家
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_LHD_Act_Banker($msg)
    {
        if ($this->playerInfo[$msg['uid']]['gold'] < APPLICATION_CONDITION_SCORE) {
            Logic::SendError($msg['uid'], 'Msg_LHD_Act_Banker', '金币不够申请庄家');
            return;
        }
        $this->applybanker[$msg['uid']] = ['uid' => $msg['uid'], 'nickname' => $this->playerInfo[$msg['uid']]['nickname']];

        Logic::SendAll('Msg_LHD_Act_Banker', ['uid' => $msg['uid'], 'nickname' => $this->playerInfo[$msg['uid']]['nickname']], $this->roomRule['rid']);
    }

    /**
     *申请下庄
     * @param [type] $msg
     * @return void
     */
    private function Msg_LHD_Act_BackBanker($msg)
    {
        if ($msg['uid'] != $this->banker['uid'] && !isset($this->applybanker[$msg['uid']])) {
            Logic::SendError($msg['uid'], 'Msg_LHD_Act_BackBanker', '你不是庄家或者不在庄家列表');
            return;
        }

        if ($msg['uid'] == $this->banker['uid']) {
            $this->banker['isbanker'] = true;
        } else {
            unset($this->applybanker[$msg['uid']]);
        }

        Logic::SendAll('Msg_LHD_Act_BackBanker', ['uid' => $msg['uid']], $this->roomRule['rid']);
    }

    private function Msg_LHD_GetUserList($msg)
    {
        $players = [];
        $count = 0;
        foreach ($this->playerInfo as $key => $val) {
            $_res = $this->historyinfo($key);
            $count++;
            $players[$key] = [
                'nickname' => $val['nickname'],
                'headimgurl' => $val['headimgurl'],
                'gold' => $val['gold'],
                'batVictory' => $_res['batVictory'],
                'batGold' => $_res['batGold']
            ];
        }
        Logic::SendRight($msg['uid'], 'Msg_LHD_GetUserList', ['players' => $players]);
    }
    /**
     * 检测下注情况
     *
     * @param [type] $state
     * @return void
     */
    private function checkBet($state, $_score)
    {
        $code = true;
        $score = $this->playerInfo[$this->banker['uid']]['gold'];

        // if ($this->prize[$state]['score'] > 0) {
        if ($state == LHD_LOONG_TYPE) {
            $code = ($score + $this->prize[LHD_TIGER_TYPE]['score'] + $this->prize[LHD_FLAT_TYPE]['score']) >=
                ($this->prize[LHD_LOONG_TYPE]['score'] + $_score) * BEISHU_LOONG;
        } elseif ($state == LHD_FLAT_TYPE) {
            $code = $score - ($this->prize[LHD_FLAT_TYPE]['score'] + $_score) * BEISHU_FLAT >= 0;
        } else {
            $code = ($score + $this->prize[LHD_LOONG_TYPE]['score'] + $this->prize[LHD_FLAT_TYPE]['score']) >=
                ($this->prize[LHD_TIGER_TYPE]['score'] + $_score) * BEISHU_TIGER;
        }
        // }

        return $code;
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
            'palyers' => [],
            'result' => [
                'uid' => []
            ], //结算消息
            'gtype' => $this->roomRule['gtype'],
        ];
        Logic::RoomOld($olddata);
        return;
    }

    /**
     * 玩家退出房间
     *
     * @return void
     */
    public function QuitRoom($msg)
    {
        $count = $this->playerInfo[$msg['uid']]['Loongbet'] + $this->playerInfo[$msg['uid']]['flatbet'] + $this->playerInfo[$msg['uid']]['tigerbet'];
        if (($this->gameState != STAGE_OPEN && $count > 0) || ($msg['uid'] == $this->banker['uid'] && $this->disRoom == 0)) {
            Logic::SendError($msg['uid'], 'Msg_LHD_Out', '正在游戏中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_LHD_Out', ['uid' => $msg['uid'], 'gold' => $gold], $this->roomRule['rid']);

        unset($this->userWin[$msg['uid']]);
        unset($this->userScore[$msg['uid']]);
        unset($this->playerInfo[$msg['uid']]);
        unset($this->applyBanker[$msg['uid']]);

        $data = ['uid' => $msg['uid'], 'rid' => $this->roomRule['rid']];
        Logic::QuitRoom($data);

        if (isset($this->moreScore[$msg['uid']]) || isset($this->moreWin[$msg['uid']])) {
            $this->maxWinScore();
            Logic::SendAll('Msg_LHD_Head', [
                'moreScore' => $this->moreScore,
                'moreWin' => $this->moreWin,
                'applybanker' => array_values($this->applybanker),
            ], $this->roomRule['rid']);
        }
    }

    /**
     * 玩家重连
     * @param string
     * @param int
     */
    public function UserOnline($client_id, $uid)
    {
        $this->playerInfo[$uid]['online'] = 0;

        if ($client_id != '') {
            $this->playerInfo[$uid]['client_id'] = $client_id;

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
        $this->playerInfo[$uid]['onlinetime'] = time() + OUT_TIME;
        if (isset($this->applybanker[$uid])) {
            $this->All_RECV(['event' => 'Msg_LHD_Act_BackBanker', 'uid' => $uid]);
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
        Logic::SendAll('Msg_LHD_Add', [
            'uid' => $msg['uid'],
            'nickname' => $msg['nickname'],
            'headimgurl' => $msg['headimgurl'],
            'gold' => $msg['gold']
        ], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);

        $this->roomInfo($msg['uid']);

        if ($this->banker['uid'] != 0 && count($this->playerInfo) >= $this->roomRule['vals']['min'] && $this->cri < 1) {
            $this->starGame();
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
        $this->playerInfo[$msg['uid']]['gold'] = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
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

    /**
     *定时同步消息/AI定时操作

     * @return void
     */
    private function aiTimer()
    {
        $this->aiActTimer = Timer::add(2, function () {
            if ($this->gameState == STAGE_BET) {
                DBInstance::SavePlayerBets($this->roomRule['gtype'], $this->roomRule['level'], $this->playerBet);
                $num = 0;
                $_num = 0;
                $playercount = count($this->playerInfo);
                foreach ($this->playerInfo as $key => $val) {
                    if ($key == $this->banker['uid']) {
                        continue;
                    }

                    $rand = rand(0, 100);

                    if ($val['client_id'] == '' && $rand <= 50) {
                        $count = $val['Loongbet'] + $val['tigerbet'] + $val['flatbet'];
                        if ($count <= 0 && $playercount >= $this->roomRule['vals']['min']) {
                            $code = $this->playerInfo[$key]['robotfun']->QuitRoom($val['gold']);

                            if ($code) {
                                $this->QuitRoom(['uid' => $key]);
                                continue;
                            }
                        }

                        $num++;

                        $data = $val['robotfun']->bat();

                        $this->All_RECV(['uid' => $key, 'event' => 'Msg_LHD_Act_Bet', 'data' => $data]);

                        if (rand(0, 100) < 50 && count($this->applybanker) < 4 && $_num <= AI_BANKER_NUM && $val['gold'] >= APPLICATION_CONDITION_SCORE && !isset($this->applybanker[$key])) {
                            $this->All_RECV(['uid' => $key, 'event' => 'Msg_LHD_Act_Banker']);
                            $_num++;
                        }
                    }
                }

                if (!empty($this->curbat)) {
                    Logic::SendAll('Msg_LHD_Act_Table', $this->curbat, $this->roomRule['rid']);
                }

                $this->curbat = [];
            } else {
                foreach ($this->playerInfo as $key => $val) {
                    if ($val['onlinetime'] == 0) {
                        continue;
                    }

                    if ($val['onlinetime'] < time()) {
                        $this->QuitRoom(['uid' => $key]);
                    }
                }
            }
        });
    }


    //增加场控地图
    private function Z_Control ($region, $temp = true)
    {
        $card = array_shift($this->cards);
        $point1 =intval($card / 100);
        $ret = [$card];
        foreach ($this->cards as $key => $value) {
            if ($region == LHD_FLAT_TYPE && intval($value / 100) == $point1) {
                $ret[] = $value;
                unset($this->cards[$key]);
                break;
            } elseif ($region != LHD_FLAT_TYPE && intval($value / 100) != $point1) {
                $ret[] = $value;
                unset($this->cards[$key]);
                break;
            }
        }

        $this->cards = array_values($this->cards);
        if (count($ret) == 1 && $temp) {
            $this->InitCards();
            return $this->Z_Control($region, false);
        } elseif (count($ret) == 1) {
            $ret[] = array_shift($this->cards);
        }

        $point2 = intval($ret[1] / 100);
        //交换牌张
        if (($region == LHD_LOONG_TYPE && $point2 > $point1) || ($region == LHD_TIGER_TYPE && $point2 < $point1)) {
            return [$ret[1], $ret[0]];
        }
        return $ret;
    }
}

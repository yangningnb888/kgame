<?php
date_default_timezone_set("Asia/Shanghai");

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
define('TIME_BET', 21); //下注时间
define('TIME_OPEN', 20); //开奖时间

define('WIN_SHOWE_NUM', 1); //钱最多、赢得概率的人数
define('SCORE_SHOWE_NUM', 5); //钱最多、赢得概率的人数

define('OPEN_ARR', 5); //开奖个数

define('BANKER_NUM', 10);
define('BANKER_SCORE', 200000000); //庄家单局分数
define('APPLICATION_CONDITION_SCORE', 60000000); //庄家申请分数

define('FFC_LOONG_TYPE', 1); //龙
define('FFC_FLAT_TYPE', 2); //平
define('FFC_TIGER_TYPE', 3); //虎
define('FFC_BIG_TYPE', 4); //大
define('FFC_SMALL_TYPE', 5); //小
define('FFC_SINGLE_TYPE', 6); //单
define('FFC_DOUBLE_TYPE', 7); //双

define('BEISHU_ARR', [
    FFC_LOONG_TYPE => 1,
    FFC_FLAT_TYPE => 9,
    FFC_TIGER_TYPE => 1,
    FFC_BIG_TYPE => 1,
    FFC_SMALL_TYPE => 1,
    FFC_SINGLE_TYPE => 1,
    FFC_DOUBLE_TYPE => 1,
]); //每个区域赔率

define('BET_NUM', 5);

define('BET_WANG', 5); //万
define('BET_QIAN', 4); //千
define('BET_BAI', 3); //百
define('BET_SHI', 2); //十
define('BET_GE', 1); //个

define('BET_ARR', [BET_GE, BET_SHI, BET_BAI, BET_QIAN, BET_WANG]);

define('BET_HISTORY_PLAYER', 20);
define('BET_HISTORY_GAME', 66);

define('AI_BANKER_NUM', 1);

define('BET_CONTROL_ALL', [
    FFC_LOONG_TYPE => [
        [FFC_BIG_TYPE, FFC_SINGLE_TYPE],
        [FFC_BIG_TYPE, FFC_DOUBLE_TYPE],
        [FFC_SMALL_TYPE, FFC_DOUBLE_TYPE],
        [FFC_SMALL_TYPE, FFC_SINGLE_TYPE]
    ],
    FFC_FLAT_TYPE => [
        [FFC_BIG_TYPE, FFC_SINGLE_TYPE],
        [FFC_BIG_TYPE, FFC_DOUBLE_TYPE],
        [FFC_SMALL_TYPE, FFC_DOUBLE_TYPE],
        [FFC_SMALL_TYPE, FFC_SINGLE_TYPE]
    ],
    FFC_TIGER_TYPE => [
        [FFC_BIG_TYPE, FFC_SINGLE_TYPE],
        [FFC_BIG_TYPE, FFC_DOUBLE_TYPE],
        [FFC_SMALL_TYPE, FFC_DOUBLE_TYPE],
        [FFC_SMALL_TYPE, FFC_SINGLE_TYPE]
    ]

]);

define('SCORE_BET_FFC', [1000 => 500, 10000 => 500, 100000 => 500, 1000000 => 200, 10000000 => 50, 50000000 => 20]);

define('PROBABILITY_AI_LHD', [
    FFC_FLAT_TYPE => 1,
    FFC_LOONG_TYPE => 48,
    FFC_TIGER_TYPE => 48,
    FFC_BIG_TYPE => 48,
    FFC_SMALL_TYPE => 48,
    FFC_DOUBLE_TYPE => 48,
    FFC_SINGLE_TYPE => 48
]);

define('PLAYER_TIME', 300); //玩家离线好久踢出房间

define('HORSE_LAMO', 9000000); //跑马灯

class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $gameState = STAGE_WAIT; //游戏状态

    private $disRoom = -1;

    private $userWin = []; //玩家uid=>赢得概率
    private $userScore = []; //玩家uid => 分数

    private $prize = [
        FFC_LOONG_TYPE => 0, //龙
        FFC_FLAT_TYPE => 0, //平
        FFC_TIGER_TYPE => 0, //虎
        FFC_BIG_TYPE => 0, //大
        FFC_SMALL_TYPE => 0, //小
        FFC_SINGLE_TYPE => 0, //单
        FFC_DOUBLE_TYPE => 0 //双
    ]; //奖池数据

    private $timer = 0; //定时器id

    private $banker = ['num' => 0, 'uid' => 0, 'nickname' => '', 'gold' => 0, 'isbanker' => false]; //庄家['uid'=>0,'num'=>次数]

    private $applybanker = [];

    private $WennerUid = 0; //神算子

    private $aiActTimer = 0; //ai操作定时器

    private $history = [];

    private $curbet = []; //['玩家uid'=>[[1=>分数，2=>分数，3=>分数]] 1龙 2平 3 虎

    private $moreScore = []; //最多的

    private $moreWin = []; //胜利率最高的

    private $timestamp = 0;

    private $betarr = []; //下注数组['龙'=>大=>'金额']

    private $phase = 0;

    private $nowtime = 0;

    private $playnum = 1;

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
            'controls' => $msg['controls'],
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
            if ($this->WennerUid == 0) {
                $this->WennerUid = $key;
            }
            $this->addPlayer($key, $val);
        }
        arsort($this->userScore);
    }

    private function addPlayer($uid, $info)
    {
        $this->PlayerInfo[$uid] = [
            'allWinScore' => 0, //总共赢了多少
            'winScore' => 0, //当局赢了多少
            'bet' => [],
            'win' => 0, //赢得次数
            'playnum' => 0, //下注把数
            'nickname' => $info['nickname'],
            'gold' => $info['gold'],
            'headimgurl' => $info['headimgurl'],
            'client_id' => $info['client_id'],
            'robotfun' => null,
            'history' => [], //历史记录 总下注数 胜利局数    $betGold  $betVictory 
            'time' => time(),
            'region' => 0, //区域
            'onlineTime' => 0
        ];

        if ($info['client_id'] != '') {
            Gateway::joinGroup($info['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $rand = rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]);
            $this->PlayerInfo[$uid]['time'] += $rand;
            if ($info['gold'] >= BANKER_SCORE) {
                if ($this->banker['uid'] == 0) {
                    $this->banker = [
                        'uid' => $uid,
                        'num' => 0,
                        'nickname' => $this->PlayerInfo[$uid]['nickname'],
                        'gold' => $this->PlayerInfo[$uid]['gold'],
                        'isbanker' => false
                    ];
                }
            }
        }

        $this->userWin[$uid] = 0; //玩家uid=>赢得次数
        $this->userScore[$uid] = $info['gold']; //玩家uid => 分数 

        $this->roomInfo($uid);
    }

    /**
     * 刷新房间
     *
     * @param integer $uid
     * @return void
     */
    private function roomInfo($uid)
    {
        $time = TIME_OPEN;
        if ($this->gameState == STAGE_BET) {
            $time = TIME_BET;
        }

        $res = [
            'history' => $this->history,
            'prize' => $this->prize,
            'banker' => $this->banker,
            'applybanker' => array_values($this->applybanker),
            'moreScore' => $this->moreScore,
            'moreWin' =>  $this->moreWin,
            'gameState' => $this->gameState,
            'mybet' => $this->PlayerInfo[$uid]['bet'],
            'time' => $this->timestamp <= 0 ? 0 : $time - (time() - $this->timestamp),
            'playerCount' => count($this->PlayerInfo),
            'gold' => $this->PlayerInfo[$uid]['gold'],
            'phase' => $this->phase
        ];

        Logic::SendRight($uid, 'Msg_FFC_RoomInfo', $res);
    }

    /**
     * 开始
     *
     * @return void
     */
    private function starGame()
    {
        // $this->gameState == STAGE_BET;
        $this->changeBanker();

        $this->maxWinScore();
        $nowtime = date("Ymd", time());
        if ($nowtime != $this->nowtime) {
            $this->nowtime = $nowtime;
            $this->playnum = 0;
        }

        $this->playnum++;

        $this->phase = $this->nowtime . $this->playnum;

        foreach ($this->PlayerInfo as $key => $val) {
            $this->PlayerInfo[$key]['bet'] = [];
            $this->PlayerInfo[$key]['region'] = 0;
        }
        $this->banker['gold'] = $this->PlayerInfo[$this->banker['uid']]['gold'];

        $this->prize = [
            FFC_LOONG_TYPE => 0, //龙
            FFC_FLAT_TYPE => 0, //平
            FFC_TIGER_TYPE => 0, //虎
            FFC_BIG_TYPE => 0, //大
            FFC_SMALL_TYPE => 0, //小
            FFC_SINGLE_TYPE => 0, //单
            FFC_DOUBLE_TYPE => 0 //双
        ];
        $this->betarr = [];
        $this->banker['num']++;
        $this->changeApplyBanker();

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
        if ($this->disRoom == 1) {
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['client_id'] != '') {
                    $this->All_RECV(['event' => 'Msg_FFC_Out', 'uid' => $key]);
                }
            }
            $this->OldRoom();
            return;
        }

        $data = [
            'banker' => $this->banker,
            'moreScore' => $this->moreScore,
            'moreWin' =>  $this->moreWin,
            'time' => TIME_BET,
            'applybanker' => array_values($this->applybanker),
            'phase' => $this->phase
        ];

        Logic::SendAll('Msg_FFC_State_Stake', $data, $this->roomRule['rid']);

        $this->timestamp = time();

        $this->timer = Timer::add(TIME_BET, function () {
            Timer::del($this->timer);
            if (!empty($this->curbet)) {
                Logic::SendAll('Msg_FFC_Act_Table', $this->curbet, $this->roomRule['rid']);
                $this->curbet = [];
            }

            $this->procedure(STAGE_OPEN);
        }, [], false);
    }

    /**
     * 有头像玩家 
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
                'nickname' => $this->PlayerInfo[$key]['nickname'],
                'gold' => $this->PlayerInfo[$key]['gold'],
                'headimgurl' => $this->PlayerInfo[$key]['headimgurl'],
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
                'nickname' => $this->PlayerInfo[$key]['nickname'],
                'headimgurl' => $this->PlayerInfo[$key]['headimgurl'],
            ];

            $i--;
            if ($i <= 0) {
                break;
            }
        }

        $this->moreScore = $moreScore;
        $this->moreWin = $moreWin;
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

        if ($this->PlayerInfo[$this->banker['uid']]['gold'] < APPLICATION_CONDITION_SCORE) {

            $bankerCode = false;
        }

        if ($this->banker['isbanker']) {
            $bankerCode = false;
        }

        if (!$bankerCode) {
            foreach ($this->applybanker as $key => $val) {
                unset($this->applybanker[$key]);
                if ($this->PlayerInfo[$key]['gold'] < BANKER_SCORE) {
                    continue;
                }

                $uid = $key;
                break;
            }

            if (!isset($uid)) {
                foreach ($this->PlayerInfo as $key => $val) {
                    if ($val['client_id'] == '' && $val['gold'] >= BANKER_SCORE) {
                        $uid = $key;
                        break;
                    }
                }
            }

            $this->banker = [
                'uid' => $uid,
                'num' => 0,
                'nickname' => $this->PlayerInfo[$uid]['nickname'],
                'gold' => $this->PlayerInfo[$uid]['gold'],
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
        $openArr = $this->openArr();

        $players = [];
        $win_arr = FFC_TIGER_TYPE;
        $sendHorse = [];

        $bankerScore = 0;
        if ($openArr[BET_WANG] > $openArr[BET_GE]) {
            $win_arr = FFC_LOONG_TYPE;
        } elseif ($openArr[BET_WANG] == $openArr[BET_GE]) {
            $win_arr = FFC_FLAT_TYPE;
        }

        foreach ($this->PlayerInfo as $key => $val) {
            $this->PlayerInfo[$key]['playnum']++;
            $score = 0;
            $betScore = 0;
            if (!empty($val['bet']) || $this->banker['uid'] != $key) {
                $allTypes = [];
                foreach ($val['bet'] as $key1 => $val1) {
                    if (empty($val1)) {
                        continue;
                    }
                    $InsertProfit = 0;

                    if ($key1 == $win_arr) {
                        $_score = BEISHU_ARR[$key1] * $val1;
                        $InsertProfit += $_score;

                        $bankerScore += $_score;
                        $_score *= $this->roomRule['rebate'];;
                        $betScore += $val1;
                        $score += $_score;
                        $this->changScore($key, $val1 + $_score);
                    } elseif ($key1 < FFC_BIG_TYPE) {
                        $score -= $val1;
                        $InsertProfit -= $val1;

                        $bankerScore -= $val1;

                        $betScore += $val1;
                    } else {
                        foreach ($val1 as $key2 => $val2) {
                            $number = $openArr[$key2];
                            $types = [];
                            $betScore += $val2;

                            if ($number > 4) {
                                $types[] = FFC_BIG_TYPE;

                                if (!in_array(FFC_BIG_TYPE, $allTypes)) {
                                    $allTypes[] = FFC_BIG_TYPE;
                                }
                            } else {
                                $types[] = FFC_SMALL_TYPE;

                                if (!in_array(FFC_SMALL_TYPE, $allTypes)) {
                                    $allTypes[] = FFC_SMALL_TYPE;
                                }
                            }

                            if ($number % 2 == 0) {
                                $types[] = FFC_DOUBLE_TYPE;

                                if (!in_array(FFC_DOUBLE_TYPE, $allTypes)) {
                                    $allTypes[] = FFC_DOUBLE_TYPE;
                                }
                            } else {
                                $types[] = FFC_SINGLE_TYPE;

                                if (!in_array(FFC_SINGLE_TYPE, $allTypes)) {
                                    $allTypes[] = FFC_SINGLE_TYPE;
                                }
                            }

                            if (in_array($key1, $types)) {
                                $_score = BEISHU_ARR[$key1] * $val2;
                                $bankerScore += $_score;
                                $InsertProfit += $_score;

                                $_score *= $this->roomRule['rebate'];
                                $score += $_score;
                                $this->changScore($key, ($val2 + $_score));
                            } else {
                                $score -=  $val2;
                                $InsertProfit -= $val2;

                                $bankerScore -= $val2;
                            }
                        }
                    }
                }

                if ($val['client_id'] != '') {
                    Logic::InsertProfit($this->roomRule['level'],  $InsertProfit);
                }

                // if ($score > 0) {
                //     $score = intval($score * $this->roomRule['rebate']);
                //     $this->changScore($key, $score);

                if ($score >= HORSE_LAMO) {
                    $sendHorse[$key] = $score;
                }
                // }

                $historyinfo = $this->historyinfo($this->banker['uid']);
                $count = count($val['history']);
                if ($count >= 20) {
                    array_shift($this->PlayerInfo[$key]['history']);
                }

                $this->PlayerInfo[$key]['history'][] = [
                    'betGold' => $betScore,
                    'betVictory' => $score > 0 ? 1 : 0,

                ];
                $allTypes[] = $win_arr;

                if ($betScore > 0) {
                    if ($val['client_id'] != '') {
                        DBInstance::IncrementUserGet($key, $score);
                    }
                    $players[$key] = [
                        'score' => $score,
                        'gold' => $this->PlayerInfo[$key]['gold'],
                        'allTypes' => $allTypes,
                        'betGold' => $historyinfo['betGold'],
                        'betVictory' => $historyinfo['betVictory'],

                    ];
                }
            }

            if ($score > 0) {
                $this->PlayerInfo[$key]['win']++;
            }

            $_num = $this->PlayerInfo[$key]['win'] / $this->PlayerInfo[$key]['playnum'];
            $_num = sprintf("%.2f", $_num);
            $this->userWin[$key] = $_num; //玩家概率
            $this->userScore[$key] = $this->PlayerInfo[$key]['gold']; //玩家分数
        }

        if ($this->PlayerInfo[$this->banker['uid']]['client_id'] != '') {
            Logic::InsertProfit($this->roomRule['level'],  -$bankerScore);
        }

        if (-$bankerScore >= HORSE_LAMO) {
            $sendHorse[$this->banker['uid']] = -$bankerScore;
        }

        $this->changScore($this->banker['uid'], -$bankerScore);

        if ($bankerScore > 0) {
            $this->PlayerInfo[$this->banker['uid']]['win']++;
        }

        $_num = sprintf("%.2f", $_num);
        $this->userWin[$this->banker['uid']] = $_num; //玩家概率
        $this->userScore[$this->banker['uid']] = $this->PlayerInfo[$this->banker['uid']]['gold']; //玩家分数

        $count = count($this->PlayerInfo[$this->banker['uid']]['history']);
        if ($count >= 20) {
            array_shift($this->PlayerInfo[$key]['history']);
        }

        if ($this->PlayerInfo[$this->banker['uid']]['client_id'] != '') {
            DBInstance::IncrementUserGet($this->banker['uid'], -$bankerScore);
        }

        $this->PlayerInfo[$this->banker['uid']]['history'][] = [
            'betGold' => 0,
            'betVictory' => $bankerScore > 0 ? 1 : 0
        ];

        $historyinfo = $this->historyinfo($this->banker['uid']);
        $players[$this->banker['uid']] = [
            'score' => -$bankerScore,
            'gold' => $this->PlayerInfo[$this->banker['uid']]['gold'],
            'allTypes' => [],
            'betGold' => $historyinfo['betGold'],
            'betVictory' => $historyinfo['betVictory']
        ];

        $res = [
            'player' => $players,
            'time' => TIME_OPEN,
            'openArr' => $openArr
        ];

        Logic::SendAll('Msg_FFC_State_Open', $res, $this->roomRule['rid']);

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
    /**
     * 申请上庄列表分数变化
     */
    private function changeApplyBanker()
    {
        foreach ($this->applybanker as $key => $val) {
            if ($this->PlayerInfo[$key]['gold'] < BANKER_SCORE) {
                unset($this->applybanker[$key]);
                continue;
            }

            $this->applybanker[$key]['gold'] = $this->PlayerInfo[$key]['gold'];
        }
    }

    /**
     * 开奖的数组
     */
    private function openArr()
    {
        $controlnum = DBInstance::GetControlRand($this->roomRule['gtype']);

        if (rand(0, 100) <= $controlnum) {
            if ($this->PlayerInfo[$this->banker['uid']]['client_id'] == '') {
                $control = 2; //庄家赢
            } else {
                $control = 1; //庄家输
            }
        }

        $prize = []; //龙虎和=>分数 大小单双=>球的位置=>分数
        foreach ($this->PlayerInfo as $key => $val) {
            if (($this->PlayerInfo[$this->banker['uid']]['client_id'] != '' && $val['client_id'] == '') || ($this->PlayerInfo[$this->banker['uid']]['client_id'] == '' && $val['client_id'] != '')) {
                foreach ($val['bet'] as $key1 => $val1) {
                    if ($key1 <= FFC_TIGER_TYPE) {
                        if (!isset($prize[$key1])) {
                            $prize[$key1] = 0;
                        }
                        $prize[$key1] += $val1;
                    } else {
                        foreach ($val1 as $key2 => $val2) {
                            if (!isset($prize[$key1][$key2])) {
                                $prize[$key1][$key2] = 0;
                            }
                            $prize[$key1][$key2] += $val2;
                        }
                    }
                }
            }
        }
        $one = 0;
        $res = [];

        if (isset($control)) {
            foreach (BET_ARR as $key => $val) {
                $data = $this->openRegionInfo($val, $prize);
                if ($control == 2) {
                    // if (($control == 2 && $this->PlayerInfo[$this->banker['uid']]['client_id'] == '') || ($control == 2 && $this->PlayerInfo[$this->banker['uid']]['client_id'] != '')) {
                    $_data = $data['win'];

                    if (empty($_data)) {
                        $_data = $data['trans'];
                        if ($one == 0) {
                            $one = FFC_TIGER_TYPE;
                        }
                    }

                    if ($one == 0) {
                        $one = FFC_LOONG_TYPE;
                    }
                } else {
                    $_data = $data['trans'];
                    if (empty($_data)) {
                        $_data = $data['win'];
                        if ($one == 0) {
                            $one = FFC_LOONG_TYPE;
                        }
                    }

                    if ($one == 0) {
                        $one = FFC_TIGER_TYPE;
                    }
                }
                $means = array_rand($_data);

                $res[$val] = $this->generate($means, $_data[$means]);
            }
        } else {
            foreach (BET_ARR as $key => $val) {
                $res[$val] = rand(0, 9);
            }
        }
        return $res;
    }

    /**
     *
     * @param int $ball 个十百千万
     * @param [] $prize 【龙虎和=>分数 大小单双=>球的位置=>分数】
     * @return void
     */
    private function openRegionInfo($ball, $prize)
    {
        $res = ['win' => [], 'trans' => []];
        foreach (BET_CONTROL_ALL as $key => $val) {
            $score = 0;

            if (isset($prize[$key])) {
                $score = - (BEISHU_ARR[$key] * $prize[$key]);
            }

            foreach (BET_CONTROL_ALL as $key1 => $val1) {
                if ($key == $key1 || !isset($prize[$key1])) {
                    continue;
                }
                $score += BEISHU_ARR[$key1] * $prize[$key1];
            }

            foreach ($val as $key1 => $val1) {
                $_score = $score;
                foreach ($val1 as $key2 => $val2) {
                    if (isset($prize[$val2][$ball])) {
                        $_score -= BEISHU_ARR[$val2] * $prize[$val2][$ball];
                    }


                    $type = FFC_BIG_TYPE;
                    if ($val2 == FFC_BIG_TYPE) {
                        $type = FFC_SMALL_TYPE;
                    } elseif ($val2 == FFC_SINGLE_TYPE) {
                        $type = FFC_DOUBLE_TYPE;
                    } elseif ($val2 == FFC_DOUBLE_TYPE) {
                        $type = FFC_SINGLE_TYPE;
                    }

                    if (isset($prize[$type][$ball])) {
                        $_score += BEISHU_ARR[$type] * $prize[$type][$ball];
                    }
                }

                $name = 'win';
                if ($_score <= 0) {
                    $name = 'trans';
                }
                $res[$name][$key][$key1] = $_score;
            }
        }

        return $res;
    }
    /**
     * 生成开奖信息
     *
     * @param int $means
     * @return void
     */
    private function generate($means, $data)
    {
        $res = BET_CONTROL_ALL[$means][array_rand($data)];
        // $res = BET_CONTROL_ALL[$means][array_rand($data[$means])];

        $min = 0;
        $max = 9;

        if (in_array(FFC_BIG_TYPE, $res)) {
            $min = 5;
            $rand = rand($min, $max);
            $type = $rand % 2 == 1 ? FFC_SINGLE_TYPE : FFC_DOUBLE_TYPE;
            if (!in_array($type, $res)) {
                // $rand++;
                $rand = $rand + 1 >= 10 ? $rand - 1 : $rand + 1;
            }
        } else {
            $max = 4;
            $rand = rand($min, $max);
            $type = $rand % 2 == 1 ? FFC_SINGLE_TYPE : FFC_DOUBLE_TYPE;

            if (!in_array($type, $res)) {
                // $rand++;
                $rand = $rand - 1 < 0 ? $rand + 1 : $rand - 1;
            }
        }

        return $rand;
    }

    /**
     *最近下注20把的输赢
     * @param [type] $uid
     * @return void
     */
    private function historyinfo($uid)
    {
        $betGold = 0;
        $betVictory = 0;

        foreach ($this->PlayerInfo[$uid]['history'] as $key => $val) {
            $betGold += $val['betGold'];
            $betVictory += $val['betVictory'];
        }

        return ['betVictory' => $betVictory, 'betGold' => $betGold];
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
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_FFC_Act_Bet':
                $this->Msg_FFC_Act_Bet($message);
                break;
            case 'Msg_FFC_Out':
                $this->QuitRoom($message);
                break;
            case 'Msg_FFC_Act_Banker':
                $this->Msg_FFC_Act_Banker($message);
                break;
            case 'Msg_FFC_Act_BackBanker':
                $this->Msg_FFC_Act_BackBanker($message);
                break;
            case 'Msg_FFC_GetUserList';
                $this->Msg_FFC_GetUserList($message);
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
    private function Msg_FFC_Act_Bet($msg)
    {
        $uid = $msg['uid'];
        if ($uid == $this->banker['uid']) {
            Logic::SendError($uid, 'Msg_FFC_Act_Bet', '庄家不能下注');
            return;
        }

        if ($this->gameState != STAGE_BET) {
            Logic::SendError($uid, 'Msg_FFC_Act_Bet', '阶段错误');

            return;
        }

        $data = $msg['data'];

        if (!isset($data['code']) || !isset($data['bet']) || $data['bet'] % 1000 != 0 || $data['bet'] <= 0 || !isset($data['region'])) {
            Logic::SendError($uid, 'Msg_FFC_Act_Bet', '数据格式错误');
            return;
        }

        if ($data['code'] > FFC_TIGER_TYPE &&  !in_array($data['region'], BET_ARR)) {
            Logic::SendError($uid, 'Msg_FFC_Act_Bet', '数据格式错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['gold'] < $data['bet']) {
            Logic::SendError($uid, 'Msg_FFC_Act_Bet', '金币不足');
            return;
        }

        $betCode = $this->checkBet($data);

        if (!$betCode) {
            Logic::SendError($uid, 'Msg_FFC_Act_Bet', '庄家不够赔付');
            return;
        }

        $bet = $data['bet'];
        if ($data['code'] <= FFC_TIGER_TYPE) {
            if (isset($this->PlayerInfo[$uid]['bet'][$data['code']])) {
                $bet += $this->PlayerInfo[$uid]['bet'][$data['code']];
            }
        } else {
            $bet += empty($this->PlayerInfo[$uid]['bet'][$data['code']]) ? 0 : array_sum($this->PlayerInfo[$uid]['bet'][$data['code']]);
        }

        if ($bet > $this->roomRule['controls']['maxbet']) {
            Logic::SendError($uid, 'Msg_FFC_Act_Bet', '单区域最高下注1000万');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['region'] != 0 && $msg['data']['code'] > FFC_TIGER_TYPE && $this->PlayerInfo[$msg['uid']]['region'] != $msg['data']['region']) {
            Logic::SendError($uid, 'Msg_FFC_Act_Bet', '请选择要下注的分位');
            return;
        }

        if ($msg['data']['code'] > FFC_TIGER_TYPE && $this->PlayerInfo[$msg['uid']]['region'] == 0) {
            $this->PlayerInfo[$msg['uid']]['region'] = $msg['data']['region'];
        }

        if ($data['code'] <= FFC_TIGER_TYPE) {
            if (!isset($this->PlayerInfo[$uid]['bet'][$data['code']])) {
                $this->PlayerInfo[$uid]['bet'][$data['code']] = 0;
            }
            $this->PlayerInfo[$uid]['bet'][$data['code']] += $data['bet'];
        } else {
            if (!isset($this->PlayerInfo[$uid]['bet'][$data['code']][$data['region']])) {
                $this->PlayerInfo[$uid]['bet'][$data['code']][$data['region']] = 0;
            }
            $this->PlayerInfo[$uid]['bet'][$data['code']][$data['region']] += $data['bet'];

            if (!isset($this->betarr[$data['code']][$data['region']])) {
                $this->betarr[$data['code']][$data['region']] = 0;
            }
            $this->betarr[$data['code']][$data['region']] += $data['bet'];
        }

        $this->prize[$data['code']] += $data['bet'];

        if (!isset($this->curbet[$msg['uid']][$data['code']])) {
            $this->curbet[$msg['uid']][$data['code']] = 0;
        }

        $this->curbet[$msg['uid']][$data['code']] +=  $data['bet'];

        $this->changScore($msg['uid'], -$data['bet']);

        Logic::SendRight($msg['uid'], 'Msg_FFC_Act_Bet', $data);
    }

    /**
     * 申请庄家
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_FFC_Act_Banker($msg)
    {
        if ($this->PlayerInfo[$msg['uid']]['gold'] < APPLICATION_CONDITION_SCORE) {
            Logic::SendError($msg['uid'], 'Msg_FFC_Act_Banker', '庄家不够赔付');
            return;
        }

        $this->applybanker[$msg['uid']] = [
            'uid' => $msg['uid'],
            'nickname' => $this->PlayerInfo[$msg['uid']]['nickname'],
            'headimgurl' =>  $this->PlayerInfo[$msg['uid']]['headimgurl'],
            'gold' => $this->PlayerInfo[$msg['uid']]['gold']
        ];

        Logic::SendAll('Msg_FFC_Act_Banker', array_values($this->applybanker), $this->roomRule['rid']);
    }

    /**
     *申请下庄
     * @param [type] $msg
     * @return void
     */
    private function Msg_FFC_Act_BackBanker($msg)
    {
        if ($msg['uid']  != $this->banker['uid'] && !isset($this->applybanker[$msg['uid']])) {
            Logic::SendError($msg['uid'], 'Msg_FFC_Act_BackBanker', '你不是庄家或不在庄家列表');
            return;
        }

        if ($msg['uid']  == $this->banker['uid']) {
            $this->banker['isbanker'] = true;
        }

        unset($this->applybanker[$msg['uid']]);
        Logic::SendAll('Msg_FFC_Act_BackBanker', ['uid' => $msg['uid']], $this->roomRule['rid']);
    }

    /**
     * 玩家列表
     */
    private function Msg_FFC_GetUserList($msg)
    {
        $players = [];
        $count = 0;
        foreach ($this->PlayerInfo as $key => $val) {
            $_res = $this->historyinfo($key);
            $count++;
            $players[$key] = [
                'nickname' => $val['nickname'],
                'headimgurl' => $val['headimgurl'],
                'gold' => $val['gold'],
                'betVictory' => $_res['betVictory'],
                'betGold' => $_res['betGold']
            ];
        }
        Logic::SendRight($msg['uid'], 'Msg_FFC_GetUserList', ['players' => $players]);
    }
    /**
     * 检测下注情况
     *
     * @param [type] $state
     * @return void
     */
    private function checkBet($data)
    {
        $score = 0;
        $prize = []; //龙虎和=>分数 大小单双=>球的位置=>分数
        foreach ($this->PlayerInfo as $key => $val) {
            if (($this->PlayerInfo[$this->banker['uid']]['client_id'] != '' && $val['client_id'] != '') || ($this->PlayerInfo[$this->banker['uid']]['client_id'] == '' && $val['client_id'] == '')) {
                foreach ($val['bet'] as $key1 => $val1) {
                    if ($key1 <= FFC_TIGER_TYPE) {
                        if (!isset($prize[$key1])) {
                            $prize[$key1] = 0;
                        }
                        $prize[$key1] += $val1;
                    } else {
                        foreach ($val1 as $key2 => $val2) {
                            if (!isset($prize[$key1][$key2])) {
                                $prize[$key1][$key2] = 0;
                            }
                            $prize[$key1][$key2] += $val2;
                        }
                    }
                }
            }
        }

        foreach (BET_ARR as $key => $val) {
            $res = $this->openRegionInfo($val, $prize);
            $winscore = 0;

            foreach ($res['trans'] as $key1 => $val1) {
                $_score = 0;
                foreach ($val1 as $key2 => $val2) {
                    $_arr = BET_CONTROL_ALL[$key1][$key2];
                    $_arr[] = $key1;
                    foreach ($this->PlayerInfo as $key3 => $val3) {
                        if (empty($val3['bet'])) {
                            continue;
                        }

                        foreach ($val3['bet'] as $key4 => $val4) {
                            if ($key4 < FFC_BIG_TYPE) {
                                if (in_array($key4, $_arr)) {
                                    $_score -= BEISHU_ARR[$key4] * $val4;
                                } else {
                                    $_score += $val4;
                                }
                            } else {
                                foreach ($val4 as $key5 => $val5) {
                                    if (in_array($key4, $_arr)) {
                                        $_score -= BEISHU_ARR[$key4] * $val5;
                                    } else {
                                        $_score += $val5;
                                    }
                                }
                            }
                        }
                    }
                }
                if ($winscore = 0 || $winscore > $_score) {
                    $winscore = $_score;
                }
            }
            $score += $winscore;
        }
        $bankerScores = $this->PlayerInfo[$this->banker['uid']]['gold'];
        $bankerScore = $bankerScores  + $score - ($data['bet'] * BEISHU_ARR[$data['code']]);

        return $bankerScore > 0 ? true : false;
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
        $count = 0;
        foreach ($this->PlayerInfo[$msg['uid']]['bet'] as $key1 => $val1) {
            if (empty($val1)) {
                continue;
            }

            if ($key1 < FFC_BIG_TYPE) {
                $count += $val1;
                continue;
            }
            $count += array_sum($val1);
        }

        if (($this->gameState != STAGE_OPEN && $count > 0) || ($msg['uid'] == $this->banker['uid'] && $this->disRoom == 0)) {
            Logic::SendError($msg['uid'], 'Msg_FFC_Out', '游戏正在进行中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_FFC_Out', ['uid' => $msg['uid'], 'gold' => $gold], $this->roomRule['rid']);

        unset($this->userWin[$msg['uid']]);
        unset($this->userScore[$msg['uid']]);
        unset($this->PlayerInfo[$msg['uid']]);

        $data = ['uid' => $msg['uid'], 'rid' => $this->roomRule['rid']];
        Logic::QuitRoom($data);

        if (isset($this->moreScore[$msg['uid']]) || isset($this->moreWin[$msg['uid']]) || isset($this->applybanker[$msg['uid']])) {
            if (isset($this->applybanker[$msg['uid']])) {
                unset($this->applybanker[$msg['uid']]);
            }

            $this->maxWinScore();
            Logic::SendAll('Msg_FFC_Head', [
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
        if ($client_id != '') {
            $this->PlayerInfo[$uid]['onlineTime'] = 0;

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
        $this->PlayerInfo[$uid]['onlineTime'] = time() + OUT_TIME;

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
        Logic::SendAll('Msg_FFC_Add', [], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);

        if ($this->banker['uid'] != 0 && count($this->PlayerInfo) >= $this->roomRule['vals']['min'] && $this->gameState == STAGE_WAIT) {
            $this->starGame();

            $this->aiTimer();
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

    /**
     *定时同步消息/AI定时操作
     * @return void
     */
    private function aiTimer()
    {
        $this->aiActTimer = Timer::add(2, function () {
            if ($this->gameState == STAGE_BET) {
                $_num = 0;
                $playercount = count($this->PlayerInfo);

                foreach ($this->PlayerInfo as $key => $val) {
                    if ($key == $this->banker['uid']) {
                        continue;
                    }

                    if ($val['client_id'] == '' &&  rand(0, 100) <= 80) {
                        $count = 0;
                        foreach ($val['bet'] as $key1 => $val1) {
                            if (empty($val1)) {
                                continue;
                            }

                            if ($key1 < FFC_BIG_TYPE) {
                                $count += $val1;
                                continue;
                            }
                            $count += array_sum($val1);
                        }

                        if ($count <= 0 && $playercount >= $this->roomRule['vals']['min']) {
                            $code = false;
                            if (time() > $val['time']) {
                                $code = true;
                            }

                            if ($code) {
                                $this->QuitRoom(['uid' => $key]);
                                continue;
                            }
                        }

                        $data = $this->AIbet($key);

                        if ($data['bet'] > 0 && rand(0, 100) < 50) {
                            $this->All_RECV(['uid' => $key, 'event' => 'Msg_FFC_Act_Bet', 'data' => $data]);
                        }

                        if (count($this->applybanker) < 5 && $val['gold'] >= APPLICATION_CONDITION_SCORE && !isset($this->applybanker[$key])) {
                            $this->All_RECV(['uid' => $key, 'event' => 'Msg_FFC_Act_Banker']);
                            $_num++;
                        }
                    }
                }

                if (!empty($this->curbet)) {
                    Logic::SendAll('Msg_FFC_Act_Table', $this->curbet, $this->roomRule['rid']);
                }

                $this->curbet = [];
            } else {
                foreach ($this->PlayerInfo as $key => $val) {
                    if ($val['onlineTime'] == 0) {
                        continue;
                    }

                    if ($val['onlineTime'] < time()) {
                        $this->QuitRoom(['uid' => $key]);
                    }
                }
            }
        });
    }

    /**
     * 机器人下注
     */
    private function AIbet($uid)
    {
        $count = array_sum(PROBABILITY_AI_LHD);

        $rand = rand(0, $count);
        $probability = 0;
        foreach (PROBABILITY_AI_LHD as $key => $val) {
            $probability += $val;
            if ($probability >= $rand) {
                $type = $key;
                break;
            }
        }

        $arr = SCORE_BET_FFC;
        foreach ($arr as $key => $val) {
            if ($val > $this->PlayerInfo[$uid]['gold']) {
                unset($arr[$key]);
            }
        }
        $sum = array_sum($arr);
        $betrand = rand(1, $sum);
        $score = 0;
        $_sum = 0;
        foreach ($arr as $key => $val) {
            $_sum += $val;
            if ($_sum > $betrand) {
                $score = $key;
                break;
            }
        }

        $region = 0;
        if ($type > FFC_TIGER_TYPE) {
            $region = $this->PlayerInfo[$uid]['region'];
            if ($this->PlayerInfo[$uid]['region'] == 0) {

                $region = BET_ARR[array_rand(BET_ARR)];
            }
        }

        return  ['code' => $type, 'bet' => $score, 'region' => $region];
    }
}

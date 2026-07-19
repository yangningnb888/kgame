<?php

use Workerman\Lib\Timer;

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;

use function PHPSTORM_META\map;
use function PHPSTORM_META\type;

define("CLZS_STAGE_WAIT", 0);  //等待开始
define("CLZS_STAGE_START", 1);  //等待开始
define("CLZS_STAGE_OLD", 2);  //解散

define("CLZS_TYPE_WARRIOR", 1); //战士
define("CLZS_TYPE_SCATTER", 2); //替换
define("CLZS_TYPE_JACKPOT", 3); //免费和奖池图标
define("CLZS_TYPE_CHIM", 4); //猩猩
define("CLZS_TYPE_ELEPHANT", 5); //大象
define("CLZS_TYPE_LEOPARD", 6); //豹子
define("CLZS_TYPE_CROCODILE", 7); //鳄鱼
define("CLZS_TYPE_A", 8); //A
define("CLZS_TYPE_K", 9); //K
define("CLZS_TYPE_Q", 10); //Q
define("CLZS_TYPE_J", 11); //j
define("CLZS_TYPE_10", 12); //10
define("CLZS_TYPE_9", 13); //9

define('CLZS_MAX_GEAR', 15); //最大档位

define('CLZS_MAX_LINE', 75); //线数

define('CLZS_FREE_NUM', [3 => [8 => 1, 10 => 1, 15 => 1, 12 => 1], 4 => [15 => 1, 20 => 1, 25 => 1, 30 => 1], 5 => [20 => 1, 30 => 1, 40 => 1, 50 => 1]]); //免费次数范围

define("CLZS_WIN_BEISHU", [
    CLZS_TYPE_WARRIOR => [
        2 => 20,
        3 => 100,
        4 => 150,
        5 => 300
    ],
    // CLZS_TYPE_SCATTER => [
    //     3 => 0,
    //     4 => 0,
    //     5 => 0
    // ],

    CLZS_TYPE_JACKPOT => [
        3 => 100,
        4 => 500,
        5 => 1000
    ],

    CLZS_TYPE_CHIM => [
        3 => 40,
        4 => 80,
        5 => 150
    ],
    CLZS_TYPE_ELEPHANT => [
        3 => 40,
        4 => 80,
        5 => 150,
    ],
    CLZS_TYPE_LEOPARD => [
        3 => 20,
        4 => 60,
        5 => 100
    ],

    CLZS_TYPE_CROCODILE => [
        3 => 20,
        4 => 60,
        5 => 100
    ],
    CLZS_TYPE_A => [
        3 => 10,
        4 => 40,
        5 => 80
    ],
    CLZS_TYPE_K => [
        3 => 10,
        4 => 40,
        5 => 80
    ],
    CLZS_TYPE_Q => [
        3 => 5,
        4 => 20,
        5 => 60
    ],
    CLZS_TYPE_J => [
        3 => 5,
        4 => 20,
        5 => 60
    ],
    CLZS_TYPE_10 => [
        3 => 5,
        4 => 10,
        5 => 60
    ],
    CLZS_TYPE_9 => [
        2 => 2,
        3 => 5,
        4 => 40,
        5 => 60
    ]
]); //中奖对应倍数

define('CLZS_TYPR_ALL', [
    CLZS_TYPE_WARRIOR, CLZS_TYPE_SCATTER, CLZS_TYPE_JACKPOT, CLZS_TYPE_CHIM, CLZS_TYPE_ELEPHANT, CLZS_TYPE_LEOPARD,
    CLZS_TYPE_CROCODILE, CLZS_TYPE_A, CLZS_TYPE_K, CLZS_TYPE_Q, CLZS_TYPE_J, CLZS_TYPE_10, CLZS_TYPE_9
]); //所有的图标

define('CLZS_JACKPOT_MINI', 1);
define('CLZS_JACKPOT_MIN', 2);
define('CLZS_JACKPOT_MAIOR', 3);
define('CLZS_JACKPOT_SUPRE', 4);
define('CLZS_JACKPOT_GRAND', 5);

define('CLZS_MAP_SIZE', [0 => 4, 1 => 5, 2 => 5, 3 => 5, 4 => 4]);

define('CLZS_FREE_FREE', [2 => 5, 3 => 8, 4 => 15, 5 => 20]); //免费中免费

define('CLZS_JACKPOT_RATIO', 0.066); //根据档位奖池分数比例

define('CLZS_JACKPOT_REAL_SCORE', [CLZS_JACKPOT_GRAND => 50, CLZS_JACKPOT_SUPRE => 30, CLZS_JACKPOT_MAIOR => 10, CLZS_JACKPOT_MIN => 6, CLZS_JACKPOT_MINI => 4]); //每个奖池分数

define('CLZS_TYPE_SCORE', [
    1 => ['score' => 5000, 'beishu' => 5],
    2 => ['score' => 10000, 'beishu' => 10],
    3 => ['score' => 50000, 'beishu' => 20],
    4 => ['score' => 100000, 'beishu' => 40],
]);

define('CLZS_SAVE_DATA', ['score' => 800000, 'beishu' => 40]); //需要存数据
define('CLZS_SAVE_DATA_TYPE_SCORE', 1); //分数和倍数达到标准
define('CLZS_SAVE_DATA_TYPE_FREE', 2); //免费
define('CLZS_SAVE_DATA_TYPE_JACKPOT', 3); //中奖池
define('CLZS_JACKPOT_SCORE_WIN', 800000);
define('CLZS_FREE_SCORE_WIN', 800000);

define('CLZS_GEAR_ARR', [
    1 => 100,
    2 => 250,
    3 => 500,
    4 => 750,
    5 => 1000,
    6 => 1750,
    7 => 2500,
    8 => 3750,
    9 => 5000,
    10 => 7500,
    11 => 10000,
    12 => 15000,
    13 => 20000,
    14 => 25000,
    15 => 37500,
]);


class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $map = [];

    private $controlMap = [
        0 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 0,
            CLZS_TYPE_JACKPOT => 1000,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,
        ],
        1 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 500,
            CLZS_TYPE_JACKPOT => 1000,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,
        ],
        2 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 500,
            CLZS_TYPE_JACKPOT => 1000,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,
        ],
        3 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 500,
            CLZS_TYPE_JACKPOT => 300,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,

        ],
        4 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 0,
            CLZS_TYPE_JACKPOT => 300,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,
        ],
    ];

    private $freeControlMap = [
        0 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 100,
            CLZS_TYPE_JACKPOT => 20,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,
        ],
        1 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 100,
            CLZS_TYPE_JACKPOT => 20,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,
        ],
        2 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 20,
            CLZS_TYPE_JACKPOT => 300,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,
        ],
        3 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 20,
            CLZS_TYPE_JACKPOT => 300,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,
        ],
        4 => [
            CLZS_TYPE_WARRIOR => 500,
            CLZS_TYPE_SCATTER => 20,
            CLZS_TYPE_JACKPOT => 300,
            CLZS_TYPE_CHIM => 700,
            CLZS_TYPE_ELEPHANT => 700,
            CLZS_TYPE_LEOPARD => 800,
            CLZS_TYPE_CROCODILE => 800,
            CLZS_TYPE_A => 900,
            CLZS_TYPE_K => 900,
            CLZS_TYPE_Q => 1000,
            CLZS_TYPE_J => 1000,
            CLZS_TYPE_10 => 1000,
            CLZS_TYPE_9 => 800,
        ],
    ];
    private $typeJF = []; //免费奖池图标个数

    private $typeScatter = 0; //万能图标的个数

    private $jackpotFree = [
        'jackpot' => 20, //中奖池概率
        'jackpotbeishu' => [1 => 80, 2 => 8, 3 => 5, 4 => 3, 5 => 2, 10 => 1], //中奖池倍数
        'jackLeve' => [1 => 40, 2 => 25, 3 => 20, 4 => 15, 5 => 5], //中奖池等级对应概率
    ]; //中奖池

    private $agoMap = []; //上一把的地图

    private $freeAgoMap = []; //中免费前的地图

    private $disRoom = 0; //解散房间标志

    private $ransport = [5, 8];

    private $countbet = 0;

    private $maxbeishu = 0;

    private $freenum = CLZS_FREE_NUM;

    private $jackpot = 0;

    private $controlinfo = [];

    private $timer = 0;
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
            'vals' => $msg['vals'], //可选倍数
        ];

        $this->userEnter($msg['players']);

        $this->roomInfo();
        foreach (CLZS_GEAR_ARR as $key => $val) {
            $this->countbet += $val;
        }
        $this->controlinfo = DBInstance::GetControlInfo('control_clzs');
    }

    /**
     * 玩家进房
     * @param int
     * */
    private function userEnter($PlayerInfo)
    {
        $data = [
            'score' => 0,
            'free' => 0,
            'multiple' => 1,
            'winNum' => 0,
            'ransport' => -1,
            'freeData' => [],
            'freeScore' => 0,
            'gear' => 1,
            'control' => 0
        ];

        foreach ($PlayerInfo as $key => $val) {
            $this->uid = $key;
            $this->PlayerInfo = array_merge($val, $data);
        }
    }

    /**
     * 刷新房间
     *
     * @param integer $uid
     * @return void
     */
    private function roomInfo()
    {
        if ($this->PlayerInfo['client_id'] != '') {
            Gateway::joinGroup($this->PlayerInfo['client_id'], 'ROOM:' . $this->roomRule['rid']);
        }

        $res = [
            'map' => $this->map,
            'curgrade' => $this->PlayerInfo['multiple'],
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'gold' => $this->PlayerInfo['gold'],
            'free' => $this->PlayerInfo['free'],
            'max_multiple' => CLZS_MAX_GEAR,
            'gearConfig' => CLZS_GEAR_ARR,
            'jackpotConfig' => CLZS_JACKPOT_REAL_SCORE,
        ];

        Logic::SendAll('Msg_CLZS_RoomInfo', $res, $this->roomRule['rid']);
    }

    /**
     * 初始地图
     *
     * @return void
     */
    private function InitMap($code = true)
    {
        $this->map = [];
        $this->typeJF = [];
        $this->typeScatter = 0;
        foreach (CLZS_MAP_SIZE as $key => $val) {
            for ($i = 0; $i < $val; $i++) {
                $type = $this->createType($key);
                if ($type == CLZS_TYPE_JACKPOT) {
                    $this->typeJF[] = [$key, $i];
                }

                if ($type == CLZS_TYPE_SCATTER) {
                    $this->typeScatter++;
                }
                $this->map[$key][] = $type;
            }
        }
        if ($code) {
            $data = $this->checkdata();
            if ($this->ransport == 0) {
                $this->newMap();
            } elseif ($data['beishu'] > $this->maxbeishu) {
                $this->InitMap(false);
            }
        }
    }

    private function newMap()
    {
        $_alltype = array_diff(CLZS_TYPR_ALL, [CLZS_TYPE_JACKPOT, CLZS_TYPE_WARRIOR]);
        $type = $_alltype[array_rand($_alltype)];
        $arr = [];
        foreach (CLZS_WIN_BEISHU[$this->roomRule['level']][$type] as $key => $val) {
            if ($key >= 5) {
                continue;
            }
            $arr[$key] = 1;
        }
        $rand = array_rand($arr);
        for ($i = 0; $i < $rand; $i++) {
            if (!in_array($type, $this->map[$i])) {
                $_rand = rand(0, count($this->map[$i]) - 1);
                $this->map[$i][$_rand] = $type;
            }
        }
    }

    private function createType($row)
    {
        $controlMap = $this->controlMap[$row];
        if ($this->PlayerInfo['free'] > 0) {
            $controlMap = $this->freeControlMap[$row];
        }

        if ($row == 0 || $row == 4) {
            unset($controlMap[CLZS_TYPE_SCATTER]);
        }

        $rand = rand(1, array_sum($controlMap));
        $_rand = 0;
        $type = 0;
        foreach ($controlMap as $key => $val) {
            $_rand += $val;
            if ($_rand >= $rand) {
                $type = $key;
                break;
            }
        }

        return $type;
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_CLZS_Start':
                $this->Msg_CLZS_Start($message);
                break;
            case 'Msg_CLZS_Out':
                $this->OldRoom($message);
                break;
            default: {
                    Logic::SendError($message['uid'], $message['event'], '');

                    MyTools::msg('uid:' . $message['uid'] . '-----UnKnown!!!!!!!--------' . $message['event'], true);
                    break;
                }
        }
    }

    /**
     * 开始游戏
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_CLZS_Start($msg)
    {
        if (!isset($msg['data']['multiple']) || !is_int($msg['data']['multiple'])  || $msg['data']['multiple'] <= 0 || $msg['data']['multiple'] > CLZS_MAX_GEAR) {
            Logic::SendError($msg['uid'], 'Msg_CLZS_Start', '下注倍数错误');
            return;
        }

        $score = 0;
        if ($this->PlayerInfo['free'] <= 0) {
            $score = CLZS_GEAR_ARR[$msg['data']['multiple']] * $this->roomRule['doublescore'];
            $this->PlayerInfo['multiple'] = $msg['data']['multiple'];
        }

        if ($this->PlayerInfo['gold'] < $score) {
            Logic::SendError($msg['uid'], 'Msg_CLZS_Start', '金币不足，请充值');
            return;
        }

        if ($score > 0) {
            $this->changScore(-$score);
        }

        $this->PlayerInfo['control'] =  DBInstance::GetLBControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);

        $this->GetControlCLZS();
        if ($this->PlayerInfo['ransport'] < 0) {
            $this->PlayerInfo['ransport'] = rand($this->ransport[0], $this->ransport[1]);
        }

        $this->InitMap();

        $this->agoMap = $this->map;

        if ($score > 0 || empty($this->freeAgoMap)) {
            $this->freeAgoMap = $this->map;
        }

        $data = $this->checkdata();
        if ($score <= 0) {
            $this->PlayerInfo['free']--;
        }

        $this->PlayerInfo['free'] += $data['curfree'];

        $gamescore = $data['beishu'] * (CLZS_GEAR_ARR[$msg['data']['multiple']] * $this->roomRule['doublescore'] / CLZS_MAX_LINE);
        $jackpot = $data['jackpot'] * $data['jackpotbeishu'];
        $data['score'] = $gamescore;

        if ($data['score'] + $jackpot > 0) {
            $this->changScore($data['score'] + $jackpot);
        }

        $data['conscore'] = $score;
        $data['gold'] = $this->PlayerInfo['gold'];
        $data['free'] = $this->PlayerInfo['free'];
        $data['map'] = $this->map;
        $data['type'] = $this->typeScore($data['score']);

        Logic::SendAll('Msg_CLZS_Start', $data, $this->roomRule['rid']);

        $jackpotarr =  $this->SaveJackpot(0, 1);
        $this->jackpot = $jackpotarr['jackpot'];
        $jackpot  = 0;
        if ($score > $data['score']) {
            $jackpot = intval(($score - $data['score']) * $jackpotarr['probability'] / 100);

            $this->SaveJackpot($jackpot, 2);
            $this->PlayerInfo['ransport']--;
        } else {
            $this->PlayerInfo['ransport'] = rand($this->ransport[0], $this->ransport[1]);
        }
        $jackpotscore = 0;
        if ($data['jackpot'] > 0) {
            $this->SaveJackpot(-$data['jackpot'], 2);
            $jackpotbeishu = $data['jackpotbeishu'] - 1;
            if ($jackpotbeishu > 0) {
                $jackpotscore = $data['jackpot'] * $jackpotbeishu;
            }
        }

        if (abs($this->PlayerInfo['control'])  != 2) {
            $profit = $gamescore + $jackpotscore - $score + $jackpot;
            Logic::InsertProfit($this->roomRule['level'], $profit);
        }

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($msg['uid'], $data['score'] - $score);
        }

        if ($score <= 0 || $this->PlayerInfo['free'] > 0) {
            $this->PlayerInfo['freeData'][] = $data;
            $this->PlayerInfo['freeScore'] += $data['score'];
        } else {
            $this->PlayerInfo['freeData'] = [];
            $this->PlayerInfo['freeScore'] = 0;
        }

        $this->saveData($data);

        if ($this->disRoom == 1 && $this->PlayerInfo['free'] <= 0) {
            $this->All_RECV(['event' => 'Msg_CLZS_Out', 'uid' => $msg['uid']]);
        }
    }

    /**
     * 线路检测
     *
     * @return void
     */
    private function checkdata()
    {
        $beishu = 0; //除开奖池的倍数
        $curMap = []; //中奖坐标
        $win = []; //赢得数据
        $free = 0; //免费次数
        $jackpotscore = 0; //奖池分数
        $jackpotbeishu = 1; //奖池中奖倍数
        $jackpotleve = 0; //奖池的等级
        $freebeishu = 1; //中免费后有白边图标的倍数

        foreach ($this->map[0] as $key => $val) {
            $count = 1;
            $continuity = 1;
            $map = [];
            $map[] = [0, $key];
            foreach ($this->map as $key1 => $val1) {
                if ($key1 == 0) {
                    continue;
                }
                $temp = array_count_values($val1);

                if (isset($temp[$val]) || (isset($temp[CLZS_TYPE_SCATTER]) && $val != CLZS_TYPE_JACKPOT)) {
                    foreach ($val1 as $key2 => $val2) {
                        if (!in_array([$key1, $key2], $curMap) && ($val2 == $val || ($val2 == CLZS_TYPE_SCATTER &&  $val != CLZS_TYPE_JACKPOT))) {
                            $map[] = [$key1, $key2];
                        }
                    }

                    $num = isset($temp[$val]) ? $temp[$val] : 0;
                    $num +=  isset($temp[CLZS_TYPE_SCATTER]) ? isset($temp[CLZS_TYPE_SCATTER]) : 0;
                    $count *= $num;
                    $continuity++;
                } else {
                    break;
                }
            }

            if (isset(CLZS_WIN_BEISHU[$val][$continuity])) {
                $_beishu = CLZS_WIN_BEISHU[$val][$continuity] * $count;
                $curMap = array_merge($curMap, $map);
                if (!isset($win[$val])) {
                    $win[$val] = [
                        'type' => $val,
                        'multiple' => 0,
                    ];
                }
                $win[$val]['multiple'] += $_beishu;

                $beishu += $_beishu;
            }
        }
        $countJF = count($this->typeJF);

        if ($this->PlayerInfo['free'] > 0) {
            if ($this->typeScatter > 0) {
                $freebeishu = rand(2, 3);
                $beishu *= $freebeishu;
            }

            if ($countJF >= min(array_keys(CLZS_FREE_FREE))) {
                if (isset(CLZS_FREE_FREE[$countJF])) {
                    $free += CLZS_FREE_FREE[$countJF];
                } else {
                    $free += CLZS_FREE_FREE[max(array_keys(CLZS_FREE_FREE))];
                }
            }
        } else {
            if ($countJF >= min(array_keys(CLZS_WIN_BEISHU[CLZS_TYPE_JACKPOT]))) {
                $_num = 0;
                if (isset(CLZS_WIN_BEISHU[CLZS_TYPE_JACKPOT][$countJF])) {
                    $_num  = $countJF;
                } else {
                    $_num  = max(array_keys(CLZS_WIN_BEISHU[CLZS_TYPE_JACKPOT]));
                }

                $beishu += CLZS_WIN_BEISHU[CLZS_TYPE_JACKPOT][$_num];

                if (rand(1, 100) <= $this->jackpotFree['jackpot']) {
                    $jackpotarr = $this->SaveJackpot(0, 1);
                    $jackpotscore =  intval(CLZS_GEAR_ARR[$this->PlayerInfo['multiple']] / $this->countbet * $jackpotarr['jackpot']);
                    $rand = rand(1, array_sum($this->jackpotFree['jackLeve']));
                    $_rand = 0;
                    foreach ($this->jackpotFree['jackLeve'] as $key => $val) {
                        $_rand += $val;
                        if ($_rand >= $rand) {
                            $jackpotleve = $key;
                            break;
                        }
                    }
                    $jackpotscore *= CLZS_JACKPOT_REAL_SCORE[$jackpotleve] / 100;


                    if ($jackpotleve != CLZS_JACKPOT_GRAND) {
                        $jackpotbeishuarr = $this->jackpotFree['jackpotbeishu'];
                        if ($jackpotleve == CLZS_JACKPOT_SUPRE) {
                            unset($jackpotbeishuarr[1]);
                        }

                        $rand = rand(1, array_sum($jackpotbeishuarr));
                        $_rand = 0;

                        foreach ($jackpotbeishuarr as $key => $val) {
                            $_rand += $val;
                            if ($_rand >= $rand) {
                                $jackpotbeishu = $key;
                                break;
                            }
                        }
                    }
                }
                $randfree = rand(1, array_sum($this->freenum[$_num]));
                $_randfree = 0;
                foreach ($this->freenum[$_num] as $key => $val) {
                    $_randfree += $val;
                    if ($_randfree >= $randfree) {
                        $free += $key;
                        break;
                    }
                }
            }
        }

        if ($free > 0) {
            foreach ($this->typeJF as $key => $val) {
                if (!in_array($val, $curMap)) {
                    $curMap[] = $val;
                }
            }
        }

        $data = [
            'beishu' => $beishu,
            'win' => array_values($win),
            'curMap' => $curMap,
            'freebeishu' => $freebeishu,
            'curfree' => $free,
            'jackpotleve' => $jackpotleve,
            'jackpot' => intval($jackpotscore),
            'jackpotbeishu' => $jackpotbeishu,
        ];

        return $data;
    }


    /**
     *分数变化
     *
     * @param int $score
     * @return void
     */
    private function changScore($score)
    {
        $this->PlayerInfo['score'] += $score;
        $this->PlayerInfo['gold'] += $score;

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $this->uid, $score);
        }

        if ($score > 0) {
            DBInstance::IncrementWinPoint($this->uid, $score);
        }
    }

    /**
     *对奖池的操作
     *
     * @param int $score
     * @param int $code  1查询 2修改
     * @return int
     */
    private function SaveJackpot($score, $code)
    {
        if ($code == 2) {
            DBInstance::SaveJackpot($this->roomRule['gtype'], $this->roomRule['level'], $score);
        }

        $res = DBInstance::GetGameJackpot(['gtype' => $this->roomRule['gtype'], 'level' => $this->roomRule['level']]);

        return $res;
    }

    /**
     *获得大小奖
     */
    private function typeScore($score)
    {
        $beishu = $score / (CLZS_GEAR_ARR[$this->PlayerInfo['multiple']] * $this->roomRule['doublescore']);
        foreach (CLZS_TYPE_SCORE as $key => $val) {
            if ($beishu < $val['beishu'] || $score < $val['score']) {
                $type = $key;
                break;
            }
        }
        if (!isset($type)) {
            $type = 4;
        }
        return $type;
    }

    /**
     * 回放存储
     *
     * @param [type] $score
     * @param [type] $beishu
     * @param [type] $data
     * @return void
     */
    private function saveData($data)
    {
        $type = 0;
        $map = $this->agoMap;
        $score = $data['score'];
        if ($this->PlayerInfo['freeScore'] > 0 && $this->PlayerInfo['free'] <= 0) {
            $score = $this->PlayerInfo['freeScore'];
            $map = $this->freeAgoMap;
        }
        $beishu =  $score / (CLZS_GEAR_ARR[$this->PlayerInfo['multiple']] * $this->roomRule['doublescore']);
        if ($score < CLZS_SAVE_DATA['score'] || $beishu < CLZS_SAVE_DATA['beishu']) {
            return;
        }

        if ($this->PlayerInfo['freeScore'] > 0 && $this->PlayerInfo['free'] <= 0) {
            if ($this->PlayerInfo['freeData'][0]['jackpot'] > 0) {
                $type = $this->PlayerInfo['freeData'][0]['jackpotleve'] + 2;
            } else {
                $type = CLZS_SAVE_DATA_TYPE_FREE;
            }
            $data = $this->PlayerInfo['freeData'];
        } elseif ($data['score'] >= CLZS_SAVE_DATA['score']) {
            $type = CLZS_SAVE_DATA_TYPE_SCORE;
            $data['free'] = 0;
            $data['curfree'] = 0;
            $data = [$data];
        }

        if ($type > 0) {
            $_data = [];
            $_data['uid'] =  $this->uid;
            $_data['doublescore'] =  $this->roomRule['doublescore'];
            $_data['curgrade'] = $this->PlayerInfo['multiple'];
            $_data['map'] = $map;
            $_data['jackpotscore'] = $this->jackpot;
            $_data['data'] = $data;

            $res = [
                'type' => $type,
                'nickname' => $this->PlayerInfo['nickname'],
                'gtype' => $this->roomRule['gtype'],
                'level' => $this->roomRule['level'],
                'vals' => json_encode($_data),
                'score' => $score,
                'created' => MyTools::GET_NOW(),
                'playnum' => 0
            ];

            DBInstance::SaveData('game_back', $res);
        }

        if ($score >= 5000000 && $beishu >= 20) {
            Logic::HorseLamp($this->uid, $score, $beishu);
        }
    }
    /**
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        if ($this->PlayerInfo['free'] > 0) {
            Logic::SendError($this->uid, 'Msg_CLZS_Out', '正在免费旋转中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);

        Logic::SendAll('Msg_CLZS_Out', ['gold' => $gold], $this->roomRule['rid']);

        $olddata = [
            'rid' =>  $this->roomRule['rid'],
            'win' => [],
            'palyers' => [$this->uid => $this->PlayerInfo],
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
        $this->PlayerInfo['client_id'] = $client_id;
        Timer::del($this->timer);

        $this->roomInfo($uid);
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
        $this->timer = Timer::add(OUT_TIME, function () {
            Timer::del($this->timer);
            $this->OldRoom();
        }, [], false);
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
    }
    /**
     * 玩家金钱变化
     *
     * @param [type] $msg
     * @return void
     */
    public function ChangeGold($msg)
    {
        if ($this->roomRule['level'] != 1) {
            $this->PlayerInfo['gold'] = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        }
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
        Timer::del($this->timer);

        if ($this->PlayerInfo['free'] <= 0) {
            $this->OldRoom();
        }
    }

    private function GetControlCLZS()
    {
        $data = $this->controlinfo[$this->PlayerInfo['control']];
        // $data = DBInstance::GetTableOneWord('control_clzs', 'vals', ['level' => $this->PlayerInfo['control']]);

        // $data = json_decode($data, true);
        if (isset($data['list'])) {
            $this->controlMap = $data['list'];
        }

        if (isset($data['freelist'])) {
            $this->freeControlMap = $data['freelist'];
        }
        if (isset($data['jackpotFree'])) {
            if (isset($data['jackpotFree']['jackpot'])) {
                $this->jackpotFree['jackpot'] = $data['jackpotFree']['jackpot'];
            }

            if (isset($data['jackpotFree']['jackpotbeishu'])) {
                $this->jackpotFree['jackpotbeishu'] = $data['jackpotFree']['jackpotbeishu'];
            }
            if (isset($data['jackpotFree']['jackLeve'])) {
                $this->jackpotFree['jackLeve'] = $data['jackpotFree']['jackLeve'];
            }
        }

        if (isset($data['ransport']) && is_array($data['ransport'])) {
            $this->ransport = $data['ransport'];
        }

        if (isset($data['beishu'])) {
            $this->maxbeishu = $data['beishu'];
        }

        if (isset($data['freenum'])) {
            if (isset($data['freenum'][3])) {
                $this->freenum[3] = $data['freenum'][3];
            }
            if (isset($data['freenum'][4])) {
                $this->freenum[4] = $data['freenum'][4];
            }
            if (isset($data['freenum'][5])) {
                $this->freenum[5] = $data['freenum'][5];
            }
        }
        // Logic::SendAll('Msg_Game_Control', [
        //     'beishu' => $this->maxbeishu,
        //     'ransport' => $this->ransport,
        //     'jackpotFree' => $this->jackpotFree,
        //     'freelist' => $this->freeControlMap,
        //     'list' => $this->controlMap,
        //     'freenum' => $this->freenum,
        //     'control' => $this->PlayerInfo['control']
        // ], $this->roomRule['rid']);
    }
}

<?php

use Workerman\Lib\Timer;

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;

use function PHPSTORM_META\map;
use function PHPSTORM_META\type;

define("THB_STAGE_WAIT", 0);  //等待开始
define("THB_STAGE_START", 1);  //等待开始
define("THB_STAGE_OLD", 2);  //解散

define("THB_TYPE_TELESCOPE", 1); //望远镜
define("THB_TYPE_FISH", 2); //一盆鱼
define("THB_TYPE_TURNTABLE", 3); //转盘
define("THB_TYPE_HOOK", 4); //钩子
define("THB_TYPE_BOTTLE", 5); //瓶子
define("THB_TYPE_A", 6); //A
define("THB_TYPE_K", 7); //K
define("THB_TYPE_Q", 8); //Q
define("THB_TYPE_J", 9); //j
define("THB_TYPE_10", 10); //10
define("THB_TYPE_9", 11); //9
define("THB_TYPE_DUCK", 12); //鸭子

define("THB_TYPE_GREEN", 13); //绿色
define("THB_TYPE_PURPLE", 14); //紫色
define("THB_TYPE_RED", 15); //红色
define("THB_TYPE_YELLOW_G", 16); //黄色G
define("THB_TYPE_YELLOW_R", 17); //黄色R
define("THB_TYPE_YELLOW_A", 18); //黄色A
define("THB_TYPE_YELLOW_N", 19); //黄色N
define("THB_TYPE_YELLOW_D", 20); //黄色D
define("THB_TYPE_RED_M", 21); //红色M
define("THB_TYPE_RED_A", 22); //红色A
define("THB_TYPE_RED_J", 23); //红色J
define("THB_TYPE_RED_O", 24); //红色O
define("THB_TYPE_RED_R", 25); //红色R
define("THB_TYPE_PURPLE_M", 26); //紫色M
define("THB_TYPE_PURPLE_I", 27); //紫色I
define("THB_TYPE_PURPLE_N", 28); //紫色N
define("THB_TYPE_PURPLE_O", 29); //紫色O
define("THB_TYPE_PURPLE_R", 30); //紫色R
define("THB_TYPE_GREEN_M", 31); //绿色M
define("THB_TYPE_GREEN_I1", 32); //绿色I
define("THB_TYPE_GREEN_N", 33); //绿色N
define("THB_TYPE_GREEN_I2", 34); //绿色I

define('THB_MAX_GEAR', 14); //最大档位

define('THB_WIDTH', 5); //地图宽度

define('THB_MAX_LINE', 75); //线数

define("THB_WIN_BEISHU", [
    THB_TYPE_TELESCOPE => [
        2 => 2,
        3 => 25,
        4 => 50,
        5 => 250
    ],
    THB_TYPE_FISH => [
        3 => 10,
        4 => 25,
        5 => 100
    ],
    THB_TYPE_TURNTABLE => [
        3 => 10,
        4 => 25,
        5 => 100
    ],
    THB_TYPE_HOOK => [
        3 => 10,
        4 => 25,
        5 => 100
    ],
    THB_TYPE_BOTTLE => [
        3 => 10,
        4 => 25,
        5 => 100,
    ],
    THB_TYPE_A => [
        3 => 5,
        4 => 10,
        5 => 50
    ],
    THB_TYPE_K => [
        3 => 5,
        4 => 10,
        5 => 50
    ],
    THB_TYPE_Q => [
        3 => 5,
        4 => 10,
        5 => 50
    ],
    THB_TYPE_J => [
        3 => 5,
        4 => 10,
        5 => 50
    ],
    THB_TYPE_10 => [
        3 => 5,
        4 => 10,
        5 => 50
    ],
    THB_TYPE_9 => [
        3 => 5,
        4 => 10,
        5 => 50
    ]
]); //中奖对应倍数

define('THB_FREE_TYPE_GREEN', 1); //绿色
define('THB_FREE_TYPE_PURPLE', 2); //紫色
define('THB_FREE_TYPE_RED', 3); //红色
define('THB_FREE_TYPE_GREEN_PURPLE', 4); //绿色+紫色
define('THB_FREE_TYPE_GREEN_RED', 5); //绿色+红色
define('THB_FREE_TYPE_PURPLE_RED', 6); //紫色+红色
define('THB_FREE_TYPE_GREEN_PURPLE_RED', 7); //绿色+紫色+红色

define('THB_FREE_NUM', [
    THB_FREE_TYPE_GREEN => 5,
    THB_FREE_TYPE_PURPLE => 5,
    THB_FREE_TYPE_RED => 10,
    THB_FREE_TYPE_GREEN_PURPLE => 5,
    THB_FREE_TYPE_GREEN_RED => 10,
    THB_FREE_TYPE_PURPLE_RED => 10,
    THB_FREE_TYPE_GREEN_PURPLE_RED => 10
]); //免费次数

define('THB_TYPR_ALL_BASE', [
    THB_TYPE_TELESCOPE, THB_TYPE_FISH, THB_TYPE_TURNTABLE, THB_TYPE_HOOK, THB_TYPE_BOTTLE, THB_TYPE_A, THB_TYPE_K, THB_TYPE_Q, THB_TYPE_J, THB_TYPE_10, THB_TYPE_9, THB_TYPE_DUCK,
    THB_TYPE_GREEN, THB_TYPE_PURPLE, THB_TYPE_RED
]); //基础图标

define('THB_TYPR_ALL', [
    THB_TYPE_TELESCOPE, THB_TYPE_FISH, THB_TYPE_TURNTABLE, THB_TYPE_HOOK, THB_TYPE_BOTTLE, THB_TYPE_A, THB_TYPE_K, THB_TYPE_Q, THB_TYPE_J, THB_TYPE_10, THB_TYPE_9, THB_TYPE_DUCK, THB_TYPE_RED,
    THB_TYPE_YELLOW_G, THB_TYPE_YELLOW_R, THB_TYPE_YELLOW_A, THB_TYPE_YELLOW_N, THB_TYPE_YELLOW_D, THB_TYPE_RED_M, THB_TYPE_RED_A, THB_TYPE_RED_J, THB_TYPE_RED_O, THB_TYPE_RED_R,
    THB_TYPE_PURPLE_M, THB_TYPE_PURPLE_I, THB_TYPE_PURPLE_N, THB_TYPE_PURPLE_O, THB_TYPE_PURPLE_R, THB_TYPE_GREEN_M, THB_TYPE_GREEN_I1, THB_TYPE_GREEN_N, THB_TYPE_GREEN_I2
]); //所有的图标
define('THB_TYPE_ALL_SPE', [THB_TYPE_TELESCOPE => 1, THB_TYPE_FISH => 1, THB_TYPE_TURNTABLE => 1, THB_TYPE_HOOK => 1, THB_TYPE_BOTTLE => 1, THB_TYPE_A => 1, THB_TYPE_K => 1, THB_TYPE_Q => 1, THB_TYPE_J => 1, THB_TYPE_10 => 1, THB_TYPE_9 => 1]);
define('THB_JACKPOT_MINI', 1);
define('THB_JACKPOT_MIN', 2);
define('THB_JACKPOT_MAIOR', 3);
define('THB_JACKPOT_SUPRE', 4);

define('THB_JACKPOT_RATIO', 0.071); //根据档位奖池分数比例

define('THB_COIN_SCORE', [88, 188, 288, 388, 488, 588, 688, 788, 988, 1088, 1188, 1288, 1388, 1488, 1588, 1688, 1788, 1888, 1988, 2088]);

define('THB_WIN_MAP', [
    [2, 2, 2, 2, 2], [1, 1, 1, 1, 1], [3, 3, 3, 3, 3], [0, 0, 0, 0, 0], [2, 1, 0, 1, 2], [1, 2, 3, 2, 1], [3, 2, 1, 2, 3], [0, 1, 2, 1, 0], [1, 0, 1, 0, 1], [2, 3, 2, 3, 2],
    [2, 2, 1, 0, 0], [1, 1, 2, 3, 3], [0, 1, 1, 1, 0], [3, 2, 2, 2, 3], [2, 1, 2, 3, 2], [1, 2, 1, 0, 1], [2, 3, 3, 2, 1], [1, 0, 0, 1, 2], [2, 1, 1, 1, 2], [1, 2, 2, 2, 1],
    [1, 1, 0, 1, 2], [2, 2, 3, 2, 1], [3, 2, 3, 2, 3], [0, 1, 0, 1, 0], [3, 3, 2, 3, 3], [0, 0, 1, 0, 0], [2, 2, 1, 2, 2], [1, 1, 2, 1, 1], [3, 3, 2, 1, 1], [0, 0, 1, 2, 2],
    [2, 1, 2, 1, 2], [1, 2, 1, 2, 1], [1, 0, 1, 2, 1], [2, 3, 2, 1, 2], [2, 3, 3, 3, 2], [1, 0, 0, 0, 1], [2, 2, 2, 1, 0], [1, 1, 1, 2, 3], [3, 2, 1, 0, 2], [0, 1, 2, 3, 2],
    [2, 1, 0, 0, 0], [1, 2, 3, 3, 3], [3, 3, 3, 2, 1], [0, 0, 0, 1, 2], [0, 1, 1, 2, 3], [3, 2, 2, 1, 0], [2, 1, 1, 0, 0], [1, 2, 2, 3, 3], [3, 2, 3, 2, 1], [0, 1, 0, 1, 2]
]); //地图是4X5

define('THB_WIN_FREE_MAP', [
    [4, 4, 4, 4, 4], [3, 3, 3, 3, 3], [5, 5, 5, 5, 5], [2, 2, 2, 2, 2], [4, 3, 2, 3, 4], [3, 4, 5, 4, 3], [5, 4, 3, 4, 5], [2, 3, 4, 3, 2], [3, 2, 3, 2, 3], [4, 5, 4, 5, 4],
    [4, 4, 3, 2, 2], [3, 3, 4, 5, 5], [2, 3, 3, 3, 2], [5, 4, 4, 4, 5], [4, 3, 4, 5, 4], [3, 4, 3, 2, 3], [4, 5, 5, 4, 3], [3, 2, 2, 3, 4], [4, 3, 3, 3, 4], [3, 4, 4, 4, 3],
    [3, 3, 2, 3, 4], [4, 4, 5, 4, 3], [5, 4, 5, 4, 5], [2, 3, 2, 3, 2], [5, 5, 4, 5, 5], [2, 2, 3, 2, 2], [4, 4, 3, 4, 4], [3, 3, 4, 3, 3], [5, 5, 4, 3, 3], [2, 2, 3, 4, 4],
    [4, 3, 4, 3, 4], [3, 4, 3, 4, 3], [3, 2, 3, 4, 3], [4, 5, 4, 3, 4], [4, 5, 5, 5, 4], [3, 2, 2, 2, 3], [4, 4, 4, 3, 2], [3, 3, 3, 4, 5], [5, 4, 3, 2, 4], [2, 3, 4, 5, 4],
    [4, 3, 2, 2, 2], [3, 4, 5, 5, 5], [5, 5, 5, 4, 3], [2, 2, 2, 3, 4], [2, 3, 3, 4, 5], [5, 4, 4, 3, 2], [4, 3, 3, 2, 2], [3, 4, 4, 5, 5], [5, 4, 5, 4, 3], [2, 3, 2, 3, 4],
    [2, 2, 2, 2, 2], [1, 1, 1, 1, 1], [3, 3, 3, 3, 3], [0, 0, 0, 0, 0], [2, 1, 0, 1, 2], [1, 2, 3, 2, 1], [3, 2, 1, 2, 3], [0, 1, 2, 1, 0], [1, 0, 1, 0, 1], [2, 3, 2, 3, 2],
    [2, 2, 1, 0, 0], [1, 1, 2, 3, 3], [0, 1, 1, 1, 0], [3, 2, 2, 2, 3], [2, 1, 2, 3, 2], [1, 2, 1, 0, 1], [2, 3, 3, 2, 1], [1, 0, 0, 1, 2], [2, 1, 1, 1, 2], [1, 2, 2, 2, 1],
    [1, 1, 0, 1, 2], [2, 2, 3, 2, 1], [3, 2, 3, 2, 3], [0, 1, 0, 1, 0], [3, 3, 2, 3, 3], [0, 0, 1, 0, 0], [2, 2, 1, 2, 2], [1, 1, 2, 1, 1], [3, 3, 2, 1, 1], [0, 0, 1, 2, 2],
    [2, 1, 2, 1, 2], [1, 2, 1, 2, 1], [1, 0, 1, 2, 1], [2, 3, 2, 1, 2], [2, 3, 3, 3, 2], [1, 0, 0, 0, 1], [2, 2, 2, 1, 0], [1, 1, 1, 2, 3], [3, 2, 1, 0, 2], [0, 1, 2, 3, 2],
    [2, 1, 0, 0, 0], [1, 2, 3, 3, 3], [3, 3, 3, 2, 1], [0, 0, 0, 1, 2], [0, 1, 1, 2, 3], [3, 2, 2, 1, 0], [2, 1, 1, 0, 0], [1, 2, 2, 3, 3], [3, 2, 3, 2, 1], [0, 1, 0, 1, 2]
]); //地图是6X5

define('FREE_PURPLE_BOX', 3); //在紫色箱子中的倍数

define('THB_JACKPOT_REAL_SCORE', [THB_JACKPOT_SUPRE => 60, THB_JACKPOT_MAIOR => 20, THB_JACKPOT_MIN => 15, THB_JACKPOT_MINI => 5]); //每个奖池分数

define('THB_TYPE_SCORE', [
    1 => ['score' => 10000, 'beishu' => 5],
    2 => ['score' => 50000, 'beishu' => 10],
    3 => ['score' => 100000, 'beishu' => 20],
    4 => ['score' => 5000000, 'beishu' => 40],
]);

define('THB_SAVE_DATA', ['score' => 800000, 'beishu' => 40]); //需要存数据
define('THB_SAVE_DATA_TYPE_SCORE', 1); //分数和倍数达到标准
define('THB_SAVE_DATA_TYPE_FREE', 2); //免费
define('THB_SAVE_DATA_TYPE_JACKPOT', 3); //中奖池
define('THB_JACKPOT_SCORE_WIN', 800000);
define('THB_FREE_SCORE_WIN', 800000);

define('THB_GEAR_ARR', [
    1 => 11000,
    2 => 27500,
    3 => 55000,
    4 => 82500,
    5 => 110000,
    6 => 192500,
    7 => 275000,
    8 => 412500,
    9 => 550000,
    10 => 825000,
    11 => 1100000,
    12 => 1650000,
    13 => 2200000,
    14 => 2750000,
]);

class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $map = [];

    private $coinProbabitity = [
        8.8 => 200, 88 => 190, 188 => 180, 288 => 170, 388 => 160, 488 => 150, 588 => 140, 688 => 130, 788 => 120, 988 => 110, 1088 => 100, 1188 => 90, 1288 => 50, 1388 => 40, 1488 => 30, 1588 => 20, 1688 => 10, 1788 => 5, 1888 => 1,
    ]; //硬币分数的概率

    private $controlMap = [
        0 => [
            THB_TYPE_TELESCOPE => 500,
            THB_TYPE_FISH => 0,
            THB_TYPE_TURNTABLE => 1000,
            THB_TYPE_HOOK => 700,
            THB_TYPE_BOTTLE => 700,
            THB_TYPE_A => 900,
            THB_TYPE_K => 900,
            THB_TYPE_Q => 1000,
            THB_TYPE_J => 1000,
            THB_TYPE_10 => 1000,
            THB_TYPE_9 => 800,
            THB_TYPE_DUCK => 500,
            THB_TYPE_GREEN => 200,
            THB_TYPE_PURPLE => 200,
            THB_TYPE_RED => 200,
            THB_TYPE_YELLOW_G => 200,
            THB_TYPE_YELLOW_R => 200,
            THB_TYPE_YELLOW_A => 200,
            THB_TYPE_YELLOW_N => 200,
            THB_TYPE_YELLOW_D => 200,
            THB_TYPE_RED_M => 200,
            THB_TYPE_RED_A => 200,
            THB_TYPE_RED_J => 200,
            THB_TYPE_RED_O => 200,
            THB_TYPE_RED_R => 200,
            THB_TYPE_PURPLE_M => 200,
            THB_TYPE_PURPLE_I => 200,
            THB_TYPE_PURPLE_N => 200,
            THB_TYPE_PURPLE_O => 200,
            THB_TYPE_PURPLE_R => 200,
            THB_TYPE_GREEN_M => 200,
            THB_TYPE_GREEN_I1 => 200,
            THB_TYPE_GREEN_N => 200,
            THB_TYPE_GREEN_I2 => 200
        ],
        1 => [
            THB_TYPE_TELESCOPE => 500,
            THB_TYPE_FISH => 0,
            THB_TYPE_TURNTABLE => 1000,
            THB_TYPE_HOOK => 700,
            THB_TYPE_BOTTLE => 700,
            THB_TYPE_A => 900,
            THB_TYPE_K => 900,
            THB_TYPE_Q => 1000,
            THB_TYPE_J => 1000,
            THB_TYPE_10 => 1000,
            THB_TYPE_9 => 800,
            THB_TYPE_DUCK => 500,
            THB_TYPE_GREEN => 200,
            THB_TYPE_PURPLE => 200,
            THB_TYPE_RED => 200,
            THB_TYPE_YELLOW_G => 200,
            THB_TYPE_YELLOW_R => 200,
            THB_TYPE_YELLOW_A => 200,
            THB_TYPE_YELLOW_N => 200,
            THB_TYPE_YELLOW_D => 200,
            THB_TYPE_RED_M => 200,
            THB_TYPE_RED_A => 200,
            THB_TYPE_RED_J => 200,
            THB_TYPE_RED_O => 200,
            THB_TYPE_RED_R => 200,
            THB_TYPE_PURPLE_M => 200,
            THB_TYPE_PURPLE_I => 200,
            THB_TYPE_PURPLE_N => 200,
            THB_TYPE_PURPLE_O => 200,
            THB_TYPE_PURPLE_R => 200,
            THB_TYPE_GREEN_M => 200,
            THB_TYPE_GREEN_I1 => 200,
            THB_TYPE_GREEN_N => 200,
            THB_TYPE_GREEN_I2 => 200
        ],
        2 => [
            THB_TYPE_TELESCOPE => 500,
            THB_TYPE_FISH => 0,
            THB_TYPE_TURNTABLE => 1000,
            THB_TYPE_HOOK => 700,
            THB_TYPE_BOTTLE => 700,
            THB_TYPE_A => 900,
            THB_TYPE_K => 900,
            THB_TYPE_Q => 1000,
            THB_TYPE_J => 1000,
            THB_TYPE_10 => 1000,
            THB_TYPE_9 => 800,
            THB_TYPE_DUCK => 500,
            THB_TYPE_GREEN => 200,
            THB_TYPE_PURPLE => 200,
            THB_TYPE_RED => 200,
            THB_TYPE_YELLOW_G => 200,
            THB_TYPE_YELLOW_R => 200,
            THB_TYPE_YELLOW_A => 200,
            THB_TYPE_YELLOW_N => 200,
            THB_TYPE_YELLOW_D => 200,
            THB_TYPE_RED_M => 200,
            THB_TYPE_RED_A => 200,
            THB_TYPE_RED_J => 200,
            THB_TYPE_RED_O => 200,
            THB_TYPE_RED_R => 200,
            THB_TYPE_PURPLE_M => 200,
            THB_TYPE_PURPLE_I => 200,
            THB_TYPE_PURPLE_N => 200,
            THB_TYPE_PURPLE_O => 200,
            THB_TYPE_PURPLE_R => 200,
            THB_TYPE_GREEN_M => 200,
            THB_TYPE_GREEN_I1 => 200,
            THB_TYPE_GREEN_N => 200,
            THB_TYPE_GREEN_I2 => 200
        ],
        3 => [
            THB_TYPE_TELESCOPE => 500,
            THB_TYPE_FISH => 0,
            THB_TYPE_TURNTABLE => 1000,
            THB_TYPE_HOOK => 700,
            THB_TYPE_BOTTLE => 700,
            THB_TYPE_A => 900,
            THB_TYPE_K => 900,
            THB_TYPE_Q => 1000,
            THB_TYPE_J => 1000,
            THB_TYPE_10 => 1000,
            THB_TYPE_9 => 800,
            THB_TYPE_DUCK => 500,
            THB_TYPE_GREEN => 200,
            THB_TYPE_PURPLE => 200,
            THB_TYPE_RED => 200,
            THB_TYPE_YELLOW_G => 200,
            THB_TYPE_YELLOW_R => 200,
            THB_TYPE_YELLOW_A => 200,
            THB_TYPE_YELLOW_N => 200,
            THB_TYPE_YELLOW_D => 200,
            THB_TYPE_RED_M => 200,
            THB_TYPE_RED_A => 200,
            THB_TYPE_RED_J => 200,
            THB_TYPE_RED_O => 200,
            THB_TYPE_RED_R => 200,
            THB_TYPE_PURPLE_M => 200,
            THB_TYPE_PURPLE_I => 200,
            THB_TYPE_PURPLE_N => 200,
            THB_TYPE_PURPLE_O => 200,
            THB_TYPE_PURPLE_R => 200,
            THB_TYPE_GREEN_M => 200,
            THB_TYPE_GREEN_I1 => 200,
            THB_TYPE_GREEN_N => 200,
            THB_TYPE_GREEN_I2 => 200
        ],
        4 => [
            THB_TYPE_TELESCOPE => 500,
            THB_TYPE_FISH => 0,
            THB_TYPE_TURNTABLE => 1000,
            THB_TYPE_HOOK => 700,
            THB_TYPE_BOTTLE => 700,
            THB_TYPE_A => 900,
            THB_TYPE_K => 900,
            THB_TYPE_Q => 1000,
            THB_TYPE_J => 1000,
            THB_TYPE_10 => 1000,
            THB_TYPE_9 => 800,
            THB_TYPE_DUCK => 500,
            THB_TYPE_GREEN => 200,
            THB_TYPE_PURPLE => 200,
            THB_TYPE_RED => 200,
            THB_TYPE_YELLOW_G => 200,
            THB_TYPE_YELLOW_R => 200,
            THB_TYPE_YELLOW_A => 200,
            THB_TYPE_YELLOW_N => 200,
            THB_TYPE_YELLOW_D => 200,
            THB_TYPE_RED_M => 200,
            THB_TYPE_RED_A => 200,
            THB_TYPE_RED_J => 200,
            THB_TYPE_RED_O => 200,
            THB_TYPE_RED_R => 200,
            THB_TYPE_PURPLE_M => 200,
            THB_TYPE_PURPLE_I => 200,
            THB_TYPE_PURPLE_N => 200,
            THB_TYPE_PURPLE_O => 200,
            THB_TYPE_PURPLE_R => 200,
            THB_TYPE_GREEN_M => 200,
            THB_TYPE_GREEN_I1 => 200,
            THB_TYPE_GREEN_N => 200,
            THB_TYPE_GREEN_I2 => 200
        ],
    ];

    private $jackpotFree = [
        'jackpot' => 20, //中奖池概率
        'jackLeve' => [1 => 40, 2 => 25, 3 => 20, 4 => 15, 5 => 5], //中奖池等级对应概率
    ]; //中奖池

    private $freebox = [
        'green' => 20,
        'purple' => 20,
        'red' => 20
    ]; //中盒子的概率

    private $freeAgoMap = []; //中免费前的地图

    private $disRoom = 0; //解散房间标志

    private $ransport = [5, 8];

    private $countbet = 0;

    private $maxbeishu = 0;

    private $heigth = 4;

    private $green = 0;

    private $red = 0;

    private $purple = 0;

    private $freenum = [1 => 80, 2 => 80];

    private $jackpotinfo = [];
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
        foreach (THB_GEAR_ARR as $key => $val) {
            $this->countbet += $val;
        }
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
            'control' => 0,
            'freetype' => 0,
            'green' => [],
            'purple' => [],
            'red' => [],
            'yellow' => []
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
            'max_multiple' => THB_MAX_GEAR,
            'gearConfig' => THB_GEAR_ARR,
            'jackpotConfig' => THB_JACKPOT_REAL_SCORE,
            'boxType' => $this->PlayerInfo['freetype'],
            'mini' => $this->PlayerInfo['green'],
            'minor' => $this->PlayerInfo['purple'],
            'major' =>  $this->PlayerInfo['red'],
            'grand' => $this->PlayerInfo['yellow'],
            'jackpotinfo' => $this->jackpotinfo
        ];

        Logic::SendAll('Msg_THB_RoomInfo', $res, $this->roomRule['rid']);
    }

    /**
     * 初始地图
     *
     * @return void
     */
    private function InitMap($code = true)
    {
        $this->map = [];
        $map = THB_TYPR_ALL_BASE;
        $_map = [];
        if ($this->PlayerInfo['freetype'] > 0) {
            foreach ($this->coinProbabitity as $key => $val) {
                $_map[$key * THB_GEAR_ARR[$this->PlayerInfo['multiple']] / $this->roomRule['doublescore']] = $val;
            }

            if ($this->PlayerInfo['freetype'] == THB_FREE_TYPE_RED || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_GREEN_PURPLE_RED || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_PURPLE_RED || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_GREEN_RED) {
                $map = THB_TYPR_ALL;
                $_data = array_merge($this->PlayerInfo['purple'], $this->PlayerInfo['red'], $this->PlayerInfo['yellow'], $this->PlayerInfo['green']);
                foreach ($map as $key => $val) {
                    if (in_array($val, $_data)) {
                        unset($map[$key]);
                    }
                }
            }
        }

        // $data = [6, 9, 7, 10, 14, 14,  8, 1, 3, 3, 11, 5, 14,  12, 6, 10,  9,  6,  1, 8];
        for ($i = 0; $i < THB_WIDTH; $i++) {
            $temp = [];
            foreach ($map as $key => $val) {
                $temp[$val] = $this->controlMap[$i][$val];
            }
            // if ($this->PlayerInfo['freetype'] != THB_FREE_TYPE_GREEN && !empty($_map)) {
            //     $temp +=  $_map;
            // }

            if ($i == 0) {
                unset($temp[THB_TYPE_DUCK]);
            }
            for ($j = 0; $j < $this->heigth; $j++) {
                $type = $this->createType($temp);
                if ($type > THB_TYPE_DUCK && $type <= THB_TYPE_GREEN_I2) {
                    $map = array_diff($map, [$type]);
                    unset($temp[$type]);
                }

                if (
                    (($type == THB_TYPE_RED && ($this->PlayerInfo['freetype'] == THB_FREE_TYPE_RED || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_GREEN_RED || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_PURPLE_RED || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_GREEN_PURPLE_RED)) ||
                        ($type == THB_TYPE_PURPLE && ($this->PlayerInfo['freetype'] == THB_FREE_TYPE_PURPLE || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_GREEN_PURPLE) && rand(1, 100) > $this->freebox['purple']))   && !empty($_map)
                ) {
                    $rand = rand(1, array_sum($_map));
                    $_rand = 0;
                    $type = 0;
                    foreach ($_map as $key => $val) {
                        $_rand += $val;
                        if ($_rand >= $rand) {
                            $type = $key;
                            break;
                        }
                    }
                }
                // $type = array_shift($data);

                $this->map[$i][$j] = $type;
            }

            if ($this->heigth > 4) {
                if (in_array(THB_TYPE_DUCK, $this->map[$i])) {
                    if (rand(1, 100) < 50) {
                        $code = true;
                        for ($k = 0; $k < $this->heigth; $k++) {
                            $_type = $this->map[$i][$k];

                            if ($code) {
                                $this->map[$i][$k] = THB_TYPE_DUCK;
                            } else {
                                $this->map[$i][$k] = array_rand(THB_TYPE_ALL_SPE);
                            }

                            if ($_type == THB_TYPE_DUCK) {
                                $code = false;
                            }
                        }
                    } else {
                        $code = true;
                        for ($k = $this->heigth - 1; $k >= 0; $k--) {
                            $_type = $this->map[$i][$k];

                            if ($code) {
                                $this->map[$i][$k] = THB_TYPE_DUCK;
                            } else {
                                $this->map[$i][$k] = array_rand(THB_TYPE_ALL_SPE);
                            }

                            if ($_type == THB_TYPE_DUCK) {
                                $code = false;
                            }
                        }
                    }
                }
            }
        }

        if ($code) {
            $data = $this->checkdata();
            if ($this->PlayerInfo['ransport'] == 0 && $data['score'] + $data['beishu'] * THB_GEAR_ARR[$this->PlayerInfo['multiple']] / $this->roomRule['doublescore'] < THB_GEAR_ARR[$this->PlayerInfo['multiple']]) {
                $this->newMap();
            } elseif ($data['beishu'] + $data['score'] / (THB_GEAR_ARR[$this->PlayerInfo['multiple']] / $this->roomRule['doublescore']) > $this->maxbeishu) {
                $this->InitMap(false);
            }
        }
    }

    private function newMap()
    {
        $data = array_diff(THB_TYPR_ALL_BASE, [THB_TYPE_TELESCOPE, THB_TYPE_DUCK, THB_TYPE_GREEN, THB_TYPE_PURPLE, THB_TYPE_RED]);
        $line = THB_WIN_MAP[array_rand(THB_WIN_MAP)];
        $type = $data[array_rand($data)];

        $rand = rand(3, 5);
        foreach ($line as $key => $val) {
            $rand--;
            $this->map[$key][$val] = $type;
            if ($rand <= 0) {
                break;
            }
        }
    }

    private function createType($map)
    {
        $rand = rand(1, array_sum($map));
        $_rand = 0;
        $type = 0;
        foreach ($map as $key => $val) {
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
            case 'Msg_THB_Start':
                $this->Msg_THB_Start($message);
                break;
            case 'Msg_THB_Out':
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
    private function Msg_THB_Start($msg)
    {
        if (!isset($msg['data']['multiple']) || !is_int($msg['data']['multiple'])  || $msg['data']['multiple'] <= 0 || $msg['data']['multiple'] > THB_MAX_GEAR) {
            Logic::SendError($msg['uid'], 'Msg_THB_Start', '下注倍数错误');
            return;
        }
        $score = 0;
        if ($this->PlayerInfo['free'] <= 0) {
            $score = THB_GEAR_ARR[$msg['data']['multiple']];
            $this->PlayerInfo['multiple'] = $msg['data']['multiple'];
        }

        if ($this->PlayerInfo['gold'] < $score) {
            Logic::SendError($msg['uid'], 'Msg_THB_Start', '金币不足，请充值');
            return;
        }

        if ($score > 0) {
            $this->changScore(-$score);
        }

        $this->PlayerInfo['control'] =  DBInstance::GetLBControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);

        $this->GetControlTHB();

        if ($this->PlayerInfo['ransport'] < 0) {
            $this->PlayerInfo['ransport'] = rand($this->ransport[0], $this->ransport[1]);
        }

        $this->InitMap();
        $freetype = $this->PlayerInfo['freetype'];

        if ($score > 0 || empty($this->freeAgoMap)) {
            $this->freeAgoMap = $this->map;
        }

        $data = $this->checkdata();
        $this->jackpotinfo = $data['jackpotinfo'];
        $this->PlayerInfo['green'] = $data['greenarr'];
        $this->PlayerInfo['purple'] = $data['purplearr'];
        $this->PlayerInfo['red'] = $data['redarr'];
        $this->PlayerInfo['yellow'] = $data['yellowarr'];

        $boxType = 0;
        $freeover = 1;
        if ($score <= 0) {
            if ($data['green']) {
                $this->green++;
            }

            if ($data['purple']) {
                $this->purple++;
            }

            if ($data['red']) {
                $this->red++;
            }
        } else {
            if ($data['green'] && $data['purple'] && $data['red']) {
                $boxType = THB_FREE_TYPE_GREEN_PURPLE_RED;
            } elseif ($data['purple'] && $data['red']) {
                $boxType = THB_FREE_TYPE_PURPLE_RED;
            } elseif ($data['green'] && $data['red']) {
                $boxType = THB_FREE_TYPE_GREEN_RED;
            } elseif ($data['green'] && $data['purple']) {
                $boxType = THB_FREE_TYPE_GREEN_PURPLE;
            } elseif ($data['green']) {
                $boxType = THB_FREE_TYPE_GREEN;
            } elseif ($data['purple']) {
                $boxType = THB_FREE_TYPE_PURPLE;
            } elseif ($data['red']) {
                $boxType = THB_FREE_TYPE_RED;
            }
        }

        if ($score <= 0) {
            $this->PlayerInfo['free']--;
        }
        $this->PlayerInfo['free'] += $data['curfree'];

        if ($this->PlayerInfo['free'] <= 0) {
            $this->heigth = 4;
            $this->PlayerInfo['green'] = [];
            $this->PlayerInfo['purple'] = [];
            $this->PlayerInfo['red'] = [];
            $this->PlayerInfo['yellow'] = [];
            $this->PlayerInfo['freetype'] = 0;

            if ($data['curfree'] <= 0) {
                $freeover = 0;
            }

            if ($this->green > 0 && $this->purple > 0 && $this->red > 0) {
                $this->green--;
                $this->purple--;
                $this->red--;
                $boxType = THB_FREE_TYPE_GREEN_PURPLE_RED;
            } elseif ($this->purple > 0 && $this->red > 0) {
                $this->purple--;
                $this->red--;
                $boxType = THB_FREE_TYPE_PURPLE_RED;
            } elseif ($this->green > 0 && $this->red > 0) {
                $this->green--;
                $this->red--;
                $boxType = THB_FREE_TYPE_GREEN_RED;
            } elseif ($this->green > 0 && $this->purple > 0) {
                $this->green--;
                $this->purple--;
                $boxType = THB_FREE_TYPE_GREEN_PURPLE;
            } elseif ($this->green > 0) {
                $this->green--;
                $boxType = THB_FREE_TYPE_GREEN;
            } elseif ($this->purple > 0) {
                $this->purple--;
                $boxType = THB_FREE_TYPE_PURPLE;
            } elseif ($this->red > 0) {
                $this->red--;
                $boxType = THB_FREE_TYPE_RED;
            }
        }

        if (isset(THB_FREE_NUM[$boxType])) {
            $data['curfree'] += THB_FREE_NUM[$boxType];
            $this->PlayerInfo['free'] += THB_FREE_NUM[$boxType];
        }

        $gamescore = intval($data['beishu'] * (THB_GEAR_ARR[$msg['data']['multiple']] / $this->roomRule['doublescore'])) + $data['score'];
        $jackpot = $data['jackpot'];
        $data['score'] = $gamescore;

        if ($data['score'] > 0 || $data['jackpot'] > 0) {
            $this->changScore($data['score'] + $data['jackpot']);
        }

        $data['conscore'] = $score;
        $data['gold'] = $this->PlayerInfo['gold'];
        $data['free'] = $this->PlayerInfo['free'];
        $data['map'] = $this->map;
        $data['boxType'] = $boxType;
        $data['freeover'] = $freeover;
        if ($boxType > 0) {
            $this->PlayerInfo['freetype'] = $boxType;
        }

        $data['type'] = $this->typeScore($data['score']);
        unset($data['yellowarr']);
        unset($data['redarr']);
        unset($data['purplearr']);
        unset($data['greenarr']);
        unset($data['win']);
        Logic::SendAll('Msg_THB_Start', $data, $this->roomRule['rid']);

        $jackpotarr =  $this->SaveJackpot(0, 1);
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
        }

        if (abs($this->PlayerInfo['control'])  != 2) {
            $profit = $gamescore + $jackpotscore - $score + $jackpot;
            Logic::InsertProfit($this->roomRule['level'], $profit);
        }

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($msg['uid'], $data['score'] - $score);
        }

        if ($score > 0) {
            $this->PlayerInfo['freeData'] = [];
            $this->PlayerInfo['freeScore'] = 0;
        }

        if ($score <= 0 || $this->PlayerInfo['free'] > 0) {
            $this->PlayerInfo['freeData'][] = $data;
            $this->PlayerInfo['freeScore'] += $data['score'] + $data['jackpot'];
        }

        if ($boxType == THB_FREE_TYPE_GREEN || $boxType == THB_FREE_TYPE_GREEN_PURPLE || $boxType == THB_FREE_TYPE_GREEN_RED || $boxType == THB_FREE_TYPE_GREEN_PURPLE_RED) {
            $this->heigth = 6;
        }

        if (!empty($data['jackpotleve'])) {
            if (in_array(THB_JACKPOT_MINI, $data['jackpotleve'])) {
                $this->PlayerInfo['green'] = [];
            }
            if (in_array(THB_JACKPOT_MIN, $data['jackpotleve'])) {
                $this->PlayerInfo['purple'] = [];
            }
            if (in_array(THB_JACKPOT_MAIOR, $data['jackpotleve'])) {
                $this->PlayerInfo['red'] = [];
            }
            if (in_array(THB_JACKPOT_SUPRE, $data['jackpotleve'])) {
                $this->PlayerInfo['yellow'] = [];
            }
        }

        if ($this->PlayerInfo['free'] <= 0) {
            $this->saveData($freetype);
        }

        if ($this->disRoom == 1 && $this->PlayerInfo['free'] <= 0) {
            $this->All_RECV(['event' => 'Msg_THB_Out', 'uid' => $msg['uid']]);
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
        $jackpotleve = []; //奖池的等级
        $green = false;
        $purple = false;
        $red = false;
        $checkmap = THB_WIN_MAP;
        $score = 0;
        $greenarr = $this->PlayerInfo['green'];
        $purplearr = $this->PlayerInfo['purple'];
        $redarr = $this->PlayerInfo['red'];
        $yellowarr = $this->PlayerInfo['yellow'];
        $freeinfo = [];
        // if ($this->PlayerInfo['freetype'] == 0) {
        //     $green = true;
        //     $purple = true;
        //     $red = true;
        // }
        if ($this->heigth > 4) {
            $checkmap = THB_WIN_FREE_MAP;
        }

        foreach ($checkmap as $key => $val) {
            $num = 0;
            $type = 0;
            $_curMap = [];
            foreach ($val as $key1 => $val1) {
                $_type = $this->map[$key1][$val1];
                if ($type == 0) {
                    $type = $_type;
                    if ($type > THB_TYPE_DUCK) {
                        break;
                    }
                }

                if ($this->heigth > 4) {
                    if ($this->map[$key1][0] == THB_TYPE_DUCK || $this->map[$key1][$this->heigth - 1] == THB_TYPE_DUCK) {
                        $_type = THB_TYPE_DUCK;
                    }
                }

                if ($type == $_type || $_type ==  THB_TYPE_DUCK) {
                    if (!in_array([$key1, $val1], $curMap)) {
                        $_curMap[] = [$key1, $val1];
                    }
                    $num++;
                } else {
                    break;
                }
            }

            if (isset(THB_WIN_BEISHU[$type][$num])) {
                $win[] = [
                    'type' => $type,
                    'multiple' => THB_WIN_BEISHU[$type][$num],
                ];
                $beishu += THB_WIN_BEISHU[$type][$num];
                $curMap = array_merge($curMap, $_curMap);
            }
        }

        foreach ($this->map as $key => $val) {
            foreach ($val as $key1 => $val1) {
                if ($val1 <= THB_TYPE_DUCK) {
                    continue;
                }

                if ($val1 == THB_TYPE_GREEN && $this->green <= 0) {
                    $rand = rand(1, 100);
                    if ($rand <= $this->freebox['green']) {
                        $green = true;
                    }
                }

                if ($val1 == THB_TYPE_PURPLE || $val1 > THB_TYPE_GREEN_I2) {
                    $rand = rand(1, 100);

                    if ($val1 == THB_TYPE_PURPLE && ($this->PlayerInfo['freetype'] == THB_FREE_TYPE_PURPLE || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_GREEN_PURPLE)) {
                        $rand = rand(1, array_sum($this->freenum));
                        $_rand = 0;
                        foreach ($this->freenum  as $key2 => $val2) {
                            $_rand += $val2;
                            if ($_rand >= $rand) {
                                $free += $key2;

                                $freeinfo[] = $key2;
                                break;
                            }
                        }
                    }

                    if ($rand <= $this->freebox['purple'] && $this->purple <= 0 && $this->PlayerInfo['freetype'] != THB_FREE_TYPE_RED && $this->PlayerInfo['freetype'] != THB_FREE_TYPE_GREEN_RED && $this->PlayerInfo['freetype'] != THB_FREE_TYPE_PURPLE_RED  && $this->PlayerInfo['freetype'] != THB_FREE_TYPE_GREEN_PURPLE_RED) {
                        $purple = true;
                    }
                }

                if ($val1 == THB_TYPE_RED && $this->red <= 0) {
                    $rand = rand(1, 100);

                    if ($rand <= $this->freebox['red']) {
                        $red = true;
                    }
                }

                if ($val1 >= THB_TYPE_YELLOW_G && $val1 <= THB_TYPE_YELLOW_D) {
                    if (!in_array($val, $yellowarr)) {
                        $yellowarr[] = $val1;
                    }
                }

                if ($val1 >= THB_TYPE_RED_M && $val1 <= THB_TYPE_RED_R) {
                    if (!in_array($val, $redarr)) {
                        $redarr[] = $val1;
                    }
                }

                if ($val1 >= THB_TYPE_PURPLE_M && $val1 <= THB_TYPE_PURPLE_R) {
                    if (!in_array($val, $purplearr)) {
                        $purplearr[] = $val1;
                    }
                }

                if ($val1 >= THB_TYPE_GREEN_M && $val1 <= THB_TYPE_GREEN_I2) {
                    if (!in_array($val, $greenarr)) {
                        $greenarr[] = $val1;
                    }
                }

                if ($val1 > THB_TYPE_GREEN_I2) {
                    $score += $val1;
                }
            }
        }

        if ($this->PlayerInfo['freetype'] == THB_FREE_TYPE_PURPLE || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_PURPLE_RED || $this->PlayerInfo['freetype'] == THB_FREE_TYPE_GREEN_PURPLE_RED) {
            $beishu *= FREE_PURPLE_BOX;
        }

        $jackpot = 0;
        $jackpotarr = $this->SaveJackpot(0, 1);
        $jackpotscore =  intval(THB_GEAR_ARR[$this->PlayerInfo['multiple']] / $this->countbet * $jackpotarr['jackpot']);

        $jackpotinfo = [];
        foreach (THB_JACKPOT_REAL_SCORE as $key => $val) {
            $jackpotinfo[$key] = intval($jackpotscore * THB_JACKPOT_REAL_SCORE[$key] / 100);
        }

        if (count($greenarr) >= 4) {
            $jackpotleve[] = THB_JACKPOT_MINI;
            $jackpot += $jackpotinfo[THB_JACKPOT_MINI];
        }

        if (count($purplearr) >= 5) {
            $jackpotleve[] = THB_JACKPOT_MIN;
            $jackpot += $jackpotinfo[THB_JACKPOT_MIN];
        }
        if (count($redarr) >= 5) {
            $jackpotleve[] = THB_JACKPOT_MAIOR;
            $jackpot += $jackpotinfo[THB_JACKPOT_MAIOR];
        }

        if (count($yellowarr) >= 5) {
            $jackpotleve[] = THB_JACKPOT_SUPRE;
            $jackpot += $jackpotinfo[THB_JACKPOT_SUPRE];
        }
        $data = [
            'beishu' => $beishu,
            'win' => $win,
            'curMap' => $curMap,
            'curfree' => $free,
            'jackpotleve' => $jackpotleve,
            'red' => $red,
            'purple' => $purple,
            'green' => $green,
            'score' => $score,
            'jackpot' => $jackpot,
            'freeinfo' => $freeinfo,
            'jackpotinfo' => $jackpotinfo,
            'greenarr' => $greenarr,
            'purplearr' => $purplearr,
            'redarr' => $redarr,
            'yellowarr' => $yellowarr
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
        $beishu = $score / (THB_GEAR_ARR[$this->PlayerInfo['multiple']]);
        foreach (THB_TYPE_SCORE as $key => $val) {
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
    private function saveData($type)
    {
        $beishu =  $this->PlayerInfo['freeScore'] / (THB_GEAR_ARR[$this->PlayerInfo['multiple']]);

        if ($this->PlayerInfo['freeScore'] < THB_SAVE_DATA['score'] || $beishu < THB_SAVE_DATA['beishu']) {
            return;
        }

        $data = $this->PlayerInfo['freeData'];
        $_data = [];
        $_data['uid'] =  $this->uid;
        $_data['doublescore'] =  $this->roomRule['doublescore'];
        $_data['curgrade'] = $this->PlayerInfo['multiple'];
        $_data['map'] = $this->freeAgoMap;
        $_data['data'] = $data;

        $res = [
            'type' => $type,
            'nickname' => $this->PlayerInfo['nickname'],
            'gtype' => $this->roomRule['gtype'],
            'level' => $this->roomRule['level'],
            'vals' => json_encode($_data),
            'score' => $this->PlayerInfo['freeScore'],
            'created' => MyTools::GET_NOW(),
            'playnum' => 0
        ];

        DBInstance::SaveData('game_back', $res);

        if ($this->PlayerInfo['freeScore'] >= 5000000 && $beishu >= 20) {
            Logic::HorseLamp($this->uid, $this->PlayerInfo['freeScore'], $beishu);
        }
    }
    /**
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        if ($this->PlayerInfo['free'] > 0) {
            Logic::SendError($this->uid, 'Msg_THB_Out', '正在免费旋转中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);

        Logic::SendAll('Msg_THB_Out', ['gold' => $gold], $this->roomRule['rid']);

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

    private function GetControlTHB()
    {
        $data = DBInstance::GetTableOneWord('control_thb', 'vals', ['level' => $this->PlayerInfo['control']]);

        $data = json_decode($data, true);
        if (isset($data['list'])) {
            $this->controlMap = $data['list'];
        }

        if (isset($data['jackpotInfo'])) {
            if (isset($data['jackpotInfo']['jackpot'])) {
                $this->jackpotFree['jackpot'] = $data['jackpotInfo']['jackpot'];
            }

            if (isset($data['jackpotInfo']['jackLeve'])) {
                $this->jackpotFree['jackLeve'] = $data['jackpotInfo']['jackLeve'];
            }
        }

        if (isset($data['ransport']) && is_array($data['ransport'])) {
            $this->ransport = $data['ransport'];
        }

        if (isset($data['beishu'])) {
            $this->maxbeishu = $data['beishu'];
        }

        if (isset($data['freenum'])) {
            if (isset($data['freenum'][1])) {
                $this->freenum[1] = $data['freenum'][1];
            }
            if (isset($data['freenum'][2])) {
                $this->freenum[2] = $data['freenum'][2];
            }
        }
        if (isset($data['freebox'])) {
            if (isset($data['freebox']['green'])) {
                $this->freebox['green'] = $data['freebox']['green'];
            }
            if (isset($data['freebox']['purple'])) {
                $this->freebox['purple'] = $data['freebox']['purple'];
            }
            if (isset($data['freebox']['red'])) {
                $this->freebox['red'] = $data['freebox']['red'];
            }
        }

        if (isset($data['coinProbabitity'])) {
            $this->coinProbabitity = $data['coinProbabitity'];
        }

        // Logic::SendAll('Msg_Game_Control', [
        //     'beishu' => $this->maxbeishu,
        //     'ransport' => $this->ransport,
        //     'jackpotFree' => $this->jackpotFree,
        //     'list' => $this->controlMap,
        //     'freenum' => $this->freenum,
        //     'control' => $this->PlayerInfo['control'],
        //     'freebox' => $this->freebox,
        //     'coinProbabitity' => $this->coinProbabitity
        // ], $this->roomRule['rid']);
    }
}

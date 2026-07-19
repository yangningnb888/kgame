<?php

$arrinfo = [
    1 => [
        1 =>     [710, 710, 620, 440, 260],
        2 =>     [720, 720, 640, 480, 320],
        3 =>     [730, 730, 660, 520, 380],
        4 =>     [740, 740, 680, 560, 440],
        5 =>     [750, 750, 700, 600, 500],
        6 =>     [760, 760, 720, 640, 560],
        7 =>     [770, 770, 740, 680, 620],
        8 =>     [780, 780, 760, 720, 680],
        9 =>     [790, 790, 780, 760, 740],
        10 =>     [800, 800, 800, 800, 800],
        11 =>     [1000, 1000, 1000, 1000, 1000],
        12 =>     [200, 200, 200, 200, 200],
        13 =>     [700, 700, 700, 700, 700],
        14 =>     [400, 400, 400, 400, 400]
    ],
    0 => [
        1 =>     [0, 0, 0, 0, 0],
        2 =>     [720, 720, 640, 480, 320],
        3 =>     [730, 730, 660, 520, 380],
        4 =>     [740, 740, 680, 560, 440],
        5 =>     [750, 750, 700, 600, 500],
        6 =>     [760, 760, 720, 640, 560],
        7 =>     [770, 770, 740, 680, 620],
        8 =>     [780, 780, 760, 720, 680],
        9 =>     [790, 790, 780, 760, 740],
        10 =>     [800, 800, 800, 800, 800],
        11 =>     [500, 500, 500, 500, 500],
        12 =>     [200, 200, 200, 200, 200],
        13 =>     [700, 700, 700, 700, 700],
        14 =>     [400, 400, 400, 400, 400]
    ],
    -1 => [
        1 =>     [710, 710, 620, 440, 260],
        2 =>     [720, 720, 640, 480, 320],
        3 =>     [730, 730, 660, 520, 380],
        4 =>     [740, 740, 680, 560, 440],
        5 =>     [750, 750, 700, 600, 500],
        6 =>     [760, 760, 720, 640, 560],
        7 =>     [770, 770, 740, 680, 620],
        8 =>     [780, 780, 760, 720, 680],
        9 =>     [790, 790, 780, 760, 740],
        10 =>     [800, 800, 800, 800, 800],
        11 =>     [0, 0, 0, 0, 0],
        12 =>     [200, 200, 200, 200, 200],
        13 =>     [700, 700, 700, 700, 700],
        14 =>     [400, 400, 400, 400, 400]
    ]
];
var_dump(json_encode([
    1 =>     [710, 710, 620, 440, 260],
    2 =>     [720, 720, 640, 480, 320],
    3 =>     [730, 730, 660, 520, 380],
    4 =>     [740, 740, 680, 560, 440],
    5 =>     [750, 750, 700, 600, 500],
    6 =>     [760, 760, 720, 640, 560],
    7 =>     [770, 770, 740, 680, 620],
    8 =>     [780, 780, 760, 720, 680],
    9 =>     [790, 790, 780, 760, 740],
    10 =>     [800, 800, 800, 800, 800],
    11 =>     [1000, 1000, 1000, 1000, 1000],
    12 =>     [200, 200, 200, 200, 200],
    13 =>     [700, 700, 700, 700, 700],
    14 =>     [400, 400, 400, 400, 400]
]));
var_dump('================================');
var_dump(json_encode([
    1 =>     [0, 0, 0, 0, 0],
    2 =>     [720, 720, 640, 480, 320],
    3 =>     [730, 730, 660, 520, 380],
    4 =>     [740, 740, 680, 560, 440],
    5 =>     [750, 750, 700, 600, 500],
    6 =>     [760, 760, 720, 640, 560],
    7 =>     [770, 770, 740, 680, 620],
    8 =>     [780, 780, 760, 720, 680],
    9 =>     [790, 790, 780, 760, 740],
    10 =>     [800, 800, 800, 800, 800],
    11 =>     [500, 500, 500, 500, 500],
    12 =>     [200, 200, 200, 200, 200],
    13 =>     [700, 700, 700, 700, 700],
    14 =>     [400, 400, 400, 400, 400]
]));
var_dump('================================');

var_dump(json_encode([
    1 =>     [710, 710, 620, 440, 260],
    2 =>     [720, 720, 640, 480, 320],
    3 =>     [730, 730, 660, 520, 380],
    4 =>     [740, 740, 680, 560, 440],
    5 =>     [750, 750, 700, 600, 500],
    6 =>     [760, 760, 720, 640, 560],
    7 =>     [770, 770, 740, 680, 620],
    8 =>     [780, 780, 760, 720, 680],
    9 =>     [790, 790, 780, 760, 740],
    10 =>     [800, 800, 800, 800, 800],
    11 =>     [0, 0, 0, 0, 0],
    12 =>     [200, 200, 200, 200, 200],
    13 =>     [700, 700, 700, 700, 700],
    14 =>     [400, 400, 400, 400, 400]
]));

define('MAP_HIGH', 3); //地图高
define('MAP_WIDE', 5); //地图宽

define('LITCHI', 1); //荔枝
define('ORANGE', 2); //橘子
define('MANGO', 3); //芒果
define('WATERMELON', 4); //西瓜
define('PINEAPPLE', 5); //菠萝
define('APPLE', 6); //苹果
define('CHERRY', 7); //樱桃
define('BANANA', 8); //香蕉
define('BELL', 9); //铃铛
define('GRAPE', 10); //葡萄
define('BAR', 11); //bar
define('SENVE', 12); //七
define('DIAMONDS', 13); //钻石
define('BOX', 14); //宝箱

define('DISCHARGE_BEISHU', 10); //放的倍数

define('MAP_ALL', [LITCHI, ORANGE, MANGO, WATERMELON, PINEAPPLE, BAR, SENVE, DIAMONDS, APPLE, CHERRY, BANANA, BELL, GRAPE, BOX]); //地图所以的图案
define('ARR_MAP', [
    1 => [[1, 0], [1, 1], [1, 2], [1, 3], [1, 4]],
    2 => [[2, 0], [2, 1], [2, 2], [2, 3], [2, 4]],
    3 => [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4]],
    4 => [[2, 0], [1, 1], [0, 2], [1, 3], [2, 4]],
    5 => [[0, 0], [1, 1], [2, 2], [1, 3], [0, 4]],
    6 => [[1, 0], [2, 1], [2, 2], [2, 3], [1, 4]],
    7 => [[1, 0], [0, 1], [0, 2], [0, 3], [1, 4]],
    8 => [[2, 0], [2, 1], [1, 2], [0, 3], [0, 4]],
    9 => [[0, 0], [0, 1], [1, 2], [2, 3], [2, 4]],
]); //中奖地图

define('WIN_CONDITION', [
    LITCHI => [
        3 => 50, 4 => 200, 5 => 2000
    ],
    ORANGE => [
        3 => 20, 4 => 50, 5 => 300
    ],
    MANGO => [
        3 => 15, 4 => 25, 5 => 250
    ],
    WATERMELON => [
        3 => 10, 4 => 20, 5 => 200
    ],
    APPLE => [
        3 => 8, 4 => 20, 5 => 150
    ],

    CHERRY => [
        3 => 5, 4 => 40, 5 => 90
    ],
    GRAPE => [
        3 => 5, 4 => 40, 5 => 90
    ],
    BELL => [
        3 => 8, 4 => 350, 5 => 85
    ],
    BANANA => [
        3 => 6, 4 => 30, 5 => 80
    ],
    PINEAPPLE => [
        3 => 5, 4 => 15, 5 => 75

    ],
    BAR => [
        2 => 5, 3 => 100, 4 => 900, 5 => 6000
    ],
    SENVE => [
        3 => 1000, 4 => 3000, 5 => 5000
    ],

    DIAMONDS => [
        3 => 5, 4 => 10, 5 => 20
    ],

    BOX => [
        3 => 0.1, 4 => 0.3, 5 => 0.5
    ],
]);

$run = new text();

$score = 0;
$winscore = 0;
$free = 0; //免费把数
$num = 0; //赢得把数
$stock = 0; //库存
$pump = 0.2; //库存抽成
$fail = rand(3, 6); //几把中
$jackpot = 0; //奖池
$control = -999; //场控值
for ($i = 0; $i < 30000; $i++) {
    $score += 5 * 9 * 1000;
    $running = $run->InitMap($stock, $fail, $jackpot, $control);
    $winscore += $running['allScore'];
    $free += $running['res']['curfree'];
    if (!empty($running['res']['win'])) {
        $num++;
    }
    // var_dump(json_encode($running));

    $jackpot -= $running['res']['jackpot'];

    if (($running['allScore'] - 5 * 9 * 1000) < 0) {
        $stock += 5 * 9 * 1000 * $pump;
        $fail--;
        $jackpot += 5 * 9 * 1000 * 0.1;
    } else {
        $fail = rand(3, 6);
        $stock -= $running['allScore'];
    }
}

$profit = $score - $winscore;
var_dump("======库存数====：：$stock==========");

$_free = $free;
while ($free > 0) {
    $free--;
    $running = $run->InitMap($stock, $fail, $jackpot, $control);
    $winscore += $running['allScore'];
    $free += $running['res']['curfree'];
    $_free += $running['res']['curfree'];
    if (!empty($running['res']['win'])) {
        $num++;
    }

    if ($free <= 0) {
        break;
    }
    if (($running['allScore'] - 5 * 9 * 1000) < 0) {
        $stock += 5 * 9 * 1000 * $pump;
        $fail--;
        $jackpot += 5 * 9 * 1000 * 0.1;
    } else {
        $fail = rand(3, 6);
        $stock -= $running['allScore'];
    }
}

$profit = $score - $winscore;
$data = [
    'freeNum' => $_free,
    'winNum' => $num,
    'winScore' => $winscore,
    'betScore' => $score,
    'winAllScore' => $profit,
    'RTP' => $winscore / $score * 100,
    'stock' => $stock,
    'jackpot' => $jackpot
];

var_dump(json_encode($data));

class text
{
    private $arrinfo = [
        1 => [
            1 =>     [710, 710, 620, 440, 260],
            2 =>     [720, 720, 640, 480, 320],
            3 =>     [730, 730, 660, 520, 380],
            4 =>     [740, 740, 680, 560, 440],
            5 =>     [750, 750, 700, 600, 500],
            6 =>     [760, 760, 720, 640, 560],
            7 =>     [770, 770, 740, 680, 620],
            8 =>     [780, 780, 760, 720, 680],
            9 =>     [790, 790, 780, 760, 740],
            10 =>     [800, 800, 800, 800, 800],
            11 =>     [1000, 1000, 1000, 1000, 1000],
            12 =>     [200, 200, 200, 200, 200],
            13 =>     [700, 700, 700, 700, 700],
            14 =>     [400, 400, 400, 400, 400]
        ],
        0 => [
            1 =>     [0, 0, 0, 0, 0],
            2 =>     [720, 720, 640, 480, 320],
            3 =>     [730, 730, 660, 520, 380],
            4 =>     [740, 740, 680, 560, 440],
            5 =>     [750, 750, 700, 600, 500],
            6 =>     [760, 760, 720, 640, 560],
            7 =>     [770, 770, 740, 680, 620],
            8 =>     [780, 780, 760, 720, 680],
            9 =>     [790, 790, 780, 760, 740],
            10 =>     [800, 800, 800, 800, 800],
            11 =>     [500, 500, 500, 500, 500],
            12 =>     [200, 200, 200, 200, 200],
            13 =>     [700, 700, 700, 700, 700],
            14 =>     [400, 400, 400, 400, 400]
        ],
        -1 => [
            1 =>     [710, 710, 620, 440, 260],
            2 =>     [720, 720, 640, 480, 320],
            3 =>     [730, 730, 660, 520, 380],
            4 =>     [740, 740, 680, 560, 440],
            5 =>     [750, 750, 700, 600, 500],
            6 =>     [760, 760, 720, 640, 560],
            7 =>     [770, 770, 740, 680, 620],
            8 =>     [780, 780, 760, 720, 680],
            9 =>     [790, 790, 780, 760, 740],
            10 =>     [800, 800, 800, 800, 800],
            11 =>     [0, 0, 0, 0, 0],
            12 =>     [200, 200, 200, 200, 200],
            13 =>     [700, 700, 700, 700, 700],
            14 =>     [400, 400, 400, 400, 400]
        ]
    ];

    private $arr = [];

    private $remap = [];
    private $map = [];

    private $doublescore = 1000;
    private $free = 0;

    public function InitMap($stock, $fail, $jackpot, $control)
    {
        $this->map = [];
        if (!isset($this->arrinfo[$control])) {
            if ($stock > 450000 * DISCHARGE_BEISHU) {
                $this->arr = $this->arrinfo[1];
            } else {
                $this->arr = $this->arrinfo[0];
            }
        } else {
            $this->arr = $this->arrinfo[$control];
        }

        $this->remap = $this->dealcontrol();

        for ($i = 0; $i < MAP_HIGH; $i++) {
            for ($j = 0; $j < MAP_WIDE; $j++) {
                $type = $this->gradeMap($j);
                $this->map[$i][] = $type;
            }
        }

        $data = ['line' => 9, 'multiple' => 5];
        $res = $this->calculationWin($data);
        if ($res['allScore'] - 450000 <= 0 && $fail <= 0) {
            $this->newMap();
            $res = $this->calculationWin($data);
        }

        return $res;
    }

    private function newMap()
    {
        $rands = array_keys(ARR_MAP);

        for ($i = 0; $i < 10; $i++) {
            if (empty($rands)) {
                $arr = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12];
                $type = array_rand($arr);
                $type = $arr[$type];
                break;
            }
            $_rand = array_rand($rands);
            $rand = $rands[$_rand];
            $type = $this->map[ARR_MAP[$rand][0][0]][ARR_MAP[$rand][0][1]];
            if ($type != DIAMONDS && $type != BOX && $type != BAR) {
                break;
            } else {
                unset($rands[$_rand]);
            }
        }

        $arr = [];

        foreach (WIN_CONDITION[$type] as $key => $val) {
            if ($val < 9) {
                continue;
            }
            $arr[$key] = 1;
        }

        $rands = array_rand($arr);

        foreach (ARR_MAP[$rand] as $key => $val) {
            $rands--;
            $this->map[$val[0]][$val[1]] = $type;
            if ($rands <= 0) {
                break;
            }
        }
    }

    public function dealcontrol()
    {
        $arr = [];
        foreach ($this->arr as $key => $val) {
            foreach ($val as $key1 => $val1) {
                $arr[$key1][$key] = $val1;
            }
        }
        return $arr;
    }

    private function gradeMap($row)
    {
        $total = array_sum($this->remap[$row]);
        $rand = rand(1, $total);
        $num = 0;
        $res = -1;
        foreach ($this->remap[$row] as $key => $val) {
            $num += $val;
            if ($num >= $rand) {
                $res = $key;
                break;
            }
        }
        return $res;
    }
    private function calculationWin($data)
    {
        $res = [];
        $score = 0;
        $jackpot = 0; //从奖池中的奖金
        $curfree = 0; //当时免费次数
        $beishu = 0;
        $allScore = 0;
        foreach (ARR_MAP as $key => $val) {
            if ($key > $data['line']) {
                continue;
            }

            $graphical = 0;
            $_res = 1;
            foreach ($val as $key1 => $val1) {
                if ($graphical == 0) {
                    $graphical = $this->map[$val1[0]][$val1[1]];
                    continue;
                }

                if (($this->map[$val1[0]][$val1[1]] == $graphical || ($graphical != SENVE &&  $graphical != DIAMONDS &&  $this->map[$val1[0]][$val1[1]] == BAR))) {
                    $_res++;
                } else {
                    break;
                }
            }

            if ($_res >= min(array_keys(WIN_CONDITION[$graphical]))) {
                $scoreArr = $this->score($_res, $graphical, $data['multiple'], $jackpot);
                $score += $scoreArr['score'];
                $jackpot += $scoreArr['jackpot'];
                $curfree += $scoreArr['curfree'];
                $multiple = 0;
                if ($graphical < DIAMONDS) {
                    $multiple = WIN_CONDITION[$graphical][$_res];
                }

                $beishu += $multiple;
                $allScore += $scoreArr['allScore'];
                $res['win'][] = ['num' => $_res, 'type' => $graphical, 'line' => $key, 'multiple' => $multiple];
            }
        }
        if (empty($res)) {
            $res['win'] = [];
        }

        $res['score'] = $score;
        $res['jackpot'] = $jackpot;
        $res['curfree'] = $curfree;
        return ['res' => $res, 'beishu' => $beishu, 'allScore' => $allScore];
    }
    /**
     * 分数计算
     *
     * @param [type] $num 中了几个
     * @param [type] $type 中的类型
     * 
     * @return int
     */
    private function score($num, $type, $multiple, $jackpots)
    {
        $score = 0;
        $curfree = 0;
        $jackpot = 0;
        if ($type == DIAMONDS) {
            $this->free += WIN_CONDITION[$type][$num];
            $curfree =  WIN_CONDITION[$type][$num];
        } elseif ($type == BOX) {
            $jackpot = intval(WIN_CONDITION[$type][$num] * $jackpots);
        } else {
            $score =  WIN_CONDITION[$type][$num];
        }
        $score *= $multiple  * $this->doublescore;

        $allScore = $score  + $jackpot;

        return ['score' => $score, 'jackpot' => $jackpot, 'curfree' => $curfree, 'allScore' => $allScore];
    }
}

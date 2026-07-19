<?php
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
$run = new control();

$score = 0;
$winscore = 0;
$free = 0;
$num = 0;
for ($i = 0; $i < 100000; $i++) {
    $score += 5 * 9 * 1000;
    $running = $run->InitMap();
    // var_dump(json_encode($running));
    $winscore += $running['allScore'];
    $free += $running['res']['curfree'];
    if (!empty($running['res']['win'])) {
        $num++;
    }
}
$profit = $score - $winscore;

// var_dump("========1==赢得把数==$num====赢分：：$winscore== 输分：：$score======" . $free . '========赢得分数==：：' . $profit);
while ($free > 0) {
    $free--;
    $running = $run->InitMap();
    $winscore += $running['allScore'];
    $free += $running['res']['curfree'];
    if (!empty($running['res']['win'])) {
        $num++;
    }
    if ($free <= 0) {
        break;
    }
}

$profit = $score - $winscore;
var_dump("=2==赢得把数==$num=============赢分：：$winscore== 输分：：$score======" . $free . '========赢得分数==：：' .  $profit);

class control
{
    private $arr = [
        1 => [20, 10, 20],
        2 => [50, 50, 50],
        3 => [66, 67, 65],
        4 => [150,80, 150],
        5 => [300, 150, 300],
        6 => [187, 100, 187],
        7 => [300, 150, 300],
        8 => [250, 250, 250],
        9 => [187, 100, 187],
        10 => [300, 80, 300],
        11 => [300, 80, 150],
        12 => [1, 1, 1],
        13 => [150, 150, 75],
        14 => [1, 1, 1]
    ];


    private $remap = [];
    private $map = [];

    private $doublescore = 1000;
    private $free = 0;

    public function InitMap()
    {
        $this->map = [];
        
        $this->remap = $this->dealcontrol();
        for ($i = 0; $i < MAP_HIGH; $i++) {
            for ($j = 0; $j < MAP_WIDE; $j++) {
                $type = $this->gradeMap($i);
                $this->map[$i][] = $type;
            }
        }
        // var_dump(json_encode($this->map));

        $data = ['line' => 9, 'multiple' => 5];
        $res = $this->calculationWin($data);
        return $res;
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
        // var_dump('============随机值====' . $rand);
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
            $_free = 0;
            $code = true;
            foreach ($val as $key1 => $val1) {
                if ($graphical == 0) {
                    $graphical = $this->map[$val1[0]][$val1[1]];
                    continue;
                }

                if ($this->map[$val1[0]][$val1[1]]  == DIAMONDS) {
                    $_free++;
                }

                if ($code && ($this->map[$val1[0]][$val1[1]] == $graphical || ($graphical != SENVE &&  $graphical != DIAMONDS &&  $this->map[$val1[0]][$val1[1]] == BAR))) {
                    $_res++;
                } else {
                    $code = false;
                }
            }

            if ($_res >= min(array_keys(WIN_CONDITION[$graphical]))) {
                $scoreArr = $this->score($_res, $graphical, $data['multiple']);
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

            if ($_free >= min(array_keys(WIN_CONDITION[DIAMONDS]))) {
                $scoreArr = $this->score($_free, DIAMONDS, $data['multiple']);
                $score += $scoreArr['score'];
                $jackpot += $scoreArr['jackpot'];
                $curfree += $scoreArr['curfree'];
                $multiple = 0;
                $beishu += $multiple;
                $allScore += $scoreArr['allScore'];
                $res['win'][] = ['num' => $_free, 'type' => DIAMONDS, 'line' => $key, 'multiple' => $multiple];
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
    private function score($num, $type, $multiple)
    {
        $score = 0;
        $curfree = 0;
        $jackpot = 0;
        if ($type == DIAMONDS) {
            $this->free += WIN_CONDITION[$type][$num];
            $curfree =  WIN_CONDITION[$type][$num];
        } elseif ($type == BOX) {
            $Jackpot = 26780570;

            $jackpot = intval(WIN_CONDITION[$type][$num] * $Jackpot);
        } else {
            $score =  WIN_CONDITION[$type][$num];
        }
        $score *= $multiple  * $this->doublescore;

        $allScore = $score  + $jackpot;

        return ['score' => $score, 'jackpot' => $jackpot, 'curfree' => $curfree, 'allScore' => $allScore];
    }
}

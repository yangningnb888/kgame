<?php
const SINGLE = 1;  //单
const DOUBLE = 2;   //双
const BIGPOINT = 3;  //大
const SMALLPOINT = 4;  //小
const ONEPOINT = 10;  //单个骰子
const COUPLE = 20;    //对子
const BAOZI = 30;  //豹子
const TWOPOINT = 100;  //两个骰子
const ALLSUM = 300;   //三个骰子点数和

const ODDS = [
    1 => 1, 2 => 1, 3 => 1, 4 => 1,
    11 => 3, 12 => 3, 13 => 3, 14 => 3, 15 => 3, 16 => 3, 21 => 8, 22 => 8, 23 => 8, 24 => 8, 25 => 8, 26 => 8,
    30 => 24, 31 => 150, 32 => 150, 33 => 150, 34 => 150, 35 => 150, 36 => 150,
    112 => 5, 113 => 5, 114 => 5, 115 => 5, 116 => 5, 123 => 5, 124 => 5, 125 => 5, 126 => 5, 134 => 5, 135 => 5, 136 => 5, 145 => 5, 146 => 5, 156 => 5,
    304 => 50, 305 => 18, 306 => 14, 307 => 12, 308 => 8, 309 => 6, 310 => 6, 311 => 6, 312 => 6, 313 => 8, 314 => 12, 315 => 14, 316 => 18, 317 => 50
];

const TOUZI = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
const AREAPOS = [
    [
        'rand' => [SINGLE => 20, DOUBLE => 20, BIGPOINT => 20, SMALLPOINT => 20],
        'pos' => 70
    ],
    [
        'rand' => [11 => 10, 12 => 10, 13 => 15, 14 => 15, 15 => 10, 16 => 10],
        'pos' => 15
    ],
    [
        'rand' => [21 => 5, 22 => 5, 23 => 5, 24 => 5, 25 => 5, 26 => 5],
        'pos' => 5
    ],
    [
        'rand' => [30 => 5, 31 => 1, 32 => 1, 33 => 1, 34 => 1, 35 => 1, 36 => 1],
        'pos' => 1
    ],
    [
        'rand' => [112 => 5, 113 => 5, 114 => 5, 115 => 5, 116 => 5, 123 => 5, 124 => 5, 125 => 5, 126 => 5, 134 => 5, 135 => 5, 136 => 5, 145 => 5, 146 => 5, 156 => 5],
        'pos' => 1
    ],
    [
        'rand' => [304 => 1, 305 => 2, 306 => 3, 307 => 4, 308 => 8, 309 => 18, 310 => 18, 311 => 18, 312 => 18, 313 => 8, 314 => 4, 315 => 3, 316 => 2, 317 => 1],
        'pos' => 8
    ],
];

class Algorithm
{
    //获取骰子点数
    public static function GetPoint()
    {
        $ret = [];
        for ($i = 0; $i < 3; $i++) {
            $ret[] = array_rand(TOUZI);
        }
        return $ret;
    }

    //获取开奖结果
    public static function GetResult($arr)
    {
        $ret = [];
        $ponintsum = array_sum($arr);
        $index = $ponintsum % 2 == 0 ? DOUBLE : SINGLE;
        $ret[$index] = 1;
        if ($ponintsum > 3 && $ponintsum < 18) {
            if ($ponintsum < 11) {
                $ret[SMALLPOINT] = 1;
            } else {
                $ret[BIGPOINT] = 1;
            }
            $ret[ALLSUM + $ponintsum] = 1;
        }
        $arr_val = array_count_values($arr);
        if (max($arr_val) == 3) {
            $ret[BAOZI + $arr[0]] = 1;
            unset($ret[SMALLPOINT]);
            unset($ret[BIGPOINT]);
        } elseif (max($arr_val) == 2) {
            $num = array_search(2, $arr_val);
            $ret[COUPLE + $num] = 1;
        }

        ksort($arr_val);
        foreach ($arr_val as $key => $value) {
            $ret[ONEPOINT + $key] = $value;
            foreach ($arr_val as $key1 => $value1) {
                if ($key < $key1) {
                    $ret[TWOPOINT + $key * 10 + $key1] = 1;
                }
            }
        }

        return $ret;
    }
}
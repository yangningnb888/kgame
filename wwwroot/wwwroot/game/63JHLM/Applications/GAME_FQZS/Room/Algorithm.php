<?php
const POSSIBLE = [
    11 => 80,
    21 => 80,
    12 => 70,
    22 => 70,
    13 => 70,
    24 => 30,
    14 => 30,
    23 => 70,
    31 => 4,
    32 => 2,
];

const BETPOSSIBLE = [
    1 => 100,
    2 => 100,
    3 => 5,
    11 => 40,
    12 => 20,
    13 => 20,
    14 => 10,
    21 => 40,
    22 => 20,
    23 => 20,
    24 => 10,
];

const ODDS = [
    1 => 1,  //飞禽
    2 => 1,  //走兽
    3 => 1,  //鲨鱼
    11 => 5, //鸡
    12 => 7, //鸭
    13 => 7, //猫头鹰
    14 => 11, //老鹰
    21 => 5, //兔子
    22 => 7, //猴子
    23 => 7,  //熊猫
    24 => 11,  //狮子
    31 => 24,  //银鲨鱼
    32 => 50,  //金鲨鱼
];


class Algorithm
{
    public static function GetResult ($del = [])
    {
        $_possible = POSSIBLE;
        foreach ($del as $key => $value) {
            unset($_possible[$value]);
        }

        $_rand = rand(1, array_sum($_possible));
        $_sum = 0;
        foreach ($_possible as $key => $value) {
            $_sum += $value;
            if ($_rand <= $_sum) {
                return $key;
            }
        }

        return array_rand(POSSIBLE);
    }
}
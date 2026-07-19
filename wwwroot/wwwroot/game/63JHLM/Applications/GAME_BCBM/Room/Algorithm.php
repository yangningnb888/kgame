<?php
const POSSIBLE = [
    11 => 20,
    12 => 20,
    13 => 20,
    14 => 20,
    21 => 2,
    22 => 4,
    23 => 6,
    24 => 9,
];

const ODDS = [
    11 => 4,
    12 => 4,
    13 => 4,
    14 => 4,
    21 => 39,
    22 => 29,
    23 => 19,
    24 => 9,
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
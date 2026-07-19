<?php

const BCOUPLE = 1;  //庄对
const PCOUPLE = 2;  //闲对
const BWIN = 3;     //庄大
const PWIN = 4;     //闲大
const SPACE = 5;    //平
const BKING = 6;    //庄天王
const PKING = 7;    //闲天王
const SAMEPOINT = 8;//点同平
const DOUBLE = [
    BCOUPLE => 11,
    PCOUPLE => 11,
    BWIN => 1,
    PWIN => 1,
    SPACE => 8,
    BKING => 2,
    PKING => 2,
    SAMEPOINT => 32
];
const CARDS = [
    1 => [101, 102, 103, 104],
    2 => [201, 202, 203, 201],
    3 => [301, 302, 303, 301],
    4 => [401, 402, 403, 401],
    5 => [501, 502, 503, 501],
    6 => [601, 602, 603, 601],
    7 => [701, 702, 703, 701],
    8 => [801, 802, 803, 801],
    9 => [901, 902, 903, 901],
    0 => [1001, 1002, 1003, 1001, 1101, 1102, 1103, 1104, 1201, 1202, 1203, 1204, 1301, 1302, 1303, 1304],
];

class Algorithm
{
    private static $Cards = [];

    //获取点数
    public static function GetPoint($arr)
    {
        $point = 0;
        foreach ($arr as $key => $value) {
            if ($value < 1000) {
                $point += intval($value / 100);
            }
        }
        return $point % 10;
    }

    //判断对子
    public static function GetCouple($cards)
    {
        if (intval($cards[0] / 10) == intval($cards[1] / 10)) {
            return 1;
        } else {
            return 0;
        }
    }

    //发牌规则
    public static function GetCards($control = [])
    {
        $ret = [
            'banker' => [],  //庄家手牌
            'player' => [],  //闲家手牌
        ];

        if (!empty($control)) {
            $cards = self::GetNewCards();
            if ($control[BWIN]) {
                $max = $control[BKING] ? 9 : 7;
                if ($max == 9 && rand(1, 10) == 1) {
                    $bpoint = rand(8, $max);
                } else {
                    $bpoint = rand(3, $max);
                }
                $max = $control[PKING] ? 9 : 7;
                $max = $max > $bpoint - 1 ? $bpoint - 1 : $max;
                $ppoint = rand(0, $max);
            } elseif ($control[PWIN]) {
                $max = $control[PKING] ? 9 : 7;
                if ($max == 9 && rand(1, 10) == 1) {
                    $ppoint = rand(8, $max);
                } else {
                    $ppoint = rand(3, $max);
                }
                $max = $control[BKING] ? 9 : 7;
                $max = $max > $ppoint - 1 ? $ppoint - 1 : $max;
                $bpoint = rand(0, $max);
            } else {
                $ppoint = $bpoint = rand(0, 7);
            }

            $ret['banker'] = self::GetCurCards($cards, $bpoint, $control[BCOUPLE]);
            foreach ($ret['banker'] as $key => $value) {
                unset(self::$Cards[array_search($value, $cards)]);
            }

            $num = $bpoint >= 8 && count($ret['banker']) == 2 ? 2 : 3;
            $ret['player'] = self::GetCurCards($cards, $ppoint, $control[PCOUPLE], $num);

            if ($ppoint >= 8 && count($ret['player']) == 2 && count($ret['banker']) == 3) {
                array_pop($ret['banker']);
            }
        } else {
            if (count(self::$Cards) < 6) {
                self::$Cards = [];
                for ($k = 0; $k < 8; $k++) {
                    for ($i = 1; $i < 14; $i++) {
                        for ($j = 1; $j < 5; $j++) {
                            self::$Cards[] = $i * 100 + $j;
                        }
                    }
                }
            }
            shuffle(self::$Cards);
            for ($i = 0; $i < 2; $i++) {
                $ret['banker'][] = array_shift(self::$Cards);
                $ret['player'][] = array_shift(self::$Cards);
            }

            $bankerpoint = self::GetPoint($ret['banker']);
            $playerpoint = self::GetPoint($ret['player']);

            if ($bankerpoint >= 8 || $playerpoint >= 8) {
                return $ret;
            }
            //闲家补牌
            if ($playerpoint < 6) {
                $add = array_shift(self::$Cards);
                $ret['player'][] = $add;
            }

            if ($bankerpoint < 6) {
                $ret['banker'][] = array_shift(self::$Cards);
            }
        }

        return $ret;
    }

    //获取胜利押注
    //庄对子 1 闲对子 2 庄赢 3 闲赢 4 平 5 庄天王 6 闲天王 7 同点平 8
    public static function GetWin($cards)
    {
        $wintable = [BCOUPLE => self::GetCouple($cards['banker']), PCOUPLE => self::GetCouple($cards['player']), BWIN => 0, PWIN => 0, SPACE => 0, BKING => 0, PKING => 0, SAMEPOINT => 0];
        $bankerpoint = self::GetPoint($cards['banker']);
        $playerpoint = self::GetPoint($cards['player']);
        if ($bankerpoint > $playerpoint) {
            $wintable[BWIN] = 1;  //庄赢
        } elseif ($bankerpoint == $playerpoint) {
            $wintable[SPACE] = 1;  //平
            $news = ['banker' => [], 'player' => []];
            foreach ($cards as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    $news[$key][$key1] = intval($value1 / 10);
                }
            }
            if ($news['banker'] == $news['player']) {
                $wintable[SAMEPOINT] = 1;  //同点平
            }
        } else {
            $wintable[PWIN] = 1;
        }

        if (count($cards['banker']) == 2 && $bankerpoint >= 8 && $wintable[BWIN]) {
            $wintable[BKING] = 1;
        }

        if (count($cards['player']) == 2 && $playerpoint >= 8 && $wintable[PWIN]) {
            $wintable[PKING] = 1;
        }
        return $wintable;
    }

    //生成对应牌组
    private static function GetCurCards($cards, $flagpoint, $couple = 1, $num = 3)
    {
        $res = [];

        //排除对子牌张
        if ($flagpoint % 2 == 0) {
            $couple_point = intval($flagpoint / 2);
            if ($couple == 0) {
                $cards = array_diff($cards, [$couple_point * 100 + 1, $couple_point * 100 + 2, $couple_point * 100 + 3, $couple_point * 100 + 4]);
            }
        }

        //随机取第一张牌
        $card = $cards[array_rand($cards)];
        unset($cards[array_rand($cards)]);
        $res[] = $card;
        //小点数则随机取第二张牌
        if ($flagpoint <= 5 && $num == 3) {
            $rand = array_fill(0, 5, 1);
            if ($couple == 0) {
                unset($rand[self::GetPoint($res) % 10]);
            }
            $_test = array_rand($rand);
            $index = ($_test - self::GetPoint($res) + 10) % 10;
            $card = self::GetOneCard($cards, $index);
            if ($card) {
                $res[] = $card;
            }

            if (isset($couple_point)) {
                $cards = array_merge($cards, [$couple_point * 100 + 1, $couple_point * 100 + 2, $couple_point * 100 + 3, $couple_point * 100 + 4]);
            }
        }

        $index = ($flagpoint - self::GetPoint($res) + 10) % 10;
        $card = self::GetOneCard($cards, $index);
        if ($card) {
            $res[] = $card;
        }

        if (count($res) == 1) {
            $res[] = array_shift($cards);
        }

        if (self::GetPoint($res) <= 5 && count($res) == 2 && $num == 3) {
            $res[] = array_shift($cards);
        }

        return $res;
    }

    //
    private static function GetOneCard($cards, $index)
    {
        foreach (CARDS[$index] as $key => $value) {
            if (in_array($value, $cards)) {
                return $value;
            }
        }

        return 0;
    }

    //场控取牌
    private static function GetNewCards()
    {
        $cards = [];
        for ($k = 0; $k < 8; $k++) {
            for ($i = 1; $i < 14; $i++) {
                for ($j = 1; $j < 5; $j++) {
                    $cards[] = $i * 100 + $j;
                }
            }
        }
        return $cards;
    }
}
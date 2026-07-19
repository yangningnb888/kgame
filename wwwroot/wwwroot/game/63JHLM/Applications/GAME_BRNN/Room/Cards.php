<?php
define('HANDS_MAX', 13);

define('TYPE_NOT', 1); //没牛
define('TYPE_ONE', 2); //牛一
define('TYPE_TWO', 3); //牛二
define('TYPE_THREE', 4); //牛三
define('TYPE_FORE', 5); //牛四
define('TYPE_FIVE', 6); //牛五
define('TYPE_SIX', 7); //牛六
define('TYPE_SEVEN', 8); //牛七
define('TYPE_EIGHT', 9); //牛八
define('TYPE_NINE', 10); //牛九
define('TYPE_TEN', 11); //牛牛
define('TYPE_FORE_F', 12); //四花牛
define('TYP_EFIVE_F', 13); //五花牛
define('TYPE_BOOM', 14); //四炸
define('TYPE_FIVE_S', 15); //五小牛

define('BRNN_HANDS_GHOST', [1501, 1502]); //大小王

define(
    'CATTLE_ALL',
    [
        0 => TYPE_TEN,
        1 => TYPE_ONE,
        2 => TYPE_TWO,
        3 => TYPE_THREE,
        4 => TYPE_FORE,
        5 => TYPE_FIVE,
        6 => TYPE_SIX,
        7 => TYPE_SEVEN,
        8 => TYPE_EIGHT,
        9 => TYPE_NINE,
    ]
); //牛牛类型

define('BEISHU_CATTLE', [
    TYPE_NOT => 1,
    TYPE_ONE => 1,
    TYPE_TWO => 1,
    TYPE_THREE => 1,
    TYPE_FORE => 1,
    TYPE_FIVE => 1,
    TYPE_SIX => 1,
    TYPE_SEVEN => 2,
    TYPE_EIGHT => 2,
    TYPE_NINE => 2,
    TYPE_TEN => 3,
    TYPE_FORE_F => 3,
    TYP_EFIVE_F => 3,
    TYPE_BOOM => 3,
    TYPE_FIVE_S => 3
]); //牌型倍数

define('HANDS_ALL_HANDS', 5); //手牌张数

class Cards
{
    /**
     * 初始化手堆
     *
     * @return array
     */
    public static function InitCard()
    {
        $hands = [];
        for ($i = 1; $i <= HANDS_MAX; $i++) {
            for ($j = 1; $j < 5; $j++) {
                $hands[] = $i * 100 + $j;
            }
        }
        $hands = array_merge($hands, BRNN_HANDS_GHOST);

        for ($i = 0; $i < 5; $i++) {
            shuffle($hands);
        }

        return $hands;
    }

    /**
     * 牌型检测
     *
     * @param [] $hands
     * @return int
     */
    public static function checkCattle($hands)
    {
        $data = [];
        $type = TYPE_NOT;
        $_hands = self::dealHands($hands);
        $res = self::checkCattleBig($hands);
        if ($res > TYPE_NOT) {
            return ['cards' => $hands, 'type' => $res];
        }

        foreach ($_hands as $key => $val) {
            foreach ($_hands as $key1 => $val1) {
                if ($key == $key1) {
                    continue;
                }

                $_val = $val;
                $_val1 = $val1;
                if ($_val > 10) {
                    $_val = 10;
                }
                if ($_val1 > 10) {
                    $_val1 = 10;
                }
                $_temp = $_hands;
                unset($_temp[$key]);
                unset($_temp[$key1]);

                $sum = $_val + $_val1;
                $mod = 10 - ($sum % 10);

                if ($mod == 0 || $mod == 10) {
                    $max =  max($_temp);
                    if ($max >= 10) {
                        $mod = $max;
                    }
                }


                if (in_array($mod, $_temp) || in_array(15, $_temp)) {
                    $index = array_search($mod, $_temp);

                    if ($index === false) {
                        $index = array_search(15, $_temp);
                        $mod = 15;
                    }

                    unset($_temp[$index]);
                    $type = self::checkType($_temp);
                    $_cards = array_merge([$val, $val1, $mod], $_temp);
                    $cards = self::reHands($hands, $_cards);
                    if (empty($data) || $type >= $data['type']) {
                        $data = ['cards' => $cards, 'type' => $type];
                    }
                }
            }
        }

        if (empty($data)) {
            $data = ['cards' => $hands, 'type' => $type];
        }

        return  $data;
    }


    /**
     * 还原手牌
     * @param [type] $hands
     * @param [type] $dealhands
     * @return void
     */
    private static function reHands($hands, $dealhands)
    {
        $res = [];
        foreach ($dealhands as $key => $val) {
            foreach ($hands as $key1 => $val1) {
                $print = self::getPrint($val1);
                if ($print == $val) {
                    $res[] = $val1;
                    unset($hands[$key1]);
                    break;
                }
            }
        }
        return $res;
    }

    /**
     * 牌型(大于牛牛的牌型)
     *
     * @param [] $hands
     * @return int
     */
    private static function checkCattleBig($cards)
    {
        $hands = [];
        foreach ($cards as $key => $val) {
            $hands[] = self::getPrint($val);
        }

        $type = TYPE_NOT;
        $fiveHands = array_diff($hands, [15]);
        $max = max($fiveHands);
        if ($max < 5) {
            $count = array_sum($hands);
            if ($count < 10) {
                $type = TYPE_FIVE_S; //五小牛
            }
        } else {
            $_hands = array_count_values($hands);
            $boom = array_count_values($fiveHands);

            $max = max($boom);
            if (isset($_hands[15])) {
                $max += $_hands[15];
            }

            if ($max >= 4) {
                $type = TYPE_BOOM; //四炸
            } else {
                $count = 0;
                foreach ($hands as $key => $val) {
                    if ($val > 10) {
                        $count++;
                    }
                }

                if ($count >= 5) {
                    $type = TYP_EFIVE_F; //五花牛
                } elseif ($count >= 4 && isset($hands[10])) {
                    $type = TYPE_FORE_F; //四花牛
                }
            }
        }

        return $type;
    }
    /**
     * 手牌处理
     *
     * @param [type] $hands
     * @return []
     */
    private static function dealHands($hands)
    {
        $res = [];
        foreach ($hands as $key => $val) {
            $print = self::getPrint($val);
            $res[] = $print;
        }
        return $res;
    }

    /**
     * 牌值
     *
     * @param int $card
     * @return int
     */
    private static function getPrint($card)
    {
        return intval($card / 100);
    }

    /**
     * 牛数
     *
     * @param array $hands
     * @return void
     */
    private static function checkType($hands)
    {
        $sum = 0;
        foreach ($hands as $key => $val) {
            if ($val > 10) {
                $val = 10;
            }
            $sum += $val;
        }

        if (in_array(15, $hands)) {
            $sum = 10;
        }

        $sum = $sum % 10;
        return CATTLE_ALL[$sum];
    }

    /**
     * 手牌编号
     *
     * @param int $type
     * @param [] $hands
     * @return string
     */
    public static function GetId($type, $hands)
    {
        $id = $type;
        rsort($hands);
        $corlor = '';
        $_hands = array_diff($hands, BRNN_HANDS_GHOST);
        $count = count($_hands);

        $code = 15;

        if ($count < HANDS_ALL_HANDS && $count != HANDS_ALL_HANDS - count(BRNN_HANDS_GHOST)) {
            $code = 0;
        }

        if ($type == TYPE_BOOM) {
            $boomhands = [];
            foreach ($hands as $key => $val) {
                $print = self::getPrint($val);
                if ($print == 15) {
                    continue;
                }
                if (!isset($boomhands[$print])) {
                    $boomhands[$print] = 0;
                }
                $boomhands[$print]++;
            }

            $code = array_search(max($boomhands), $boomhands);
        }

        foreach ($hands as $key  => $val) {
            $print = self::getPrint($val);
            if (in_array($val, BRNN_HANDS_GHOST)) {
                $print = $code;
            }

            if ($print / 10 < 1) {
                $id .= '0';
            }

            $id .= $print;
            $_color = $val % 100;
            $corlor .= $_color;
        }
        $id .= $corlor;

        return $id;
    }
}

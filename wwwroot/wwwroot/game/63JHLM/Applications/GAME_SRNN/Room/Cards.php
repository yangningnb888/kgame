<?php
define('HANDS_MAX', 13); //最大牌id

define('HANDS_NUM', 5); //手牌张数

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
define('TYPE_FORE_F', 12); //
define('TYP_EFIVE_F', 13); //金牛

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
    TYPE_EIGHT => 3,
    TYPE_NINE => 4,
    TYPE_TEN => 5,
    TYPE_FORE_F => 5,
    TYP_EFIVE_F => 5,
]); //牌型倍数
// 黑桃>红桃>梅花>方块
define('HANDS_ALL_HANDS', 5); //手牌张数

define('BRNN_HANDS_GHOST', [1401, 1501]); //大小王

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
        $hands = BRNN_HANDS_GHOST;
        for ($i = 1; $i <= HANDS_MAX; $i++) {
            for ($j = 1; $j < 5; $j++) {
                $hands[] = $i * 100 + $j;
            }
        }

        for ($i = 0; $i < 5; $i++) {
            shuffle($hands);
        }
        return $hands;
    }

    /**
     * 牌型检测
     *
     * @param [] $hands
     * @return array ['hands'=>'整理后的手牌','type'=>'牌型']
     */
    public static function checkCattle($hands)
    {
        $type = TYPE_NOT;
        sort($hands);

        $deal = self::getHandsPrint($hands);
        $min = min($deal);
        $realhands = array_count_values($deal);
        $data = [];
        if ($min > 10) {
            $type = TYP_EFIVE_F;
        } elseif ($min >= 10 && $realhands[10] == 1) {
            $type = TYPE_FORE_F;
        } else {
            foreach ($deal as $key => $val) {
                $arr = $deal;
                unset($arr[$key]);
                foreach ($deal as $key1 => $val1) {
                    if ($key == $key1) {
                        continue;
                    }
                    $_arr = $arr;
                    unset($_arr[$key1]);

                    $_temp = $val > 10 ? 10 : $val;
                    $_temp1 = $val1 > 10 ? 10 : $val1;
                    $sum = $_temp + $_temp1;
                    $mod = 10 - $sum % 10;

                    $num = 0;
                    foreach ($_arr as $key2 => $val2) {
                        if (($mod == 10 && $val2 >= 10) || $val2 == $mod) {
                            $num = $val2;
                            unset($_arr[$key2]);
                            break;
                        }
                    }

                    if ($num != 0) {
                        $type = self::checkType($_arr);
                        $_hands = self::reHands($hands, $_arr);
                        $_hands1 = self::reHands($_hands['hands'], [$val, $val1, $num]);
                        $hands = array_merge($_hands['res'], $_hands1['res']);
                        break;
                    }
                }

                if ($type > TYPE_NOT) {
                    break;
                }
            }
        }
        $id = self::GetId($type, $hands);
        $data = [
            'hands' => $hands,
            'type' => $type,
            'id' => $id
        ];
        return  $data;
    }

    /**
     * 处理手牌 
     * @param [type] $hands
     * @return array ['realhands' => ['真实牌点'], 'usehands' => ['JQK的牌点为10']]
     */
    private static function getHandsPrint($hands)
    {
        $arr = [];
        foreach ($hands as $key => $val) {
            $print = self::getPrint($val);
            $arr[] = $print;
        }
        return $arr;
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
        return ['res' => $res, 'hands' => $hands];
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
        $max = max($hands);
        if ($max / 1000 < 1) {
            $max = '0' . $max;
        }

        $id .= $max;
        return $id;
    }
}

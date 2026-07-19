<?php
define('HANDS_MAX', 13); //最大牌id

define('TYPE_LINGD', 1);
define('TYPE_YD', 2);
define('TYPE_ED', 3);
define('TYPE_SAND', 4);
define('TYPE_SD', 5);
define('TYPE_WD', 6);
define('TYPE_LD', 7);
define('TYPE_QD', 8);
define('TYPE_BD', 9);
define('TYPE_JD', 10);
define('TYPE_SG', 11);
define('TYPE_BOOM', 12);
define('TYPE_BOJIU', 13);

define('HANDS_NUM', 3); //手牌张数

define('FIND_TYPE', [
    0 => TYPE_LINGD,
    1 => TYPE_YD,
    2 => TYPE_ED,
    3 => TYPE_SAND,
    4 => TYPE_SD,
    5 => TYPE_WD,
    6 => TYPE_LD,
    7 => TYPE_QD,
    8 => TYPE_BD,
    9 => TYPE_JD
]);

define('TYPE_BEISHU', [
    TYPE_BOJIU => 5,
    TYPE_BOOM => 4,
    TYPE_SG => 3,
    TYPE_JD => 2,
    TYPE_BD => 2,
    TYPE_QD => 2,
    TYPE_LD => 1,
    TYPE_WD => 1,
    TYPE_SD => 1,
    TYPE_SAND => 1,
    TYPE_ED => 1,
    TYPE_YD => 1,
    TYPE_LINGD => 1,
]);

class Cards
{
    /**
     * 初始化手堆
     *
     * @return array
     */
    public static function InitCard()
    {
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
        $type = TYPE_LINGD;
        sort($hands);

        $deal = self::getHandsPrint($hands);
        $countdeal = array_count_values($deal);

        if (count($countdeal) == 1) {
            if (isset($countdeal[3])) {
                $type = TYPE_BOJIU;
            } else {
                $type = TYPE_BOOM;
            }
        }

        if ($type <= TYPE_LINGD) {
            $min = min($deal);
            if ($min > 10) {
                $type = TYPE_SG;
            } else {
                $sum = 0;
                foreach ($deal as $key => $val) {
                    if ($val > 10) {
                        $val = 10;
                    }
                    $sum += $val;
                }

                $sum = $sum % 10;
                $type = FIND_TYPE[$sum];
            }
        }
        $id = self::GetId($type, $hands, $deal);
        return ['type' => $type, 'id' => $id];
    }

    /**
     * 处理手牌 
     * @param [type] $hands
     * @return array [牌点]
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
     * 手牌编号
     *
     * @param int $type
     * @param [] $hands
     * @return string
     */
    public static function GetId($type, $hands, $deal)
    {
        $id = $type;
        $count = 0;
        foreach ($deal as $key => $val) {
            if ($val > 10) {
                $count++;
            }
        }
        $id .= $count;

        $max = max($hands);
        if ($max / 1000 < 1) {
            $max = '0' . $max;
        }
        $id .= $max;
        return $id;
    }
}

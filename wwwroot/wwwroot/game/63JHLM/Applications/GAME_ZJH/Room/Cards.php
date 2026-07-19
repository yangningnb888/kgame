<?php
define('ZJH_TYPE_SCATTER', 1); //散牌
define('ZJH_TYPE_PAIR', 2); //对子
define('ZJH_TYPE_SHUNZI', 3); //顺子
define('ZJH_TYPE_SAME', 4); //同花
define('ZJH_TYPE_SAMESHUN', 5); //同花顺
define('ZJH_TYPE_LEOPARD', 6); //豹子

define('ZJH_HANDS_NUM', 3); //手牌张数
// 黑桃>红桃>梅花>方块
class Cards
{
    /**
     * 初始化手牌
     * @return []
     */
    public static function InitCards()
    {
        $hands = [];
        for ($i = 6; $i <= 14; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                $hands[] = $i * 100 + $j;
            }
        }

        for ($i = 0; $i < 5; $i++) {
            shuffle($hands);
        }
        return $hands;
    }

    /**
     * 获取牌点
     * @param int $card
     * @return int
     */
    private static function GET_C($card)
    {
        return $card % 100;
    }

    /** 
     * 获取牌值
     * @param int $card
     * @return int
     */
    private static function GET_P($card)
    {
        return intval($card / 100);
    }

    /**
     * 牌型检测
     * @param [type] $hands
     * @return void
     */
    public static function ChechType($hands)
    {
        $type = ZJH_TYPE_SCATTER;
        $_hands = self::dealHands($hands);
        $countPrint = count($_hands['print']);
        $countColor = count($_hands['color']);
        $shunzi = self::shunzi($_hands['print']);

        if ($countPrint == 1) {
            $type = ZJH_TYPE_LEOPARD;
        } elseif (!empty($shunzi) && $countColor == 1) {
            $type = ZJH_TYPE_SAMESHUN;
        } elseif ($countColor == 1) {
            $type = ZJH_TYPE_SAME;
        } elseif (!empty($shunzi)) {
            $type = ZJH_TYPE_SHUNZI;
        } elseif ($countPrint == 2) {
            $type = ZJH_TYPE_PAIR;
        }

        $id = self::GetId($type, $hands);

        return ['type' => $type, 'id' => $id];
    }

    /**
     * 手牌处理
     * @param array $hands
     * @return ['color'=>['颜色id'=>['牌点']], 'print'=>['牌点'=>张数]]
     */
    private static function dealhands($hands)
    {
        $data = ['color' => [], 'print' => []];
        foreach ($hands as $key => $val) {
            $color =  self::GET_C($val);
            $print = self::GET_P($val);
            $data['color'][$color][] = $print;
            if (!isset($data['print'][$print])) {
                $data['print'][$print] = 0;
            }
            $data['print'][$print]++;
        }
        return $data;
    }

    /**
     * 找顺子
     * @param [] $hands
     * @return void
     */
    private static function shunzi($hands)
    {
        if (max($hands) != 1) {
            return [];
        }

        krsort($hands);
        $data = [];

        foreach ($hands as $key => $val) {
            if (empty($data) || isset($data[$key + 1])) {
                $data[$key] = $val;
            } else {
                return [];
            }
        }

        return $data;
    }

    /**
     *  获取牌编号
     * @param [type] $type
     * @param array $hands
     * @return void
     */
    private static function getId($type, $hands)
    {
        $id = $type;
        rsort($hands);
        $color = '';
        $temp = [];
        foreach ($hands as $key => $val) {
            $temp[] = self::GET_P($val);
            $_color = self::GET_C($val);
            $color .= $_color;
        }
        $_temp = array_count_values($temp);
        arsort($_temp);
        foreach ($_temp as $key => $val) {
            for ($i = 0; $i < $val; $i++) {
                if ($key / 10 < 1) {
                    $id .= '0';
                }
                $id .= $key;
            }
        }

        $id .= $color;
        return $id;
    }
}

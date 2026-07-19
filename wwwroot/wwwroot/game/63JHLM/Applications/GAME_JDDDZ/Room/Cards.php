<?php
define('DDZ_TYPE_ONE', 1); //单张
define('DDZ_TYPE_PAIR', 2); //对子
define('DDZ_TYPE_THREE', 3); //三张
define('DDZ_TYPE_THREE_BELT1', 4); //三代一
define('DDZ_TYPE_THREE_BELT2', 5); //三代对
define('DDZ_TYPE_SHUNZI', 6); //顺子
define('DDZ_TYPE_LAINDUI', 7); //连对
define('DDZ_TYPE_FENJI', 8); //飞机
define('DDZ_TYPE_FEIJ_BELT1', 9); //飞机带翅膀
define('DDZ_TYPE_FEIJ_BELT2', 10); //飞机带翅膀
define('DDZ_TYPE_FOUR_BELT1', 11); //四带二带单张
define('DDZ_TYPE_FOUR_BELT2', 12); //四带二带对子
define('DDZ_TYPE_BOOM', 13); //炸弹
define('DDZ_TYPE_GHOST', 14); //对王

define('DDZ_HANDS_NUM', 17);
define('DDZ_HANDS_GHOST', [1701, 1801]);

define('DDZ_SHUNZI_LENGTH', 5); //顺子最短长度
define('DDZ_LIANDUI_LENGTH', 3); //连对最短长度
define('DDZ_FEIJ_LENGTH', 2); //飞机最短长度

define('DDZ_BEISHU_SPRING', 2); //春天倍数
define('DDZ_BEISHU_ANTISPRING', 2); //反春倍数
define('DDZ_BEISHU_BOOM', 2); //炸弹倍数
//
class Cards
{
    public static $CardsArr = [];

    //初始化手牌
    public static function InitCards()
    {
        self::$CardsArr = [];

        for ($i = 3; $i < 17; $i++) {
            if ($i == 15) {
                continue;
            }
            for ($j = 1; $j <= 4; $j++) {
                self::$CardsArr[] = $i * 100 + $j;
            }
        }

        self::$CardsArr = array_merge(self::$CardsArr, DDZ_HANDS_GHOST);

        for ($i = 0; $i < 5; $i++) {
            shuffle(self::$CardsArr);
        }
    }

    //发牌
    public static function FaCards()
    {
        $cards = [];
        for ($i = 0; $i < DDZ_HANDS_NUM; $i++) {
            $cards[] = array_shift(self::$CardsArr);
        }

        return $cards;
    }

    /**
     * 提示
     * @param int $type
     * @param int $min
     * @param [] $hands
     * @param int $length
     * @return void
     */
    public static function tips($last, $hands)
    {
        $_hands = [];
        foreach ($hands as $key => $val) {
            $print = self::GET_P($val);
            if ($print > $last['min']) {
                $_hands[] = $val;
            }
        }

        $data = [];

        switch ($last['type']) {
            case DDZ_TYPE_ONE:
                $data = self::sequallycards($_hands, 1);
                break;

            case DDZ_TYPE_PAIR:
                $data = self::sequallycards($_hands, 2);
                break;

            case DDZ_TYPE_THREE:
                $data = self::sequallycards($_hands, 3);
                break;

            case DDZ_TYPE_THREE_BELT1:
                $data = self::sequallycards($_hands, 3);
                if (!empty($data)) {
                    $beltHands = self::beltHands($hands, $data);
                    $belt = self::belt($beltHands, 1);
                    if (empty($belt)) {
                        $data = [];
                    } else {
                        $data = array_merge($data, $belt);
                    }
                }
                break;

            case DDZ_TYPE_THREE_BELT2:
                $data = self::sequallycards($_hands, 3);
                if (!empty($data)) {
                    $beltHands = self::beltHands($hands, $data);
                    $belt = self::belt($beltHands, 2);
                    if (empty($belt)) {
                        $data = [];
                    } else {
                        $data = array_merge($data, $belt);
                    }
                }
                break;

            case DDZ_TYPE_SHUNZI:
                if ($last['length'] >= DDZ_SHUNZI_LENGTH) {
                    sort($hands);
                    $data = self::Getshunzi($_hands, $last['length'], 1);
                }
                break;

            case DDZ_TYPE_LAINDUI:
                if ($last['length']  >= DDZ_LIANDUI_LENGTH) {
                    $data = self::Getshunzi($_hands, $last['length'], 2);
                }
                break;

            case DDZ_TYPE_FENJI:
                if ($last['length']  >= DDZ_FEIJ_LENGTH) {
                    $data = self::Getshunzi($_hands, $last['length'], 3);
                }
                break;

            case DDZ_TYPE_FEIJ_BELT1:
                if ($last['length']  >= DDZ_FEIJ_LENGTH) {
                    $data = self::Getshunzi($_hands, $last['length'], 3);
                    if (!empty($data)) {
                        foreach ($data as $key => $val) {
                            $beltHands = self::beltHands($hands, $data);
                            $belt = self::belt($beltHands, 1);
                            if (empty($belt)) {
                                $data = [];
                            } else {
                                $data = array_merge($data, $belt);
                            }
                        }
                    }
                }
                break;

            case DDZ_TYPE_FEIJ_BELT2:
                if ($last['length'] >= DDZ_FEIJ_LENGTH) {
                    $data = self::Getshunzi($_hands, $last['length'], 3);
                    if (!empty($data)) {
                        foreach ($data as $key => $val) {
                            $beltHands = self::beltHands($hands, $data);
                            $belt = self::belt($beltHands, 2);
                            if (empty($belt)) {
                                $data = [];
                            } else {
                                $data = array_merge($data, $belt);
                            }
                        }
                    }
                }
                break;

            case DDZ_TYPE_FOUR_BELT1:
                $data = self::sequallycards($_hands, 4);
                if (!empty($data)) {
                    $beltHands = self::beltHands($hands, $data);
                    $belt = self::belt($beltHands, 1);
                    if (empty($belt)) {
                        $data = [];
                    } else {
                        $data = array_merge($data, $belt);
                        $beltHands = self::beltHands($hands, $data);
                        $belt = self::belt($beltHands, 1);
                        if (empty($belt)) {
                            $data = [];
                        } else {
                            $data = array_merge($data, $belt);
                        }
                    }
                }
                break;

            case DDZ_TYPE_FOUR_BELT2:
                $data = self::sequallycards($_hands, 4);
                if (!empty($data)) {
                    $beltHands = self::beltHands($hands, $data);
                    $belt = self::belt($beltHands, 2);
                    if (empty($belt)) {
                        $data = [];
                    } else {
                        $data = array_merge($data, $belt);
                        if (empty($belt)) {
                            $data = [];
                        } else {
                            $beltHands = self::beltHands($hands, $data);
                            $belt = self::belt($beltHands, 2);
                            $data = array_merge($data, $belt);
                        }
                    }
                }
                break;

            case DDZ_TYPE_BOOM:
                $data = self::sequallycards($_hands, 4);
                break;
        }

        $res = [];
        if (!empty($data)) {
            $res[$last['type']] = $data;
        }

        if ($last['type'] < DDZ_TYPE_BOOM) {
            $boom = self::sequallycards($hands, 4);
            if (!empty($boom)) {
                $res[DDZ_TYPE_BOOM] = $boom;
            }
        }

        if (in_array(DDZ_HANDS_GHOST[0], $hands) && in_array(DDZ_HANDS_GHOST[1], $hands)) {
            $res[DDZ_TYPE_GHOST] = DDZ_HANDS_GHOST;
        }

        return $res;
    }

    /**
     *  检测牌型
     * @param  array $cards
     * @return void
     */
    public static function checkType($cards)
    {
        sort($cards);
        $type = 0;
        $count = count($cards);
        $hands = self::getprintHands($cards);
        $_hands = array_count_values($hands);

        $_count = count($_hands);

        if ($_count == 1) { //单牌 对子 三张 炸弹
            if ($count == 1) {
                $type = DDZ_TYPE_ONE;
            } elseif ($count == 2) {
                $type = DDZ_TYPE_PAIR;
            } elseif ($count == 3) {
                $type = DDZ_TYPE_THREE;
            } else {
                $type = DDZ_TYPE_BOOM;
            }
        } else {
            $min = min($_hands);
            $data = [];
            if ($count >= 5) {
                $data = self::Getshunzi($cards, $_count, $min);
                sort($data);
            }

            if ($data == $cards) { //顺子 连对 飞机
                if ($min == 1 && $_count >= DDZ_SHUNZI_LENGTH) {
                    $type = DDZ_TYPE_SHUNZI;
                } elseif ($min == 2 && $_count >= DDZ_LIANDUI_LENGTH) {
                    $type = DDZ_TYPE_LAINDUI;
                } elseif ($min == 3 && $_count >= DDZ_FEIJ_LENGTH) {
                    $type = DDZ_TYPE_FENJI;
                }
            }

            if ($type <= 0) {

                $max = max($_hands);

                if ($_count == 2) {
                    if ($max == 3) {
                        if ($min == 1) {
                            $type = DDZ_TYPE_THREE_BELT1;
                        } elseif ($min == 2) {
                            $type = DDZ_TYPE_THREE_BELT2;
                        }
                    }

                    $ghost = DDZ_HANDS_GHOST;
                    sort($ghost);
                    if ($cards == $ghost) {
                        $type = DDZ_TYPE_GHOST;
                    }
                }

                if ($max == 4) {
                    $data = self::sequallycards($cards, 4);

                    if (!empty($data)) {
                        $beltHands = self::beltHands($cards, $data);
                        $belt = self::belt($beltHands, 1);
                        $data = array_merge($data, $belt);


                        $beltHands = self::beltHands($cards, $data);
                        $belt = self::belt($beltHands, 1);
                        $data = array_merge($data, $belt);
                    }
                    $_data = $data;
                    if (!empty($data)) {
                        $beltHands = self::beltHands($cards, $_data);
                        $belt = self::belt($beltHands, 2);
                        $_data = array_merge($_data, $belt);

                        $beltHands = self::beltHands($cards, $_data);
                        $belt = self::belt($beltHands, 2);
                        $_data = array_merge($_data, $belt);
                    }

                    sort($data);
                    sort($_data);

                    if ($data == $cards) {
                        $type = DDZ_TYPE_FOUR_BELT1;
                    } elseif ($_data == $cards) {
                        $type = DDZ_TYPE_FOUR_BELT2;
                    }
                }


                if ($type <= 0) {
                    $arr = [];
                    $length = 0;
                    foreach ($_hands as $key => $val) {
                        if ($val != 3) {
                            continue;
                        }
                        $length++;

                        foreach ($cards as $key1 => $val1) {
                            $print = self::GET_P($val1);
                            if ($key == $print) {
                                $arr[] = $val1;
                            }
                        }
                    }

                    $data = self::Getshunzi($arr, $length, 3);

                    if ($length == 4 && empty($data)) {
                        $length--;
                        $data = self::Getshunzi($arr, $length, 3);
                    }

                    if (!empty($data)) {
                        $beltHands = self::beltHands($cards, $data);
                        for ($i = 0; $i < $length; $i++) {
                            $beltHands = self::beltHands($cards, $data);

                            $belt1 = self::belt($beltHands, 1);
                            $data = array_merge($data, $belt1);
                        }

                        for ($i = 0; $i < $length; $i++) {
                            $beltHands = self::beltHands($cards, $data);
                            $belt2 = self::belt($beltHands, 2);
                            $_data = array_merge($data, $belt2);
                        }

                        sort($data);
                        sort($_data);
                        if ($data == $cards) {
                            $type = DDZ_TYPE_FEIJ_BELT1;
                        } elseif ($_data == $cards) {
                            $type = DDZ_TYPE_FEIJ_BELT2;
                        }
                    }
                }
            }
        }

        return $type;
    }

    /**
     * 除去
     * @param [type] $hands
     * @param [] $data
     * @return void
     */
    private static function beltHands($hands, $data)
    {
        $_belt = [];
        foreach ($hands as $key => $val) {
            $print = self::GET_P($val);
            $code = true;
            foreach ($data as $key1 => $val1) {
                $_print = self::GET_P($val1);
                if ($print == $_print) {
                    $code = false;
                    break;
                }
            }
            if ($code) {
                $_belt[] = $val;
            }
        }

        return $_belt;
    }

    /**
     * 找顺子
     * @param array [$pid,$pid] $hands
     * @param int $length
     * @param int $wide
     * @return array
     */
    public static function Getshunzi($hands, $length, $wide)
    {
        $res = [];
        $count = count($hands);
        if ($count < $length * $wide) {
            return [];
        }
        $_hands = self::getprintHands($hands);
        $countHands = array_count_values($_hands);
        krsort($countHands);

        foreach ($countHands as $key => $val) {
            if ($val < $wide) {
                continue;
            }

            if (empty($res) || isset($res[$key + 1])) {
                $res[$key] = $wide;
            } else {
                $res = [];

                $res[$key] = $wide;
            }

            if (count($res) >= $length) {
                break;
            }
        }

        if (count($res) >= $length) {
            $res = self::rehands($hands, $res);
        } else {
            return [];
        }

        return $res;
    }

    /**
     * 带
     * @param array [$pid, $pid] $hands
     * @param int  $length 带牌 1单 2双 
     * @return void
     */
    public static function belt($hands, $length)
    {
        $res = [];
        $_hands = self::getprintHands($hands);
        $countHands = array_count_values($_hands);
        krsort($countHands);
        $code = in_array($length, $countHands);

        foreach ($countHands as $key => $val) {
            if (($code && $val == $length) || (!$code && $val > $length)) {
                $res[$key] = $val;
                break;
            }
        }

        if (!empty($res)) {
            $res = self::rehands($hands, $res);
        }
        return $res;
    }

    /**
     * 获取牌值
     * @param int $card
     * @return int
     */
    public static function GET_P($card)
    {
        return intval($card / 100);
    }

    /**
     * 还原手牌
     * @param [$pid,$pid] $rehands//原始手牌
     * @param [牌值=>张数] $hands 
     * @return []
     */
    public static function rehands($rehands, $hands)
    {
        $res = [];
        foreach ($hands as $key => $val) {
            $num = $val;
            foreach ($rehands as $key1 => $val1) {
                $cardprint = self::GET_P($val1);
                if ($cardprint == $key) {
                    $res[] = $val1;
                    $num--;
                    unset($rehands[$key1]);
                    if ($num <= 0) {
                        break;
                    }
                }
            }
        }

        return $res;
    }

    /**
     * 获取手牌的牌点
     * @param array [$pid,$pid] $hands
     * @return array
     */
    public static function getprintHands($hands)
    {
        $_hands = [];

        foreach ($hands as $key => $val) {
            $_hands[] = self::GET_P($val);
        }
        return $_hands;
    }

    //找单张 对子 三张 四张
    private static function sequallycards($hands, $wide)
    {
        $res = [];
        $_hans = self::getprintHands($hands);
        $_hands = array_count_values($_hans);
        $code = in_array($wide, $_hands);
        foreach ($_hands as $key => $val) {
            if (($code && $val == $wide) || (!$code && $val > $wide)) {
                $res[$key] = $wide;
                break;
            }
        }
        $res = self::rehands($hands, $res);
        return $res;
    }

    /**
     * 有效最小牌
     * @param [type] $cards
     * @param [type] $type
     * @return void
     */
    public static function Mincards($cards, $type, $length)
    {
        $getprintHands = self::getprintHands($cards);
        $getprintHands = array_count_values($getprintHands);

        if ($type == DDZ_TYPE_THREE_BELT1 || $type == DDZ_TYPE_THREE_BELT2) {
            foreach ($getprintHands as $key => $val) {
                if ($val >= 3) {
                    $min = $key;
                    break;
                }
            }
        } elseif ($type == DDZ_TYPE_FOUR_BELT1 || $type == DDZ_TYPE_FOUR_BELT2) {
            foreach ($getprintHands as $key => $val) {
                if ($val >= 4) {
                    $min = $key;
                    break;
                }
            }
        } elseif ($type == DDZ_TYPE_FEIJ_BELT1 || $type == DDZ_TYPE_FEIJ_BELT2) {
            $shunzi = self::Getshunzi($cards, $length, 3);
            $min = min($shunzi);
            $min = self::GET_P($min);
        } else {
            $min = min($cards);
            $min = self::GET_P($min);
        }

        return $min;
    }
}

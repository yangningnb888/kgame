<?php
const PX_MAX = 10;
const PX_THS = 9;
const PX_JG = 8;
const PX_HL = 7;
const PX_TH = 6;
const PX_SZ = 5;
const PX_ST = 4;
const PX_LD = 3;
const PX_DZ = 2;
const PX_GP = 1;


class Algorithm
{
    //获取牌型
    public static function GetPX($cards)
    {
        rsort($cards);
        $couples = self::GetCouple($cards);
        $res = [];
        foreach ($couples as $key => $value) {
            $val = '';
            $points = [];
            $color = [];
            foreach ($value as $key1 => $value1) {
                $_val = intval($value1 / 100);
                $_color = $value1 % 100;
                if (!isset($points[$_val])) {
                    $points[$_val] = 0;
                }
                $points[$_val]++;
                if (!isset($color[$_color])) {
                    $color[$_color] = 0;
                }
                $color[$value1 % 100]++;
            }

            arsort($points);
            foreach ($points as $key1 => $value1) {
                for ($i = 0; $i < $value1; $i++) {
                    $val .= $key1 >= 10 ? $key1 : '0' . $key1;
                }
            }
            //顺子判定
            $shunzi = false;
            if (max($points) == 1 && count($value) == 5) {
                $shunzi = true;
                if (isset($points[14]) && isset($points[2])) {
                    $points[1] = $points[14];
                    unset($points[14]);
                    krsort($points);
                }
                foreach ($points as $key2 => $value2) {
                    if (isset($_card) && $key2 != $_card - 1) {
                        $shunzi = false;
                    }
                    $_card = $key2;
                }
                unset($_card);
            }

            if ($shunzi && isset($points[1])) {
                $val = '0504030201';
            }

            if (min(array_keys($points)) == 10 && $shunzi && count($color) == 1) {
                $val = PX_MAX . $val;
            } elseif ($shunzi && count($color) == 1) {
                $val = PX_THS . $val;
            } elseif (max($points) == 4) {
                $val = PX_JG . $val;
            } elseif (max($points) == 3 && min($points) == 2) {
                $val = PX_HL . $val;
            } elseif (count($color) == 1 && count($value) == 5) {
                $val = PX_TH . $val;
            } elseif ($shunzi) {
                $val = PX_SZ . $val;
            } elseif (max($points) == 3) {
                $val = PX_ST . $val;
            } elseif (max($points) == 2) {
                $_count = array_count_values($points);
                if (max($_count) == 2) {
                    $val = PX_LD . $val;
                } else {
                    $val = PX_DZ . $val;
                }
            } else {
                $val = PX_GP . $val;
            }

            $res[$key] = $val;
        }

        $index = array_search(max($res), $res);
        return [
            'cards' => array_values($couples[$index]),
            'value' => $res[$index]
        ];
    }

    //对应牌组
    public static function GetCouple($cards, $res = [])
    {
        $end = [];
        $num = count($cards);
        if (empty($res)) {
            $num = $num > 3 ? 3 : $num;
            for ($i = 0; $i < $num; $i++) {
                $end[] = [$i => $cards[$i]];
            }
        } else {
            foreach ($res as $key => $value) {
                $index = max(array_keys($value)) + 1;
                for ($index; $index < $num; $index++) {
                    $_value = $value;
                    $_value[$index] = $cards[$index];
                    $end[] = $_value;
                }
            }
        }

        if (count($end[0]) >= 5 || count($end[0]) >= count($cards)) {
            return $end;
        }

        return self::GetCouple($cards, $end);
    }

    //花色价值
    public static function ColorValue($cards)
    {
        rsort($cards);
        $ret = '';
        foreach ($cards as $key => $value) {
            $ret .= $value;
        }
        return $ret;
    }

    //获取可以组合的牌型
    /*public static function GetCouplePX($cards, $allcards, $px = 0)
    {
        $px_pos = [PX_DZ => 300, PX_LD => 160, PX_ST => 80, PX_SZ => 40, PX_TH => 19, PX_HL => 10, PX_JG => 3];
        $pos = [];
        //统计点数及花色数量
        $points = [];
        $colors = [];
        shuffle($cards);
        foreach ($cards as $key => $value) {
            $_point = intval($value / 100);
            $_color = $value % 100;
            if (!isset($points[$_point])) {
                $points[$_point] = 0;
            }

            if (!isset($colors[$_color])) {
                $colors[$_color] = 0;
            }

            $points[$_point]++;
            $colors[$_color]++;
        }

        $allcount = [];
        foreach ($allcards as $key => $value) {
            $_point = intval($value / 100);
            if (!isset($allcount[$_point])) {
                $allcount[$_point] = [];
            }
            $allcount[$_point][] = $value % 100;
        }

        $ret = [];
        $_points = $points;
        ksort($_points);
        $sz = [];
        foreach ($_points as $key => $value) {
            $count = 0;
            $_sz = [];
            for ($i = $key + 4; $i >= $key; $i--) {
                if (!isset($_points[$i])) {
                    $count++;
                }
                $_sz[] = $key;
            }

            if ($count <= 2) {
                $sz[] = $_sz;
            }
        }

        foreach ($px_pos as $key => $value) {
            if ($key <= $px) {
                continue;
            }

            if ($key == PX_DZ || $key == PX_ST || $key == PX_JG) {
                $need = [PX_DZ => 2, PX_ST => 3, PX_JG => 4];
                foreach ($allcount as $key1 => $value1) {
                    $num = $points[$key1] ?? 0;
                    if (count($value1) + $num >= $need[$key] && 2 + $num >= $need[$key]) {
                        $ret[$key] = [];
                        foreach ($value1 as $key2 => $value2) {
                            $ret[$key][] = $key1 * 100 + $value2;
                            if (count($ret[$key]) + $num >= $need[$key]) {
                                break 2;
                            }
                        }
                    }
                }
            } elseif ($key == PX_LD || $key == PX_HL) {
                if (max($points) == 1 && $key == PX_HL) {
                    continue;
                }

                $_cards = [];
                if ($key == PX_LD) {
                    foreach ($points as $key1 => $value1) {
                        if ($value1 == 1 && !empty($allcount[$key1])) {
                            $_cards[] = $key1 * 100 + $allcount[$key1][array_rand($allcount[$key1])];
                        }
                    }
                } elseif ($key == PX_HL) {
                    foreach ($points as $key1 => $value1) {
                        if ($value1 == 1 && !empty($allcount[$key1]) && count($allcount[$key1]) >= 2) {
                            foreach ($allcount[$key1] as $key2 => $value2) {
                                $_cards[] = $key1 * 100 + $value2;
                                if (count($_cards) >= 2) {
                                    break 2;
                                }
                            }
                        }
                    }
                }

                if (!empty($_cards)) {
                    $ret[$key] = $_cards;
                }
            } elseif ($key == PX_SZ) {
                if (!empty($sz)) {
                    foreach ($sz as $key1 => $value1) {
                        $count = 0;
                        $ret[PX_SZ] = [];
                        foreach ($value1 as $key2 => $value2) {
                            if (empty($points[$value2]) && !empty($allcount[$value2])) {
                                $ret[PX_SZ][] = $value2 * 100 + $allcount[$value2][array_rand($allcount[$value2])];
                            }
                            if (!empty($points[$value2]) || !empty($allcount[$value2])) {
                                $count++;
                            }
                        }
                        if ($count >= 5) {
                            break;
                        }
                    }
                }
            } elseif ($key == PX_TH) {
                $color = array_search(max($colors), $colors);
                $ret[PX_TH] = [];
                foreach ($allcount as $key1 => $value1) {
                    if (in_array($color, $value1)) {
                        $ret[PX_TH][] = $key1 * 100 + $color;
                    }

                    if (count($ret[PX_TH]) >= 2) {
                        break;
                    }
                }
            }

            if (!empty($ret[$key])) {
                $pos[$key] = $value;
            }
        }

        if (!empty($pos)) {
            $rand = rand(1, array_sum($pos));
            $sum = 0;
            foreach ($pos as $key => $value) {
                $sum += $value;
                if ($rand <= $sum) {
                    return $ret[$key];
                }
            }
        }
        MyTools::log('********************************notfind');
        MyTools::log(json_encode($allcount));
        MyTools::log(json_encode($cards));
        return [];
    }*/

}
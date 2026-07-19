<?php

const BLACK = 1;  //黑
const RED = 2;  //红
const LUCK = 3;  //幸运一击

const PX_BAOZI = 9;
const PX_SHUNJIN = 8;
const PX_JINHUA = 7;
const PX_SHUNZI = 6;
const PX_DUIZI = 5;
const PX_DANZHANG = 4;


const DOUBLE = [
    BLACK => 1,
    RED => 1,
    PX_DANZHANG => 0,
    PX_DUIZI => 1,
    PX_SHUNZI => 2,
    PX_JINHUA => 3,
    PX_SHUNJIN => 5,
    PX_BAOZI => 10,
];

const GET = 0.95;

class Algorithm
{
    private static $Cards = [];

    //发牌规则
    public static function GetCards($control = 0)
    {
        $ret = [
            'red' => [],  //红方手牌
            'black' => [],  //黑方手牌
        ];

        if (!empty($control)) {
            $allcards = self::GetNewCards();
            $getwin = intval($control / 10);
            if ($control % 10 == 9 && rand(1, 100) < 3) {
                //生成牌型牌
                $control = $getwin * 10 + rand(2, 9);
            } elseif ($control % 10 >= 7 && rand(1, 100) < 15) {
                //生成牌型牌
                $control = $getwin * 10 + rand(2, 7);
            }

            $ret['red'] = self::ControlCards($control, $allcards);
            foreach ($ret['red'] as $key => $value) {
                unset($allcards[array_search($value, $allcards)]);
            }

            //修改下一步场控值
            if ($control % 10 >= 2) {
                if (rand(1, 100) <= 70) {
                    $control = $getwin * 10;
                } else {
                    $control = $getwin * 10 + rand(2, $control % 10 - 1);
                }
            }

            $ret['black'] = self::ControlCards($control, $allcards);
            $result = self::GetWin($ret);
            if (intval($result['result'] / 10) != $getwin) {
                $_cards = $ret['red'];
                $ret['red'] = $ret['black'];
                $ret['black'] = $_cards;
            }
        } else {
            if (count(self::$Cards) < 6) {
                self::$Cards = self::GetNewCards();
            }
            for ($i = 0; $i < 3; $i++) {
                $ret['red'][] = array_shift(self::$Cards);
                $ret['black'][] = array_shift(self::$Cards);
            }
        }

        return $ret;
    }

    //获取胜利押注
    public static function GetWin($cards)
    {
        $bpx = self::GetPX($cards['black']);
        $rpx = self::GetPX($cards['red']);

        $values = self::GetValue($cards);
        $bvalue = $bpx * 1000000 + $values['black'];
        $rvalue = $rpx * 1000000 + $values['red'];
        if ($bvalue > $rvalue) {
            $result = BLACK * 10;
            if ($bpx == PX_DUIZI) {
                $points = [];
                foreach ($cards['black'] as $key => $value) {
                    $points[] = intval($value / 100);
                }
                $points = array_count_values($points);
                if (array_search(2, $points) >= 9) {
                    $result += PX_DUIZI;
                }
            } elseif ($bpx > PX_DUIZI) {
                $result += $bpx;
            }
        } else {
            $result = RED * 10;
            if ($rpx == PX_DUIZI) {
                $points = [];
                foreach ($cards['red'] as $key => $value) {
                    $points[] = intval($value / 100);
                }
                $points = array_count_values($points);
                if (array_search(2, $points) >= 9) {
                    $result += PX_DUIZI;
                }
            } elseif ($rpx > PX_DUIZI) {
                $result += $rpx;
            }
        }

        return [
            'blackpx' => $bpx,
            'redpx' => $rpx,
            'result' => $result,
        ];
    }

    //获取牌型
    public static function GetPX($cards)
    {
        sort($cards);
        $point = [];
        $color = [];
        foreach ($cards as $key => $value) {
            $point[] = intval($value / 100);
            $color[] = $value % 100;
        }

        if (count(array_count_values($point)) == 1) {
            return PX_BAOZI;
        } else {
            $shunzi = true;
            foreach ($point as $key => $value) {
                if (!empty($card) && $value - 1 != $card) {
                    $shunzi = false;
                }
                $card = $value;
            }

            if ($shunzi && count(array_count_values($color)) == 1) {
                return PX_SHUNJIN;
            } elseif (count(array_count_values($color)) == 1) {
                return PX_JINHUA;
            } elseif ($shunzi) {
                return PX_SHUNZI;
            } elseif (max(array_count_values($point)) == 2) {
                return PX_DUIZI;
            } else {
                return PX_DANZHANG;
            }
        }
    }

    //获取大小
    public static function GetValue($cards)
    {
        $res = [
            'black' => '1',
            'red' => '1'
        ];

        rsort($cards['black']);
        rsort($cards['red']);
        $values = [
            'black' => [],
            'red' => []
        ];
        for ($i = 0; $i < 3; $i++) {
            $balck = intval($cards['black'][$i] / 100);
            $balck = $balck < 10 ? '0' . $balck : $balck;
            if (!isset($values['balck'][$balck])) {
                $values['balck'][$balck] = 0;
            }

            $red = intval($cards['red'][$i] / 100);
            $red = $red < 10 ? '0' . $red : $red;
            if (!isset($values['red'][$red])) {
                $values['red'][$red] = 0;
            }
            $values['balck'][$balck]++;
            $values['red'][$red]++;
        }

        arsort($values['balck']);
        arsort($values['red']);

        foreach ($values['balck'] as $key => $value) {
            for ($i = 0; $i < $value; $i++) {
                $res['black'] .= $key;
            }
        }

        foreach ($values['red'] as $key => $value) {
            for ($i = 0; $i < $value; $i++) {
                $res['red'] .= $key;
            }
        }

        if ($res['black'] == $res['red']) {
            if (max($cards['black']) > max($cards['red'])) {
                $res['black'] .= '0';
            } else {
                $res['red'] .= '0';
            }
        }

        return $res;
    }

    //生成牌堆
    private static function GetNewCards()
    {
        $cards = [];
        for ($i = 2; $i < 14; $i++) {
            for ($j = 1; $j < 5; $j++) {
                $cards[] = $i * 100 + $j;
            }
        }
        shuffle($cards);
        return $cards;
    }

    //生成牌组
    private static function ControlCards($res, $cards)
    {
        $ret = [];
        $px = $res % 10;
        if (rand(1, 100) < 60) {
            if ($px >= PX_DUIZI && rand(1, 100) < 80) {
                $rand = 100;
                $_arr = [9, 10, 11, 12, 13, 14];
            } else {
                $rand = 10;
                $_arr = [2, 3, 4, 5, 6, 7, 8];
            }

            if (rand(1, 100) <= $rand) {
                //对子
                $get = $_arr[array_rand($_arr)];
                foreach ($cards as $key => $value) {
                    if (intval($value / 100) == $get && count($ret) < 2 || intval($value / 100) != $get && count($ret) == 2) {
                        $ret[] = $value;
                        unset($cards[$key]);
                    }

                    if (count($ret) == 3) {
                        break;
                    }
                }
            }
        } elseif ($px >= PX_SHUNZI && rand(1, 100) < 40) {
            $get = rand(2, 12);
            for ($i = 0; $i < 3; $i++) {
                foreach ($cards as $key => $value) {
                    if (intval($value / 100) == $get + $i && (empty($color) || $value % 10 != $color)) {
                        $color = $value % 10;
                        $ret[] = $value;
                        unset($cards[$key]);
                        break;
                    }
                }
            }
        } elseif ($px >= PX_JINHUA && rand(1, 100) < 30) {
            $get = rand(1, 4);
            $_in = [];
            foreach ($cards as $key => $value) {
                if ($value % 100 == $get && (empty($_in) || (intval($value / 100) - $_in[0]) >= 3)) {
                    $_in[] = intval($value / 100);
                    $ret[] = $value;
                    unset($cards[$key]);
                }
                if (count($ret) == 3) {
                    break;
                }
            }
        } elseif ($px >= PX_SHUNJIN && rand(1, 100) < 20) {
            $get = rand(2, 12);
            for ($i = 0; $i < 3; $i++) {
                foreach ($cards as $key => $value) {
                    if (intval($value / 100) == $get + $i && (empty($color) || $value % 10 == $color)) {
                        $color = $value % 10;
                        $ret[] = $value;
                        unset($cards[$key]);
                        break;
                    }
                }
            }
        } elseif ($px == PX_BAOZI && rand(1, 100) < 10) {
            $get = rand(2, 14);
            foreach ($cards as $key => $value) {
                if (intval($value / 100) == $get) {
                    $ret[] = $value;
                    unset($cards[$key]);
                }
                if (count($ret) == 3) {
                    break;
                }
            }
        }

        if (count($ret) < 3) {
            $_in = [];
            foreach ($cards as $key => $value) {
                $_num = intval($value / 100);
                if (!in_array($_num, $_in) && count($ret) < 2) {
                    $_in[] = $_num;
                    $ret[] = $value;
                    unset($cards[$key]);
                } elseif (!in_array($_num, $ret) && count($ret) == 2 && (empty($_in) || abs($_num - $_in[0]) >= 3)) {
                    $_in[] = $_num;
                    $ret[] = $value;
                    unset($cards[$key]);
                }

                if (count($ret) == 3) {
                    break;
                }
            }
        }
        return $ret;
    }
}
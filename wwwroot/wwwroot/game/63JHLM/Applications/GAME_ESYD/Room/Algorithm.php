<?php
const PX_HJK = 40;
const PX_WXL = 30;

class Algorithm
{
    //获取牌型
    public static function GetPX($cards)
    {
        $point = 0;
        $A = 0;
        foreach ($cards as $key => $value) {
            $_point = intval($value / 100);
            if ($_point == 14) {
                $A++;
                $point += 11;
            } elseif ($_point > 10) {
                $point += 10;
            } else {
                $point += $_point;
            }
        }

        if (count($cards) == 2 && $point == 21) {
            return PX_HJK;
        }

        for ($i = 0; $i < $A; $i++) {
            if ($point > 21) {
                $point -= 10;
            }
        }

        if (count($cards) == 5 && $point <= 21) {
            return PX_WXL;
        } elseif ($point > 21) {
            return -1;
        } else {
            return $point;
        }
    }

    //获取最小牌型
    public static function GetMinPX ($cards)
    {
        $point = 0;
        foreach ($cards as $key => $value) {
            $_point = intval($value / 100);
            if ($_point == 14) {
                $point += 1;
            } elseif ($_point > 10) {
                $point += 10;
            } else {
                $point += $_point;
            }
        }

        if (count($cards) == 2 && $point == 21) {
            return PX_HJK;
        }

        if (count($cards) == 5 && $point <= 21) {
            return PX_WXL;
        } elseif ($point > 21) {
            return -1;
        } else {
            return $point;
        }
    }

    //牌堆
    public static function GetAllCards()
    {
        $all = [];
        for ($i = 0; $i < 8; $i++) {
            for ($j = 2; $j < 15; $j++) {
                for ($k = 1; $k <= 4; $k++) {
                    $all[] = $j * 100 + $k;
                }
            }
        }
        shuffle($all);
        return $all;
    }

    //机器人下注
    public static function RobotBet($seat, $bets, $empty = [])
    {
        $rand = rand(1, 100);
        if (!empty($empty) && $rand <= 10) {
            return [
                'seat' => $empty[array_rand($empty)],
                'gold' => $bets[rand(0, 4)]
            ];
        } elseif ($rand <= 80) {
            return [
                'seat' => $seat,
                'gold' => 0
            ];
        } else {
            return [
                'seat' => $seat,
                'gold' => $bets[rand(0, 4)]
            ];
        }
    }

    //机器人行为
    public static function RobotAct($cards, $fen = false)
    {
        if ($fen && rand(1, 2) == 1) {
            return 3;
        } else {
            $point = self::GetPX($cards);
            $minpoint = self::GetMinPX($cards);
            if (count($cards) <= 4 && $minpoint <= 10) {
                return 1;
            }  elseif ($point < 18 && count($cards) == 4 && rand(1, 2) == 1) {
                return 2;
            } elseif ($point >= 12 && $point <17 && rand(1, 5) == 1) {
                return 2;
            } elseif ($point >= 19) {
                return 0;
            } elseif ($point >= 15 && $point <= 18) {
                if (rand(1, 3) == 1) {
                    return 0;
                } else {
                    return 1;
                }
            } else {
                return 1;
            }
        }
    }
}
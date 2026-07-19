<?php

class AI
{
    public $uid = 0;  //uid
    public $endtime = 0;   //退出时间
    public $publicCards = [];  //公共牌
    public $controlUid = 0;    //当局场控最大uid
    public $gold = 0;    //剩余金币
    public $allbet = 0;
    public $doubleScore = 0;
    public $cards = [];  //手牌
    public $userAct = [];   //玩家下注信息
    private $points = [];
    private $color = [];
    private $lastActTime = 0;

    //构造
    public function __construct($uid, $gold, $endtime, $double)
    {
        $this->uid = $uid;
        $this->gold = $gold;
        $this->endtime = $endtime;
        $this->doubleScore = $double;
        $this->lastActTime = time();
    }

    //机器人行为
    public function RobotActions($stage = 0, $min = 0)
    {
        if ((time() >= $this->endtime || time() - $this->lastActTime >= 60) && rand(1, 5) == 1 && ($stage == 0 || !empty($this->userAct[$this->uid]) && $this->userAct[$this->uid]['act'] == 5)) {
            return ['event' => 'Msg_DZPK_Out', 'uid' => $this->uid];
        } elseif ($stage != 0) {
            $this->lastActTime = time();
            $this->GetRand();
            $act = 0;
            foreach ($this->userAct as $key => $value) {
                if (($value['act'] == 3 || $value['act'] == 6) && $value['act'] > $act) {
                    $act = $value['act'];
                }
            }

            if (count($this->publicCards) == 0) {
                $keys = array_keys($this->points);
                $rand = $act == 0 ? 3 : 5;
                if ($min <= 2 * $this->doubleScore && rand(1, 2) == 1) {
                    $gold = $min;
                } elseif ((max($this->color) == 2 || max($this->points) == 2)) {
                    $gold = rand(1, $rand) == 1 ? $this->GetBet($min, 40, 20) : $min;
                } elseif (abs($keys[0] - $keys[1]) <= 3 || $this->uid == $this->controlUid) {
                    if ($min == 0 && rand(1, $rand) == 1) {
                        $gold = $this->GetBet($min, 30, 10);
                    } else {
                        $gold = $min;
                    }
                } elseif ($this->gold < 20 * $this->doubleScore && rand(1, 30) == 1) {
                    $gold = $this->gold;
                } else {
                    $gold = $min == 0 ? $min : -1;
                }
            } else {
                $px = Algorithm::GetPX(array_merge($this->cards, $this->publicCards));
                if (count($this->publicCards) == 3) {
                    $rand = $act == 0 ? 3 : 10;
                    if ($px['value'] > 50000000000) {
                        $gold = rand(1, 7) < 5 ? $this->gold : $this->GetBet($min, 40, 20);
                    } elseif ($px['value'] > 30000000000 && $this->allbet < 50 * $this->doubleScore && rand(1, $rand) == 1) {
                        $gold = $this->GetBet($min, 40, 20);
                    } elseif ($px['value'] > 30000000000 && $act == 6 && rand(1, $rand) == 1) {
                        $gold = $this->gold;
                    } elseif ($px['value'] > 30000000000 || $this->uid == $this->controlUid) {
                        $gold = $min == 0 && rand(1, $rand) == 1 ? $this->doubleScore : $this->GetBet($min, 20, 10);
                    } elseif ($px['value'] > 10000000000 || max($this->color) >= 4) {
                        $rand = rand(1, 10);
                        if ($rand <= 2) {
                            $gold = $this->GetBet($min, 40, 20);
                        } elseif ($rand <= 7 && $min < 10 * $this->doubleScore) {
                            $gold = $min;
                        } else {
                            $gold = $min == 0 ? $min : -1;
                        }
                    } else {
                        $gold = $min == 0 ? $min : -1;
                    }
                } else {
                    $rand = $act == 0 ? 3 : 8;
                    if ($px['value'] > 50000000000 && rand(1, $rand) < 5) {
                        $gold = $this->gold;
                    } elseif ($px['value'] > 30000000000 && $this->allbet < 100 * $this->doubleScore && rand(1, $rand) == 1) {
                        $gold = $this->GetBet($min, 40, 20);
                    } elseif ($px['value'] > 30000000000 && $act == 6 && rand(1, $rand) == 1) {
                        $gold = $this->gold;
                    } elseif ($px['value'] > 30000000000 || $this->uid == $this->controlUid) {
                        $gold = $min;
                    } elseif ($px['value'] > 10000000000) {
                        $rand = rand(1, 10);
                        if ($rand <= 2) {
                            $gold = $this->GetBet($min, 40, 20);
                        } elseif ($rand <= 7 && $min < 10 * $this->doubleScore) {
                            $gold = $min;
                        } else {
                            $gold = $min == 0 ? $min : -1;
                        }
                    } else {
                        $gold = $min == 0 ? $min : -1;
                    }
                }
            }

            if ($gold > $this->gold) {
                $gold = $this->gold;
            }

            return [
                'event' => 'Msg_DZPK_ActBet',
                'uid' => $this->uid,
                'data' => [
                    'gold' => $gold
                ],
            ];
        }
        return [];
    }

    //机器人手牌概率
    public function GetRand()
    {
        $cards = array_merge($this->cards, $this->publicCards);
        $duizi = 0;
        $points = [];
        $color = [];
        foreach ($cards as $key => $value) {
            $_val = intval($value / 100);
            $_color = $value % 100;
            if (!isset($points[$_val])) {
                $points[$_val] = 0;
            }
            $points[$_val]++;
            if (!isset($color[$_color])) {
                $color[$_color] = 0;
            }
            $color[$value % 100]++;
        }

        $this->points = $points;
        $this->color = $color;
        foreach ($points as $key => $value) {
            if ($value >= 2) {
                $duizi++;
            }
        }

        if (max($color) - count($this->publicCards) > 1 && count($this->publicCards) <= 2) {
            return 70;
        } elseif (max($color) >= 5) {
            return 100;
        } elseif ($duizi >= 2) {
            return 80;
        } elseif (max($points) >= 4) {
            return 100;
        } elseif (max($points) >= 3) {
            return 80;
        } elseif (max($points) >= 2) {
            if (count($this->publicCards) > 1) {
                return 50;
            } else {
                return 70;
            }
        } else {
            return 30;
        }
    }

    //加注额度
    private function GetBet($minbet, $max, $min)
    {
        $bet = 0;
        if (rand(1, 3) == 1) {
            $bet = round(rand($max, $min) / 10) * $minbet;
        } else {
            $pos = [2 => 100, 10 => 20, 20 => 10, 50 => 5, 100 => 1];
            $rand = rand(1, array_sum($pos));
            foreach ($pos as $key => $value) {
                if ($rand <= $value) {
                    $bet = $minbet == 0 ? $this->doubleScore * $key : $minbet * $key;
                    break;
                } else {
                    $rand -= $value;
                }
            }
        }

        if ($bet > $this->gold) {
            $bet = $this->gold;
        }

        if ($bet == 0 && $minbet > 0) {
            $bet = -1;
        }
        return $bet;
    }
}
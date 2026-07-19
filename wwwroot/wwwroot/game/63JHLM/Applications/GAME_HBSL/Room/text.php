<?php
$run = new text;
for ($i = 0; $i < 100; $i++) {
    $running = $run->Fahb();
    var_dump(json_encode($running));
}
class text
{
    // public function arr($score = 200000, $num = 10)
    // {
    //     $scoreCount = 0;
    //     $res = [];
    //     for ($i = 1; $i < $num; $i++) {
    //         $arg = 10000000 / $num-$i;
    //         $rand = rand(1, 2);
    //         $_rand = rand(1, $arg - 100000);
    //         if ($rand == 1) {
    //             $_avg = $arg + $_rand;
    //         } else {
    //             $_avg = $arg - $_rand;
    //         }

    //         $_score = intval(($score-$scoreCount) *$_avg/100000));
    //         $scoreCount += $_score;
    //         $res[] = $_score;
    //     }
    //     $res[] = $score - $scoreCount;
    //     return $res;
    // }

    public function Fahb($total = 200000, $num = 8, $min = 14000)
    {
        $res = [];
        for ($i = 1; $i < $num; $i++) {
            $safe_total = ($total - ($num - $i) * $min) / ($num - $i); //随机安全上限  
            $money = intval(mt_rand($min * 1000, $safe_total * 1000) / 1000);
            $total -= $money;
            $res[] = $money;
        }
        $res[] = $total;
        return $res;
    }

    /**
     * 红包金额
     * @param int $score
     * @param int $num
     * @return void
     */
    private function hbArr($total, $num, $thunder)
    {
        $min = 10000;
        $res = [];
        for ($i = 1; $i < $num; $i++) {
            $safe_total = ($total - ($num - $i) * $min) / ($num - $i); //随机安全上限  
            $money = intval(mt_rand($min * 1000, $safe_total * 1000) / 1000);
            $total -= $money;
            $res[] = $money;
        }
        $res[] = $total;
        $res = $this->controlhb($res, $thunder);
        return $res;
    }

    /**
     * 通过炸弹控制
     *
     * @param array $res
     * @param int $thunder
     * @param integer $control 1 杀 2放
     * 
     * @return void
     */
    private function controlhb($res, $thunder, $control = 1)
    {
        $count = 0;
        $data = [];
        foreach ($res as $key => $val) {
            $_thunder = $val % 10;
            if ($_thunder == $thunder) {
                $count++;
                $data[$key] = $val;
            }
        }

        $number = 0;
        if ($control == 1 && $count == 0) {
            $rand = rand(0, 2);
            for ($i = 0; $i > $rand; $i++) {
                $_thunder = $res[$i] % 10;
                $num = $_thunder - $thunder;
                $res[$i] -= $num;
                $number += $number;
            }

            if ($number > 0) {
                $rands = rand($rand, count($res));
                $res[$rands] += $number;
            }
        }

        shuffle($res);
    }

    // private function hbArr($score, $num)
    // {
    //     $res = [];
    //     // [1-3];
    //     $scoreCount = 0;
    //     for ($i = 1; $i < $num; $i++) {
    //     }

    //     return $res;
    // }
}

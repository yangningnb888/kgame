<?php
$arr =[
    [8,13,16],
    [4,7,6],
    [9,17,12],
    [9,15,11],
    [9,14,15]
];

define('DFDC_TYPE_GOLDEN_DRAGON', 1); //金色龙
define('DFDC_TYPE_GOLDEN_SHIP', 2); //金色船
define('DFDC_TYPE_GOLDEN_FISH', 3); //金色鱼
define('DFDC_TYPE_GOLDEN_YUANBAO', 4); //金色元宝
define('DFDC_TYPE_GREEN_DRAGON', 5); //绿色龙
define('DFDC_TYPE_GREEN_SHIP', 6); //绿色船
define('DFDC_TYPE_GREEN_FISH', 7); //绿色鱼
define('DFDC_TYPE_GREEN_YUANBAO', 8); //绿色元宝
define('DFDC_TYPE_SCATTER', 9); //免费图标
define('DFDC_TYPE_COPPER', 10); //硬币
define('DFDC_TYPE_WILD', 11); //福
define('DFDC_TYPE_A', 12); //A
define('DFDC_TYPE_K', 13); //K
define('DFDC_TYPE_Q', 14); //Q
define('DFDC_TYPE_J', 15); //J
define('DFDC_TYPE_10', 16); //10
define('DFDC_TYPE_9', 17); //9

define('DFDC_TYPE_ALL', [
    DFDC_TYPE_GOLDEN_DRAGON, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_FISH, DFDC_TYPE_GOLDEN_YUANBAO, DFDC_TYPE_A,
    DFDC_TYPE_GREEN_SHIP, DFDC_TYPE_GREEN_FISH, DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_SCATTER, DFDC_TYPE_COPPER,
    DFDC_TYPE_K, DFDC_TYPE_Q, DFDC_TYPE_J, DFDC_TYPE_10, DFDC_TYPE_9, DFDC_TYPE_GREEN_DRAGON,
]);

define('MAP_HIGH', 3); //地图高
define('MAP_WIDE', 5); //地图宽

define('WIN_CONDITION', [
    WLPD_TYPE_WILD => [
        3 => 50,
        4 => 400,
        5 => 2000,
    ],

    WLPD_TYPE_HEAD => [
        3 => 50,
        4 => 400,
        5 => 2000,
    ],

    WLPD_TYPE_POTION => [
        3 => 40,
        4 => 200,
        5 => 800,
    ],

    WLPD_TYPE_LOLLIPOP => [
        3 => 30,
        4 => 150,
        5 => 600,
    ],
    WLPD_TYPE_CACTUS => [
        3 => 20,
        4 => 100,
        5 => 400,
    ],
    WLPD_TYPE_VIOLET => [
        3 => 10,
        4 => 75,
        5 => 250,
    ],
    WLPD_TYPE_GREEN => [
        3 => 10,
        4 => 75,
        5 => 250,
    ],
    WLPD_TYPE_ORANGE => [
        3 => 10,
        4 => 75,
        5 => 250,
    ],
    WLPD_TYPE_A => [
        3 => 3,
        4 => 15,
        5 => 40
    ],
    WLPD_TYPE_K => [
        3 => 3,
        4 => 15,
        5 => 40
    ],
    WLPD_TYPE_Q => [
        3 => 3,
        4 => 15,
        5 => 40
    ],
    WLPD_TYPE_J => [
        3 => 3,
        4 => 15,
        5 => 40
    ],
    WLPD_TYPE_10 => [
        3 => 3,
        4 => 15,
        5 => 40
    ],
]); //中奖奖励

$run =  new text();
$run->checkdate();

class text
{
    public $map = [];
    private $mapsamestr = 0;
    private $mapsamelen = 0;

      /**
     * 初始地图
     *
     * @return void
     */
    private function InitMap()
    {
        $this->map = [];
        $arr = [];
        if ($this->PlayerInfo['multiple'] == 1) {
            $arr = [DFDC_TYPE_GOLDEN_YUANBAO, DFDC_TYPE_GOLDEN_FISH, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_DRAGON];
        } elseif ($this->PlayerInfo['multiple'] == 2) {
            $arr = [DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_GOLDEN_FISH, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_DRAGON];
        } elseif ($this->PlayerInfo['multiple'] == 3) {
            $arr = [DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_GREEN_FISH, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_DRAGON];
        } elseif ($this->PlayerInfo['multiple'] == 4) {
            $arr = [DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_GREEN_FISH, DFDC_TYPE_GREEN_SHIP, DFDC_TYPE_GOLDEN_DRAGON];
        } else {
            $arr = [DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_GREEN_FISH, DFDC_TYPE_GREEN_SHIP, DFDC_TYPE_GREEN_DRAGON];
        }
        $this->bonus = 0;

        $alltype = array_diff(DFDC_TYPE_ALL, $arr);
        $_alltype = array_diff($alltype, [DFDC_TYPE_WILD]);

        for ($i = 0; $i < DFDC_HEIGHT; $i++) {
            for ($j = 0; $j < DFDC_WIDTH; $j++) {
                if ($j == 0) {
                    $type = $_alltype[array_rand($_alltype)];
                } else {
                    $type = $alltype[array_rand($alltype)];
                    if ($type == DFDC_TYPE_WILD) {
                        $this->bonus++;
                    }
                }
                $this->map[] = $type;
            }
        }
    }
    /**
     * 线路检测
     *
     * @return void
     */
    public function checkdate()
    {
        $arr = [];
        $score = 0;
        $map = [];
        $beishu = 0;
        $this->map = [
            [2, 11, 10],
            [4, 1, 7],
            [4, 1, 7],
            [6, 6, 2],
            [5, 13, 2]

        ];

        foreach ($this->map[0] as $key => $val) {
            $_map = [];
            $_map[] = [0, $key];
            // var_dump($val);
            // var_dump('==' . json_encode($_map));
            if ($val == WLPD_TYPE_WILD) {
                $data = $this->spe($map, $arr, $key);
                $beishu += $data['beishu'];
                $arr = $data['arr'];
                $map = $data['map'];
            } else {
                $data = $this->algorithm($val, $map, $arr, $key);
                $beishu += $data['beishu'];
                $arr = $data['arr'];
                $map = $data['map'];

                //     for ($i = 1; $i < MAP_WIDE; $i++) {
                //         foreach ($this->map[$i] as $key1 => $val1) {
                //             if ($type == $val1 || $val1 == WLPD_TYPE_WILD) {
                //                 if (!in_array([$i, $key1], $map)) {
                //                     $_map[] = [$i, $key1];
                //                 }

                //                 if (!isset($num[$i])) {
                //                     $num[$i] = 0;
                //                 }
                //                 $num[$i]++;
                //             }
                //         }

                //         if (!isset($num[$i])) {
                //             break;
                //         }
                //     }
                // }


                // $count = count($num) + 1;
                // if (min(array_keys(WIN_CONDITION[$type])) <= $count) {
                //     $line = 0;
                //     foreach ($num as $key1 => $val1) {
                //         $line += $val1;
                //     }
                //     $multiple = WIN_CONDITION[$type][$count] * $line;
                //     $map = array_merge($map, $_map);
                //     if (isset($arr[$type])) {
                //         $arr[$type]['multiple'] += $multiple;
                //     } else {
                //         $arr[$type]['multiple'] = $multiple;
                //     }
                //     $beishu += $multiple;

                // $score +=  $multiple * $this->roomRule['doublescore'];
            }
        }
        // $this->changScore($score);

        $data = [
            'win' => $arr,
            'score' => $score,
            'curMap' => $map,
            'map' => $this->map,
            'gold' => $this->PlayerInfo['gold'],
            'beishu' => $beishu,
            'mapSameStar' => $this->mapsamestar,
            'mapSameLen' => $this->mapsamelen
        ];

        return $data;
    }

    /**
     *癞子计算
     * @param [type] $map
     * @param [type] $arr
     * @param [type] $row
     * @return void
     */
    private function spe($map, $arr, $row)
    {
        $beishu = 0;
        foreach (MAP_ALL as $key => $val) {
            if (!in_array($val, $this->map[1])) {
                continue;
            }
            $data =  $this->algorithm($val, $map, $arr, $row);
            $beishu += $data['beishu'];
            $arr = $data['arr'];
            $map = $data['map'];
        }

        return ['beishu' => $beishu, 'arr' => $arr, 'map' => $map];
    }

    /**
     *倍数计算
     * @param [type] $type
     * @param [] $map
     * @param [type] $arr
     * @param [type] $row
     * @return void
     */
    private function algorithm($type, $map, $arr, $row)
    {
        $beishu = 0;
        if (!in_array([0, $row], $map)) {
            $_map[] = [0, $row];
        }
        $num = [];
        for ($i = 1; $i < MAP_WIDE; $i++) {
            foreach ($this->map[$i] as $key1 => $val1) {
                if ($type == $val1 || $val1 == WLPD_TYPE_WILD) {
                    if (!in_array([$i, $key1], $map)) {
                        $_map[] = [$i, $key1];
                    }

                    if (!isset($num[$i])) {
                        $num[$i] = 0;
                    }
                    $num[$i]++;
                }
            }

            if (!isset($num[$i])) {
                break;
            }
        }


        $count = count($num) + 1;
        if (min(array_keys(WIN_CONDITION[$type])) <= $count) {
            $line = 1;
            foreach ($num as $key1 => $val1) {
                $line *= $val1;
            }

            $multiple = WIN_CONDITION[$type][$count] * $line;
            $map = array_merge($map, $_map);
            if (isset($arr[$type])) {
                $arr[$type]['multiple'] += $multiple;
            } else {
                $arr[$type]['multiple'] = $multiple;
            }

            $beishu += $multiple;
        }

        return ['beishu' => $beishu, 'arr' => $arr,  'map' => $map];
    }
}

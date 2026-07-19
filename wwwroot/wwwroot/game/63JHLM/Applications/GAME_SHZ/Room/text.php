<?php
// $arr = [
//     1 => [
//         1 => [[1, 0], [1, 1], [1, 2], [1, 3], [1, 4]],
//         2 => [[1, 4], [1, 3], [1, 2], [1, 1], [1, 0]],
//     ],
// ];


// var_dump(key($arr));

define('MAP_HIGH', 3); //地图高
define('MAP_WIDE', 5); //地图宽


define('SEAT_OFFLINE', 0); //离线
define('SEAT_ONLINE', 1); //在线

define("STAGE_WAIT", 0);  //等待开始
define("STAGE_START", 1);  //等待开始
define("STAGE_OLD", 2);  //解散

define("MAX_BEI", 5);  //倍数
define('MAX_LINE', 9); //总线数

define('SHZ_TYPE_REPLACES', 1); //水浒传
define('SHZ_TYPE_LOYALTY', 2); //忠义堂
define('SHZ_TYPE_GO', 3); //替天行道
define('SHZ_TYPE_SONG', 4); //宋江
define('SHZ_TYPE_LING', 5); //林冲
define('SHZ_TYPE_LU', 6); //鲁智深
define('SHZ_TYPE_KNIFE', 7); //大刀
define('SHZ_TYPE_GUN', 8); //枪
define('SHZ_TYPE_AXE', 9); //斧头

define('SHZ_TYPE_HERO', 10); //英雄
define('SHZ_TYPE_ARMS', 11); //武器

define('NUM_GAME', 3); //进入小游戏

define('WIN_ARMS', [SHZ_TYPE_KNIFE, SHZ_TYPE_GUN, SHZ_TYPE_AXE]); //武器集合
define('WIN_HERO', [SHZ_TYPE_SONG, SHZ_TYPE_LING, SHZ_TYPE_LU]); //英雄集合

define('MAP_ALL', [SHZ_TYPE_REPLACES, SHZ_TYPE_LOYALTY, SHZ_TYPE_GO, SHZ_TYPE_SONG, SHZ_TYPE_LING, SHZ_TYPE_LU, SHZ_TYPE_KNIFE, SHZ_TYPE_GUN, SHZ_TYPE_AXE]); //地图所以的图案

define('ARR_MAP', [
    1 => [
        1 => [[0, 1], [1, 1], [2, 1], [3, 1], [4, 1]],
        // 2 => [[4, 1], [3, 1], [2, 1], [1, 1], [0, 1]],
    ],
    // 2 => [
    //     1 => [[0, 2], [1, 2], [2, 2], [3, 2], [4, 2]],
    //     2 => [[4, 2], [3, 2], [2, 2], [1, 2], [0, 2]],
    // ],
    // 3 => [
    //     1 => [[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]],
    //     2 => [[4, 0], [3, 0], [2, 0], [1, 0], [0, 0]],
    // ],
    // 4 => [
    //     1 => [[0, 2], [1, 1], [2, 0], [3, 1], [4, 2]],
    //     2 => [[4, 2], [3, 1], [2, 0], [1, 1], [0, 2]],
    // ],
    // 5 => [
    //     1 => [[0, 0], [1, 1], [2, 2], [3, 1], [4, 0]],
    //     2 => [[4, 0], [3, 1], [2, 2], [1, 1], [0, 0]],
    // ],
    // 6 => [
    //     1 => [[0, 2], [1, 2], [2, 1], [3, 2], [4, 2]],
    //     2 => [[4, 2], [3, 2], [2, 1], [1, 2], [0, 2]],
    // ],
    // 7 => [
    //     1 => [[0, 0], [1, 0], [2, 1], [3, 0], [4, 0]],
    //     2 => [[4, 0], [3, 0], [2, 1], [1, 0], [0, 0]],
    // ],
    // 8 => [
    //     1 => [[0, 2], [1, 0], [2, 0], [3, 0], [4, 1]],
    //     2 => [[4, 1], [3, 0], [2, 0], [1, 0], [0, 2]],
    // ],
    // 9 => [
    //     1 => [[0, 1], [1, 2], [2, 2], [3, 2], [4, 1]],
    //     2 => [[4, 1], [3, 1], [2, 2], [1, 2], [0, 1]],
    // ],
]); //中奖地图

define('WIN_CONDITION', [
    SHZ_TYPE_REPLACES => [
        5 => 2000
    ],
    SHZ_TYPE_LOYALTY => [
        3 => 50, 4 => 200, 5 => 1000
    ],
    SHZ_TYPE_GO => [
        3 => 20, 4 => 80, 5 => 400
    ],
    SHZ_TYPE_SONG => [
        3 => 15, 4 => 40, 5 => 200
    ],
    SHZ_TYPE_LING => [
        3 => 10, 4 => 30, 5 => 160
    ],
    SHZ_TYPE_LU => [
        3 => 7, 4 => 90, 5 => 100
    ],
    SHZ_TYPE_KNIFE => [
        3 => 5, 4 => 15, 5 => 60
    ],
    SHZ_TYPE_GUN => [
        3 => 3, 4 => 10, 5 => 40
    ],
    SHZ_TYPE_AXE => [
        3 => 2, 4 => 5, 5 => 20
    ],
]); //中奖奖励

define('SPE_WIN_CONDITION', [
    SHZ_TYPE_REPLACES => 5000, //水浒传
    SHZ_TYPE_LOYALTY => 2500, //忠义堂
    SHZ_TYPE_GO => 1000, //替天行道
    SHZ_TYPE_SONG => 500, //宋江
    SHZ_TYPE_LING => 400, //林冲
    SHZ_TYPE_LU => 250, //鲁智深
    SHZ_TYPE_KNIFE => 150, //大刀
    SHZ_TYPE_GUN => 100, //枪
    SHZ_TYPE_AXE => 50, //斧头
    SHZ_TYPE_HERO => 50,
    SHZ_TYPE_ARMS => 15,
]); //特殊中奖奖励


$run = new text();
// for ($i = 0; $i < 100; $i++) {
$running = $run->calculationWin();
// var_dump('===============================================');
// }

class text
{
    private $map = [];
    private $leve = -1;

    public function InitMap()
    {
        $this->map = [];

        for ($i = 0; $i < MAP_HIGH; $i++) {
            for ($j = 0; $j < MAP_WIDE; $j++) {
                $type = $this->gradeMap($j);
                $this->map[$i][] = $type;
                // var_dump('==============================');
            }
        }

        $this->map = [
            [12, 11, 12, 1, 2],
            [3, 12, 7, 9, 11],
            [11, 4, 12, 2, 8],
        ];

        foreach ($this->map as $key => $val) {
            var_dump(json_encode($val));
        }
        $this->calculationWin();
    }


    private function gradeMap($row)
    {
        $total = array_sum(CONTROL_MAP[$this->leve][$row]);
        // var_dump('=============总分数===' . $total);
        $rand = rand(1, $total);
        // var_dump('============随机值====' . $rand);

        $num = 0;
        $res = -1;
        foreach (CONTROL_MAP[$this->leve][$row] as $key => $val) {
            $num += $val;
            if ($num > $rand) {
                $res = $key;
                break;
            }
        }
        // var_dump('============结果====' . $res);
        return $res;
    }

    public function calculationWin()
    {
        $res = [];

        $this->map = [
            [3, 8, 1],
            [4, 1, 6],
            [6, 6, 2],
            [6, 7, 6],
            [7, 6, 6],
        ];

        $res = ['win' => []];
        // $speData = $this->speCalculationWin();
        $score = 0;
        $game = 0;
        $beishu = 0;

        if (empty($speData)) {
            foreach (ARR_MAP as $key => $val) {
                foreach ($val as $key1 => $val1) {
                    $type = 0;
                    $num = 1;
                    $_game = 0;
                    foreach ($val1 as $key2 => $val2) {
                        $_type = $this->map[$val2[0]][$val2[1]];
                        var_dump('-------------_type-'.$_type);
                        var_dump('==============type==='.$type);

                        if ($type == 0) {
                            $type =  $_type;
                            continue;
                        }

                        if ($num >= NUM_GAME && $type == SHZ_TYPE_REPLACES) {
                            $_game = GAME_CONDITION[$num];
                        }

                        if ($_type == $type || $_type == SHZ_TYPE_REPLACES || $type == SHZ_TYPE_REPLACES) {
                            $num++;
                            if ($type == SHZ_TYPE_REPLACES) {
                                $type = $_type;
                            }

                        } else {
                            break;
                        }
                    }

                    $game += $_game;

                    if ($num >= min(array_keys(WIN_CONDITION[$type]))) {
                        $res['win'][] = [
                            'num' => $num,
                            'type' => $type,
                            'line' => $key,
                            'dir' => $key1,
                            'multiple' => WIN_CONDITION[$type][$num]
                        ];
                        $beishu += WIN_CONDITION[$type][$num];

                        $score += WIN_CONDITION[$type][$num] * 1000 * 10;
                    }
                }
            }
        } else {
            // $score += $speData['multiple'] * 1000 * $this->roomRule['doublescore'] * MAX_LINE;
            // $res['win'][] = $speData;
        }

        $this->PlayerInfo['score'] = $score; //存储玩家当前赢的分数

        $res['score'] = $score;
        // $res['gold'] = $this->PlayerInfo['gold'];
        $res['map'] = $this->map;
        $res['game'] = $game;
        $res['beishu'] = $beishu;

        // $this->PlayerInfo['game'] = $game; //小游戏标志
        var_dump(json_encode($res));
        return $res;
    }
    /**
     * 全屏计算
     *
     * @return [ 'type' => 类型, 'multiple' => 倍数, 'line' => 0, 'dir' => 0, 'num' => 数量,]
     */
    private function speCalculationWin()
    {
        $data = [];
        $maps = array_count_values($this->maps);
        $count = count($maps);
        $type = 0;

        if ($count == 1) {
            $type = key($maps);
        } elseif ($count <= 3) {
            $types = array_keys($maps); //类型集合

            $arms = array_diff($types, WIN_ARMS);
            $hero = array_diff($types, WIN_HERO);

            if (empty($arms)) {
                $type = SHZ_TYPE_HERO;
            }

            if (empty($hero)) {
                $type = SHZ_TYPE_HERO;
            }
        }

        if ($type > 0) {
            $data = [
                'type' => $type,
                'multiple' => SPE_WIN_CONDITION[$type],
                'line' => 0,
                'dir' => 0,
                'num' => MAP_HIGH * MAP_WIDE,
            ];
        }

        return $data;
    }
}

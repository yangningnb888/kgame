<?php

define('XBLY_TYPE_BLUE', 1);
define('XBLY_TYPE_GREEN', 2);
define('XBLY_TYPE_VIOLET', 3);
define('XBLY_TYPE_RED', 4);
define('XBLY_TYPE_YELLOW', 5);
define('XBLY_TYPE_BOOM', 6);

define('XBLY_TYPE_ALL', [XBLY_TYPE_BLUE, XBLY_TYPE_GREEN, XBLY_TYPE_VIOLET, XBLY_TYPE_RED, XBLY_TYPE_YELLOW]);

define('XBLY_AUX_ARR', [[1, 0], [0, 1], [-1, 0], [0, -1]]); //辅助数组

define('XBLY_BOOM_PRO', 20); //辅助数组

// $run = new text();
// $running = $run->InitMap();

$data = [
    'list' => [
        1 => 100, //蓝色
        2 => 200, //绿色
        3 => 300, //紫色
        4 => 400, //红色
        5 => 500, //黄色
    ],
    'boom' => 30//炸弹概率
];
var_dump(json_encode($data));

// var_dump(json_encode($running));
// $running = $run->repairmap([], []);

class text
{
    private $winner = [];

    private $long = 6;

    private $wide = 6;

    private $repairMap = [];

    private $clearnum = 4;

    //初始化地图
    public function InitMap($code = true)
    {
        $map = [];
        if ($code) {
            $rand = rand(0, 100);
            if ($rand <= XBLY_BOOM_PRO) {
                $x = rand(0, $this->long - 1);
                $y = rand(0, $this->wide - 1);

                $map[$x][$y] = XBLY_TYPE_BOOM;
            }
        }

        for ($i = 0; $i < 6; $i++) {
            for ($j = 0; $j < 6; $j++) {
                if (!empty($map[$i][$j])) {
                    continue;
                }

                $map[$i][$j] = XBLY_TYPE_ALL[array_rand(XBLY_TYPE_ALL)];
            }
            ksort($map[$i]);
        }


        $res = [];

        if ($code) {
            // $map = [
            //     [6, 2, 3, 4, 2, 5],
            //     [1, 3, 1, 3, 4, 4],
            //     [4, 1, 4, 3, 1, 2],
            //     [4, 3, 3, 3, 1, 5],
            //     [4, 2, 3, 2, 3, 2],
            //     [2, 2, 5, 4, 4, 3],
            // ];

            $data = $this->checkmap($map);
            $repairmap = $this->repairmap($map, $data);
            var_dump(json_encode($data));
            $res[] = [
                'data' => $data,
                'repairmap' => $repairmap['repairMap']
            ];
            $map = $repairmap['map'];

            while (!empty($data)) {
                $data = $this->checkmap($map);
                var_dump("=================清除===========" . json_encode($data));
                $repairmap = $this->repairmap($map, $data);
                $map = $repairmap['map'];

                $res[] = [
                    'map' => $map,
                    'data' => $data,
                    'repairmap' => $repairmap['repairMap']
                ];
            }

            for ($i = 5; $i >= 0; $i--) {
                echo json_encode($map[$i]) . "\n";
            }
            var_dump('结果：：：：=====' . json_encode($res));
        }



        // return $data;
        return ['map' => $map, 'data' => $res];
    }

    public function map()
    {
        // $map = [
        //     [3, 4, 2, 2, 2, 2],
        //     [4, 2, 3, 3, 3, 3],
        //     [4, 5, 3, 3, 3, 3],
        //     [4, 5, 4, 4, 4, 4],
        //     [2, 4, 3, 3, 5, 5],
        //     [6, 1, 4, 4, 2, 5],
        // ];
        $map = [
            [6, 2, 3, 4, 2, 5],
            [1, 3, 1, 3, 4, 4],
            [4, 1, 4, 3, 1, 2],
            [4, 3, 3, 3, 1, 5],
            [4, 2, 3, 2, 3, 2],
            [2, 2, 5, 4, 4, 3],
        ];
        $data = $this->checkmap($map);
        $repairmap = $this->repairmap($map, $data);
        var_dump(json_encode($data), json_encode($repairmap));
    }
    //检测中间地图
    private function checkmap($map)
    {
        $res = [];
        $i = 0;
        foreach ($map as $key => $val) {
            foreach ($val as $key1 => $val1) {
                $i++;
                if (isset($map[$key][$key1])) {

                    $this->winner[] = [$key, $key1];
                    $this->adjacent($key, $key1, $val1, $map);
                    // var_dump(json_encode($this->winner));

                    if (count($this->winner) >= $this->clearnum) {
                        $res[] = ['type' => $val1, 'map' => $this->winner];
                    }

                    $this->winner = [];
                }
            }
        }

        return $res;
    }

    /**
     * 检测周围的是否一样
     * @param int $x
     * @param int $y
     * @param int $type
     * @param array $map
     * @return void
     */
    private function adjacent($x, $y, $type, &$map)
    {
        if ($x <= 5 && $y <= 5) {
            $arr = [];
            foreach (XBLY_AUX_ARR as $key => $val) {
                if ($x + $val[0] < 0 || $y + $val[1] < 0) {
                    continue;
                }

                $arr[] = [$x + $val[0], $y + $val[1]];
            }

            foreach ($arr as $key => $val) {

                if (isset($map[$val[0]][$val[1]])) {
                    // var_dump("$type ==[$x,$y]========= " . $val[0] . '==============' . $val[1] . json_encode($this->winner));
                }

                if (isset($map[$val[0]][$val[1]]) && $map[$val[0]][$val[1]] == $type) {

                    if (!in_array($val, $this->winner)) {
                        $this->winner[] = $val;
                    }
                    unset($map[$val[0]][$val[1]]);
                    $this->adjacent($val[0], $val[1], $type, $map);
                }
            }
        }
    }

    public function repairmap($map, $clearArr)
    {
        $_map = $map;
        $this->getRepairMap();
        $repairMap = $this->repairMap;
        $data = [];


        if (!empty($clearArr)) {
            foreach ($clearArr as $key => $val) {
                foreach ($val['map'] as $key1 => $val1) {
                    unset($_map[$val1[0]][$val1[1]]);
                }
            }

            $newmap = array_merge($_map, $this->repairMap);

            for ($i = 0; $i < $this->wide; $i++) {
                for ($j = 0; $j < $this->long; $j++) {
                    if (!isset($newmap[$i][$j])) {
                        for ($k = $i + 1; $k < $this->wide * 2; $k++) {
                            if (isset($newmap[$k][$j])) {
                                $type = $newmap[$k][$j];
                                unset($newmap[$k][$j]);
                                break;
                            }
                        }
                    } else {
                        $type =  $newmap[$i][$j];
                    }
                    $data[$i][$j] = $type;
                }
            }

            $_data = [];
            for ($i = $this->wide; $i < $this->wide * 2; $i++) {
                for ($j = $this->long; $j < $this->long * 2; $j++) {
                    if (isset($newmap[$i][$j])) {
                        $_data[$i - $this->wide][$j - $this->long] =  $newmap[$i][$j];
                    }
                }
            }

            $this->repairMap = $_data;
        }

        if (empty($data)) {
            $data = $map;
        }

        return ['map' => $data, 'repairMap' => $repairMap];
    }

    public function getRepairMap()
    {
        if (empty($this->repairMap)) {
            $arr = $this->InitMap(false);
            $this->repairMap = $arr['map'];
        } else {
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 6; $j++) {
                    if (!isset($this->repairMap[$i][$j])) {
                        $this->repairMap[$i][$j] = XBLY_TYPE_ALL[array_rand(XBLY_TYPE_ALL)];
                    }
                }
            }
        }
    }
}

<?php

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define("STAGE_WAIT", 0);  //等待开始
define("STAGE_START", 1);  //等待开始
define("STAGE_OLD", 2);  //解散

define('XBLY_TYPE_BLUE', 1); //蓝色
define('XBLY_TYPE_GREEN', 2); //绿色
define('XBLY_TYPE_VIOLET', 3); //紫色
define('XBLY_TYPE_RED', 4); //红色
define('XBLY_TYPE_YELLOW', 5); //黄色
define('XBLY_TYPE_BOOM', 6); //炸弹

define('XBLY_ALL_LINE', 5);
define('XBLY_MAX_BEI', 5);

define('XBLY_TYPE_ALL', [XBLY_TYPE_BLUE, XBLY_TYPE_GREEN, XBLY_TYPE_VIOLET, XBLY_TYPE_RED, XBLY_TYPE_YELLOW]);

define('XBLY_AUX_ARR', [[1, 0], [0, 1], [-1, 0], [0, -1]]); //辅助数组

define('XBLY_LINE_MAX', 5); //最大线数
define('XBLY_GEAR_MAX', 5); //最高等级

define('XBXL_UPGRADE_ALL', 45); //全部通关个数
define('XBXL_UPGRADE_ONE', 15); //一个通关

define('XBXL_WIN_ARR', [
    1 => [
        XBLY_TYPE_BLUE => [
            4 => 0.2,
            5 => 0.3,
            6 => 0.4,
            7 => 1,
            8 => 2,
            9 => 4,
            10 => 8,
            11 => 16,
            12 => 32,
            13 => 64,
            14 => 100
        ],
        XBLY_TYPE_GREEN => [
            4 => 0.3,
            5 => 0.4,
            6 => 1,
            7 => 2,
            8 => 4,
            9 => 8,
            10 => 16,
            11 => 32,
            12 => 64,
            13 => 100,
            14 => 200
        ],
        XBLY_TYPE_VIOLET => [
            4 => 0.4,
            5 => 1,
            6 => 2,
            7 => 4,
            8 => 8,
            9 => 16,
            10 => 32,
            11 => 64,
            12 => 100,
            13 => 200,
            14 => 300
        ],
        XBLY_TYPE_RED => [
            4 => 1,
            5 => 2,
            6 => 4,
            7 => 8,
            8 => 16,
            9 => 32,
            10 => 64,
            11 => 100,
            12 => 200,
            13 => 300,
            14 => 400
        ],
        XBLY_TYPE_YELLOW => [
            4 => 2,
            5 => 4,
            6 => 8,
            7 => 16,
            8 => 32,
            9 => 64,
            10 => 100,
            11 => 200,
            12 => 300,
            13 => 400,
            14 => 500
        ],
    ],
    2 => [
        XBLY_TYPE_BLUE => [
            5 => 0.3,
            6 => 0.4,
            7 => 1,
            8 => 2,
            9 => 4,
            10 => 8,
            11 => 16,
            12 => 32,
            13 => 64,
            14 => 100,
            15 => 200
        ],
        XBLY_TYPE_GREEN => [
            5 => 0.4,
            6 => 1,
            7 => 2,
            8 => 4,
            9 => 8,
            10 => 16,
            11 => 32,
            12 => 64,
            13 => 100,
            14 => 200,
            15 => 300
        ],
        XBLY_TYPE_VIOLET => [
            5 => 1,
            6 => 2,
            7 => 4,
            8 => 8,
            9 => 16,
            10 => 32,
            11 => 64,
            12 => 100,
            13 => 200,
            14 => 300,
            15 => 400
        ],
        XBLY_TYPE_RED => [
            5 => 2,
            6 => 4,
            7 => 8,
            8 => 16,
            9 => 32,
            10 => 64,
            11 => 100,
            12 => 200,
            13 => 300,
            14 => 400,
            15 => 500
        ],
        XBLY_TYPE_YELLOW => [
            5 => 4,
            6 => 8,
            7 => 16,
            8 => 32,
            9 => 64,
            10 => 100,
            11 => 200,
            12 => 300,
            13 => 400,
            14 => 500,
            15 => 600
        ],
    ],
    3 => [
        XBLY_TYPE_BLUE => [
            6 => 0.4,
            7 => 1,
            8 => 2,
            9 => 4,
            10 => 8,
            11 => 16,
            12 => 32,
            13 => 64,
            14 => 100,
            15 => 200,
            16 => 300,
            17 => 300,
        ],
        XBLY_TYPE_GREEN => [
            6 => 1,
            7 => 2,
            8 => 4,
            9 => 8,
            10 => 16,
            11 => 32,
            12 => 64,
            13 => 100,
            14 => 200,
            15 => 300,
            16 => 400,
            17 => 400,
        ],
        XBLY_TYPE_VIOLET => [
            6 => 2,
            7 => 4,
            8 => 8,
            9 => 16,
            10 => 32,
            11 => 64,
            12 => 100,
            13 => 200,
            14 => 300,
            15 => 400,
            16 => 500,
            17 => 500,
        ],
        XBLY_TYPE_RED => [
            6 => 4,
            7 => 8,
            8 => 16,
            9 => 32,
            10 => 64,
            11 => 100,
            12 => 200,
            13 => 300,
            14 => 400,
            15 => 500,
            16 => 600,
            17 => 600

        ],
        XBLY_TYPE_YELLOW => [
            6 => 8,
            7 => 16,
            8 => 32,
            9 => 64,
            10 => 100,
            11 => 200,
            12 => 300,
            13 => 400,
            14 => 500,
            15 => 600,
            16 => 700,
            17 => 700,
        ],
    ],
]);

define('SAVE_DATA', ['score' => 10000, 'beishu' => 50]); //需要存数据
define('SAVE_DATA_TYPE_SCORE', 1); //分数和倍数达到标准
define('SAVE_DATA_TYPE_FREE', 2); //免费次数
define('SAVE_DATA_TYPE_JACKPOT', 3); //中奖池
define('JACKPOT_SCORE_WIN', 10000);
define('FREE_SCORE_WIN', 100000);

class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $gameState = STAGE_WAIT; //游戏状态

    private $uid = 0; //玩家uid

    private $disRoom = -1;

    private $boomSeat = [];

    private $upgrade = 0; //当前消除的个数

    private $level = 1; //当前等级

    private $long = 4; //长

    private $wide = 4; //宽

    private $winscore = 0; //赢得分数

    private $map = [];

    private $repairMap = []; //补充地图

    private $winner = []; //可以消除的位置

    private $beishu = 0;

    private $controlinfo = [];

    private $controlinfos = [];

    private $timer = 0;
    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->roomRule = [
            'rid' => $msg['rid'],
            'gtype' => $msg['gtype'],
            'level' => $msg['level'],
            'min_gold' => $msg['min_gold'],
            'max_gold' => $msg['max_gold'],
            'doublescore' => $msg['doublescore'],
            // 'rule' => $msg['rule'],
            // 'Jackpot' => 100000,
            'vals' => $msg['vals'], //可选倍数
        ];

        $this->userEnter($msg['players']);

        $Cache =  DBInstance::selectCache($this->uid, $this->roomRule['level'], $this->roomRule['gtype']);

        if ($Cache !== false) {
            $Cache = json_decode($Cache, true);
            $this->level = $Cache['level'];
            $this->long = $Cache['long'];
            $this->wide = $Cache['wide'];
            $this->upgrade = $Cache['upgrade'];
            $this->PlayerInfo['score'] = $Cache['score'];
        }

        $this->roomInfo();
        $this->controlinfo = DBInstance::GetControlInfo('control_xbly');
    }

    /**
     * 玩家进房
     * @param int
     * */
    private function userEnter($PlayerInfo)
    {
        $data = [
            'score' => 0,
            'winNum' => 0, //连续赢得次数
            'ransport' => 0, //连续数的次数
            'line' => 1, //当前线数
            'multiple' => 1, //当前等级
            'control' => -2, //场控值
        ];

        foreach ($PlayerInfo as $key => $val) {
            $this->uid = $key;
            $this->PlayerInfo = array_merge($val, $data);
            if ($val['client_id'] != '') {
                Gateway::joinGroup($val['client_id'], 'ROOM:' . $this->roomRule['rid']);
            }
        }
    }

    private function roomInfo()
    {
        $res = [
            'map' => $this->map,
            'line' => $this->PlayerInfo['line'],
            'curgrade' => $this->PlayerInfo['multiple'],
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'max_line' => XBLY_ALL_LINE,
            'max_multiple' => XBLY_MAX_BEI,
            'gold' => $this->PlayerInfo['gold'],
            'upgrade' => $this->upgrade,
            'score' => $this->PlayerInfo['score']
        ];
        Gateway::joinGroup($this->PlayerInfo['client_id'], 'ROOM:' . $this->roomRule['rid']);

        //判断断线重连
        Logic::SendAll('Msg_XBLY_RoomInfo', $res, $this->roomRule['rid']);
    }

    /**
     * 初始地图
     *
     * @return []
     */
    private function creatMap($code = true)
    {
        $map = [];
        $x = -1;
        $y = -1;
        $control = DBInstance::GetLBControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $this->PlayerInfo['control'] = $control;

        $data = $this->GetControlXBLY($control);
        if ($control == -2 && $code) {
            return  $this->controlmap($data);
        }

        $sum = array_sum($data['list']);

        if ($code) {
            $rand = rand(1, 100);
            if ($rand <= $data['boom'][$this->level]) {
                $x = rand(0, $this->long - 1);
                $y = rand(0, $this->wide - 1);
                $this->boomSeat = [$x, $y];
            }
        }

        for ($i = 0; $i < $this->long; $i++) {
            for ($j = 0; $j < $this->wide; $j++) {
                if ($x == $i && $y == $j) {
                    $map[$i][$j] = XBLY_TYPE_BOOM;
                } else {
                    $rand = rand(1, $sum);
                    $_sum = 0;
                    foreach ($data['list'] as $key => $val) {
                        $_sum += $val;
                        if ($_sum > $rand) {
                            $map[$i][$j] = $key;
                            break;
                        }
                    }
                }
            }
        }
        if ($code) {
            $map = $this->checkbeishu($map, $data);
        }

        return $map;
    }

    private function checkbeishu($map, $control)
    {
        $data = $this->resData($map, false);
        $rand = rand(1, 100);
        if ($this->beishu >= $control['beishu'] && $rand < $control['probability']) {
            foreach ($data['clear'] as $key => $val) {
                foreach ($val['data'] as $key1 => $val1) {
                    $rand = rand(1, intval(count($val1['map']) / 3));

                    for ($i = 0; $i < $rand; $i++) {
                        $_rand = array_rand($val1['map']);
                        $res = array_diff(XBLY_TYPE_ALL, [$val1['type']]);
                        $type = $res[array_rand($res)];

                        $map[$val1['map'][$_rand][0]][$val1['map'][$_rand][1]] = $type;
                    }
                }
            }
        }

        $this->winscore = 0;
        $this->beishu = 0;

        return $map;
    }
    /**
     * 所有消息回调
     * @param array
     */

    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_XBLY_Start':
                $this->Msg_XBLY_Start($message);
                break;
            case 'Msg_XBLY_Out':
                $this->OldRoom($message);
                break;
            default: {
                    Logic::SendError($message['uid'], $message['event'], '');

                    MyTools::msg('uid:' . $message['uid'] . '-----UnKnown!!!!!!!--------' . $message['event'], true);
                    break;
                }
        }
    }

    /**
     * 开始游戏
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_XBLY_Start($msg)
    {
        if (!isset($msg['data']['line']) || !isset($msg['data']['multiple']) || $msg['data']['line'] <= 0 || $msg['data']['line'] > XBLY_LINE_MAX || $msg['data']['multiple'] <= 0 || $msg['data']['multiple'] > XBLY_GEAR_MAX) {
            Logic::SendError($msg['uid'], 'Msg_XBLY_Start', '数据格式错误');
            return;
        }

        $score = $msg['data']['line'] * $msg['data']['multiple'] * $this->roomRule['doublescore'];
        if ($score > $this->PlayerInfo['gold']) {
            Logic::SendError($msg['uid'], 'Msg_XBLY_Start', '金币不足');
            return;
        }

        $this->beishu = 0;

        $this->PlayerInfo['line'] = $msg['data']['line'];
        $this->PlayerInfo['multiple'] = $msg['data']['multiple'];
        $this->winscore = 0;
        $userscore =   $this->PlayerInfo['gold'];

        $this->changScore(-$score);
        $this->boomSeat = [];

        $map = $this->creatMap();
        $this->map = $map;
        $this->getRepairMap();
        $data = $this->resdata($map);

        $data['conscore']  = $score;
        // $data['control'] = $this->PlayerInfo['control'];
        // $data['controlinfo'] = $this->controlinfos;

        MyTools::msg(json_encode($msg['data']));
        MyTools::msg(json_encode($data));
        Logic::SendAll('Msg_XBLY_Start', $data, $this->roomRule['rid']);

        if ($this->upgrade != 0 && !empty($this->boomSeat)) {
            if ($this->upgrade % XBXL_UPGRADE_ONE == 0) {
                $this->level++;
                $this->long++; //长
                $this->wide++; //宽
            }
        }
        $num = $this->PlayerInfo['gold'] - $userscore;
        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($msg['uid'], $num);
        }

        if ($num < 0) {
            $sysjackpot = $this->SaveJackpot(0, 1);

            $jackpot_tax  = - (intval($num * $sysjackpot['probability'] / 100));
            $userscore -= $jackpot_tax;
            $this->SaveJackpot($jackpot_tax, 2);
        }

        if (abs($this->PlayerInfo['control'])  != 2) {
            Logic::InsertProfit($this->roomRule['level'], ($this->PlayerInfo['gold'] - $userscore));
        }



        if ($this->upgrade >= XBXL_UPGRADE_ALL) {
            $this->level = 1;
            $this->long = 4; //长
            $this->wide = 4; //宽
            $this->upgrade = 0;
            $rand  = rand(1, 5);
            $score = $this->SaveJackpot(0, 1);

            $_score = intval($score['jackpot'] / 100 * $rand);

            $this->changScore($_score);

            Logic::SendAll('Msg_XBLY_GrandPrix', [
                'gold' => $this->PlayerInfo['gold'],
                'score' => $_score
            ], $this->roomRule['rid']);

            $this->PlayerInfo['score'] = 0;
            $this->SaveJackpot(-$_score, 2);
        }
    }

    private function resdata($map, $code = true)
    {
        $boom = [];

        $repairMap = array_values($this->repairMap);

        if (!empty($this->boomSeat)) {
            if ($code) {
                $this->upgrade++;
            }

            $repairmap = $this->repairmap($map, [[
                'type' => XBLY_TYPE_BOOM,
                'map' => [$this->boomSeat],
                'score' => 0,
            ]]);

            $boom[] = [
                'data' => [
                    [
                        'type' => XBLY_TYPE_BOOM,
                        'map' => [$this->boomSeat],
                        'score' => 0,
                    ]

                ],
                'repairmap' => $this->repairMap,
                'newmap' => $repairmap['map']
            ];

            $map = $repairmap['map'];
        }

        $res = $this->checkall($map, $code);
        if (!empty($boom)) {
            $res = array_merge($boom, $res);
        }

        $data = [
            'clear' => $res,
            'map' => $this->map,
            'gold' => $this->PlayerInfo['gold'],
            'repairmap' => $repairMap,
        ];

        return $data;
    }

    //综合检测
    private function checkall($map, $code)
    {
        $data = $this->checkmap($map, $code);
        $repairmap = $this->repairmap($map, $data);
        $res = [];
        if (!empty($data)) {
            $res[] = [
                'data' => $data,
                'repairmap' =>  $this->repairMap,
                'newmap' => $repairmap['map'],
            ];
        }

        $map = $repairmap['map'];

        while (!empty($data)) {
            $data = $this->checkmap($map, $code);
            $repairmap = $this->repairmap($map, $data);
            $map = $repairmap['map'];
            if (!empty($data)) {
                $res[] = [
                    'data' => $data,
                    'repairmap' => $this->repairMap,
                    'newmap' => $repairmap['map'],
                ];
            }
        }
        return $res;
    }

    //检测中间地图
    private function checkmap($map, $code = true)
    {
        $res = [];
        $i = 0;
        foreach ($map as $key => $val) {
            foreach ($val as $key1 => $val1) {
                if ($val1 == 0) {
                    continue;
                }

                $i++;
                if (isset($map[$key][$key1])) {
                    $this->winner[] = [$key, $key1];
                    $this->adjacent($key, $key1, $val1, $map);
                    $count = count($this->winner);

                    if (count($this->winner) >= min(array_keys(XBXL_WIN_ARR[$this->level][$val1]))) {
                        if (!isset(XBXL_WIN_ARR[$this->level][$val1])) {
                            $count = max(array_keys(XBXL_WIN_ARR[$this->level][$val1]));
                        }

                        $score = intval($this->PlayerInfo['multiple'] * $this->PlayerInfo['line'] * XBXL_WIN_ARR[$this->level][$val1][$count] * $this->roomRule['doublescore']);

                        if ($code) {
                            $this->changScore($score);
                            $this->PlayerInfo['score'] += $score;
                            $this->winscore += $score;
                        }

                        $res[] = ['type' => $val1, 'map' => $this->winner, 'score' => $score, 'beishu' => XBXL_WIN_ARR[$this->level][$val1][$count]];
                        $this->beishu += XBXL_WIN_ARR[$this->level][$val1][$count];
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

    private function controlmap($data)
    {
        if ($this->PlayerInfo['control'] != -2) {
            return;
        }

        $map = [];
        if (rand(1, 100) <= $data['boom'][$this->level]) {
            $x = rand(0, $this->long - 1);
            $y = rand(0, $this->wide - 1);
            $this->boomSeat = [$x, $y];
            $map[$y][$x] = XBLY_TYPE_BOOM;
        }
        $temp = [XBLY_TYPE_BLUE => 1, XBLY_TYPE_GREEN => 1, XBLY_TYPE_VIOLET => 1, XBLY_TYPE_RED => 1, XBLY_TYPE_YELLOW => 1];
        $arr = $temp;
        for ($i = 0; $i < $this->wide; $i++) {
            $rand = rand(2, 3);
            $_arr = [];
            for ($k = 0; $k < $rand; $k++) {
                $key = array_rand($arr);

                $_arr[$key] = 1;
                unset($arr[$key]);
                if (empty($arr)) {
                    break;
                }
            }
            $_temp = $_arr;
            $_rand = rand(1, 3);
            $type = array_rand($_arr);
            for ($j = 0; $j < $this->long; $j++) {
                if (isset($map[$i][$j])) {
                    continue;
                }

                $map[$i][$j] = $type;
                $_rand--;
                if ($_rand <= 0) {
                    unset($_temp[$type]);
                    if (empty($_temp)) {
                        $_temp = $_arr;
                        unset($_temp[$type]);
                    }

                    $type = array_rand($_temp);
                }
            }

            if (count($arr) <= 1) {
                $arr = $temp;
                foreach ($_arr as $key => $val) {
                    unset($arr[$key]);
                }
            }
        }
        $res = [];
        for ($i = 0; $i < $this->long; $i++) {
            for ($j = 0; $j < $this->wide; $j++) {
                $res[$i][$j] = $map[$j][$i];
            }
        }
        return $res;
    }
    /**
     * 补充地图
     * @param [type] $map
     * @param [type] $clearArr
     * @return void
     */
    public function repairmap($map, $clearArr)
    {
        $_map = $map;
        $repairMap = $this->repairMap;
        $data = [];

        if (!empty($clearArr)) {
            foreach ($clearArr as $key => $val) {
                foreach ($val['map'] as $key1 => $val1) {
                    unset($_map[$val1[0]][$val1[1]]);
                }
            }

            $newmap = array_merge($_map, $this->repairMap);

            for ($i = 0; $i < $this->wide * 2; $i++) {
                for ($j = 0; $j < $this->long; $j++) {
                    if (isset($newmap[$i])) {
                        $type = 0;
                        if (!isset($newmap[$i][$j])) {
                            for ($k = $i + 1; $k < $this->wide * 2; $k++) {
                                if (isset($newmap[$k][$j])) {
                                    $type = $newmap[$k][$j];
                                    unset($newmap[$k][$j]);
                                    break;
                                }
                            }
                        } else {
                            $type = $newmap[$i][$j];
                        }

                        if ($i < $this->wide) {
                            $data[$i][$j] = $type;
                        }

                        $newmap[$i][$j] = $type;
                    }
                }
            }

            $_data = [];
            for ($i = $this->wide; $i < $this->wide * 2; $i++) {
                for ($j = 0; $j < $this->long; $j++) {
                    if (isset($newmap[$i][$j])) {
                        $_data[$i - $this->wide][$j] =  $newmap[$i][$j];
                    }
                }
            }

            if (!empty($_data)) {
                $this->repairMap = $_data;
            }
        }

        if (empty($data)) {
            $data = $map;
        }

        $this->getRepairMap();



        return ['map' => $data, 'repairMap' => $repairMap];
    }

    /**
     * 获取补充地图
     * @return void
     */
    public function getRepairMap()
    {
        if (empty($this->repairMap)) {
            $this->repairMap = $this->creatMap(false);
        } else {
            $arr = [];
            for ($i = 0; $i < $this->long; $i++) {
                for ($j = 0; $j < $this->wide; $j++) {
                    if (!isset($this->repairMap[$i][$j]) || $this->repairMap[$i][$j] == 0) {
                        $arr[$i][$j] = XBLY_TYPE_ALL[array_rand(XBLY_TYPE_ALL)];
                    } else {
                        $arr[$i][$j] = $this->repairMap[$i][$j];
                    }
                }
            }
            $this->repairMap = $arr;
        }
    }
    /**
     * 分数变化
     *
     * @param int $score
     * @return void
     */
    private function changScore($score)
    {
        $this->PlayerInfo['gold'] += $score;

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $this->uid, $score);
        }

        if ($score > 0) {
            DBInstance::IncrementWinPoint($this->uid, $score);
        }

        if ($score >= 5000000 && $this->beishu >= 20) {
            Logic::HorseLamp($this->uid, $score, $this->beishu);
        }
    }

    /**
     *对奖池的操作
     *
     * @param int $score
     * @param int $code  1查询 2修改
     * @return void
     */
    private function SaveJackpot($score, $code)
    {
        $res = DBInstance::GetGameJackpot(['gtype' => $this->roomRule['gtype'], 'level' => $this->roomRule['level']]);
        if ($code == 2) {
            DBInstance::SaveJackpot($this->roomRule['gtype'], $this->roomRule['level'], $score);
        }
        return $res;
    }
    /**
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);
        Logic::SendAll('Msg_XBLY_Out', ['gold' => $gold], $this->roomRule['rid']);
        $olddata = [
            'rid' =>  $this->roomRule['rid'],
            'win' => [],
            'palyers' => [$this->uid => $this->PlayerInfo],
            'result' => [
                'uid' => []
            ], //结算消息
            'gtype' => $this->roomRule['gtype'],
        ];

        Logic::RoomOld($olddata);
        DBInstance::seveUpdateCache($this->uid, $this->roomRule['level'], $this->roomRule['gtype'], json_encode([
            'level' => $this->level,
            'long' => $this->long,
            'wide' => $this->wide,
            'upgrade' => $this->upgrade,
            'score' => $this->PlayerInfo['score']
        ]));

        //数据存储
        return;
    }

    /**
     * 玩家重连
     * @param string
     * @param int
     */
    public function UserOnline($client_id, $uid)
    {
        $this->PlayerInfo['client_id'] = $client_id;
        Timer::del($this->timer);
        $this->roomInfo($uid);
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
        $this->timer = Timer::add(OUT_TIME, function () {
            Timer::del($this->timer);
            $this->OldRoom();
        }, [], false);
        return;
    }

    /**
     * 新增玩家
     *
     * @param [type] $msg
     * @return void
     */
    public function EnterRoom($msg)
    {
    }

    /**
     * 玩家金钱变化
     *
     * @param [type] $msg
     * @return void
     */
    public function ChangeGold($msg)
    {
        $this->PlayerInfo['gold'] = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);
    }

    /**
     * 强制解散房间
     *
     * @param [type] $msg
     * @return void
     */
    public function DisRoom($msg)
    {
        $this->disRoom = 1;
        Timer::del($this->timer);

        $this->OldRoom();
    }
    /**
     * 获取场控
     * @param [type] $control
     * @return void
     */
    private  function GetControlXBLY($control)
    {
        // if ($control > 1) {
        //     $control = 1;
        // }

        // if ($control < -1) {
        //     $control = -1;
        // }
        $res = [
            'list' =>
            [
                1 => 1000,
                2 => 960,
                3 => 920,
                4 => 880,
                5 => 840
            ],
            'beishu' => 65,
            'probability' => 50,
            'boom' => [
                1 => 10,
                2 => 15,
                3 => 20
            ],

        ];

        // $data = DBInstance::GetTableOneWord('control_xbly', 'vals', ['level' => $control]);
        $data = $this->controlinfo[$control];
        if (!empty($data)) {
            // $data = json_decode($data, true);
            if (isset($data['list']) && isset($data['boom']) && count($data['boom']) == 3) {
                $res = $data;
            }
        }
        $this->controlinfos = $res;
        return $res;
    }
}

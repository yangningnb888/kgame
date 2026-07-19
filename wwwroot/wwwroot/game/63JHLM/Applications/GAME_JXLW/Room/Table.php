<?php

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('TUOGUAN_OFF', 0);  //关闭托管
define('TUOGUAN_ON', 1);   //开启托管

define('SEAT_OFFLINE', 0); //离线
define('SEAT_ONLINE', 1); //在线

define("STAGE_WAIT", 0);  //等待开始
define("STAGE_START", 1);  //等待开始
define("STAGE_OLD", 2);  //解散

define("ALL_LINE", 9);  //总线数
define("MAX_BEI", 5);  //倍数

define('MAP_HIGH', 3); //地图高
define('MAP_WIDE', 5); //地图宽

define('LITCHI', 1); //荔枝
define('ORANGE', 2); //橘子
define('MANGO', 3); //芒果
define('WATERMELON', 4); //西瓜
define('PINEAPPLE', 5); //菠萝
define('APPLE', 6); //苹果
define('CHERRY', 7); //樱桃
define('BANANA', 8); //香蕉
define('BELL', 9); //铃铛
define('GRAPE', 10); //葡萄
define('BAR', 11); //bar
define('SENVE', 12); //七
define('DIAMONDS', 13); //钻石
define('BOX', 14); //宝箱

define('MAP_ALL', [LITCHI, ORANGE, MANGO, WATERMELON, PINEAPPLE, BAR, SENVE, DIAMONDS, APPLE, CHERRY, BANANA, BELL, GRAPE, BOX]); //地图所以的图案
define('ARR_MAP', [
    1 => [[1, 0], [1, 1], [1, 2], [1, 3], [1, 4]],
    2 => [[2, 0], [2, 1], [2, 2], [2, 3], [2, 4]],
    3 => [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4]],
    4 => [[2, 0], [1, 1], [0, 2], [1, 3], [2, 4]],
    5 => [[0, 0], [1, 1], [2, 2], [1, 3], [0, 4]],
    6 => [[1, 0], [2, 1], [2, 2], [2, 3], [1, 4]],
    7 => [[1, 0], [0, 1], [0, 2], [0, 3], [1, 4]],
    8 => [[2, 0], [2, 1], [1, 2], [0, 3], [0, 4]],
    9 => [[0, 0], [0, 1], [1, 2], [2, 3], [2, 4]],
]); //中奖地图

define('WIN_CONDITION', [
    LITCHI => [
        3 => 50, 4 => 200, 5 => 2000
    ],
    ORANGE => [
        3 => 20, 4 => 50, 5 => 300
    ],
    MANGO => [
        3 => 15, 4 => 25, 5 => 250
    ],
    WATERMELON => [
        3 => 10, 4 => 20, 5 => 200
    ],
    APPLE => [
        3 => 8, 4 => 20, 5 => 150
    ],

    CHERRY => [
        3 => 6, 4 => 20, 5 => 100
    ],
    GRAPE => [
        3 => 5, 4 => 40, 5 => 90
    ],
    BELL => [
        3 => 8, 4 => 35, 5 => 85
    ],
    BANANA => [
        3 => 6, 4 => 30, 5 => 80
    ],
    PINEAPPLE => [
        3 => 5, 4 => 15, 5 => 75

    ],
    BAR => [
        2 => 5, 3 => 100, 4 => 900, 5 => 6000
    ],
    SENVE => [
        3 => 1000, 4 => 3000, 5 => 5000
    ],

    DIAMONDS => [
        3 => 5, 4 => 10, 5 => 20
    ],
    BOX => [
        3 => 0.1, 4 => 0.3, 5 => 0.5
    ],
]); //中奖奖励

define('CONTROL_MAP', [ORANGE, MANGO, WATERMELON, PINEAPPLE, APPLE, CHERRY, BANANA, BELL, GRAPE]); //新地图随机
define('SAVE_DATA', ['score' => 500000, 'beishu' => 60]); //需要存数据
define('SAVE_DATA_TYPE_SCORE', 1); //分数和倍数达到标准
define('SAVE_DATA_TYPE_FREE', 2); //免费次数
define('SAVE_DATA_TYPE_JACKPOT', 3); //中奖池
define('JACKPOT_SCORE_WIN', 500000);
define('FREE_SCORE_WIN', 500000);

define('JXLW_TYPE_SCORE', [
    1 => ['score' => 5000, 'beishu' => 2],
    2 => ['score' => 10000, 'beishu' => 5],
    3 => ['score' => 50000, 'beishu' => 7],
    4 => ['score' => 100000, 'beishu' => 12],
]);
class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $gameState = STAGE_WAIT; //游戏状态

    private $map = []; //地图

    private $uid = 0; //玩家uid

    private $disRoom = -1;

    private $agoMap = [];

    private $freeAgoMap = [];

    private $arr = [
        'list' => [
            1 => [350, 300, 250, 0, 600],
            2 => [400, 355, 310, 0, 500],
            3 => [450, 410, 370, 330, 0],
            4 => [500, 465, 430, 600, 0],
            5 => [800, 795, 790, 400, 600],
            6 => [550, 520, 490, 460, 0],
            7 => [600, 575, 550, 525, 100],
            8 => [750, 740, 730, 720, 300],
            9 => [700, 685, 670, 300, 640],
            10 => [650, 630, 610, 300, 600],
            11 => [600, 0, 100, 0, 0],
            12 => [600, 0, 600, 600, 600],
            13 => [50, 10, 600, 10, 0],
            14 => [500, 0, 500, 0, 0]
        ],
        'beishu' => 20,
        'probability' => 50,
        'ransport' => [15, 25]
    ];

    private $remap = [];

    private $num = 0;

    private $controlinfo = [];
    private $timer = 0;
    private $map_save = [];  //读取缓存地图

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
            'Jackpot' => 100000,
            'vals' => $msg['vals'], //可选倍数

        ];
        $this->userEnter($msg['players']);

        $this->InitMap();
        $this->roomInfo();
        $this->controlinfo = DBInstance::GetControlInfo('control_jxlw');
    }

    /**
     * 玩家进房
     * @param int
     * */
    private function userEnter($PlayerInfo)
    {
        $data = [
            'score' => 0,
            'free' => 0,
            'multiple' => MAX_BEI,
            'line' => ALL_LINE,
            'winNum' => 0, //连续赢得次数
            'ransport' => rand(10, 20), //连续数的次数
            'freeData' => [],
            'freeScore' => 0,
            'control' => 0
        ];
        foreach ($PlayerInfo as $key => $val) {
            $this->uid = $key;
            $this->PlayerInfo = array_merge($val, $data);
            Gateway::joinGroup($val['client_id'], 'ROOM:' . $this->roomRule['rid']);
        }
    }

    private function roomInfo($uid = 0)
    {
        $res = [
            'map' => $this->map,
            'line' => $this->PlayerInfo['line'],
            'curgrade' => $this->PlayerInfo['multiple'],
            'free' => $this->PlayerInfo['free'],
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'max_line' => ALL_LINE,
            'max_multiple' => MAX_BEI,
            'gold' => $this->PlayerInfo['gold'],
        ];

        Logic::SendRight($this->uid, 'Msg_JXLW_RoomInfo', $res);
    }

    /**
     * 游戏流程
     *
     * @param [type] $gameState
     * @return void
     */
    private function procedure($gameState)
    {
        $this->gameState = $gameState;
        switch ($this->gameState) {
            case STAGE_START:
                $this->InitMap();
                break;
            case STAGE_OLD:
                $this->OldRoom();
                break;
        }
    }

    /**
     * 初始地图
     *
     * @return void
     */
    public function InitMap($code = true)
    {
        $this->map = [];
        $this->remap = $this->dealcontrol();
        for ($i = 0; $i < MAP_HIGH; $i++) {
            for ($j = 0; $j < MAP_WIDE; $j++) {
                $type = $this->gradeMap($j);
                $this->map[$i][] = $type;
            }
        }

        $data = $this->calculationWin(['line' => $this->PlayerInfo['line'], 'multiple' => $this->PlayerInfo['multiple']], false);
        if ($code) {
            $get_bx = false;
            foreach ($data['res']['win'] as $key => $value) {
                if ($value['type'] == BOX && $value['num'] > 3) {
                    $get_bx = true;
                    break;
                }
            }

            if ($data['beishu'] > $this->arr['beishu'] || $get_bx) {
                if (rand(1, 100) > $this->arr['probability'] || $get_bx) {
                    $this->InitMap(false);
                    return;
                }
            }
        }

        //配置地图
        if (!empty($this->map_save) && in_array($this->map_save['logo'], MAP_ALL)) {
            if (!empty($data['res']['win'])) {
                $_res = array_shift($data['res']['win']);
                $line = $_res['line'];
            } else {
                $line = rand(1, 9);
            }

            if ($this->map_save['logo'] == DIAMONDS || $this->map_save['logo'] == SENVE) {
                for ($k = 0; $k < 5; $k++) {
                    if ($k < $this->map_save['num']) {
                        $this->map[ARR_MAP[$line][$k][0]][$k] = $this->map_save['logo'];
                    } else {
                        $this->map[ARR_MAP[$line][$k][0]][$k] = CONTROL_MAP[array_rand(CONTROL_MAP)];
                    }
                }
            } else {
                $_rand = [0, 30, 20, 20, 10];
                $_new_logo = array_diff(CONTROL_MAP, [$this->map_save['logo']]);
                for ($k = 0; $k < 5; $k++) {
                    if ($k < $this->map_save['num']) {
                        if (rand(1, 100) <= $_rand[$k]) {
                            $this->map[ARR_MAP[$line][$k][0]][$k] = BAR;
                        } else {
                            $this->map[ARR_MAP[$line][$k][0]][$k] = $this->map_save['logo'];
                        }
                    } else {
                        $this->map[ARR_MAP[$line][$k][0]][$k] = $_new_logo[array_rand($_new_logo)];
                    }
                }
            }
        }
    }

    public function dealcontrol()
    {
        $arr = [];
        foreach ($this->arr['list'] as $key => $val) {
            foreach ($val as $key1 => $val1) {
                $arr[$key1][$key] = $val1;
            }
        }
        return $arr;
    }

    private function gradeMap($row)
    {
        $total = array_sum($this->remap[$row]);
        $rand = rand(1, $total);
        $num = 0;
        $res = -1;
        foreach ($this->remap[$row] as $key => $val) {
            $num += $val;
            if ($num >= $rand) {
                $res = $key;
                break;
            }
        }

        return $res;
    }

    /**
     * 所有消息回调
     * @param array
     */

    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_JXLW_Start':
                $this->Msg_JXLW_Start($message);
                break;
            case 'Msg_JXLW_Out':
                $this->OldRoom($message);
                break;
            default:
            {
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
    private function Msg_JXLW_Start($msg)
    {
        if (!is_int($msg['data']['multiple']) || $msg['data']['multiple'] <= 0 || $msg['data']['multiple'] > MAX_BEI) {
            Logic::SendError($msg['uid'], 'Msg_JXLW_Start', '下注倍数错误');
            return;
        }

        $score = 0;
        if ($this->PlayerInfo['free'] <= 0) {
            $score = $msg['data']['multiple'] * $msg['data']['line'] * $this->roomRule['doublescore'];
            DBInstance::SetUserBet($this->uid, $this->roomRule['gtype'], $this->roomRule['level'], $score, json_encode($msg['data']));
        }

        if ($score > $this->PlayerInfo['gold']) {
            Logic::SendError($msg['uid'], 'Msg_JXLW_Start', '金币不足');
            return;
        }

        $this->changScore(-$score);

        if ($score <= 0) {
            $this->PlayerInfo['free']--;
        }

        $this->agoMap = $this->map;
        if ($score > 0 || empty($this->freeAgoMap)) {
            $this->freeAgoMap = $this->map;
            $this->PlayerInfo['line'] = $msg['data']['line'];
            $this->PlayerInfo['multiple'] = $msg['data']['multiple'];
        }

        $this->map_save = DBInstance::GetControlMap($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $control = DBInstance::GetLBControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $this->GetControlJXLW($control);

        $this->PlayerInfo['control'] = $control;

        $this->procedure(STAGE_START);

        $_data = $this->calculationWin($msg['data']);
        if ($_data['allScore'] <= 0 && $this->PlayerInfo['ransport'] <= 0) {
            $this->newMap();
            $_data = $this->calculationWin($msg['data']);
        }

        $data = $_data['res'];
        $data['conscore'] = $score;
        $data['map'] = $this->map;

        $beishu = $_data['allScore'] / ($this->PlayerInfo['multiple'] * $this->PlayerInfo['line'] * $this->roomRule['doublescore']);

        $data['type'] = $this->typeScore($beishu, $_data['allScore']);
        // $data['control'] = $this->PlayerInfo['control'];
        // $data['controlinfo'] = $this->arr;

        MyTools::msg(json_encode($msg['data']));
        MyTools::msg(json_encode($data));
        Logic::SendAll('Msg_JXLW_Start', $data, $this->roomRule['rid']);
        if ($score <= 0 || $this->PlayerInfo['free'] > 0) {
            $this->PlayerInfo['freeData'][] = $data;
            $this->PlayerInfo['freeScore'] += $_data['allScore'];
        }

        if ($data['score'] < $score) {
            $sysjackpot = $this->SaveJackpot(0, 1);
            $jackpot_tax = ($score - $data['score']) * ($sysjackpot['probability'] / 100);
            $this->SaveJackpot($jackpot_tax, 2);
            $this->num += $jackpot_tax;
        }

        $this->saveData($_data['allScore'], $_data['beishu'], $data);

        $_score = $_data['allScore'] - $data['jackpot'] - $score;

        if ($_data['allScore'] > 0) {
            $this->PlayerInfo['ransport'] = rand($this->arr['ransport'][0], $this->arr['ransport'][1]);
        } else {
            $this->PlayerInfo['ransport']--;
        }

        if (abs($this->PlayerInfo['control']) != 2 && empty($this->map_save)) {
            if (!isset($jackpot_tax)) {
                $jackpot_tax = 0;
            }
            Logic::InsertProfit($this->roomRule['level'], ($data['score'] - $score + $jackpot_tax));
        }

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($this->uid, $_score + $data['jackpot']);
        }

        if ($this->disRoom == 1 && $this->PlayerInfo['free'] <= 0) {
            $this->OldRoom();
        }
    }

    //场控新地图
    private function newMap()
    {
        /*$rands = array_keys(ARR_MAP);

        for ($i = 0; $i < 10; $i++) {
            if (empty($rands)) {
                $arr = CONTROL_MAP;
                $type = array_rand($arr);
                $type = $arr[$type];
                break;
            }

            $_rand = array_rand($rands);
            $rand = $rands[$_rand];
            $type = $this->map[ARR_MAP[$rand][0][0]][ARR_MAP[$rand][0][1]];
            if ($type != DIAMONDS && $type != BOX && $type != BAR && $type != SENVE && $type != LITCHI) {
                break;
            } else {
                unset($rands[$_rand]);
            }
        }

        $arr = [];

        foreach (WIN_CONDITION[$type] as $key => $val) {
            if ($val < 9) {
                continue;
            }
            if ($key == 5) {
                // $rand5 = rand(0, 100);
                // if ($rand5 <= 80) {
                //     break;
                // 
                continue;
            }

            $arr[$key] = 1;
        }

        $rands = array_rand($arr);

        foreach (ARR_MAP[$rand] as $key => $val) {
            $rands--;
            $this->map[$val[0]][$val[1]] = $type;
            if ($rands <= 0) {
                break;
            }
        }*/

        $line = rand(1, 9);
        $type = CONTROL_MAP[array_rand(CONTROL_MAP)];
        $other = array_diff(CONTROL_MAP, [$type]);
        $num = rand(3, 4);

        foreach (ARR_MAP[$line] as $key => $val) {
            if ($num > 0) {
                $this->map[$val[0]][$val[1]] = $type;
            } else {
                $this->map[$val[0]][$val[1]] = $other[array_rand($other)];
            }
            $num--;
        }
    }

    /**
     * 计算中奖线数
     *
     * @return void
     */
    private function calculationWin($data, $code = true)
    {
        $res = [];
        $score = 0;
        $jackpot = 0; //从奖池中的奖金
        $curfree = 0; //当时免费次数
        $beishu = 0;
        $allScore = 0;
        foreach (ARR_MAP as $key => $val) {
            if ($key > $data['line']) {
                continue;
            }

            $graphical = 0;
            $_res = 1;
            $_free = 0;
            $typecode = true;
            foreach ($val as $key1 => $val1) {
                if ($this->map[$val1[0]][$val1[1]] == DIAMONDS) {
                    $_free++;
                }
                if ($graphical == 0) {
                    $graphical = $this->map[$val1[0]][$val1[1]];
                    continue;
                }

                if ($typecode && ($this->map[$val1[0]][$val1[1]] == $graphical || ($graphical != SENVE && $graphical != DIAMONDS && $this->map[$val1[0]][$val1[1]] == BAR))) {
                    $_res++;
                } else {
                    $typecode = false;
                }
            }

            if ($_res >= min(array_keys(WIN_CONDITION[$graphical])) && $graphical != DIAMONDS) {
                $scoreArr = $this->score($_res, $graphical, $data['multiple'], $code);
                $score += $scoreArr['score'];
                $jackpot += $scoreArr['jackpot'];
                $curfree += $scoreArr['curfree'];
                // $multiple = 0;
                // if ($graphical < DIAMONDS) {
                $multiple = WIN_CONDITION[$graphical][$_res];
                // }

                $beishu += $multiple;
                $allScore += $scoreArr['allScore'];
                $res['win'][] = ['num' => $_res, 'type' => $graphical, 'line' => $key, 'multiple' => $multiple];
            } elseif ($_free >= min(array_keys(WIN_CONDITION[DIAMONDS]))) {
                $scoreArr = $this->score($_free, DIAMONDS, $data['multiple'], $code);
                $curfree += $scoreArr['curfree'];
                // $multiple = 0;
                $res['win'][] = ['num' => $_free, 'type' => DIAMONDS, 'line' => $key, 'multiple' => 0];
            }
        }

        if (empty($res)) {
            $res['win'] = [];
        }

        $res['score'] = $score;
        $res['free'] = $this->PlayerInfo['free'];
        $res['gold'] = $this->PlayerInfo['gold'];
        $res['jackpot'] = $jackpot;
        $res['curfree'] = $curfree;
        return ['res' => $res, 'beishu' => $beishu, 'allScore' => $allScore];
    }

    /**
     * 分数计算
     *
     * @param [type] $num 中了几个
     * @param [type] $type 中的类型
     *
     * @return int
     */
    private function score($num, $type, $multiple, $code)
    {
        $score = 0;
        $curfree = 0;
        $jackpot = 0;
        if ($type == DIAMONDS) {
            if ($code) {
                $this->PlayerInfo['free'] += WIN_CONDITION[$type][$num];
            }

            $curfree = WIN_CONDITION[$type][$num];
        } elseif ($type == BOX) {
            $sysjackpot = $this->SaveJackpot(0, 1);
            $this->roomRule['Jackpot'] = $sysjackpot['jackpot'];
            if ($code) {
                $jackpot = empty($this->map_save['jackpot']) ? intval(WIN_CONDITION[$type][$num] * $this->roomRule['Jackpot']) : $this->map_save['jackpot'];
            }
            $this->roomRule['Jackpot'] = $this->SaveJackpot(-$jackpot, 2);
        } else {
            $score = WIN_CONDITION[$type][$num];
        }
        $score *= $multiple * $this->roomRule['doublescore'];

        $allScore = $score + $jackpot;

        if ($code) {
            $this->changScore($allScore);
        }

        return ['score' => $score, 'jackpot' => $jackpot, 'curfree' => $curfree, 'allScore' => $allScore];
    }

    /**
     * 分数变化
     *
     * @param int $score
     * @return void
     */
    private function changScore($score)
    {
        $this->PlayerInfo['score'] += $score;
        $this->PlayerInfo['gold'] += $score;

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $this->uid, $score);
        }

        if ($score > 0) {
            DBInstance::IncrementWinPoint($this->uid, $score);
        }
    }

    /**
     *获得大小奖
     */
    private function typeScore($beishu, $score)
    {
        foreach (JXLW_TYPE_SCORE as $key => $val) {
            if ($beishu < $val['beishu'] || $score < $val['score']) {
                $type = $key;
                break;
            }
        }

        if (!isset($type)) {
            $type = 4;
        }

        return $type;
    }

    /**
     * 解散房间
     * @param bool
     */
    public function OldRoom($message = [])
    {
        if ($this->PlayerInfo['free'] > 0) {
            Logic::SendError($this->uid, 'Msg_JXLW_Out', '正在免费旋转中');
            return;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);
        DBInstance::DelUserBet($this->uid);
        Logic::SendAll('Msg_JXLW_Out', ['gold' => $gold], $this->roomRule['rid']);
        $olddata = [
            'rid' => $this->roomRule['rid'],
            'win' => [],
            'palyers' => [$this->uid => $this->PlayerInfo],
            'result' => [
                'uid' => []
            ], //结算消息
            'gtype' => $this->roomRule['gtype'],
        ];
        Logic::RoomOld($olddata);
        return;
    }

    /**
     * 玩家重连
     * @param string
     * @param int
     */
    public function UserOnline($client_id, $uid)
    {
        Gateway::joinGroup($client_id, 'ROOM:' . $this->roomRule['rid']);
        Timer::del($this->timer);
        $this->PlayerInfo['client_id'] = $client_id;
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
     * 回放存储
     *
     * @param [type] $score
     * @param [type] $beishu
     * @param [type] $data
     * @return void
     */
    private function saveData($score, $beishu, $data)
    {
        $type = 0;
        $map = $this->agoMap;

        if ($this->PlayerInfo['freeScore'] > 0 && $this->PlayerInfo['free'] <= 0) {
            $this->PlayerInfo['freeScore'] = 0;
            $this->PlayerInfo['freeData'] = [];
            $score = $this->PlayerInfo['freeScore'];
        }

        if ($score < SAVE_DATA['score'] || $score / ($this->PlayerInfo['multiple'] * $this->PlayerInfo['line'] * $this->roomRule['doublescore']) < SAVE_DATA['beishu']) {
            return;
        }

        if ($data['jackpot'] > 0) {
            $type = SAVE_DATA_TYPE_JACKPOT;
            $data['free'] = 0;
            $data['curfree'] = 0;
        } elseif ($this->PlayerInfo['freeScore'] > 0 && $this->PlayerInfo['free'] <= 0) {
            $type = SAVE_DATA_TYPE_FREE;
            $data = $this->PlayerInfo['freeData'];
            $score = $this->PlayerInfo['freeScore'];
            $map = $this->freeAgoMap;

            $this->PlayerInfo['freeScore'] = 0;
            $this->PlayerInfo['freeData'] = [];
        } else {
            $type = SAVE_DATA_TYPE_SCORE;
            $data['free'] = 0;
            $data['curfree'] = 0;
        }

        if ($type > 0) {
            $_data = [];
            $_data['uid'] = $this->uid;
            $_data['line'] = $this->PlayerInfo['line'];
            $_data['doublescore'] = $this->roomRule['doublescore'];
            $_data['curgrade'] = $this->PlayerInfo['multiple'];
            $_data['map'] = $map;

            if ($type == SAVE_DATA_TYPE_FREE) {
                $_data['data'] = $data;
            } else {
                $_data['data'][] = $data;
            }

            $res = [
                'type' => $type,
                'nickname' => $this->PlayerInfo['nickname'],
                'gtype' => $this->roomRule['gtype'],
                'level' => $this->roomRule['level'],
                'vals' => json_encode($_data),
                'score' => $score,
                'created' => MyTools::GET_NOW(),
                'playnum' => 0
            ];

            DBInstance::SaveData('game_back', $res);
        }

        if ($score >= 5000000 && $beishu >= 20) {
            Logic::HorseLamp($this->uid, $score, $beishu);
        }
    }

    /**
     *对奖池的操作
     *
     * @param int $score
     * @param int $code 1查询 2修改
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
        if ($this->PlayerInfo['free'] <= 0) {
            $this->OldRoom();
        }
        Timer::del($this->timer);
    }

    /**
     * 获取场控
     * @param [type] $control
     * @return void
     */
    private function GetControlJXLW($control)
    {
        // if ($control > 1) {
        //     $control = 1;
        // }
        // if ($control < -1) {
        //     $control = -1;
        // }

        // $data = DBInstance::GetTableOneWord('control_jxlw', 'vals', ['level' => $control]);
        $data = $this->controlinfo[$control];
        if (!empty($data)) {
            // $data = json_decode($data, true);

            if (isset($data['ransport']) && is_array($data['ransport']) && count($data['ransport']) == 2 && isset($data['probability']) && is_int($data['probability']) && isset($data['list']) && isset($data['beishu']) && is_array($data['list']) && is_int($data['beishu'])) {
                $iscode = true;
                foreach ($data['list'] as $key => $val) {
                    if (count($val) != 5 || !in_array($key, MAP_ALL)) {
                        $iscode = false;
                        break;
                    }
                }

                if (count($data['list']) == 14 && $iscode) {
                    $this->arr = $data;
                }
            }
        }
    }
}

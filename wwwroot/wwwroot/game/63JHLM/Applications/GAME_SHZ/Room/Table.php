<?php

use Workerman\Lib\Timer;

date_default_timezone_set("Asia/Shanghai");
require_once __DIR__ . "/back.php";

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;

define('SEAT_OFFLINE', 0); //离线
define('SEAT_ONLINE', 1); //在线

define("STAGE_WAIT", 0);  //等待开始
define("STAGE_START", 1);  //等待开始
define("STAGE_OLD", 2);  //解散

define("MAX_BEI", 5);  //倍数
define('MAX_LINE', 9); //总线数

define('MAP_HIGH', 3); //地图高
define('MAP_WIDE', 5); //地图宽

define('SHZ_TYPE_REPLACES', 1); //水浒传  退出
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
define('MAP_SET_ALL', [SHZ_TYPE_REPLACES, SHZ_TYPE_LOYALTY, SHZ_TYPE_GO, SHZ_TYPE_SONG, SHZ_TYPE_LING, SHZ_TYPE_LU, SHZ_TYPE_KNIFE, SHZ_TYPE_GUN, SHZ_TYPE_AXE, SHZ_TYPE_HERO, SHZ_TYPE_ARMS]); //地图所以的图案

define('ARR_MAP', [
    1 => [
        1 => [[0, 1], [1, 1], [2, 1], [3, 1], [4, 1]],
        2 => [[4, 1], [3, 1], [2, 1], [1, 1], [0, 1]],
    ],
    2 => [
        1 => [[0, 2], [1, 2], [2, 2], [3, 2], [4, 2]],
        2 => [[4, 2], [3, 2], [2, 2], [1, 2], [0, 2]],
    ],
    3 => [
        1 => [[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]],
        2 => [[4, 0], [3, 0], [2, 0], [1, 0], [0, 0]],
    ],
    4 => [
        1 => [[0, 2], [1, 1], [2, 0], [3, 1], [4, 2]],
        2 => [[4, 2], [3, 1], [2, 0], [1, 1], [0, 2]],
    ],
    5 => [
        1 => [[0, 0], [1, 1], [2, 2], [3, 1], [4, 0]],
        2 => [[4, 0], [3, 1], [2, 2], [1, 1], [0, 0]],
    ],
    6 => [
        1 => [[0, 2], [1, 2], [2, 1], [3, 2], [4, 2]],
        2 => [[4, 2], [3, 2], [2, 1], [1, 2], [0, 2]],
    ],
    7 => [
        1 => [[0, 0], [1, 0], [2, 1], [3, 0], [4, 0]],
        2 => [[4, 0], [3, 0], [2, 1], [1, 0], [0, 0]],
    ],
    8 => [
        1 => [[0, 1], [1, 0], [2, 0], [3, 0], [4, 1]],
        2 => [[4, 1], [3, 0], [2, 0], [1, 0], [0, 1]],
    ],
    9 => [
        1 => [[0, 1], [1, 2], [2, 2], [3, 2], [4, 1]],
        2 => [[4, 1], [3, 2], [2, 2], [1, 2], [0, 1]],
    ],
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
        3 => 7, 4 => 20, 5 => 100
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

define('SPE_WIN_CONDITION_ARR', [
    SHZ_TYPE_REPLACES, //水浒传
    SHZ_TYPE_LOYALTY, //忠义堂
    SHZ_TYPE_GO, //替天行道
    SHZ_TYPE_SONG, //宋江
    SHZ_TYPE_LING, //林冲
    SHZ_TYPE_LU, //鲁智深
    SHZ_TYPE_KNIFE, //大刀
    SHZ_TYPE_GUN, //枪
    SHZ_TYPE_AXE, //斧头
]);
define('SAVE_DATA_SHZ', ['score' => 1000000, 'beishu' => 20]); //需要存数据
define('GAME_BIG', 12);
define('GAME_MIDDLE', 7);
define('GAME_SMALL', 6);

define('GAME_NUM', 4); //小游戏的中奖个数

define('GAME_CONDITION_BASE', [
    SHZ_TYPE_LOYALTY => 200,
    SHZ_TYPE_GO => 100,
    SHZ_TYPE_SONG => 70,
    SHZ_TYPE_LING => 50,
    SHZ_TYPE_LU => 20,
    SHZ_TYPE_KNIFE => 10,
    SHZ_TYPE_GUN => 5,
    SHZ_TYPE_AXE => 2
]); //小游戏中奖奖励
define('GAME_CONDITION_THREE', 20); //连续三个
define('GAME_CONDITION_FROE', 500); //连续四个

define('GAME_CONDITION', [3 => 1, 4 => 2, 5 => 3, 15 => 27]); //进入小游戏的把数

define('BRESHU_BIG', 2); //大
define('BRESHU_SMALL', 2); //小
define('BRESHU_MIDDLE', 6); //中
define('BRESHU_DUI_BIG', 4); //大两个数一样的
define('BRESHU_DUI_SMALL', 4); //小两个数一样的

define('SHZ_TYPE_SCORE', [
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

    private $maps = [];

    private $history = [];

    private $remap = [];

    private $gamewin = [
        SHZ_TYPE_REPLACES => 40, SHZ_TYPE_LOYALTY => 5, SHZ_TYPE_GO => 5, SHZ_TYPE_SONG => 5, SHZ_TYPE_LING => 5, SHZ_TYPE_LU => 10, SHZ_TYPE_KNIFE => 10, SHZ_TYPE_GUN => 10, SHZ_TYPE_AXE => 10
    ];

    private $prize = 20;

    private $controlinfo = [];

    private $controlinfos = [];

    private $ransport = [20, 25];

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
            'vals' => $msg['vals'], //可选倍数
        ];

        $this->controlinfo = DBInstance::GetControlInfo('control_shz');
        $this->userEnter($msg['players']);
        $this->InitMap();
        $this->roomInfo();
    }

    /**
     * 玩家进房
     * @param int
     * */
    private function userEnter($PlayerInfo)
    {
        $data = [
            'score' => 0,
            'multiple' => MAX_BEI,
            'winNum' => 0, //赢得次数
            'ransport' => rand($this->ransport[0], $this->ransport[1]), //输的次数
            'game' => 0, //小游戏标志
            'gameinfo' => [],
            'resinfo' => [],
            'control' => -2,
        ];

        foreach ($PlayerInfo as $key => $val) {
            $this->uid = $key;
            $this->PlayerInfo = array_merge($val, $data);
        }
    }

    /**
     * 刷新房间
     *
     * @param integer $uid
     * @return void
     */
    private function roomInfo()
    {
        $res = [
            'map' => $this->map,
            'curgrade' => $this->PlayerInfo['multiple'],
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'max_multiple' => MAX_BEI,
            'gold' => $this->PlayerInfo['gold'],
            'history' => $this->history,
            'resinfo' => $this->PlayerInfo['resinfo'],
            'game' => $this->PlayerInfo['game'],
            'score' => $this->PlayerInfo['score'],
        ];
        Gateway::joinGroup($this->PlayerInfo['client_id'], 'ROOM:' . $this->roomRule['rid']);
        //判断断线重连
        Logic::SendAll('Msg_SHZ_RoomInfo', $res, $this->roomRule['rid']);
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
    private function InitMap($code = true)
    {
        $this->map = [];
        $this->maps = [];
        $control = DBInstance::GetLBControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $this->PlayerInfo['control'] = $control;

        $vals = $this->GetControlSHZ();
        $this->remap = $this->dealcontrol($vals);

        $this->gamewin = $vals['game'];

        for ($i = 0; $i < MAP_WIDE; $i++) {
            for ($j = 0; $j < MAP_HIGH; $j++) {
                if (!empty($this->map_save) && $this->map_save['num'] >= 15 && in_array($this->map_save['logo'], MAP_SET_ALL)) {
                    if ($this->map_save['logo'] == SHZ_TYPE_HERO) {
                        $type = WIN_HERO[array_rand(WIN_HERO)];
                    } elseif ($this->map_save['logo'] == SHZ_TYPE_ARMS) {
                        $type = WIN_ARMS[array_rand(WIN_ARMS)];
                    } else {
                        $type = $this->map_save['logo'];
                    }
                } else {
                    $type = $this->gradeMap($i);
                }

                $this->map[$i][] = $type;
            }
        }

        $this->PlayerInfo['game'] = 0;

        //配置地图
        if (!empty($this->map_save) && $this->map_save['num'] <= 5 && $this->map_save['num'] > 0 && in_array($this->map_save['logo'], MAP_ALL)) {
            $_line = ARR_MAP[rand(1, 9)][rand(1, 2)];
            $map_other = array_diff(MAP_ALL, [SHZ_TYPE_REPLACES, $this->map_save['logo']]);
            for ($k = 0; $k < 5; $k++) {
                if ($k < $this->map_save['num']) {
                    $this->map[$_line[$k][0]][$_line[$k][1]] = $this->map_save['logo'];
                } elseif ($this->map[$_line[$k][0]][$_line[$k][1]] == $this->map_save['logo'] || $this->map[$_line[$k][0]][$_line[$k][1]] == SHZ_TYPE_REPLACES) {
                    $this->map[$_line[$k][0]][$_line[$k][1]] = $map_other[array_rand($map_other)];
                }
            }
        }

        for ($i = 0; $i < MAP_WIDE; $i++) {
            for ($j = 0; $j < MAP_HIGH; $j++) {
                $this->maps[] = $this->map[$i][$j];
            }
        }

        if ($code) {
            $data = $this->calculationWin(['multiple' => $this->PlayerInfo['multiple']], false);

            if ($this->PlayerInfo['ransport'] <= 0) {
                if ($data['score'] < $this->PlayerInfo['multiple'] * MAX_LINE * $this->roomRule['doublescore']) {
                    $this->newMap();
                }
                $this->PlayerInfo['ransport'] = rand($this->ransport[0], $this->ransport[1]);
            }

            if ($data['beishu'] >= $vals['beishu'] && $vals['probability'] > rand(1, 100)) {
                $this->InitMap();
            }
        }
    }

    //场控新地图
    private function newMap()
    {
        $rands = ARR_MAP;

        for ($i = 0; $i < 10; $i++) {
            if (empty($rands)) {
                $arr = CONTROL_MAP;
                $type = array_rand($arr);
                $type = $arr[$type];
                break;
            }
            $_rand = array_rand(ARR_MAP);
            $rand = array_rand(ARR_MAP[$_rand]);
            $type = $this->map[ARR_MAP[$_rand][$rand][0][0]][ARR_MAP[$_rand][$rand][0][1]];
            if ($type != SHZ_TYPE_REPLACES && $type != SHZ_TYPE_LOYALTY && $type != SHZ_TYPE_GO && $type != SHZ_TYPE_SONG) {
                break;
            } else {
                unset($rands[$_rand][$rand]);
            }
        }

        $arr = [];

        foreach (WIN_CONDITION[$type] as $key => $val) {
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

        foreach (ARR_MAP[$_rand][$rand] as $key => $val) {
            $rands--;
            $this->map[$val[0]][$val[1]] = $type;
            if ($rands <= 0) {
                break;
            }
        }
    }

    /**
     * 处理场控数据
     * @param [type] $arr
     * @return void
     */
    public function dealcontrol($arr)
    {
        $data = [];
        foreach ($arr['list'] as $key => $val) {
            foreach ($val as $key1 => $val1) {
                $data[$key1][$key] = $val1;
            }
        }
        return $data;
    }

    /**
     * 根据场控值得到参数
     * @param [type] $row
     * @return void
     */
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
            case 'Msg_SHZ_Start':
                $this->Msg_SHZ_Start($message);
                break;
            case 'Msg_SHZ_Than':
                $this->Msg_SHZ_Than($message);
                break;
            case 'Msg_SHZ_Collect':
                $this->Msg_SHZ_Collect($message);
                break;
            case 'Msg_SHZ_Game':
                $this->newGame($message);
                break;
            case 'Msg_Game_Back_List':
                back::Msg_Game_Back_List($message, $this->roomRule['gtype']);
                break;
            case 'Msg_Game_Back_Info':
                back::Msg_Game_Back_Info($message, $this->roomRule['gtype']);
                break;
            case 'Msg_Game_Jackpot':
                back::Msg_Game_Jackpot($message, $this->roomRule['gtype'], $this->roomRule['level']);
                break;
            case 'Msg_SHZ_Out':
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
    private function Msg_SHZ_Start($msg)
    {
        if (!isset($msg['data']['multiple']) || !is_int($msg['data']['multiple']) || $msg['data']['multiple'] <= 0 || $msg['data']['multiple'] > MAX_BEI) {
            Logic::SendError($msg['uid'], 'Msg_SHZ_Start', '下注档位错误');
            return;
        }

        if ($this->PlayerInfo['game'] > 0) {
            Logic::SendError($msg['uid'], 'Msg_SHZ_Start', '正在进行小游戏');
            return;
        }

        if ($this->PlayerInfo['score'] > 0) {
            $this->Msg_SHZ_Collect(['uid' => $msg['uid']], false);
            $this->PlayerInfo['score'] = 0;
            $this->PlayerInfo['winNum']++;
        }

        $score = $msg['data']['multiple'] * MAX_LINE * $this->roomRule['doublescore'];
        $this->PlayerInfo['gameinfo'] = [];
        DBInstance::SetUserBet($this->uid, $this->roomRule['gtype'], $this->roomRule['level'], $score, $msg['data']['multiple']);
        if ($score > $this->PlayerInfo['gold']) {
            Logic::SendError($msg['uid'], 'Msg_SHZ_Start', '金币不足');
            return;
        }

        $this->PlayerInfo['multiple'] = $msg['data']['multiple'];

        $this->agoMap = $this->map;
        $this->map_save = DBInstance::GetControlMap($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $this->procedure(STAGE_START);

        $this->changScore(-$score);

        $data = $this->calculationWin($msg['data']);

        $data['conscore'] = $score;

        $beishu = $data['score'] / (MAX_LINE * $this->roomRule['doublescore'] * $this->PlayerInfo['multiple']);

        $data['type'] = $this->typeScore($beishu, $data['score']);

        if ($data['score'] - $data['conscore']) {
            $this->PlayerInfo['ransport']--;
        } else {
            $this->PlayerInfo['ransport'] = rand($this->ransport[0], $this->ransport[1]);
        }

        if (abs($this->PlayerInfo['control']) != 2 && $data['score'] == 0 && empty($this->map_save)) {
            Logic::InsertProfit($this->roomRule['level'], -$score);
        }

        if ($this->roomRule['level'] != 1 && $data['score'] == 0) {
            DBInstance::IncrementUserGet($msg['uid'], -$score);
        }
        // $data['control'] = $this->PlayerInfo['control'];
        // $data['controlinfo'] = $this->controlinfos;
        $this->PlayerInfo['resinfo'] = $data;

        MyTools::msg(json_encode($msg['data']));
        MyTools::msg(json_encode($data));
        Logic::SendAll('Msg_SHZ_Start', $data, $this->roomRule['rid']);

        $this->saveData();

        if ($this->disRoom == 1 && $this->PlayerInfo['game'] <= 0) {
            $this->OldRoom();
        }
    }

    /**
     * 小游戏
     *
     * @return void
     */
    private function newGame($msg)
    {
        if ($this->PlayerInfo['game'] < 0) {
            Logic::SendError($msg['uid'], 'Msg_SHZ_Game', '没有可进入的小游戏');
            return;
        }
        // $beishup = $this->PlayerInfo['resinfo']['beishu'];

        $score = 0;
        $data = [];
        $beishu = 0;
        while ($this->PlayerInfo['game'] > 0) {
            $rand = rand(0, array_sum($this->gamewin));
            $_num = 0;
            foreach ($this->gamewin as $key => $val) {
                $_num += $val;
                if ($_num >= $rand) {
                    $outType = $key;
                    break;
                }
            }

            $innerTypes = [];
            $_score = 0;
            $_beishu = 0;
            $mapAll = MAP_ALL;
            unset($mapAll[0]);
            //不出高倍数的图标

            if ($outType == SHZ_TYPE_LOYALTY || $outType == SHZ_TYPE_GO || $outType == SHZ_TYPE_SONG) {
                unset($mapAll[array_search($outType, $mapAll)]);
            }

            $temp = [];
            for ($i = 0; $i < GAME_NUM; $i++) {
                //防止出现4个相同图标
                if (!empty($temp) && max($temp) >= 3) {
                    unset($mapAll[key($temp)]);
                }

                $rand = array_rand($mapAll);
                $innerTypes[] = $mapAll[$rand];
                if (isset($temp[$rand]) && $temp[$rand] >= 3) {
                    $rand++;
                    if (!in_array($rand, $mapAll)) {
                        $rand -= 2;
                    }
                }

                if (!isset($temp[$rand])) {
                    $temp[$rand] = 0;
                }

                $temp[$rand]++;
            }

            if (count($temp) == 1) {
                unset($mapAll[$rand]);
                $innerTypes[rand(1, GAME_NUM - 2)] = $mapAll[array_rand($mapAll)];
            }

            if ($outType == SHZ_TYPE_REPLACES) {
                $this->PlayerInfo['game']--;
            } else {
                if (in_array($outType, $innerTypes)) {
                    $_beishu = GAME_CONDITION_BASE[$outType];
                    $_score = GAME_CONDITION_BASE[$outType] * $this->roomRule['doublescore'] * $this->PlayerInfo['multiple'] * MAX_LINE;
                }
            }

            $arr = [];
            $arr[] = $innerTypes;
            $arr[] = array_reverse($innerTypes);
            foreach ($arr as $key => $val) {
                $type = 0;
                $num = 1;
                foreach ($val as $key1 => $val1) {
                    if ($type == 0) {
                        $type = $val1;
                        continue;
                    }

                    if ($type == $val1) {
                        $num++;
                    } else {
                        break;
                    }
                }

                if ($num == 4) {
                    $_beishu += GAME_CONDITION_FROE;
                    $_score += GAME_CONDITION_FROE * $this->roomRule['doublescore'] * $this->PlayerInfo['multiple'];
                    break;
                } elseif ($num == 3) {
                    $_beishu += GAME_CONDITION_THREE;
                    $_score += GAME_CONDITION_THREE * $this->roomRule['doublescore'] * $this->PlayerInfo['multiple'];
                }
            }

            $beishu += $_beishu;
            $score += $_score;

            $data[] = [
                'outType' => $outType,
                'innerTypes' => $innerTypes,
                'gold' => $_score,
                'game' => $this->PlayerInfo['game'],
                'beishu' => $_beishu,
            ];
        }
        $this->PlayerInfo['gameinfo'] = ['info' => $data, 'score' => $score, 'beishu' => $beishu];

        $this->saveData();

        $this->Msg_SHZ_Collect(['uid' => $msg['uid']], false);

        $this->PlayerInfo['score'] = $score;
        Logic::SendAll('Msg_SHZ_Game', $data, $this->roomRule['rid']);

        if (abs($this->PlayerInfo['control']) != 2) {
            Logic::InsertProfit($this->roomRule['level'], $score);
        }

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($msg['uid'], $score);
        }

        $this->Msg_SHZ_Collect(['uid' => $msg['uid']], false);

        $this->PlayerInfo['score'] = 0;

        $this->PlayerInfo['resinfo'] = [];


        if ($this->disRoom == 1 && $this->PlayerInfo['game'] <= 0) {
            $this->OldRoom();
        }
    }

    /**
     * 比倍
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_SHZ_Than($msg)
    {
        if ($this->PlayerInfo['score'] == 0) {
            Logic::SendError($msg['uid'], 'Msg_SHZ_Than', '比倍失败');
            return;
        }

        $arr = [1, 6];
        if ($this->prize <= rand(1, 100)) {
            if ($msg['data']['type'] == 2) {
                $arr[0] = 4;
            } else {
                $arr[1] = 3;
            }
        }

        $res = [];
        $sum = 0;
        for ($i = 0; $i < 2; $i++) {
            $rand = mt_rand($arr[0], $arr[1]);
            $res[] = $rand;
            $sum += $rand;
        }

        $type = 1;
        $beishu = BRESHU_BIG;
        if ($sum <= GAME_SMALL) {
            $type = 2;
            $beishu = BRESHU_SMALL;
        } elseif ($sum == GAME_MIDDLE) {
            $type = 3;
            $beishu = BRESHU_MIDDLE;
        }

        if ($type == $msg['data']['type']) {
            $_res = array_count_values($res);
            if (count($_res) == 1) {
                if ($beishu == BRESHU_SMALL) {
                    $beishu = BRESHU_DUI_SMALL;
                } else {
                    $beishu = BRESHU_DUI_BIG;
                }
            }
            $this->PlayerInfo['score'] *= $beishu;
        } else {
            $this->PlayerInfo['score'] = 0;

            if (abs($this->PlayerInfo['control']) != 2) {
                Logic::InsertProfit($this->roomRule['level'], -$this->PlayerInfo['resinfo']['conscore']);
            }
            if ($this->roomRule['level'] != 1) {
                DBInstance::IncrementUserGet($msg['uid'], -$this->PlayerInfo['resinfo']['conscore']);
            }
            $this->PlayerInfo['resinfo'] = [];
        }

        $this->history[] = $type;

        Logic::SendAll('Msg_SHZ_Than', ['res' => $res, 'score' => $this->PlayerInfo['gold'], 'gold' => $this->PlayerInfo['score'], 'history' => $this->history], $this->roomRule['rid']);
    }

    /**
     * 收分
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_SHZ_Collect($msg, $code = true)
    {
        if ($code && ($this->PlayerInfo['score'] <= 0 || $this->PlayerInfo['game'] > 0)) {
            Logic::SendError($msg['uid'], 'Msg_SHZ_Collect', '收分失败');
            return;
        }

        $this->changScore($this->PlayerInfo['score']);
        $this->PlayerInfo['score'] = 0;
        if (!empty($this->PlayerInfo['resinfo'])) {
            $score = $this->PlayerInfo['resinfo']['score'] - $this->PlayerInfo['resinfo']['conscore'];
        } else {
            $score = $this->PlayerInfo['gameinfo']['score'];
        }

        if (abs($this->PlayerInfo['control']) != 2) {
            Logic::InsertProfit($this->roomRule['level'], $score);
        }

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($msg['uid'], $score);
        }

        $this->PlayerInfo['resinfo'] = [];

        if ($code) {
            Logic::SendAll('Msg_SHZ_Collect', ['gold' => $this->PlayerInfo['gold']], $this->roomRule['rid']);
        }
    }

    /**
     * 计算中奖线数
     *
     * @return void
     */
    private function calculationWin($data, $code = true)
    {
        $res = ['win' => []];
        $speData = $this->speCalculationWin();
        $score = 0;
        $game = 0;
        $beishu = 0;
        $speDatacode = true;
        foreach ($speData['type'] as $key => $val) {
            if (in_array($val, SPE_WIN_CONDITION_ARR) || $val == SHZ_TYPE_ARMS || $val == SHZ_TYPE_HERO) {
                $speDatacode = false;
                if ($val == SHZ_TYPE_REPLACES) {
                    $game = 27;
                }
                break;
            }
        }

        if ($speDatacode) {
            foreach (ARR_MAP as $key => $val) {
                foreach ($val as $key1 => $val1) {
                    $type = 0;
                    $num = 1;
                    $_game = 0;
                    foreach ($val1 as $key2 => $val2) {
                        $_type = $this->map[$val2[0]][$val2[1]];
                        if ($type == 0) {
                            $type = $_type;
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

                        $score += WIN_CONDITION[$type][$num] * $data['multiple'] * $this->roomRule['doublescore'];
                    }
                }
            }
        }

        if (!empty($speData['data'])) {
            foreach ($speData['data'] as $key => $val) {
                $score += $val['multiple'] * $this->roomRule['doublescore'] * $data['multiple'] * MAX_LINE;
                $beishu += $val['multiple'] * MAX_LINE;
            }

            $res['win'] = array_merge($res['win'], $speData['data']);
        }

        if ($code) {
            $this->PlayerInfo['score'] = $score; //存储玩家当前赢的分数
            $this->PlayerInfo['game'] = $game; //小游戏标志
        }

        $res['score'] = $score;
        $res['gold'] = $this->PlayerInfo['gold'];
        $res['map'] = $this->map;

        $res['game'] = $game;
        $res['beishu'] = $beishu;

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
        $type = [];

        $beishu = 0;
        if ($count == 1) {
            $type[] = key($maps);
            $data[] = [
                'type' => key($maps),
                'multiple' => SPE_WIN_CONDITION[key($maps)],
                'line' => 0,
                'dir' => 0,
                'num' => MAP_HIGH * MAP_WIDE,
            ];
            $beishu += SPE_WIN_CONDITION[key($maps)];
        } elseif ($count <= 3) {
            $types = array_keys($maps); //类型集合
            $arms = array_diff($types, WIN_ARMS);
            $hero = array_diff($types, WIN_HERO);

            if (empty($arms)) {
                $type[] = SHZ_TYPE_ARMS;
                $data[] = [
                    'type' => SHZ_TYPE_ARMS,
                    'multiple' => SPE_WIN_CONDITION[SHZ_TYPE_ARMS],
                    'line' => 0,
                    'dir' => 0,
                    'num' => MAP_HIGH * MAP_WIDE,
                ];
                $beishu += SPE_WIN_CONDITION[SHZ_TYPE_ARMS];
            }

            if (empty($hero)) {
                $type[] = SHZ_TYPE_HERO;
                $data[] = [
                    'type' => SHZ_TYPE_HERO,
                    'multiple' => SPE_WIN_CONDITION[SHZ_TYPE_HERO],
                    'line' => 0,
                    'dir' => 0,
                    'num' => MAP_HIGH * MAP_WIDE,
                ];
                $beishu += SPE_WIN_CONDITION[SHZ_TYPE_HERO];
            }
        }

        return ['data' => $data, 'type' => $type, 'beishu' => $beishu];
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
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        if ($this->PlayerInfo['game'] > 0) {
            Logic::SendError($this->uid, 'Msg_SHZ_Out', '正在游戏中');
            return;
        }

        if ($this->PlayerInfo['score'] > 0) {
            $this->Msg_SHZ_Collect(['uid' => $this->uid], false);
            $this->PlayerInfo['score'] = 0;
        }
        DBInstance::DelUserBet($this->uid);
        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);

        Logic::SendAll('Msg_SHZ_Out', ['gold' => $gold], $this->roomRule['rid']);

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
     * 回放存储
     *
     * @param [type] $score
     * @param [type] $beishu
     * @param [type] $data
     * @return void
     */
    private function saveData()
    {
        $score = !empty($this->PlayerInfo['gameinfo']) ? $this->PlayerInfo['gameinfo']['score'] + $this->PlayerInfo['resinfo']['score'] : $this->PlayerInfo['resinfo']['score'];

        $beishu = !empty($this->PlayerInfo['gameinfo']) ? $this->PlayerInfo['gameinfo']['beishu'] + $this->PlayerInfo['resinfo']['beishu'] : $this->PlayerInfo['resinfo']['beishu'];

        $type = !empty($this->PlayerInfo['gameinfo']) ? 1 : 2;

        if ($score < SAVE_DATA_SHZ['score'] || $score / ($this->PlayerInfo['multiple'] * MAX_LINE * $this->roomRule['doublescore']) < SAVE_DATA_SHZ['beishu'] || $this->PlayerInfo['game'] > 0) {
            return;
        }

        $data = [
            'gameinfo' => isset($this->PlayerInfo['gameinfo']['info']) ? $this->PlayerInfo['gameinfo']['info'] : [],
            'resinfo' => $this->PlayerInfo['resinfo'],
            'map' => $this->agoMap,
            'nickname' => $this->PlayerInfo['nickname'],
            'doublescore' => $this->roomRule['doublescore'],
            'curgrade' => $this->PlayerInfo['multiple']
        ];

        $res = [
            'type' => $type,
            'nickname' => $this->PlayerInfo['nickname'],
            'gtype' => $this->roomRule['gtype'],
            'level' => $this->roomRule['level'],
            'vals' => json_encode($data),
            'score' => $score,
            'created' => MyTools::GET_NOW(),
            'playnum' => 0
        ];
        DBInstance::SaveData('game_back', $res);

        if ($score >= 5000000 && $beishu >= 20) {
            Logic::HorseLamp($this->uid, $score, $beishu);
        }
    }

    /**
     *获得大小奖
     */
    private function typeScore($beishu, $score)
    {
        foreach (SHZ_TYPE_SCORE as $key => $val) {
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
        if ($this->PlayerInfo['game'] <= 0) {
            $this->OldRoom();
        }
        Timer::del($this->timer);
    }

    /**
     * 获取场控
     * @param [type] $control
     * @return void
     */
    private function GetControlSHZ()
    {
        $control = $this->PlayerInfo['control'];
        // if ($this->PlayerInfo['control'] > 1) {
        //     $control = 1;
        // }

        // if ($control < -1) {
        //     $control = -1;
        // }

        $arr = [
            'list' => [
                SHZ_TYPE_REPLACES => [20, 50, 20, 400, 20],
                SHZ_TYPE_LOYALTY => [200, 200, 200, 200, 200],
                SHZ_TYPE_GO => [300, 300, 300, 300, 300],
                SHZ_TYPE_SONG => [400, 400, 400, 400, 400],
                SHZ_TYPE_LU => [500, 500, 500, 500, 500],
                SHZ_TYPE_KNIFE => [600, 600, 200, 600, 100],
                SHZ_TYPE_LING => [600, 700, 500, 100, 200],
                SHZ_TYPE_GUN => [700, 700, 700, 700, 700],
                SHZ_TYPE_AXE => [800, 800, 800, 800, 800]
            ],
            'game' => [
                SHZ_TYPE_REPLACES => 700,
                SHZ_TYPE_LOYALTY => 200,
                SHZ_TYPE_GO => 300,
                SHZ_TYPE_SONG => 400,
                SHZ_TYPE_LING => 100,
                SHZ_TYPE_LU => 500,
                SHZ_TYPE_KNIFE => 650,
                SHZ_TYPE_GUN => 700,
                SHZ_TYPE_AXE => 800,

            ],
            'beishu' => 15,
            'probability' => 20,
            'ransport' => [15, 20],
            'prize' => 20
        ];

        // $data = DBInstance::GetTableOneWord('control_shz', 'vals', ['level' => $control]);
        $data = $this->controlinfo[$control];

        if (!empty($data)) {
            // $data = json_decode($data, true);

            if (isset($data['prize']) && is_int($data['prize']) && isset($data['ransport']) && is_array($data['ransport']) && count(($data['ransport'])) >= 2 && isset($data['probability']) && is_int($data['probability']) && isset($data['beishu']) && is_int($data['beishu']) && isset($data['list']) && isset($data['game']) && count($data['list']) == 9 && count($data['game']) == 9) {
                $code = true;
                foreach ($data['list'] as $key => $val) {
                    if (count($val) < 5 || !in_array($key, MAP_ALL)) {
                        $code = false;
                        break;
                    }
                }
                if ($code) {
                    foreach ($data['game'] as $key => $val) {
                        if (!in_array($key, MAP_ALL)) {
                            $code = false;
                            break;
                        }
                    }
                }

                if ($code) {
                    $arr = $data;
                }
            }
        }
        $this->ransport = $arr['ransport'];
        $this->prize = $arr['prize'];
        $this->controlinfos = $arr;

        return $arr;
    }
}

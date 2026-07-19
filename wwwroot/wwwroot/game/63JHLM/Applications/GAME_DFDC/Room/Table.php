<?php

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

use function PHPSTORM_META\map;
use function PHPSTORM_META\type;

define("DFDC_MAX_BEI", 5);  //最大档位
define("DFDC_MAX_GEAR", 10);  //最大倍数

// 多福多财
define('DFDC_TYPE_GOLDEN_DRAGON', 1); //金色龙、风、麒麟、熊猫
define('DFDC_TYPE_GOLDEN_SHIP', 2); //金色船、莲花、乌龟
define('DFDC_TYPE_GOLDEN_FISH', 3); //金色鱼、簪子、发财树、蟾蜍
define('DFDC_TYPE_GOLDEN_YUANBAO', 4); //金色元宝、戒指、鱼
define('DFDC_TYPE_GREEN_DRAGON', 5); //绿色龙、风、麒麟、熊猫
define('DFDC_TYPE_GREEN_SHIP', 6); //绿色船、莲花、乌龟
define('DFDC_TYPE_GREEN_FISH', 7); //绿色鱼、簪子、发财树、蟾蜍
define('DFDC_TYPE_GREEN_YUANBAO', 8); //绿色元宝、簪子、发财树、蟾蜍
define('DFDC_TYPE_SCATTER', 9); //免费图标
define('DFDC_TYPE_COPPER', 10); //硬币
define('DFDC_TYPE_WILD', 11); //福
define('DFDC_TYPE_A', 12); //A
define('DFDC_TYPE_K', 13); //K
define('DFDC_TYPE_Q', 14); //Q
define('DFDC_TYPE_J', 15); //J
define('DFDC_TYPE_10', 16); //10
define('DFDC_TYPE_9', 17); //9
define('DFDC_TYPE_DIAMONDS', 18); //钻石

define('DFDC_TYPE_ALL', [
    DFDC_TYPE_GOLDEN_DRAGON, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_FISH, DFDC_TYPE_GOLDEN_YUANBAO, DFDC_TYPE_A, DFDC_TYPE_GREEN_DRAGON,
    DFDC_TYPE_GREEN_SHIP, DFDC_TYPE_GREEN_FISH, DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_SCATTER, DFDC_TYPE_COPPER,
    DFDC_TYPE_K, DFDC_TYPE_Q, DFDC_TYPE_J, DFDC_TYPE_10, DFDC_TYPE_9, DFDC_TYPE_WILD
]);
define('DFDC_WIN_ALL', [
    2 => [
        DFDC_TYPE_GOLDEN_DRAGON => [
            5 => 1000,
            4 => 200,
            3 => 100
        ],
        DFDC_TYPE_GOLDEN_SHIP => [
            5 => 500,
            4 => 100,
            3 => 50
        ],
        DFDC_TYPE_GOLDEN_FISH => [
            5 => 400,
            4 => 80,
            3 => 40
        ],
        DFDC_TYPE_GOLDEN_YUANBAO => [
            5 => 250,
            4 => 50,
            3 => 25
        ],

        DFDC_TYPE_GREEN_DRAGON => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_GREEN_SHIP => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_GREEN_FISH => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_GREEN_YUANBAO => [
            5 => 50,
            4 => 10,
            3 => 5
        ],

        DFDC_TYPE_SCATTER => [
            5 => 50,
            4 => 10,
            3 => 5
        ],

        DFDC_TYPE_COPPER => [
            5 => 100,
            4 => 20,
            3 => 10
        ],

        DFDC_TYPE_A => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_K => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_Q => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_J => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_10 => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_9 => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
    ],

    3 => [
        DFDC_TYPE_GOLDEN_DRAGON => [
            5 => 680,
            4 => 128,
            3 => 68
        ],
        DFDC_TYPE_GOLDEN_SHIP => [
            5 => 380,
            4 => 88,
            3 => 38
        ],
        DFDC_TYPE_GOLDEN_FISH => [
            5 => 280,
            4 => 68,
            3 => 28
        ],
        DFDC_TYPE_GOLDEN_YUANBAO => [
            5 => 180,
            4 => 68,
            3 => 28
        ],

        DFDC_TYPE_GREEN_DRAGON => [
            5 => 30,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_GREEN_SHIP => [
            5 => 30,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_GREEN_FISH => [
            5 => 20,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_GREEN_YUANBAO => [
            5 => 20,
            4 => 10,
            3 => 5
        ],

        DFDC_TYPE_SCATTER => [
            5 => 50,
            4 => 10,
            3 => 5
        ],

        DFDC_TYPE_COPPER => [
            5 => 128,
            4 => 28,
            3 => 8
        ],

        DFDC_TYPE_A => [
            5 => 15,
            4 => 8,
            3 => 5
        ],
        DFDC_TYPE_K => [
            5 => 15,
            4 => 8,
            3 => 5
        ],
        DFDC_TYPE_Q => [
            5 => 15,
            4 => 8,
            3 => 5
        ],
        DFDC_TYPE_J => [
            5 => 15,
            4 => 8,
            3 => 5
        ],
        DFDC_TYPE_10 => [
            5 => 15,
            4 => 8,
            3 => 5
        ],
        DFDC_TYPE_9 => [
            5 => 15,
            4 => 8,
            3 => 5
        ],
    ],

    4 => [
        DFDC_TYPE_GOLDEN_DRAGON => [
            5 => 750,
            4 => 150,
            3 => 100
        ],
        DFDC_TYPE_GOLDEN_SHIP => [
            5 => 450,
            4 => 100,
            3 => 50
        ],
        DFDC_TYPE_GOLDEN_FISH => [
            5 => 300,
            4 => 80,
            3 => 40
        ],
        DFDC_TYPE_GOLDEN_YUANBAO => [
            5 => 200,
            4 => 50,
            3 => 20
        ],

        DFDC_TYPE_GREEN_DRAGON => [
            5 => 50,
            4 => 15,
            3 => 10
        ],
        DFDC_TYPE_GREEN_SHIP => [
            5 => 50,
            4 => 15,
            3 => 10
        ],
        DFDC_TYPE_GREEN_FISH => [
            5 => 50,
            4 => 15,
            3 => 10
        ],
        DFDC_TYPE_GREEN_YUANBAO => [
            5 => 50,
            4 => 15,
            3 => 10
        ],

        DFDC_TYPE_SCATTER => [
            5 => 50,
            4 => 10,
            3 => 5
        ],

        DFDC_TYPE_COPPER => [
            5 => 138,
            4 => 25,
            3 => 10
        ],

        DFDC_TYPE_A => [
            5 => 30,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_K => [
            5 => 30,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_Q => [
            5 => 25,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_J => [
            5 => 25,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_10 => [
            5 => 20,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_9 => [
            5 => 20,
            4 => 10,
            3 => 5
        ],

    ],

    5 => [
        DFDC_TYPE_GOLDEN_DRAGON => [
            5 => 800,
            4 => 200,
            3 => 100
        ],
        DFDC_TYPE_GOLDEN_SHIP => [
            5 => 400,
            4 => 100,
            3 => 50
        ],
        DFDC_TYPE_GOLDEN_FISH => [
            5 => 200,
            4 => 75,
            3 => 30
        ],
        DFDC_TYPE_GOLDEN_YUANBAO => [
            5 => 150,
            4 => 50,
            3 => 25
        ],

        DFDC_TYPE_GREEN_DRAGON => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_GREEN_SHIP => [
            5 => 50,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_GREEN_FISH => [
            5 => 30,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_GREEN_YUANBAO => [
            5 => 30,
            4 => 10,
            3 => 5
        ],

        DFDC_TYPE_SCATTER => [
            5 => 50,
            4 => 10,
            3 => 5
        ],

        DFDC_TYPE_COPPER => [
            5 => 100,
            4 => 25,
            3 => 15
        ],

        DFDC_TYPE_A => [
            5 => 20,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_K => [
            5 => 20,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_Q => [
            5 => 20,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_J => [
            5 => 15,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_10 => [
            5 => 15,
            4 => 10,
            3 => 5
        ],
        DFDC_TYPE_9 => [
            5 => 15,
            4 => 10,
            3 => 5
        ],
    ]

]);

define('DFDC_FREE_FREE', [
    2 => 10,
    3 => 3,
    4 => 3,
    5 => 0
]);

define('DFDC_FREE_NUM', [
    2 => ['width' => 3, 'height' => 5, 'free' => [10, 10]],
    3 => ['width' => 3, 'height' => 5, 'free' => [10, 10]],
    4 => ['width' => 3, 'height' => 5, 'free' => [5, 20]],
    5 => ['width' => 4, 'height' => 5, 'free' => [5, 5]]
]); //不同等级得地图和免费获取

define('DFDC_BRISHU', [1 => 1, 2 => 4, 3 => 7, 4 => 10, 5 => 15]);

define('DFDC_FREE4', [DFDC_TYPE_GOLDEN_DRAGON => 5, DFDC_TYPE_GOLDEN_SHIP => 7, DFDC_TYPE_GOLDEN_FISH => 10, DFDC_TYPE_GOLDEN_YUANBAO => 15, DFDC_TYPE_COPPER => 20]);

define('DFDC_FREE3', [3 => 15, 4 => 10, 5 => 5]);

define('DFDC_SMALL_WIN', 1); //小奖
define('DFDC_MIDDLE_WIN', 2); //中奖
define('DFDC_BIG_WIN', 3); //大奖
define('DFDC_HUGE_WIN', 4); //巨奖

define('RAND_SAMLL1', [1, 50]);

define('DFDC_DIAMONDS_BEISHU', [2, 5]); //倍数

define('DFDC_JACKPOT_AWARD', [
    1 => ['arr' => [], 'chance' => 0.1, 'ratio' => 0.26],
    2 => ['arr' => [DFDC_SMALL_WIN => 100], 'chance' => 0.1, 'ratio' => 0.26],
    3 => ['arr' => [DFDC_SMALL_WIN => 60, DFDC_MIDDLE_WIN => 40], 'chance' => 0.2, 'ratio' => 0.46],
    4 => ['arr' => [DFDC_SMALL_WIN => 40, DFDC_MIDDLE_WIN => 35, DFDC_BIG_WIN => 25], 'chance' => 0.3, 'ratio' => 0.66],
    5 => ['arr' => [DFDC_SMALL_WIN => 40, DFDC_MIDDLE_WIN => 30, DFDC_BIG_WIN => 20, DFDC_HUGE_WIN => 10], 'chance' => 0.4, 'ratio' => 1],
]); //奖池中奖情况

define('DFDC_SAVE_DATA', ['score' => 800000, 'beishu' => 40]); //需要存数据
define('DFDC_SAVE_DATA_TYPE_SCORE', 1); //分数和倍数达到标准
define('DFDC_SAVE_DATA_TYPE_FREE', 2); //免费
define('DFDC_SAVE_DATA_TYPE_JACKPOT', 3); //中奖池
define('DFDC_JACKPOT_SCORE_WIN', 800000);
define('DFDC_FREE_SCORE_WIN', 800000);

define('DFDC_TYPE_SCORE', [
    1 => ['score' => 5000, 'beishu' => 2],
    2 => ['score' => 10000, 'beishu' => 5],
    3 => ['score' => 50000, 'beishu' => 7],
    4 => ['score' => 100000, 'beishu' => 12],
]);
class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $map = []; //地图

    private $uid = 0; //玩家uid

    private $disRoom = -1;

    private $agoMap = [];

    private $freeAgoMap = [];

    private $bonus = 0;

    private $width = 3;

    private $height = 5;

    private $freecode = false;

    private $freenum = 0;

    private $diamonds = false;

    private $alltype = [];

    private $_alltype = [];

    private $ransport = [20, 25];

    private $jackpost = 10000;

    private $controlinfo = [];

    private $controlinfos = [];

    private $timer = 0; //定时器id

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

        $this->userEnter($msg['players']);

        $this->width = DFDC_FREE_NUM[$this->roomRule['level']]['width'];
        $this->height = DFDC_FREE_NUM[$this->roomRule['level']]['height'];

        $this->roomInfo();

        $this->controlinfo = DBInstance::GetControlInfo('control_dfdc', ['gtype' => $this->roomRule['level']]);
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
            'multiple' => 1,
            'winNum' => 0,
            'ransport' => rand($this->ransport[0], $this->ransport[1]),
            'freeData' => [],
            'freeScore' => 0,
            'gear' => 1,
            'free4' => 0,
            'control' => -2
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
        Gateway::joinGroup($this->PlayerInfo['client_id'], 'ROOM:' . $this->roomRule['rid']);

        $res = [
            'map' => $this->map,
            'curgrade' => $this->PlayerInfo['multiple'],
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'max_multiple' => DFDC_MAX_BEI,
            'max_gear' => DFDC_MAX_GEAR,
            'gold' => $this->PlayerInfo['gold'],
            'gear' => $this->PlayerInfo['gear'],
            'free' => $this->PlayerInfo['free'],
            'free4' => $this->PlayerInfo['free4'],
        ];

        Logic::SendAll('Msg_DFDC_RoomInfo', $res, $this->roomRule['rid']);
    }

    /**
     * 初始地图
     *
     * @return void
     */
    private function InitMap($code = true)
    {
        $this->map = [];
        $arr = [];
        $this->freenum = 0;
        $this->diamonds = false;

        if ($this->PlayerInfo['free'] > 0) {
            if (($this->roomRule['level'] == 2 || $this->roomRule['level'] == 5)) {
                $arr = [DFDC_TYPE_A, DFDC_TYPE_K, DFDC_TYPE_Q, DFDC_TYPE_J, DFDC_TYPE_10, DFDC_TYPE_9];
            } elseif ($this->roomRule['level'] == 4) {
                $arr = [DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_GREEN_FISH, DFDC_TYPE_GREEN_SHIP, DFDC_TYPE_GREEN_DRAGON, DFDC_TYPE_GOLDEN_DRAGON, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_FISH, DFDC_TYPE_GOLDEN_YUANBAO, DFDC_TYPE_COPPER];
            }
        }

        if ($this->PlayerInfo['multiple'] == 1) {
            $arr = array_merge($arr, [DFDC_TYPE_GOLDEN_YUANBAO, DFDC_TYPE_GOLDEN_FISH, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_DRAGON]);
        } elseif ($this->PlayerInfo['multiple'] == 2) {
            $arr = array_merge($arr, [DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_GOLDEN_FISH, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_DRAGON]);
        } elseif ($this->PlayerInfo['multiple'] == 3) {
            $arr = array_merge($arr, [DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_GREEN_FISH, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_DRAGON]);
        } elseif ($this->PlayerInfo['multiple'] == 4) {
            $arr = array_merge($arr, [DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_GREEN_FISH, DFDC_TYPE_GREEN_SHIP, DFDC_TYPE_GOLDEN_DRAGON]);
        } else {
            $arr = array_merge($arr, [DFDC_TYPE_GREEN_YUANBAO, DFDC_TYPE_GREEN_FISH, DFDC_TYPE_GREEN_SHIP, DFDC_TYPE_GREEN_DRAGON]);
        }

        $this->bonus = 0;

        $alltype = array_diff(DFDC_TYPE_ALL, $arr);
        if (isset(DFDC_FREE4[$this->PlayerInfo['free4']]) && $this->roomRule['level'] == 4) {
            $alltype[] = $this->PlayerInfo['free4'];
        }

        // if ($this->PlayerInfo['free'] > 0 &&  $this->roomRule['level'] > 4) {
        //     $alltype = array_diff($alltype, [DFDC_TYPE_SCATTER, DFDC_TYPE_WILD]);
        // }

        $_alltype = array_diff($alltype, [DFDC_TYPE_WILD]);

        if ($this->PlayerInfo['free'] > 0 && $this->roomRule['level'] == 5) {
            $_alltype = array_diff($_alltype, [DFDC_TYPE_SCATTER]);
        }

        $control = DBInstance::GetLBControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $this->PlayerInfo['control'] = $control;

        $data = $this->GetControlDFDC($control);
        if ($this->PlayerInfo['free'] > 0) {
            $data = $data['free'];
        }

        $this->alltype = $this->dealcontrol($data['list'], $alltype);

        for ($i = 0; $i < $this->height; $i++) {
            for ($j = 0; $j < $this->width; $j++) {
                $type = 0;
                if ($this->roomRule['level'] == 3 && $i == 2 && !$this->diamonds) {
                    $rand = rand(1, 100);
                    if ($rand <= $data['diamonds']) {
                        $type = DFDC_TYPE_DIAMONDS;
                        $this->diamonds = true;
                    }
                }

                $_alltypes = $_alltype;
                if ($i == 0 && $this->PlayerInfo['free'] > 0) {
                    $_alltypes = array_diff($_alltype, [DFDC_TYPE_SCATTER, DFDC_TYPE_DIAMONDS]);
                }

                $this->_alltype = $this->dealcontrol($data['list'], $_alltypes);

                if ($type <= 0) {
                    if ($i == 0 || $i == 4) {
                        $type = $this->gradeMap($i, $this->_alltype);
                    } else {
                        $type = $this->gradeMap($i, $this->alltype);
                        if ($type == DFDC_TYPE_WILD) {
                            $this->bonus++;
                        }
                    }
                }

                if ($type == DFDC_TYPE_SCATTER || $type == DFDC_TYPE_WILD) {
                    $this->freenum++;
                }
                $this->map[$i][] = $type;
            }
        }

        //配置地图生成
        if (!empty($this->map_save) && $this->map_save['logo'] != DFDC_TYPE_WILD) {
            $map_logo = 0;
            if (in_array($this->map_save['logo'], $alltype)) {
                $map_logo = $this->map_save['logo'];
            } elseif (in_array($this->map_save['logo'] - 4, $alltype) && $this->map_save['logo'] <= DFDC_TYPE_GREEN_YUANBAO) {
                $map_logo = $this->map_save['logo'] - 4;
            }

            if ($map_logo) {
                $map_other_logo = [DFDC_TYPE_A, DFDC_TYPE_K, DFDC_TYPE_Q, DFDC_TYPE_J, DFDC_TYPE_10, DFDC_TYPE_9];
                for ($k = 0; $k < 5; $k++) {
                    if ($k < $this->map_save['num']) {
                        $_index = rand(0, 2);
                        if ($this->map[$k][$_index] != $map_logo && $this->map[$k][$_index] != DFDC_TYPE_WILD) {
                            $this->map[$k][rand(0, 2)] = $map_logo;
                        }
                    } else {
                        for ($n = 0; $n < 3; $n++) {
                            if ($this->map[$k][$n] == $map_logo || $this->map[$k][$n] == DFDC_TYPE_WILD) {
                                $this->map[$k][$n] = $map_other_logo[array_rand($map_other_logo)];
                            }
                        }
                    }
                }
            }
        }

        if ($code) {
            $checkdata = $this->checkdata(false);
            if ($this->PlayerInfo['ransport'] <= 0 && $checkdata['score'] <= 0) {
                $this->newmap($alltype);
                $this->PlayerInfo['ransport'] = rand($this->ransport[0], $this->ransport[1]);
            }

            if ($checkdata['beishu'] >= $data['beishu'] && rand(1, 100) < $data['probability']) {
                $this->InitMap(false);
            }
        }
    }

    private function newmap($alltype)
    {
        $_alltype = array_diff($alltype, [DFDC_TYPE_GOLDEN_DRAGON, DFDC_TYPE_GOLDEN_SHIP, DFDC_TYPE_GOLDEN_FISH, DFDC_TYPE_WILD, DFDC_TYPE_DIAMONDS, DFDC_TYPE_SCATTER]);
        $type = $_alltype[array_rand($_alltype)];
        $arr = [];
        foreach (DFDC_WIN_ALL[$this->roomRule['level']][$type] as $key => $val) {
            if ($key >= 5) {
                continue;
            }
            $arr[$key] = 1;
        }
        $rand = array_rand($arr);
        for ($i = 0; $i < $rand; $i++) {
            if (!in_array($type, $this->map[$i])) {
                $_rand = rand(0, count($this->map[$i]) - 1);
                $this->map[$i][$_rand] = $type;
            }
        }
    }

    private function dealcontrol($data, $array)
    {
        $arr = [];
        foreach ($data as $key => $val) {
            if (!in_array($key, $array)) {
                continue;
            }

            foreach ($val as $key1 => $val1) {
                $arr[$key1][$key] = $val1;
            }
        }
        return $arr;
    }

    private function gradeMap($row, $data)
    {
        $total = array_sum($data[$row]);
        $rand = rand(1, $total);

        $num = 0;
        $res = -1;
        foreach ($data[$row] as $key => $val) {
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
            case 'Msg_DFDC_Start':
                $this->Msg_DFDC_Start($message);
                break;
            case 'Msg_DFDC_ChangeMap':
                $this->Msg_DFDC_ChangeMap($message);
                break;
            case 'Msg_DFDC_ChangeIcon':
                $this->Msg_DFDC_ChangeIcon($message);
                break;
            case 'Msg_DFDC_Out':
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
    private function Msg_DFDC_Start($msg)
    {
        if (!isset($msg['data']['multiple']) || !is_int($msg['data']['multiple']) || $msg['data']['multiple'] <= 0 || $msg['data']['multiple'] > DFDC_MAX_BEI) {
            Logic::SendError($msg['uid'], 'Msg_DFDC_Start', '下注档位错误');
            return;
        }

        if (!isset($msg['data']['gear']) || !is_int($msg['data']['gear']) || $msg['data']['gear'] <= 0 || $msg['data']['gear'] > DFDC_MAX_GEAR) {
            Logic::SendError($msg['uid'], 'Msg_DFDC_Start', '下注倍数错误');
            return;
        }

        if ($this->PlayerInfo['free4'] == 0 && $this->PlayerInfo['free'] > 0 && ($this->roomRule['level'] == 3 || $this->roomRule['level'] == 4)) {
            Logic::SendError($msg['uid'], 'Msg_DFDC_Start', '请选择对应的免费类型');
            return;
        }

        $score = 0;
        if ($this->PlayerInfo['free'] <= 0) {
            $score = DFDC_BRISHU[$msg['data']['multiple']] * $msg['data']['gear'] * $this->roomRule['doublescore'];
            DBInstance::SetUserBet($this->uid, $this->roomRule['gtype'], $this->roomRule['level'], $score, json_encode($msg['data']));
            if ($score > $this->PlayerInfo['gold']) {
                Logic::SendError($msg['uid'], 'Msg_DFDC_Start', '金币不足');
                return;
            }
            $this->freecode = false;

            $this->width = DFDC_FREE_NUM[$this->roomRule['level']]['width'];
            $this->PlayerInfo['free4'] = 0;

            $this->PlayerInfo['freeScore'] = 0;
            $this->PlayerInfo['freeData'] = [];
            $this->map_save = DBInstance::GetControlMap($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        } else {
            $this->map_save = [];
        }

        $this->agoMap = $this->map;

        if ($score > 0 || empty($this->freeAgoMap)) {
            $this->freeAgoMap = $this->map;
            $this->changScore(-$score);
            $this->PlayerInfo['multiple'] = $msg['data']['multiple'];
            $this->PlayerInfo['gear'] = $msg['data']['gear'];
        }

        $this->InitMap();
        $data = $this->checkdata();
        $Jackpot = $this->SaveJackpot(0, 1);
        if ($score <= 0) {
            $this->PlayerInfo['free']--;
        }

        $data['conscore'] = $score;
        $data['jackpotAll'] = $Jackpot['jackpot'];
        $data['free4'] = $this->PlayerInfo['free4'];
        $data['free'] = $this->PlayerInfo['free'];
        // $data['control'] = $this->PlayerInfo['control'];
        // $data['controlinfo'] = $this->controlinfos;

        $beishu = $data['score'] / (DFDC_BRISHU[$this->PlayerInfo['multiple']] * $this->PlayerInfo['gear'] * $this->roomRule['doublescore']);
        $data['type'] = $this->typeScore($beishu, $data['score']);

        if ($score > $data['score']) {
            $jackpot = intval(($score - $data['score']) * $Jackpot['probability'] / 100);

            $this->SaveJackpot($jackpot, 2);
            $this->PlayerInfo['ransport']--;
        }

        if ($this->PlayerInfo['free'] > 0 || $score <= 0) {
            $this->PlayerInfo['freeData'][] = $data;
            $this->PlayerInfo['freeScore'] += $data['score'] + $data['jackpot'];
        }

        if (abs($this->PlayerInfo['control']) != 2 && empty($this->map_save)) {
            if (!isset($jackpot)) {
                $jackpot = 0;
            }
            $profit = $data['score'] - $data['conscore'] + $jackpot;
            Logic::InsertProfit($this->roomRule['level'], $profit);
        }

        MyTools::msg(json_encode($msg['data']));
        MyTools::msg(json_encode($data));
        Logic::SendAll('Msg_DFDC_Start', $data, $this->roomRule['rid']);

        $this->saveData($data['beishu'], $data);

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($msg['uid'], $data['score'] - $score);
        }

        if ($this->disRoom == 1 && $this->PlayerInfo['free'] <= 0) {
            $this->All_RECV(['event' => 'Msg_DFDC_Out', 'uid' => $msg['uid']]);
        }
    }

    /**
     * 修改地图
     * @param [type] $msg
     * @return void
     */
    private function Msg_DFDC_ChangeMap($msg)
    {
        if ($this->roomRule['level'] != 3) {
            Logic::SendError($msg['uid'], 'Msg_DFDC_ChangeMap', '数据错误');
            return;
        }

        if (!$this->freecode) {
            Logic::SendError($msg['uid'], 'Msg_DFDC_ChangeMap', '没有可进行的免费');
            return;
        }

        if ($msg['data']['width'] < 3 || $msg['data']['width'] > 5 || !isset(DFDC_FREE3[$msg['data']['width']])) {
            Logic::SendError($msg['uid'], 'Msg_DFDC_ChangeMap', '请选择对应的免费类型');
            return;
        }

        if ($this->PlayerInfo['free4'] > 0) {
            Logic::SendError($msg['uid'], 'Msg_DFDC_ChangeMap', '正在免费旋转中');
            return;
        }

        $this->PlayerInfo['free4'] = $msg['data']['width'];

        $this->width = $msg['data']['width'];

        $this->PlayerInfo['free'] = DFDC_FREE3[$msg['data']['width']];
        foreach ($this->PlayerInfo['freeData'] as $key => $val) {
            $this->PlayerInfo['freeData'][$key]['free'] = $this->PlayerInfo['free'];
            $this->PlayerInfo['freeData'][$key]['curfree'] = $this->PlayerInfo['free'];
            break;
        }

        Logic::SendAll('Msg_DFDC_ChangeMap', [], $this->roomRule['rid']);
    }

    private function Msg_DFDC_ChangeIcon($msg)
    {
        if ($this->roomRule['level'] != 4) {
            Logic::SendError($msg['uid'], 'Msg_DFDC_ChangeIcon', '免费图标选择错误');
            return;
        }

        if ($this->PlayerInfo['free4'] != 0 || $this->PlayerInfo['free'] <= 0) {
            Logic::SendError($msg['uid'], 'Msg_DFDC_ChangeIcon', '没有可进行的免费');
            return;
        }

        $this->PlayerInfo['free4'] = $msg['data']['type'];
        $this->PlayerInfo['free'] = DFDC_FREE4[$msg['data']['type']];

        foreach ($this->PlayerInfo['freeData'] as $key => $val) {
            $this->PlayerInfo['freeData'][$key]['free'] = $this->PlayerInfo['free'];
            $this->PlayerInfo['freeData'][$key]['curfree'] = $this->PlayerInfo['free'];
            break;
        }
        Logic::SendRight($msg['uid'], 'Msg_DFDC_ChangeIcon', ['free' => $this->PlayerInfo['free']]);
    }

    /**
     * 线路检测
     *
     * @return void
     */
    private function checkdata($code = true)
    {
        $arr = [];
        $score = 0;
        $map = [];
        $beishu = 0;
        $free = 0;
        $cursocre = DFDC_BRISHU[$this->PlayerInfo['multiple']] * $this->PlayerInfo['gear'] * $this->roomRule['doublescore'];
        foreach ($this->map[0] as $key => $val) {
            $_map = [];
            $_map[] = [0, $key];
            $num = []; //[$lie=>'该列共有几个']
            for ($i = 1; $i < $this->height; $i++) {
                foreach ($this->map[$i] as $key1 => $val1) {
                    if ($val == DFDC_TYPE_WILD) {
                        $val = $val1;
                    }

                    if ($val == $val1 || $val1 == DFDC_TYPE_WILD || ($val != DFDC_TYPE_SCATTER && $val1 == DFDC_TYPE_DIAMONDS)) {
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
            if ($count >= min(array_keys(DFDC_WIN_ALL[$this->roomRule['level']][$val]))) {
                if (($this->roomRule['level'] == 5 && $val == DFDC_TYPE_SCATTER) && $this->PlayerInfo['free'] > 0) {
                    $_beishu = 0;
                } else {
                    $_beishu = DFDC_WIN_ALL[$this->roomRule['level']][$val][$count];
                }

                $map = array_merge($_map, $map);

                if ($val == DFDC_TYPE_SCATTER) {
                    $free = rand(DFDC_FREE_NUM[$this->roomRule['level']]['free'][0], DFDC_FREE_NUM[$this->roomRule['level']]['free'][1]);

                    if ($free > 0 && $this->roomRule['level'] == 3) {
                        $this->freecode = true;
                    }
                }

                $_line = 1;
                foreach ($num as $key1 => $val1) {
                    $_line *= $val1;
                }

                $_beishu *= $_line;
                if (!isset($arr[$val]) && $_beishu > 0) {
                    $arr[$val] = [
                        'multiple' => 0,
                        'type' => $val,
                    ];
                }

                $arr[$val]['multiple'] += $_beishu;

                $beishu += $_beishu;
                $_score = $_beishu * ($cursocre / $this->roomRule['doublescore'] / $this->PlayerInfo['gear']) * $this->PlayerInfo['gear'] * 200;
                $score += $_score;
            }
        }

        $jackpot = 0;
        $region = 0;

        if (!empty($this->map_save['logo']) && $this->map_save['logo'] == DFDC_TYPE_WILD && $this->map_save['num'] && $this->bonus <= 0) {
            $this->bonus = 1;
            $this->map[4][rand(0, 2)] = DFDC_TYPE_WILD;
            $this->checkdata();
            return;
        }

        if ($code && $this->bonus > 0 && !empty(DFDC_JACKPOT_AWARD[$this->PlayerInfo['multiple']]['arr'])) {
            //$_rand = rand(1, $this->jackpost);
            if (!empty($this->map_save['logo']) && $this->map_save['logo'] == DFDC_TYPE_WILD && $this->map_save['num']) {
                // $region = DFDC_JACKPOT_AWARD[$this->PlayerInfo['multiple']]['arr'][array_rand(DFDC_JACKPOT_AWARD[$this->PlayerInfo['multiple']]['arr'])]; //等级
                $rand = rand(1, array_sum(DFDC_JACKPOT_AWARD[$this->PlayerInfo['multiple']]['arr']));
                $temp = 0;
                foreach (DFDC_JACKPOT_AWARD[$this->PlayerInfo['multiple']]['arr'] as $key => $val) {
                    $temp += $val;
                    if ($temp >= $rand) {
                        $region = $key;
                        break;
                    }
                }

                if (!empty($this->map_save['jackpot_type']) && $this->map_save['jackpot_type'] < 5) {
                    $region = $this->map_save['jackpot_type'];
                }

                $_data = $this->SaveJackpot(0, 1);

                if (empty($this->map_save['jackpot'])) {
                    $jackpot = intval($_data['jackpot'] * DFDC_JACKPOT_AWARD[$region + 1]['chance'] * DFDC_JACKPOT_AWARD[$this->PlayerInfo['multiple']]['ratio']);
                } else {
                    $jackpot = $this->map_save['jackpot'];
                }

                if ($jackpot < $_data['jackpot']) {
                    $this->SaveJackpot(-$jackpot, 2);
                }
            }
        }

        if ($this->PlayerInfo['free'] > 0) {
            if ($this->roomRule['level'] == 5) {
                $free = $this->freenum;
            } elseif ($free > 0) {
                $free = DFDC_FREE_FREE[$this->roomRule['level']];
            }
        }

        $diamonds = 0;
        if ($this->diamonds) {
            $diamonds = rand(DFDC_DIAMONDS_BEISHU[0], DFDC_DIAMONDS_BEISHU[1]);
            $score *= $diamonds;
        }

        if ($code) {
            $this->changScore($score + $jackpot);
            $this->PlayerInfo['free'] += $free;
        }

        $data = [
            'win' => array_values($arr),
            'score' => $score,
            'curMap' => $map,
            'map' => $this->map,
            'gold' => $this->PlayerInfo['gold'],
            'beishu' => $beishu,
            'jackpot' => $jackpot,
            'curfree' => $free,
            'jacktype' => $region,
            'diamonds' => $diamonds
        ];

        return $data;
    }

    /**
     *获得大小奖
     */
    private function typeScore($beishu, $score)
    {
        foreach (DFDC_TYPE_SCORE as $key => $val) {
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
     *分数变化
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
     *对奖池的操作
     *
     * @param int $score
     * @param int $code 1查询 2修改
     * @return int
     */
    private function SaveJackpot($score, $code)
    {
        if ($code == 2) {
            DBInstance::SaveJackpot($this->roomRule['gtype'], 1, $score);
        }

        $res = DBInstance::GetGameJackpot(['gtype' => $this->roomRule['gtype'], 'level' => 1]);

        return $res;
    }

    /**
     * 回放存储
     *
     * @param [type] $score
     * @param [type] $beishu
     * @param [type] $data
     * @return void
     */
    private function saveData($beishu, $data)
    {
        $type = 0;
        $map = $this->map;
        $score = $data['score'] + $data['jackpot'];
        if ($this->PlayerInfo['freeScore'] > 0 && $this->PlayerInfo['free'] <= 0) {
            $score = $this->PlayerInfo['freeScore'];
        }

        if ($score < DFDC_SAVE_DATA['score'] || $score / (DFDC_BRISHU[$this->PlayerInfo['multiple']] * $this->PlayerInfo['gear'] * $this->roomRule['doublescore']) < DFDC_SAVE_DATA['beishu']) {
            return;
        }
        if ($data['jackpot'] > 0) {
            $type = $data['jacktype'] + 2;
            $data['free'] = 0;
        } elseif ($data['score'] + $data['jackpot'] == $score) {
            $type = DFDC_SAVE_DATA_TYPE_SCORE;
        } elseif ($this->PlayerInfo['freeScore'] > 0 && $this->PlayerInfo['free'] <= 0) {
            $type = DFDC_SAVE_DATA_TYPE_FREE;
            $data = $this->PlayerInfo['freeData'];
            $map = $this->freeAgoMap;
        }

        if ($type > 0) {
            $_data = [];
            $_data['uid'] = $this->uid;
            $_data['gear'] = $this->PlayerInfo['gear'];
            $_data['doublescore'] = $this->roomRule['doublescore'];
            $_data['curgrade'] = $this->PlayerInfo['multiple'];
            $_data['map'] = $map;

            if ($type == DFDC_SAVE_DATA_TYPE_FREE) {
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
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        if ($this->PlayerInfo['free'] > 0) {
            Logic::SendError($this->uid, 'Msg_DFDC_Out', '正在免费旋转中');
            return;
        }

        DBInstance::DelUserBet($this->uid);
        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);

        Logic::SendAll('Msg_DFDC_Out', ['gold' => $gold], $this->roomRule['rid']);

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
        if ($this->roomRule['level'] != 1) {
            $this->PlayerInfo['gold'] = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        }
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
    private function GetControlDFDC($control)
    {
        // if ($control > 1) {
        //     $control = 1;
        // }
        // if ($control < -1) {
        //     $control = -1;
        // }

        $res = [
            'list' => [
                DFDC_TYPE_GOLDEN_DRAGON => [1000, 800, 200, 800, 600],
                DFDC_TYPE_GOLDEN_SHIP => [1000, 60, 50, 1000, 50],
                DFDC_TYPE_GOLDEN_FISH => [1000, 1000, 100, 1000, 1000],
                DFDC_TYPE_GOLDEN_YUANBAO => [1000, 800, 200, 1000, 1000],
                DFDC_TYPE_GREEN_DRAGON => [1000, 1000, 300, 1000, 800],
                DFDC_TYPE_GREEN_SHIP => [1000, 800, 400, 1000, 1000],
                DFDC_TYPE_GREEN_FISH => [1000, 1000, 500, 1000, 1000],
                DFDC_TYPE_GREEN_YUANBAO => [1000, 1000, 600, 500, 1000],
                DFDC_TYPE_SCATTER => [1000, 1000, 700, 1000, 1000],
                DFDC_TYPE_COPPER => [1000, 1000, 800, 1000, 1000],
                DFDC_TYPE_WILD => [1000, 1000, 800, 80, 0],
                DFDC_TYPE_A => [1000, 1000, 1000, 1000, 1000],
                DFDC_TYPE_K => [1000, 1000, 1000, 1000, 1000],
                DFDC_TYPE_Q => [1000, 1000, 1000, 1000, 1000],
                DFDC_TYPE_J => [1000, 1000, 50, 1000, 1000],
                DFDC_TYPE_10 => [1000, 1000, 1000, 1000, 1000],
                DFDC_TYPE_9 => [1000, 1000, 0, 1000, 1000],
            ],
            'diamonds' => 20,
            'beishu' => 50,
            'probability' => 50,
            'ransport' => [10, 20],
            'free' => [
                'list' => [
                    DFDC_TYPE_GOLDEN_DRAGON => [1000, 800, 200, 800, 600],
                    DFDC_TYPE_GOLDEN_SHIP => [1000, 60, 50, 1000, 50],
                    DFDC_TYPE_GOLDEN_FISH => [1000, 1000, 100, 1000, 1000],
                    DFDC_TYPE_GOLDEN_YUANBAO => [1000, 800, 200, 1000, 1000],
                    DFDC_TYPE_GREEN_DRAGON => [1000, 1000, 300, 1000, 800],
                    DFDC_TYPE_GREEN_SHIP => [1000, 800, 400, 1000, 1000],
                    DFDC_TYPE_GREEN_FISH => [1000, 1000, 500, 1000, 1000],
                    DFDC_TYPE_GREEN_YUANBAO => [1000, 1000, 600, 500, 1000],
                    DFDC_TYPE_SCATTER => [1000, 1000, 700, 1000, 1000],
                    DFDC_TYPE_COPPER => [1000, 1000, 800, 1000, 1000],
                    DFDC_TYPE_WILD => [1000, 1000, 800, 80, 0],
                    DFDC_TYPE_A => [1000, 1000, 1000, 1000, 1000],
                    DFDC_TYPE_K => [1000, 1000, 1000, 1000, 1000],
                    DFDC_TYPE_Q => [1000, 1000, 1000, 1000, 1000],
                    DFDC_TYPE_J => [1000, 1000, 50, 1000, 1000],
                    DFDC_TYPE_10 => [1000, 1000, 1000, 1000, 1000],
                    DFDC_TYPE_9 => [1000, 1000, 0, 1000, 1000],
                ],
                'diamonds' => 20,
                'beishu' => 50,
                'probability' => 50,
            ],
            'jackpost' => 10000
        ];

        // $data = DBInstance::GetTableOneWord('control_dfdc', 'vals', ['level' => $control, 'gtype' => $this->roomRule['level']]);
        $data = $this->controlinfo[$control];

        if (!empty($data)) {
            // $data = json_decode($data, true);

            if (
                isset($data['ransport']) && is_array($data['ransport']) && count($data['ransport']) == 2 && isset($data['jackpost']) && is_int($data['jackpost']) && isset($data['probability']) && is_int($data['probability']) && isset($data['beishu']) && is_int($data['beishu']) && isset($data['list']) && is_array($data['list']) && count($data['list']) >= 17
                && isset($data['free']) && isset($data['free']['probability']) && is_int($data['free']['probability']) && isset($data['free']['beishu']) && is_int($data['free']['beishu'])
            ) {
                if ($this->roomRule['level'] != 3 || ($this->roomRule['level'] == 3 && isset($data['diamonds']) && is_int($data['diamonds']) || isset($data['free']['diamonds']) && is_int($data['free']['diamonds']))) {
                    $iscode = true;
                    foreach ($data['list'] as $key => $val) {
                        if (count($val) != 5 || !in_array($key, DFDC_TYPE_ALL)) {
                            $iscode = false;
                            break;
                        }
                    }

                    foreach ($data['free']['list'] as $key => $val) {
                        if (count($val) != 5 || !in_array($key, DFDC_TYPE_ALL)) {
                            $iscode = false;
                            break;
                        }
                    }

                    if ($iscode) {
                        $res = $data;
                    }
                }
            }
        }

        $this->ransport = $res['ransport'];
        $this->jackpost = $res['jackpost'];
        $this->controlinfos = $res;

        return $res;
    }
}

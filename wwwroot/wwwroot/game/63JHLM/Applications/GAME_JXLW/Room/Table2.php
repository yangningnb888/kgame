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
        3 => 5, 4 => 40, 5 => 90
    ],
    GRAPE => [
        3 => 5, 4 => 40, 5 => 90
    ],
    BELL => [
        3 => 8, 4 => 350, 5 => 85
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

define('SAVE_DATA', ['score' => 10000, 'beishu' => 50]); //需要存数据
define('SAVE_DATA_TYPE_SCORE', 1); //分数和倍数达到标准
define('SAVE_DATA_TYPE_FREE', 2); //免费次数
define('SAVE_DATA_TYPE_JACKPOT', 3); //中奖池
define('JACKPOT_SCORE_WIN', 10000);
define('FREE_SCORE_WIN', 100000);
define('JACKPOT_TAX', 0.1); //奖池抽水

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
        $this->InitMap();
        $this->userEnter($msg['players']);
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
            'free' => 0,
            'multiple' => MAX_BEI,
            'line' => ALL_LINE,
            'winNum' => 0, //连续赢得次数
            'ransport' => 0, //连续数的次数
            'freeData' => [],
            'freeScore' => 0,
        ];
        foreach ($PlayerInfo as $key => $val) {
            $this->uid = $key;
            $this->PlayerInfo = array_merge($val, $data);
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
            'gold' => $this->PlayerInfo['gold']
        ];

        //判断断线重连
        // if ($uid == 0) {
        Gateway::joinGroup($this->PlayerInfo['client_id'], 'ROOM:' . $this->roomRule['rid']);

        Logic::SendAll('Msg_JXLW_RoomInfo', $res, $this->roomRule['rid']);
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
    private function InitMap()
    {
        $this->map = [];
        for ($i = 0; $i < MAP_HIGH; $i++) {
            for ($j = 0; $j < MAP_WIDE; $j++) {
                $rand = array_rand(MAP_ALL);
                $this->map[$i][] = MAP_ALL[$rand];
            }
        }
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
    private function Msg_JXLW_Start($msg)
    {
        if (!is_int($msg['data']['multiple'])  || $msg['data']['multiple'] <= 0 || $msg['data']['multiple'] > MAX_BEI) {
            Logic::SendError($msg['uid'], 'Msg_JXLW_Start', '倍数错误');
            return;
        }

        $score = 0;
        if ($this->PlayerInfo['free'] <= 0) {
            $score = $msg['data']['multiple'] * $msg['data']['line'] * $this->roomRule['doublescore'];
        }

        if ($score > $this->PlayerInfo['gold']) {
            Logic::SendError($msg['uid'], 'Msg_JXLW_Start', '金币数量不够');
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
        
        $this->procedure(STAGE_START);

        $_data = $this->calculationWin($msg['data']);

        $data = $_data['res'];
        $data['conscore'] = $score;
        $data['map'] = $this->map;

        Logic::SendAll('Msg_JXLW_Start', $data, $this->roomRule['rid']);
        if ($score <= 0 || $this->PlayerInfo['free'] > 0) {
            $this->PlayerInfo['freeData'][] = $data;
            $this->PlayerInfo['freeScore'] += $_data['allScore'];
        } else {
            $jackpot_tax  = $score * JACKPOT_TAX;
            $this->SaveJackpot($jackpot_tax, 2);
            $this->PlayerInfo['freeScore'] = 0;
            $this->PlayerInfo['freeData'] = [];
        }

        $this->saveData($_data['allScore'], $_data['beishu'], $data);
    }

    /**
     * 计算中奖线数
     *
     * @return void
     */
    private function calculationWin($data)
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
            foreach ($val as $key1 => $val1) {
                if ($graphical == 0) {
                    $graphical = $this->map[$val1[0]][$val1[1]];
                    continue;
                }

                if (($this->map[$val1[0]][$val1[1]] == $graphical || ($graphical != SENVE &&  $graphical != DIAMONDS &&  $this->map[$val1[0]][$val1[1]] == BAR))) {
                    $_res++;
                } else {
                    break;
                }
            }

            if ($_res >= min(array_keys(WIN_CONDITION[$graphical]))) {
                $scoreArr = $this->score($_res, $graphical, $data['multiple']);
                $score += $scoreArr['score'];
                $jackpot += $scoreArr['jackpot'];
                $curfree += $scoreArr['curfree'];
                $multiple = 0;
                if ($graphical < DIAMONDS) {
                    $multiple = WIN_CONDITION[$graphical][$_res];
                }

                $beishu += $multiple;
                $allScore += $scoreArr['allScore'];
                $res['win'][] = ['num' => $_res, 'type' => $graphical, 'line' => $key, 'multiple' => $multiple];
            }

            // if ($_free >= min(array_keys(WIN_CONDITION[DIAMONDS]))) {
            //     $scoreArr = $this->score($_free, DIAMONDS, $data['multiple']);
            //     $score += $scoreArr['score'];
            //     $jackpot += $scoreArr['jackpot'];
            //     $curfree += $scoreArr['curfree'];
            //     $multiple = 0;
            //     $beishu += $multiple;
            //     $allScore += $scoreArr['allScore'];
            //     $res['win'][] = ['num' => $_free, 'type' => DIAMONDS, 'line' => $key, 'multiple' => $multiple];
            // }
        }

        if (empty($res)) {
            $res['win'] = [];
            $this->PlayerInfo['ransport']++;
        } else {
            $this->PlayerInfo['winNum']++;
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
    private function score($num, $type, $multiple)
    {
        $score = 0;
        $curfree = 0;
        $jackpot = 0;
        if ($type == DIAMONDS) {
            $this->PlayerInfo['free'] += WIN_CONDITION[$type][$num];
            $curfree =  WIN_CONDITION[$type][$num];
        } elseif ($type == BOX) {
            $this->roomRule['Jackpot'] = $this->SaveJackpot(0, 1);
            if ($this->roomRule['Jackpot'] === false) {
                $this->roomRule['Jackpot'] = $this->SaveJackpot(0, 1);
            }

            $jackpot = intval(WIN_CONDITION[$type][$num] * $this->roomRule['Jackpot']);
            $this->roomRule['Jackpot'] = $this->SaveJackpot(-$jackpot, 2);
        } else {
            $score =  WIN_CONDITION[$type][$num];
        }
        $score *= $multiple  * $this->roomRule['doublescore'];

        $allScore = $score  + $jackpot;
        $this->changScore($allScore);

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

        Logic::InsertProfit($this->roomRule['level'], $score);

    }
    /**
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);

        Logic::SendAll('Msg_JXLW_Out', ['gold' => $gold], $this->roomRule['rid']);
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
        $this->roomInfo($uid);
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
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
        if ($score >= SAVE_DATA['score'] && $beishu >= SAVE_DATA['beishu']) {
            $type = SAVE_DATA_TYPE_SCORE;
            $data['free'] = 0;
        } elseif ($data['jackpot'] > JACKPOT_SCORE_WIN) {
            $type = SAVE_DATA_TYPE_JACKPOT;
            $data['free'] = 0;
        } elseif ($this->PlayerInfo['freeScore'] > FREE_SCORE_WIN && $this->PlayerInfo['free'] <= 0) {
            $type = SAVE_DATA_TYPE_FREE;
            $data = $this->PlayerInfo['freeData'];
            $score = $this->PlayerInfo['freeScore'];
            $map = $this->freeAgoMap;
        }

        if ($type > 0) {
            $_data = [];
            $_data['uid'] =  $this->uid;
            $_data['line'] = $this->PlayerInfo['line'];
            $_data['doublescore'] =  $this->roomRule['doublescore'];
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

        if ($score >= 3000000 || $beishu >= 80) {
            Logic::HorseLamp($this->uid, $score, $beishu);
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
        $res = DBInstance::GetTableOneWord('game_jackpot', 'jackpot', ['gtype' => $this->roomRule['gtype'], 'level' => $this->roomRule['level']]);
        if ($res === false) {
            DBInstance::SaveData('game_jackpot', ['gtype' => $this->roomRule['gtype'], 'level' => $this->roomRule['level']]);
        }

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
        $this->disRoom = $msg['type'];
        $this->OldRoom();
    }
}

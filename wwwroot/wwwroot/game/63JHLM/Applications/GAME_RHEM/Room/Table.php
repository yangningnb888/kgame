<?php

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('LINE', [
    [1, 1, 1, 1, 1], [0, 0, 0, 0, 0], [2, 2, 2, 2, 2], [0, 1, 2, 1, 0], [2, 1, 0, 1, 2], [1, 0, 0, 0, 1], [1, 2, 2, 2, 1], [0, 0, 1, 2, 2], [2, 2, 1, 0, 0], [1, 2, 1, 0, 1],
    [1, 0, 1, 2, 1], [0, 1, 1, 1, 0], [2, 1, 1, 1, 2], [0, 1, 0, 1, 0], [2, 1, 2, 1, 2], [1, 1, 0, 1, 1], [1, 1, 2, 1, 1], [0, 0, 2, 0, 0], [2, 2, 0, 2, 2], [0, 2, 2, 2, 0],
    [2, 0, 0, 0, 2], [1, 2, 0, 2, 1], [1, 0, 2, 0, 1], [0, 2, 0, 2, 0], [2, 0, 2, 0, 2], [2, 0, 1, 2, 0], [0, 2, 1, 0, 2], [0, 2, 1, 2, 0], [2, 0, 1, 0, 2], [2, 1, 0, 0, 1],
    [0, 1, 2, 2, 1], [0, 0, 2, 2, 2], [2, 2, 0, 0, 0], [1, 0, 2, 1, 2], [1, 2, 0, 1, 0], [0, 1, 0, 1, 2], [2, 1, 2, 1, 0], [1, 2, 2, 0, 0], [0, 0, 1, 1, 2], [2, 2, 1, 1, 0],
    [2, 0, 0, 0, 0], [0, 2, 2, 2, 2], [2, 2, 2, 2, 0], [0, 0, 0, 0, 2], [1, 0, 1, 0, 1], [1, 2, 1, 2, 1], [0, 1, 2, 2, 2], [2, 1, 0, 0, 0], [0, 1, 1, 1, 1], [2, 1, 1, 1, 1],
]);

define('QUICK_FIRE', 100);  //奖池图标
define('FREE', 50);  //免费旋转
define('WILD', 0);  //wild
define('CHERRY', 1);  //樱桃
define('WATERMELON', 2);  //西瓜
define('PLUM', 3);  //李子
define('BELL', 4);  //铃铛
define('PURPLE', 11);  //紫色
define('GREEN', 12);  //紫色
define('RED', 13);  //红色
define('CHILD', 9);  //小孩

define('DOUBLE', [
    WILD => [3 => 5, 4 => 50, 5 => 250],
    CHERRY => [3 => 1, 4 => 3, 5 => 8],
    WATERMELON => [3 => 1, 4 => 3, 5 => 8],
    PLUM => [3 => 1, 4 => 3, 5 => 8],
    BELL => [3 => 1, 4 => 3, 5 => 8],
    PURPLE => [3 => 5, 4 => 10, 5 => 15],
    GREEN => [3 => 5, 4 => 10, 5 => 15],
    RED => [3 => 5, 4 => 10, 5 => 15],
    CHILD => [3 => 5, 4 => 20, 5 => 100]]);

define('POSSIBLE', [
    [QUICK_FIRE => 60, FREE => 20, WILD => 200, CHERRY => 280, WATERMELON => 280, CHILD => 80, PLUM => 100, PURPLE => 100, GREEN => 100, RED => 100],
    [QUICK_FIRE => 60, FREE => 20, WILD => 80, CHERRY => 280, WATERMELON => 280, CHILD => 80, PLUM => 150, PURPLE => 150, GREEN => 150, RED => 150],
    [QUICK_FIRE => 20, FREE => 20, WILD => 80, CHERRY => 200, WATERMELON => 200, CHILD => 20, PLUM => 200, PURPLE => 200, GREEN => 200, RED => 200],
    [QUICK_FIRE => 20, FREE => 20, WILD => 100, CHERRY => 150, WATERMELON => 150, CHILD => 80, PLUM => 200, PURPLE => 200, GREEN => 200, RED => 200],
    [QUICK_FIRE => 20, FREE => 20, WILD => 150, CHERRY => 150, WATERMELON => 150, CHILD => 80, PLUM => 200, PURPLE => 200, GREEN => 200, RED => 200],
]);

define('SEVENDOUBLE', [1 => 1, 2 => 2, 3 => 4]);
define('BIG_WIN', [4 => 20, 3 => 10, 2 => 5, 1 => 1, 0 => 1]);
define('COUNT', 50);
define('GAME_JACKPOT', [5 => 0.01, 6 => 0.05, 7 => 0.14, 8 => 0.3, 9 => 0.5]);
define('POS_JACKPOT', [5 => 500, 6 => 200, 7 => 20, 8 => 1, 9 => 0]);
define('QUICK_DOUBLE', [3 => 1, 4 => 2, 5 => 7, 6 => 30, 7 => 70, 8 => 700, 9 => 2100]);
define('FREE_GAME', [3 => 7, 4 => 14, 5 => 21]);
define('LEVEL', [1 => 100, 2 => 250, 3 => 500, 4 => 750, 5 => 1000, 6 => 1750, 7 => 2500, 8 => 3750, 9 => 5000, 10 => 7500, 11 => 10000, 12 => 15000, 13 => 20000, 14 => 25000, 15 => 37500, 16 => 50000]);

define('SVAEDATA', ['score' => 500000, 'double' => 20]);

class Table
{
    private $userInfo = []; //玩家信息
    private $free = 0;  //   当前免费次数
    private $freeWild = [];  //   免费中 wild 的列数
    private $mapCount = 1;   //   免费中的地图数量
    private $map = [];   //   地图
    private $curLevel = 1;  //    当前挡位
    private $roomRule = [];  //   房间规则
    private $gameControl = [];       //   场控信息
    private $control = [];       //  场控信息
    private $use = 0;   //  单局花费
    private $outGame = false;   //  解散房间标志
    private $saveJackpot = [];  //  奖池数据缓存
    private $saveData = ['score' => 0, 'double' => 0];  //  回放数据缓存
    private $history = [];  //  回放数据缓存
    private $mTimer = 0;   //定时器id
    private $gameSave = [
        'allfree' => 0,
        'allwin' => 0
    ];

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        foreach ($msg['players'] as $key => $value) {
            $this->userInfo = $value;
        }
        $this->roomRule = $msg;
        $this->gameControl = DBInstance::GetControlInfo('control_rhem');
        MyTools::log($this->roomRule['rid']);
        Logic::SendRight($this->userInfo['uid'], 'Msg_RHEM_RoomInfo', [
            'free' => $this->free,
            'freewild' => $this->freeWild,
            'freemap' => $this->mapCount,
            'curgrade' => $this->curLevel,
            'map' => $this->map,
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'gold' => $this->userInfo['gold'],
            'game_level' => LEVEL,
            'jackpot_level' => GAME_JACKPOT,
            'allwin' => $this->gameSave['allwin'],
            'allfree' => $this->gameSave['allfree'],
        ]);
    }

    /**
     * 所有消息回调
     * @param array
     */

    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_RHEM_Start':
                $this->Msg_RHEM_Start($message);
                break;
            case 'Msg_RHEM_Out':
                $this->Msg_RHEM_Out($message);
                break;
            case 'Msg_Game_Back_List':
                $this->Msg_Game_Back_List($message);
                break;
            case 'Msg_Game_Back_Info':
                $this->Msg_Game_Back_Info($message);
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
     * */
    private function Msg_RHEM_Start($message)
    {
        $free = $this->free;
        if ($this->free <= 0) {
            $this->curLevel = $message['data']['multiple'];
            $this->use = LEVEL[$this->curLevel] * COUNT;
        } else {
            $this->free--;
        }

        if ($message['uid'] != $this->userInfo['uid'] || ($this->userInfo['gold'] < $this->use && $free <= 0)) {
            Logic::SendError($message['uid'], $message['event'], '无效操作');
            return;
        }

        if (!isset($message['data']['multiple']) || $message['data']['multiple'] < 1 || $message['data']['multiple'] > 16) {
            Logic::SendError($message['uid'], $message['event'], '等级错误');
            return;
        }

        //获取当前奖池数据
        $this->saveJackpot = DBInstance::GetGameJackpot(['gtype' => $this->roomRule['gtype'], 'level' => $this->roomRule['level']]);

        if (empty($this->history)) {
            $this->history = [
                'uid' => $this->userInfo['uid'],
                'headimgurl' => $this->userInfo['headimgurl'],
                'gold' => $this->userInfo['gold'],
                'nickname' => $this->userInfo['nickname'],
                'pictureframe' => $this->userInfo['pictureframe'],
                'jackpot' => $this->saveJackpot['jackpot'],
                'curgrade' => $this->curLevel,
                'game_level' => LEVEL,
                'data' => []
            ];
        }

        $control = DBInstance::GetLBControl($this->userInfo['uid'], $this->roomRule['gtype'], $this->roomRule['level']);
        $cs['level'] = $control;
        $cs['mapinfo'] = $this->gameControl[$control];
        $this->control = $this->gameControl[$control];
        Logic::SendRight($this->userInfo['uid'], 'Msg_RHEM_ControlCS', $cs);
        //获取地图
        $this->map = $this->GetMap($free);
        $win = 0;  //地图得分
        $jackpot = 0;  // 下注得分
        $line = [];

        foreach ($this->map as $key => $value) {
            $result = $this->getResult($value);
            $win += $result['win'];
            $jackpot += $result['jackpot'];
            $line[] = $result['line'];
        }

        $data = [
            'line' => $line,
            'score' => $win,
            'conscore' => $free ? 0 : $this->use,
            'map' => $this->map,
            'free' => $this->free,
            'reward' => 1,
            'jackpot' => $jackpot,
            'collect' => 0,
            'collect_win' => 0,
            'roulette' => [
                'num' => 0,
                'wild' => []
            ],
        ];

        //中免费 随机局数、地图
        if ($result['free'] >= 3) {
            $rands = [2 => 180, 3 => 40, 4 => 5, 7 => 150];
            $_rand = rand(1, array_sum($rands));
            foreach ($rands as $key => $value) {
                if ($_rand <= $value) {
                    $data['roulette']['num'] = $key;
                    if ($key < 7) {
                        $this->mapCount = $key;
                    } else {
                        $this->free += $key;
                    }
                    break;
                } else {
                    $_rand -= $value;
                }
            }

            $wilds = [0 => [0], 1 => [0, 4], 2 => [0, 2], 3 => [1, 4], 4 => [0, 2], 5 => [1, 3], 6 => [0, 1, 2], 7 => [0, 1, 3, 4]];
            $rands = [0 => 200, 1 => 200, 2 => 80, 3 => 40, 4 => 10, 5 => 10, 6 => 2, 7 => 2];
            $_rand = rand(1, array_sum($rands));
            foreach ($rands as $key => $value) {
                if ($_rand <= $value) {
                    $data['roulette']['wild'] = $wilds[$key];
                    $this->freeWild = $wilds[$key];
                    break;
                } else {
                    $_rand -= $value;
                }
            }
        }

        //中奖池
        $pos = !isset($this->control['collectpos']) ? 1 : $this->control['collectpos'];
        $type = $free ? 7 : 6;
        if (rand(1, 1000) <= $pos && !$free && $this->free <= 0 && $result['wild'] > 0) {
            $possible = empty($this->control['jackpotpos']) ? POS_JACKPOT : $this->control['jackpotpos'];
            $_rand = rand(1, array_sum($possible));
            foreach ($possible as $key => $value) {
                if ($_rand <= $value) {
                    $data['collect'] = $key;
                    $data['collect_win'] = intval($this->saveJackpot['jackpot'] * LEVEL[$this->curLevel] * GAME_JACKPOT[$key] / array_sum(LEVEL));
                    $type = $key - 4;
                    break;
                } else {
                    $_rand -= $value;
                }
            }
        }

        $upgold = $free ? $win + $jackpot + $data['collect_win'] : $win - $this->use + $jackpot + $data['collect_win'];
        $jiangchi = $upgold < 0 ? intval(abs($upgold) * $this->saveJackpot['probability'] / 100) : 0;

        if ($this->roomRule['level'] != 1 && !empty($result)) {
            DBInstance::IncrementUserGet($this->userInfo['uid'], $upgold);
            if (abs($control) != 2) {
                Logic::InsertProfit($this->roomRule['level'], $upgold + $jiangchi - $data['collect_win']);
            }
        }

        $_win = $free ? $upgold : $upgold + $this->use;
        $this->saveData['score'] += $_win;
        if ($_win >= 3000000) {
            Logic::HorseLamp($this->userInfo['uid'], $_win, 0);
        } elseif (intval($_win / $this->use) >= 80) {
            Logic::HorseLamp($this->userInfo['uid'], $_win, intval($_win / $this->use));
        }

        //奖金大小判定
        $win_double = intval(($upgold + $jiangchi) / $this->use);
        foreach (BIG_WIN as $key => $value) {
            if ($win_double >= $value) {
                $data['reward'] = $key;
                break;
            }
        }

        //修改奖池金额=本轮入库奖池金额-本轮消耗奖池金额
        DBInstance::SaveJackpot($this->roomRule['gtype'], $this->roomRule['level'], $jiangchi - $data['collect_win']);
        $this->useGold($upgold);
        $data['free'] = $this->free;
        if ($this->free || $free || (intval($this->saveData['score'] / $this->use) >= SVAEDATA['double'] && $this->saveData['score'] >= SVAEDATA['score'])) {
            $this->history['data'][] = $data;
        }
        Logic::SendRight($this->userInfo['uid'], 'Msg_RHEM_Start', $data);

        if ($this->free <= 0) {
            if ((intval($this->saveData['score'] / $this->use) >= SVAEDATA['double'] && $this->saveData['score'] >= SVAEDATA['score']) && !empty($this->history['data'])) {
                //1免费 2 大奖
                DBInstance::SaveData('game_back', [
                    'type' => $type,
                    'gtype' => $this->roomRule['gtype'],
                    'vals' => json_encode($this->history),
                    'level' => $this->roomRule['level'],
                    'created' => MyTools::GET_NOW(),
                    'score' => $this->saveData['score'],
                    'nickname' => $this->userInfo['nickname'],
                    'playnum' => 0
                ]);
            }
            $this->history = [];
            $this->saveData = ['score' => 0, 'double' => 0];
            $this->free = 0;
            $this->freeWild = [];
            $this->mapCount = 1;
            $this->gameSave = [
                'allfree' => 0,
                'allwin' => 0
            ];

            if ($this->outGame) {
                $this->DisRoom();
            }
        } elseif ($this->free > 0 && $free == 0) {
            $this->gameSave['allfree'] = $this->free;
        } else {
            $this->gameSave['allwin'] += $data['score'] + $data['collect_win'] + $data['jackpot'];
        }
    }

    /**
     * 回放列表
     * */
    private function Msg_Game_Back_List($message)
    {
        Logic::SendRight($this->userInfo['uid'], 'Msg_Game_Back_List', DBInstance::GetBackAll($this->roomRule['gtype']));
    }

    /**
     * 回放列表
     * */
    private function Msg_Game_Back_Info($message)
    {
        if (!isset($message['data']['id']) || !is_numeric($message['data']['id'])) {
            Logic::SendError($message['uid'], $message['event'], '参数错误');
            return;
        }
        $data = DBInstance::GetBankOne($message['data']['id']);
        Logic::SendRight($this->userInfo['uid'], 'Msg_Game_Back_List', $data);
    }

    /*
     * 退出房间
     * */
    private function Msg_RHEM_Out($message)
    {
        Timer::del($this->mTimer);
        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->userInfo['uid']]);
        Logic::SendRight($this->userInfo['uid'], 'Msg_RHEM_Out', ['gold' => $gold]);
        $olddata = [
            'rid' => $this->roomRule['rid'],
            'palyers' => [$this->userInfo['uid'] => $this->userInfo],
            'gtype' => $this->roomRule['gtype'],
        ];
        Logic::RoomOld($olddata);
    }

    /*
     * 生成地图
     * */
    private function GetMap($free = 0)
    {
        $map = [];
        $rand = empty($this->control['map']) ? POSSIBLE : $this->control['map'];

        for ($k = 0; $k < $this->mapCount; $k++) {
            for ($i = 0; $i < 3; $i++) {
                for ($j = 0; $j < 5; $j++) {
                    if (!empty($this->freeWild) && in_array($j, $this->freeWild)) {
                        $map[$k][$i][$j] = WILD;
                    } else {
                        $_rand = rand(1, array_sum($rand[$j]));
                        foreach ($rand[$j] as $key => $value) {
                            $_rand -= $value;
                            if ($free && $key == FREE) {
                                continue;
                            }
                            if ($_rand <= 0) {
                                $map[$k][$i][$j] = $key;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $map;
    }

    /**
     * 游戏结果
     * @param bool
     * @return array
     */
    private function getResult($map)
    {
        $res = [
            'line' => [],
            'win' => 0,   //地图得分
            'jackpot' => 0,   //总下注奖池
            'free' => 0,   //免费个数
            'wild' => 0,
        ];
        $free = 0;
        $quick = 0;
        foreach ($map as $key => $value) {
            foreach ($value as $key1 => $value1) {
                if ($value1 == QUICK_FIRE) {
                    $quick++;
                } elseif ($value1 == FREE) {
                    $free++;
                } elseif ($value1 == WILD) {
                    $res['wild']++;
                }
            }
        }

        if ($free >= 3) {
            if ($free < 5) {
                $this->free += FREE_GAME[$free];
            } else {
                $this->free += FREE_GAME[5];
            }
        }

        if ($quick >= 3 && $quick <= 9) {
            $res['jackpot'] = $this->use * QUICK_DOUBLE[$quick];
        } elseif ($quick > 9) {
            $res['jackpot'] = $this->use * QUICK_DOUBLE[9];
        }

        foreach (LINE as $key => $value) {
            $_flag = 0;
            $seven = [PURPLE => 0, GREEN => 0, RED => 0];
            foreach ($value as $key1 => $value1) {
                if ($map[$value1][$key1] > 20) {
                    $key1--;
                    break;
                } elseif ($map[$value1][$key1] == WILD || $_flag == WILD || $_flag == $map[$value1][$key1]) {
                    if ($map[$value1][$key1] != WILD) {
                        $_flag = $map[$value1][$key1];
                        if ($map[$value1][$key1] > 10) {
                            $seven[$map[$value1][$key1]] = 1;
                        }
                    }
                } elseif ($_flag != $map[$value1][$key1] && $_flag > 10 && $map[$value1][$key1] > 10) {
                    $seven[$map[$value1][$key1]] = 1;
                } else {
                    $key1--;
                    break;
                }
            }

            if (isset($key1) && $key1 >= 2) {
                $res['line'][$key] = $key1 + 1;

                if ($_flag < 10) {
                    $res['win'] += LEVEL[$this->curLevel] * DOUBLE[$_flag][$key1 + 1];
                } else {
                    $double = round(1 / SEVENDOUBLE[array_sum($seven)], 2);
                    $res['win'] += intval(LEVEL[$this->curLevel] * DOUBLE[$_flag][$key1 + 1] * $double);
                }
            }
            $res['free'] = $free;
        }

        return $res;
    }

    /**
     * 玩家金钱变化
     * @param [type] $msg
     * @return void
     */
    private function useGold($gold)
    {
        if ($gold != 0) {
            if ($this->roomRule['level'] > 1) {
                DBInstance::IncrementGolds('gold', $this->userInfo['uid'], $gold);
            }
            $this->userInfo['gold'] += $gold;
        }
    }

    /**
     * 玩家重连
     * @param string
     * @param int
     */
    public function UserOnline($client_id, $uid)
    {
        Timer::del($this->mTimer);
        Logic::SendRight($this->userInfo['uid'], 'Msg_RHEM_RoomInfo', [
            'free' => $this->free,
            'freewild' => $this->freeWild,
            'freemap' => $this->mapCount,
            'curgrade' => $this->curLevel,
            'map' => $this->map,
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'gold' => $this->userInfo['gold'],
            'game_level' => LEVEL,
            'jackpot_level' => GAME_JACKPOT,
            'allwin' => $this->gameSave['allwin'],
            'allfree' => $this->gameSave['allfree'],
        ]);
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
        $this->mTimer = Timer::add(OUT_TIME, function () {
            $this->Msg_RHEM_Out(['uid' => $this->userInfo['uid']]);
        }, [], false);
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
        $uid = $msg['uid'];
        $this->userInfo['gold'] = DBInstance::GetUserOneWord('gold', $uid);
    }

    /**
     * 强制解散房间
     *
     * @param [type] $msg
     * @return void
     */
    public function DisRoom($msg = [])
    {
        if (!empty($msg)) {
            $this->outGame = true;
        } else {
            $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->userInfo['uid']]);
            Logic::SendRight($this->userInfo['uid'], 'Msg_RHEM_Out', ['gold' => $gold]);

            $olddata = [
                'rid' => $this->roomRule['rid'],
                'palyers' => [$this->userInfo['uid'] => $this->userInfo],
                'gtype' => $this->roomRule['gtype'],
            ];
            Logic::RoomOld($olddata);
        }
    }
}

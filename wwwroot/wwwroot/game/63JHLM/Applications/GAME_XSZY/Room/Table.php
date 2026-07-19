<?php

date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define('LINE', [
    [1, 1, 1, 1, 1], [1, 1, 2, 1, 1], [1, 1, 0, 1, 1], [1, 2, 2, 2, 1], [1, 0, 0, 0, 1], [1, 2, 1, 2, 1], [1, 0, 1, 0, 1], [1, 1, 1, 2, 1], [1, 1, 1, 0, 1], [1, 2, 1, 1, 1],
    [1, 0, 1, 1, 1], [1, 2, 0, 2, 1], [1, 0, 2, 0, 1], [1, 2, 3, 2, 1], [1, 1, 3, 1, 1], [1, 3, 3, 3, 1], [1, 3, 2, 3, 1], [1, 3, 1, 3, 1], [1, 3, 0, 3, 1], [1, 0, 3, 0, 1],
    [1, 1, 2, 2, 1], [1, 1, 0, 0, 1], [1, 2, 2, 1, 1], [1, 0, 0, 1, 1], [1, 2, 1, 0, 1], [1, 0, 1, 2, 1], [1, 2, 3, 0, 1], [1, 2, 3, 1, 1], [1, 3, 3, 0, 1], [1, 3, 3, 1, 1],
    [0, 0, 0, 0, 0], [0, 0, 1, 0, 0], [0, 1, 1, 1, 0], [0, 1, 2, 1, 0], [0, 1, 0, 1, 0], [0, 0, 0, 1, 0], [0, 1, 0, 0, 0], [0, 0, 1, 1, 0], [0, 1, 1, 0, 0], [0, 0, 2, 0, 0],
    [0, 0, 3, 0, 0], [0, 1, 3, 1, 0], [0, 2, 0, 2, 0], [0, 2, 1, 2, 0], [0, 2, 2, 2, 0], [0, 2, 3, 2, 0], [0, 3, 0, 3, 0], [0, 3, 1, 3, 0], [0, 3, 2, 3, 0], [0, 3, 3, 3, 0],
    [3, 3, 3, 3, 3], [3, 3, 2, 3, 3], [3, 2, 2, 2, 3], [3, 2, 1, 2, 3], [3, 2, 3, 2, 3], [3, 3, 3, 2, 3], [3, 2, 3, 3, 3], [3, 3, 2, 2, 3], [3, 2, 2, 3, 3], [3, 3, 1, 3, 3],
    [3, 3, 0, 3, 3], [3, 2, 0, 2, 3], [3, 1, 3, 1, 3], [3, 1, 2, 1, 3], [3, 1, 1, 1, 3], [3, 1, 0, 1, 3], [3, 0, 3, 0, 3], [3, 0, 2, 0, 3], [3, 0, 1, 0, 3], [3, 0, 0, 0, 3],
    [2, 2, 2, 2, 2], [2, 2, 1, 2, 2], [2, 2, 3, 2, 2], [2, 1, 1, 1, 2], [2, 3, 3, 3, 2], [2, 1, 2, 1, 2], [2, 3, 2, 3, 2], [2, 2, 2, 1, 2], [2, 2, 2, 3, 2], [2, 1, 2, 2, 2],
    [2, 3, 2, 2, 2], [2, 1, 3, 1, 2], [2, 3, 1, 3, 2], [2, 1, 0, 1, 2], [2, 2, 0, 2, 2], [2, 0, 0, 0, 2], [2, 0, 1, 0, 2], [2, 0, 2, 0, 2], [2, 0, 3, 0, 2], [2, 3, 0, 3, 2],
    [2, 2, 1, 1, 2], [2, 2, 3, 3, 2], [2, 1, 1, 2, 2], [2, 3, 3, 2, 2], [2, 1, 2, 3, 2], [2, 3, 2, 1, 2], [2, 1, 0, 3, 2], [2, 1, 0, 2, 2], [2, 0, 0, 3, 2], [2, 0, 0, 2, 2],
]);

define('WILD', 100);  //wild
define('C_WILD', 120);  //c_wild
define('S_WILD', 130);   //s_wild
define('SCATTER', 30);   //scatter
define('CANDLE', 20);   //蜡烛
define('COIN_CAT', 11);  //猫
define('COIN_BAT', 10);   //蝙蝠
define('COIN_SPIDER', 9);  //蜘蛛
define('COIN_OWL', 8);  //猫头鹰
define('COIN_PALM', 7);   //手掌
define('COIN_HEAD', 6);   //骷髅
define('COIN_A', 5);    //A
define('COIN_K', 4);    //K
define('COIN_Q', 3);    //Q
define('COIN_J', 2);   //J
define('COIN_TEN', 1);  //10

define('DOUBLE', [
    WILD => [3 => 10, 4 => 25, 5 => 50],
    C_WILD => [3 => 10, 4 => 25, 5 => 50],
    S_WILD => [3 => 10, 4 => 25, 5 => 50],
    COIN_CAT => [3 => 10, 4 => 20, 5 => 75],
    COIN_BAT => [3 => 10, 4 => 15, 5 => 60],
    COIN_SPIDER => [3 => 5, 4 => 15, 5 => 50],
    COIN_OWL => [3 => 5, 4 => 10, 5 => 30],
    COIN_PALM => [3 => 5, 4 => 10, 5 => 15],
    COIN_HEAD => [3 => 5, 4 => 10, 5 => 20],
    COIN_A => [3 => 2, 4 => 5, 5 => 15],
    COIN_K => [3 => 2, 4 => 5, 5 => 15],
    COIN_Q => [3 => 2, 4 => 5, 5 => 15],
    COIN_J => [3 => 1, 4 => 5, 5 => 10],
    COIN_TEN => [3 => 1, 4 => 5, 5 => 10],
]);

define('POSSIBLE', [
    WILD => 50, C_WILD => 10, S_WILD => 20, COIN_CAT => 50, COIN_BAT => 50, COIN_SPIDER => 50, COIN_OWL => 50, CANDLE => 15, SCATTER => 20,
    COIN_PALM => 50, COIN_HEAD => 50, COIN_A => 50, COIN_K => 50, COIN_Q => 50, COIN_J => 50, COIN_TEN => 50,]);

define('FREEPOSSIBLE', [
    WILD => 20, C_WILD => 5, S_WILD => 2, COIN_CAT => 50, COIN_BAT => 50, COIN_SPIDER => 50, COIN_OWL => 50, CANDLE => 15, SCATTER => 8,
    COIN_PALM => 50, COIN_HEAD => 50, COIN_A => 50, COIN_K => 50, COIN_Q => 50, COIN_J => 50, COIN_TEN => 50,
]);

define('WILDPOSSIBLE', [WILD => 50, C_WILD => 8, S_WILD => 3]);

define('SVAEDATA', ['score' => 500000, 'double' => 20]);
define('BIG_WIN', [4 => 15, 3 => 10, 2 => 5, 1 => 0]);
define('SCORE', 100000);
define('COUNT', 100);

class Table
{
    private $userInfo = [];
    private $uid = 0;  //uid
    private $free = 0;  //当前免费次数
    private $freeCandle = 0;  //免费蜡烛数量
    private $map = [];   //地图
    private $curLevel = 1;  //当前挡位
    private $roomRule = [];  //
    private $gold = 0;
    private $collect = [];   //收集
    private $freewildcount = 0;   //免费女巫列数
    private $freewild = [];  //免费wild下标
    private $possible = [];   //图标概率
    private $wildPossible = [];    //WILD 图标概率
    private $normalPossible = [];   //普通图标概率
    private $control = [];       //场控信息
    private $history = [];    //回放信息
    private $saveData = ['score' => 0, 'double' => 0];
    private $use = 0;   //单局花费
    private $gameControl = [];
    private $outGame = false;
    private $mTimer = 0;   //定时器id

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        foreach ($msg['players'] as $key => $value) {
            $this->uid = $key;
            $this->gold = $value['gold'];
            $this->userInfo = $value;
        }
        $this->roomRule = $msg;
        $ret = DBInstance::GetXSZYYGameCache($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $this->gameControl = DBInstance::GetControlInfo('control_xszy');

        //获取玩家缓存
        if (!$ret) {
            $this->collect = [1 => [0, 0, 0, 0, 0], 2 => [0, 0, 0, 0, 0], 3 => [0, 0, 0, 0, 0], 4 => [0, 0, 0, 0, 0], 5 => [0, 0, 0, 0, 0], 6 => [0, 0, 0, 0, 0]];
            DBInstance::InsertXSZYGameCache($this->uid, $this->roomRule['gtype'], $this->roomRule['level'], $this->collect);
        } else {
            $this->collect = $ret;
        }

        Logic::SendRight($this->uid, 'Msg_XSZY_RoomInfo', [
            'free' => $this->free,
            'freecandle' => $this->freeCandle,
            'curgrade' => $this->curLevel,
            'map' => $this->map,
            'max_multiple' => 6,
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'gold' => $this->gold,
            'collect' => $this->collect,
            'freewild' => $this->free ? $this->freewild : []
        ]);
    }

    /**
     * 所有消息回调
     * @param array
     */

    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_XSZY_Start':
                $this->Msg_XSZY_Start($message);
                break;
            case 'Msg_XSZY_Out':
                $this->Msg_XSZY_Out($message);
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

    /*
     * 开始游戏
     * */
    private function Msg_XSZY_Start($message)
    {
        $cs = [
            'mapinfo' => [],
            'level' => 0,
        ];

        $free = $this->free;
        $this->curLevel = $message['data']['multiple'];
        $use = $this->free <= 0 ? $this->roomRule['doublescore'] * $this->curLevel * COUNT : 0;
        if ($this->free <= 0) {
            $this->use = $use;
        }
        $control = DBInstance::GetLBControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $cs['level'] = $control;
        $cs['mapinfo'] = $this->gameControl[$control];
        $this->control = $this->gameControl[$control];
        //Logic::SendRight($this->uid, 'Msg_XSZY_ControlCS', $cs);

        if (empty($this->history)) {
            $this->history = [
                'uid' => $this->uid,
                'doublescore' => $this->roomRule['doublescore'],
                'multiple' => $this->curLevel,
                'collect' => $this->collect,
                'headimgurl' => $this->userInfo['headimgurl'],
                'curgold' => $this->userInfo['gold'],
                'nickname' => $this->userInfo['nickname'],
                'pictureframe' => $this->userInfo['pictureframe'],
                'data' => []
            ];
        }

        //换算图标概率
        if (empty($this->control) || !is_array($this->control['map'])) {
            for ($i = 0; $i < 5; $i++) {
                $this->possible[$i] = POSSIBLE;
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                $this->possible[$i] = [];
                foreach ($this->control['map'] as $key => $value) {
                    $this->possible[$i][$key] = $value[$i];
                }

                if (count($this->possible[$i]) < 16) {
                    $this->possible[$i] = POSSIBLE;
                }
            }
        }

        //WILD 图标概率
        foreach ($this->possible as $key => $value) {
            foreach ($value as $key1 => $value1) {
                if ($key1 >= 100) {
                    $this->wildPossible[$key][$key1] = $value1;
                } elseif ($key1 < 20) {
                    $this->normalPossible[$key][$key1] = $value1;
                }
            }
        }

        if ($message['uid'] != $this->uid || $this->gold < $use) {
            Logic::SendError($message['uid'], $message['event'], '无效操作');
            return;
        }

        if (!isset($message['data']['multiple']) || $message['data']['multiple'] < 1 || $message['data']['multiple'] > 6) {
            Logic::SendError($message['uid'], $message['event'], '等级错误');
            return;
        }

        if ($this->free <= 0) {
            $this->useGold(-$use);
        }

        $this->map = $this->GetMap();
        $result = $this->getResult($this->map);
        $reward = 1;

        if ($this->free - $free < 0 || $this->free <= 0) {
            $getfree = 0;
        } elseif ($free <= 0) {
            $getfree = $this->free;
        } else {
            $getfree = $this->free - $free + 1;
        }

        $this->useGold($result['win']);
        if ($result['win'] > SCORE) {
            foreach (BIG_WIN as $key => $value) {
                if (intval($result['win'] / $this->use) >= $value) {
                    $reward = $key;
                    break;
                }
            }
        }

        if ($result['win'] >= 3000000) {
            Logic::HorseLamp($this->uid, $result['win'], 0);
        } elseif ($use > 0 && intval($result['win'] / $use) >= 80) {
            Logic::HorseLamp($this->uid, $result['win'], intval($result['win'] / $use));
        }

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($this->uid, $result['win'] - $use);
        }
        $data = [
            'line' => $result['line'],
            'win' => $result['win'],
            'conscore' => $free > 0 ? 0 : $use,
            'map' => $this->map,
            'free' => $this->free,
            'curgetfree' => $getfree,
            'freecandle' => $this->freeCandle,
            'multiple' => $this->curLevel,
            'reward' => $reward,
            'collect' => $this->collect[$this->curLevel],
            'freewild' => $free ? $this->freewild : []
        ];

        if (abs($control) != 2) {
            Logic::InsertProfit($this->roomRule['level'], $result['win'] - $use);
        }
        Logic::SendRight($this->uid, 'Msg_XSZY_Start', $data);

        $this->saveData['score'] += $result['win'];
        if ($this->free || $free || (intval($this->saveData['score'] / $this->use) >= SVAEDATA['double'] && $this->saveData['score'] >= SVAEDATA['score'])) {
            $this->history['data'][] = $data;
        }

        if ($this->free <= 0) {
            if ((intval($this->saveData['score'] / $this->use) >= SVAEDATA['double'] && $this->saveData['score'] >= SVAEDATA['score']) && !empty($this->history['data'])) {
                //1免费 2 大奖
                DBInstance::SaveData('game_back', [
                    'type' => $free ? 1 : 2,
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
            $this->freeCandle = 0;

            if ($this->outGame) {
                $this->DisRoom();
            }
        }
    }

    /**
     * 回放列表
     * */
    private function Msg_Game_Back_List($message)
    {
        Logic::SendRight($this->uid, 'Msg_Game_Back_List', DBInstance::GetBackAll($this->roomRule['gtype']));
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
        Logic::SendRight($this->uid, 'Msg_Game_Back_List', $data);
    }

    /*
     * 退出房间
     * */
    private function Msg_XSZY_Out($message)
    {
        Timer::del($this->mTimer);
        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);
        Logic::SendRight($this->uid, 'Msg_XSZY_Out', ['gold' => $gold]);
        DBInstance::UpdateXSZYGameCache($this->uid, $this->roomRule['gtype'], $this->roomRule['level'], $this->collect);

        $olddata = [
            'rid' => $this->roomRule['rid'],
            'win' => [],
            'palyers' => [$this->uid => $this->userInfo],
            'result' => [
                'uid' => []
            ], //结算消息
            'gtype' => $this->roomRule['gtype'],
        ];
        Logic::RoomOld($olddata);
    }

    /*
     * 生成地图
     * */
    private function GetMap()
    {
        $this->freewild = [];
        $map = [];
        $arr = [0, 0, 2, 3, 4, 4, 1, 3];
        $free = $this->free > 0 ? true : false;
        $candles = [];

        if ($free) {
            $this->free--;
            for ($i = 0; $i < $this->freewildcount; $i++) {
                $_rand = $arr[array_rand($arr)];
                $this->freewild[] = $_rand;
                $arr = array_diff($arr, [$_rand]);
            }
        } else {
            foreach ($this->collect[$this->curLevel] as $key => $value) {
                if ($value < 0) {
                    $this->freewild[] = $key;
                    $this->collect[$this->curLevel][$key]++;
                }
            }
        }

        $collects = [];
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 5; $j++) {
                if (!in_array($j, $this->freewild)) {
                    $possible = $this->possible[$j];
                    $sum = array_sum($possible);
                    $rand = rand(1, $sum);
                    $_flag = 0;
                    foreach ($possible as $key => $value) {
                        $_flag += $value;
                        if ($rand <= $_flag) {
                            $map[$j][$i] = $key;
                            if ($key == SCATTER || $key == S_WILD) {
                                $collects[] = $j * 10 + $i;
                            }

                            if ($key == CANDLE || $key == C_WILD) {
                                $candles[] = $j * 10 + $i;
                            }
                            break;
                        }
                    }
                } else {
                    $wildwum = array_sum($this->wildPossible[$j]);
                    //生成一列女巫 并且 减去一个蜡烛
                    $rand = rand(1, $wildwum);
                    $_flag = 0;
                    foreach ($this->wildPossible[$j] as $key => $value) {
                        $_flag += $value;
                        if ($rand <= $_flag) {
                            $map[$j][$i] = $key;
                            if ($key == C_WILD) {
                                $candles[] = $j * 10 + $i;
                            } elseif ($key == S_WILD) {
                                $collects[] = $j * 10 + $i;
                            }
                            break;
                        }
                    }
                }
            }
        }

        if (count($collects) >= 3) {
            $_count = count($collects);
            if ($free) {
                //免费中的免费
                foreach ($collects as $key => $value) {
                    $index = intval($value / 10);
                    $get = in_array($index, $this->freewild) ? 3 : 1;
                    $res = $this->changeCoin($get, $index, 100);
                    if ($res > 0) {
                        $map[$index][$value % 10] = $res;
                        $_count--;
                    }
                }
            }

            if ($this->free <= 0) {
                $this->freewildcount = 1;
            }

            if ($_count >= 3) {
                $this->free += 8;
            }
        }

        //替换蜡烛
        foreach ($candles as $key => $value) {
            $index = intval($value / 10);
            $tindex = $value % 10;
            if ($free || in_array($index, $this->freewild)) {
                $get = in_array($index, $this->freewild) ? 3 : 2;
                $changecandle = 0;
                if ($free && count($this->freewild) == 3) {
                    if ($this->freeCandle >= 3) {
                        $changecandle = 100;
                    } else {
                        $changecandle = 80;
                    }
                } elseif ($free && count($this->freewild) >= 4) {
                    if ($this->freeCandle >= 3) {
                        $changecandle = 100;
                    } else {
                        $changecandle = 100;
                    }
                }
                $res = $this->changeCoin($get, $index, $changecandle);
                if ($res > 0) {
                    $map[$index][$tindex] = $res;
                }
            }

            if ($map[$index][$tindex] == CANDLE || $map[$index][$tindex] == C_WILD) {
                if ($free) {
                    $this->freeCandle++;
                    if ($this->freeCandle >= 4) {
                        $this->freeCandle -= 4;
                        $this->freewildcount++;
                        if ($this->freewildcount > 5) {
                            $this->freewildcount = 5;
                        }
                        $this->free++;
                    }
                } else {
                    if ($this->collect[$this->curLevel][$index] < 0) {
                        $this->collect[$this->curLevel][$index]--;
                    } else {
                        $this->collect[$this->curLevel][$index]++;
                    }
                }
            }
        }

        foreach ($this->collect[$this->curLevel] as $key => $value) {
            if ($value >= 4) {
                $this->collect[$this->curLevel][$key] = -4;
            }
        }

        return $map;
    }

    /**
     * 替换图标
     * @param int $get 1 替换免费 2 替换蜡烛 3 替换女巫
     * @param int
     * @return int
     */
    private function changeCoin($get, $index, $changecandle = 0)
    {
        $ret = 0;
        if ($get == 3) {
            $rand = $this->control['changecwild'] ?? 0;
            if ($changecandle != 0) {
                $rand = $changecandle;
            }
        } elseif ($get == 2) {
            $rand = $this->control['changecandle'] ?? 0;
            if ($changecandle != 0) {
                $rand = $changecandle;
            }
        } else {
            $rand = $this->control['changefree'] ?? 0;
            if ($changecandle != 0) {
                $rand = $changecandle;
            }
        }

        if (rand(1, 100) <= $rand) {
            if ($get == 3) {
                $ret = WILD;
            } else {
                $_rand = rand(1, array_sum($this->normalPossible[$index]));
                $_flag = 0;
                foreach ($this->normalPossible[$index] as $key => $value) {
                    $_flag += $value;
                    if ($_rand <= $_flag) {
                        $ret = $key;
                        break;
                    }
                }
            }
        }

        return $ret;
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
            'win' => 0,
        ];

        foreach (LINE as $key => $value) {
            foreach ($value as $key1 => $value1) {
                if ($key1 == 0) {
                    if ($map[$key1][$value1] != CANDLE) {
                        $_flag = $map[$key1][$value1];
                    } else {
                        $key1--;
                        break;
                    }
                } elseif (!isset($_flag) || $map[$key1][$value1] != $_flag && ($map[$key1][$value1] < 100 || $_flag >= 100 || $map[$key1][$value1] == CANDLE)) {
                    $key1--;
                    break;
                }
            }

            if (isset($key1) && $key1 >= 2 && isset($_flag) && $_flag != SCATTER) {
                $res['line'][] = [
                    'linenum' => $key,
                    'len' => $key1 + 1,
                ];
                $this->saveData['double'] += DOUBLE[$_flag][$key1 + 1];
                $res['win'] += $this->curLevel * $this->roomRule['doublescore'] * DOUBLE[$_flag][$key1 + 1];
            }
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
                DBInstance::IncrementGolds('gold', $this->uid, $gold);
            }
            $this->gold += $gold;
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
        Logic::SendRight($this->uid, 'Msg_XSZY_RoomInfo', [
            'free' => $this->free,
            'freecandle' => $this->freeCandle,
            'curgrade' => $this->curLevel,
            'map' => $this->map,
            'max_multiple' => 6,
            'level' => $this->roomRule['level'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'gold' => $this->gold,
            'collect' => $this->collect,
            'freewild' => $this->free ? $this->freewild : []
        ]);
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
        $this->mTimer = Timer::add(OUT_TIME, function () {
            $this->Msg_XSZY_Out(['uid' => $this->uid]);
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
        $this->gold = DBInstance::GetUserOneWord('gold', $uid);
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
            $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);
            Logic::SendRight($this->uid, 'Msg_XSZY_Out', ['gold' => $gold]);

            $olddata = [
                'rid' => $this->roomRule['rid'],
                'win' => [],
                'palyers' => [$this->uid => $this->userInfo],
                'result' => [
                    'uid' => []
                ], //结算消息
                'gtype' => $this->roomRule['gtype'],
            ];
            Logic::RoomOld($olddata);
        }
    }
}

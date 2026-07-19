<?php

date_default_timezone_set("Asia/Shanghai");
require_once  __DIR__ . "/back.php";

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

define("STAGE_WAIT", 0);  //等待开始
define("STAGE_START", 1);  //等待开始
define("STAGE_OLD", 2);  //解散
define('MAP_HIGH', 3); //地图高
define('MAP_WIDE', 5); //地图宽

define('LDYX_TYPE_BAR', 1); //BAR
define('LDYX_TYPE_BAR50', 2); //BAR X50
define('LDYX_TYPE_SEVEN', 3); // 7
define('LDYX_TYPE_SEVEN3', 4); //7 X3
define('LDYX_TYPE_STAR', 5); //星星
define('LDYX_TYPE_STAR3', 6); //星星 X3
define('LDYX_TYPE_WATERMENLON', 7); //西瓜
define('LDYX_TYPE_WATERMENLON3', 8); //西瓜 X3
define('LDYX_TYPE_BELL', 9); //铃铛 
define('LDYX_TYPE_BELL3', 10); // 铃铛 X3
define('LDYX_TYPE_MANGO', 11); // 柠檬 
define('LDYX_TYPE_MANGO3', 12); //柠檬 X3
define('LDYX_TYPE_ORANGE', 13); //橘子
define('LDYX_TYPE_ORANGE3', 14); //橘子 X3
define('LDYX_TYPE_APPLE', 15); // 苹果
define('LDYX_TYPE_APPLE3', 16); // 苹果 X3
define('LDYX_TYPE_LUCKY', 17); // 蓝色lucky
define('LDYX_TYPE_LUCKY_BIG', 18); // 红色lucky

define('LDYX_MAP_ALL', [
    LDYX_TYPE_BAR, LDYX_TYPE_BAR50, LDYX_TYPE_SEVEN, LDYX_TYPE_SEVEN3, LDYX_TYPE_STAR, LDYX_TYPE_STAR3, LDYX_TYPE_WATERMENLON, LDYX_TYPE_WATERMENLON3, LDYX_TYPE_BELL,
    LDYX_TYPE_BELL3, LDYX_TYPE_MANGO, LDYX_TYPE_MANGO3, LDYX_TYPE_ORANGE, LDYX_TYPE_ORANGE3, LDYX_TYPE_APPLE, LDYX_TYPE_APPLE3, LDYX_TYPE_LUCKY, LDYX_TYPE_LUCKY_BIG
]); //地图所以的图案

define('LDYX_WIN_CONDITION', [
    LDYX_TYPE_BAR => 120,
    LDYX_TYPE_BAR50 => 50,
    LDYX_TYPE_SEVEN => 40,
    LDYX_TYPE_SEVEN3 => 3,
    LDYX_TYPE_STAR => 30,
    LDYX_TYPE_STAR3 => 3,
    LDYX_TYPE_WATERMENLON => 20,
    LDYX_TYPE_WATERMENLON3 => 3,
    LDYX_TYPE_BELL => 20,
    LDYX_TYPE_BELL3 => 3,
    LDYX_TYPE_MANGO => 15,
    LDYX_TYPE_MANGO3 => 3,
    LDYX_TYPE_ORANGE => 10,
    LDYX_TYPE_ORANGE3 => 3,
    LDYX_TYPE_APPLE => 5,
    LDYX_TYPE_APPLE3 => 3,
]); //中奖奖励

define('LDYX_WIN_CONDITION_SPE', [
    LDYX_TYPE_BAR => 0.5,
    LDYX_TYPE_SEVEN => 100,
    LDYX_TYPE_STAR => 80,
    LDYX_TYPE_WATERMENLON => 60,
    LDYX_TYPE_BELL => 50,
    LDYX_TYPE_MANGO => 40,
    LDYX_TYPE_ORANGE => 30,
    LDYX_TYPE_APPLE => 20,
]); //中奖奖励
define('LDYX_TYPE_LUCKY_NUM', [1, 4]); //小lucky次数
define('LDYX_TYPE_LUCKY_BIG_NUM', [5, 8]); //大lucky次数

define('LDYX_BIG', 1); //大
define('LDYX_SEMLL', 2); //小
define('LDYX_BIG_SEMLL', [1, 14]); //比大小区间
define('LDYX_BIG_SEMLL_CENTRE', 7); //大小中间

class Table
{
    private $roomRule = ['rid' => 0, 'gtype' => 0, 'rule' => 0];

    private $PlayerInfo = []; //玩家信息

    private $uid = 0; //玩家uid

    private $disRoom = -1;

    private $winfree = [];

    private $smellLuck = 20;

    private $bigLuck = 20;

    private $Jackpostwin = 0;

    private $prize = 20;

    private $list = [];

    private $jackpot_tax = 0;

    private $controlinfo = [];

    private $controlinfos = [];

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

        $this->userEnter($msg['players']);
        $this->roomInfo();
        $this->controlinfo = DBInstance::GetControlInfo('control_ldyx');
    }

    /**
     * 玩家进房
     * @param int
     * */
    private function userEnter($PlayerInfo)
    {
        $data = [
            'score' => 0,
            'winNum' => 0, //赢得次数
            'ransport' => 0, //输的次数
            'curstake' => [],
            'multiple' => 1,
            'savedata' => [],
            'control' => -2,
            'curscore' => 0,
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
            'curstake' => $this->PlayerInfo['multiple'],
            'doublescore' => $this->roomRule['doublescore'],
            'min_gold' => $this->roomRule['min_gold'],
            'max_gold' => $this->roomRule['max_gold'],
            'gold' => $this->PlayerInfo['gold'],
            'score' => $this->PlayerInfo['score'],
            'level' => $this->roomRule['level']
        ];
        Gateway::joinGroup($this->PlayerInfo['client_id'], 'ROOM:' . $this->roomRule['rid']);

        //判断断线重连
        Logic::SendAll('Msg_LDYX_RoomInfo', $res, $this->roomRule['rid']);
    }

    /**
     * 所有消息回调
     * @param array
     */

    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_LDYX_Start':
                $this->Msg_LDYX_Start($message);
                break;
            case 'Msg_LDYX_Than':
                $this->Msg_LDYX_Than($message);
                break;
            case 'Msg_LDYX_Collect':
                $this->Msg_LDYX_Collect($message);
                break;
            case 'Msg_LDYX_Out':
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
    private function Msg_LDYX_Start($msg)
    {
        if (empty($msg['data']['stake'])) {
            Logic::SendError($msg['uid'], 'Msg_LDYX_Start', '数据错误');
            return;
        }

        $min = max($msg['data']['stake']);

        if ($min < 0) {
            Logic::SendError($msg['uid'], 'Msg_LDYX_Start', '当前未下注');
            return;
        }
        $this->Jackpostwin = 0;
        $gold = 0;
        foreach ($msg['data']['stake'] as $key => $val) {
            if ($val > 0) {
                $gold += $val;
            }
        }

        $gold = $gold / 2 * $this->roomRule['doublescore'];
        if ($this->PlayerInfo['score'] > 0) {
            $this->Msg_LDYX_Collect(['uid' => $msg['uid']], false);
        }

        if ($gold <= 0 || $this->PlayerInfo['gold'] < $gold) {
            Logic::SendError($msg['uid'], 'Msg_LDYX_Start', '金币不足');
            return;
        }

        //获取缓存地图
        $this->map_save = DBInstance::GetControlMap($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $this->PlayerInfo['multiple'] = $msg['data']['stake'];

        $this->changScore(-$gold);

        $type = $this->gettype();

        $this->PlayerInfo['curstake'] = $msg['data']['stake'];
        $score = 0;
        $this->PlayerInfo['curscore'] = $gold;

        $data = [
            'type' => $type,
            'gold' => $this->PlayerInfo['gold'],
            'score' => 0,
            'curscore' => $gold,
            'info' => [],
        ];

        $free = 0;
        $info = [];
        $res = [];
        $beishu = 0;
        if (isset($msg['data']['stake'][$type]) && $msg['data']['stake'][$type] > 0 || $type > LDYX_TYPE_APPLE3) {
            if ($type >= LDYX_TYPE_LUCKY) {
                $rand = rand(0, 100);
                if ($type == LDYX_TYPE_LUCKY && $rand <= $this->smellLuck) {
                    $free = rand(LDYX_TYPE_LUCKY_NUM[0], LDYX_TYPE_LUCKY_NUM[1]);
                } elseif ($type > LDYX_TYPE_LUCKY && $rand <= $this->bigLuck) {
                    $free = rand(LDYX_TYPE_LUCKY_BIG_NUM[0], LDYX_TYPE_LUCKY_BIG_NUM[1]);
                }
            } else {
                $beishu += LDYX_WIN_CONDITION[$type];
                $_score = LDYX_WIN_CONDITION[$type] * $this->roomRule['doublescore'] * $msg['data']['stake'][$type];
                $data['score'] = $_score;
                $score +=  $_score;
            }

            if ($free > 0) {
                $_info = $this->freedata($free, $msg['data']['stake'], $gold);
                $info = $_info['info'];
                $score += $_info['score'];
            }
        }

        /*$_res = array_count_values($res);

        $max = 0;
        if (!empty($_res)) {
            $max = max($_res);
        }

        if ($max > 3) {
            foreach ($_res as $key => $val) {
                if ($val < 3) {
                    continue;
                }
            }
        }*/

        $data['info'] = $info;
        $data['countScore'] = $score;
        $data['beishu'] = $beishu;
        // $data['control'] = $this->PlayerInfo['control'];
        // $data['controlinfo'] = $this->controlinfos;

        //缓存玩家押注情况·
        $bets = json_encode($this->PlayerInfo['curstake']);
        DBInstance::SetUserBet($this->uid, $this->roomRule['gtype'], $this->roomRule['level'], $gold, $bets);

        MyTools::msg(json_encode($msg['data']));
        MyTools::msg(json_encode($data));
        Logic::SendAll('Msg_LDYX_Start', $data, $this->roomRule['rid']);

        $this->PlayerInfo['savedata'] = $data;

        $this->PlayerInfo['score'] = $score;
        $this->jackpot_tax = 0;
        if ($gold > $score) {
            $data = $this->SaveJackpot(0, 1);
            $jackpot_tax  = intval(($gold - $score) * $data['probability'] / 100);
            $this->jackpot_tax = $jackpot_tax;
            $this->SaveJackpot($jackpot_tax, 2);
        }

        if ($this->PlayerInfo['score'] <= 0 && $this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($msg['uid'], $this->PlayerInfo['score'] - $this->PlayerInfo['curscore']);
        }

        if (abs($this->PlayerInfo['control']) != 2 || !empty($this->map_save)) {
            if (!isset($jackpot_tax)) {
                $jackpot_tax = 0;
            }

            Logic::InsertProfit($this->roomRule['level'], ($score  - $gold - $this->Jackpostwin + $jackpot_tax));
        }
    }

    private function gettype()
    {
        $control = DBInstance::GetLBControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        $this->PlayerInfo['control'] = $control;
        // if ($control > 2) {
        //     $control = 1;
        // }

        // if ($control < -1) {
        //     $control = -1;
        // }
        // $data = DBInstance::GetTableOneWord('control_ldyx', 'vals', ['level' => $control]);
        // $data = json_decode($data, true);

        $data = $this->controlinfo[$control];
        $res = [
            'list' => [
                LDYX_TYPE_BAR => 200,
                LDYX_TYPE_BAR50 => 300,
                LDYX_TYPE_SEVEN => 400,
                LDYX_TYPE_SEVEN3 => 1000,
                LDYX_TYPE_STAR => 500,
                LDYX_TYPE_STAR3 => 1000,
                LDYX_TYPE_WATERMENLON => 600,
                LDYX_TYPE_WATERMENLON3 => 1000,
                LDYX_TYPE_BELL => 600,
                LDYX_TYPE_BELL3 => 1000,
                LDYX_TYPE_MANGO => 700,
                LDYX_TYPE_MANGO3 => 1000,
                LDYX_TYPE_ORANGE => 800,
                LDYX_TYPE_ORANGE3 => 1000,
                LDYX_TYPE_APPLE => 900,
                LDYX_TYPE_APPLE3 => 1000,
                LDYX_TYPE_LUCKY => 1500,
                LDYX_TYPE_LUCKY_BIG => 1000,
            ],
            'smellLuck' => 40,
            'bigLuck' => 40,
            'free' => [
                LDYX_TYPE_BAR => 10,
                LDYX_TYPE_SEVEN => 400,
                LDYX_TYPE_STAR => 500,
                LDYX_TYPE_WATERMENLON => 600,
                LDYX_TYPE_BELL => 700,
                LDYX_TYPE_MANGO => 800,
                LDYX_TYPE_ORANGE => 900,
                LDYX_TYPE_APPLE => 1000,
            ],
            "prize" => 20
        ];

        if (isset($data['prize']) && is_int($data['prize']) && isset($data['smellLuck']) && isset($data['bigLuck']) && count($data['list']) >= 18 && is_array($data['list']) && is_int($data['smellLuck']) && is_int($data['bigLuck'])) {
            $code = true;
            foreach ($data['list'] as $key => $val) {
                if (!in_array($key, LDYX_MAP_ALL)) {
                    $code = false;
                    break;
                }
            }
            if ($code) {
                $res = $data;
            }
        }

        $type = LDYX_TYPE_LUCKY;
        $sum = array_sum($res['list']);
        $_sum = 0;
        $rand = rand(1, $sum);
        foreach ($res['list'] as $key => $val) {
            $_sum += $val;
            if ($_sum >= $rand) {
                $type = $key;
                break;
            }
        }
        $this->smellLuck = $res['smellLuck'];
        $this->bigLuck = $res['bigLuck'];
        $this->winfree = $res['free'];
        $this->prize = $res['prize'];
        $this->list = $res['list'];
        $this->controlinfos = $res;
        if (isset($this->map_save['logo']) && in_array($this->map_save['logo'], LDYX_MAP_ALL)) {
            $type = $this->map_save['logo'];
        }
        return $type;
    }

    private function freedata($free, $data, $gold)
    {
        $score = 0;
        while ($free  > 0) {
            $type = LDYX_TYPE_LUCKY;
            $sum = array_sum($this->list);
            $_sum = 0;
            $rand = rand(1, $sum);
            foreach ($this->list as $key => $val) {
                $_sum += $val;
                if ($_sum >= $rand) {
                    $type = $key;
                    break;
                }
            }

            $freeArr = [];
            $_score = 0;

            $free--;
            $rand = rand(1, 100);
            $num = $this->smellLuck;
            if ($type > LDYX_TYPE_LUCKY) {
                $num = $this->bigLuck;
            }

            if (isset($data[$type]) && $data[$type] > 0 || $type > LDYX_TYPE_APPLE3 || $rand <= $num) {
                $_free = [];

                if ($type >= LDYX_TYPE_LUCKY) {
                    $_freetype = 0;
                    for ($i = 0; $i < 3; $i++) {
                        //禁止出现三个bar  7
                        if ($i == 3 && (isset($_free[LDYX_TYPE_BAR]) || isset($_free[LDYX_TYPE_SEVEN]))) {
                            unset($_free[LDYX_TYPE_BAR]);
                            unset($_free[LDYX_TYPE_SEVEN]);
                        }
                        $randSum = array_sum($this->winfree);
                        $rand = rand(0, $randSum);
                        $_sum = 0;

                        foreach ($this->winfree as $key => $val) {
                            $_sum += $val;
                            if ($_sum >= $rand) {
                                $_type = $key;

                                break;
                            }
                        }

                        if (!isset($_free[$_type])) {
                            $_free[$_type] = 0;
                        }
                        $_free[$_type]++;
                        $freeArr[] = $_type;
                        $_freetype = $_type;
                    }

                    $count = count($_free);
                    $additional = 0;
                    if ($count == 1 && isset(LDYX_WIN_CONDITION_SPE[$_freetype])) {
                        if ($_freetype <= LDYX_TYPE_BAR50) {
                            $jackpot = $this->SaveJackpot(0, 1);

                            $jackpot = intval(LDYX_WIN_CONDITION_SPE[$type] * $jackpot['jackpot']);
                            $this->SaveJackpot(-$jackpot, 2);
                            $additional += $jackpot;
                            $this->Jackpostwin += $jackpot;
                        } else {
                            $additional  += intval(LDYX_WIN_CONDITION_SPE[$_freetype] * $gold);
                        }

                        $_score = $additional;
                        $score += $additional;
                    }
                } else {
                    $_score = LDYX_WIN_CONDITION[$type] * $this->roomRule['doublescore'] * $data[$type];

                    $score += $_score;
                }
            }

            $info[] = ['type' => $type, 'score' => $_score, 'freeArr' => $freeArr];
        }
        return ['info' => $info, 'score' => $score];
    }

    /**
     * 比倍
     *
     * @param [type] $msg
     * @return void
     */
    private function Msg_LDYX_Than($msg)
    {
        $_score = $this->PlayerInfo['score'] - $msg['data']['score'];

        if ($this->PlayerInfo['score'] == 0 || ($_score < 0 && $msg['data']['score'] != $this->PlayerInfo['score'] * 2)) {
            Logic::SendError($msg['uid'], 'Msg_LDYX_Than', '比倍失败');
            return;
        }

        $this->changScore($_score);

        $arr = LDYX_BIG_SEMLL;
        if (rand(1, 100) < $this->prize) {
            if ($msg['data']['type'] == LDYX_SEMLL) {
                $arr[0] = LDYX_BIG_SEMLL_CENTRE + 1;
            } else {
                $arr[1] = LDYX_BIG_SEMLL_CENTRE;
            }
        }
        $rand = rand($arr[0], $arr[1]);
        $type = LDYX_SEMLL;

        if ($rand > LDYX_BIG_SEMLL_CENTRE) {
            $type = LDYX_BIG;
        }

        $score = 0;
        if ($type == $msg['data']['type']) {
            $score = $msg['data']['score'] + $msg['data']['score'];
            $this->PlayerInfo['savedata']['beishu'] *= 2;
        }

        $this->PlayerInfo['score'] = $score;

        if ($score == 0) {
            DBInstance::IncrementUserGet($msg['uid'], -$this->PlayerInfo['curscore']);
        }

        Logic::SendAll('Msg_LDYX_Than', [
            'res' => $rand,
            'gold' => $this->PlayerInfo['gold'],
            'score' => $score
        ], $this->roomRule['rid']);
    }

    private function Msg_LDYX_Collect($msg, $code = true)
    {
        if ($this->PlayerInfo['score'] <= 0) {
            Logic::SendError($msg['uid'], 'Msg_LDYX_Collect', '收分失败');
        }

        $this->changScore($this->PlayerInfo['score']);

        if ($code) {
            Logic::SendAll('Msg_LDYX_Collect', ['gold' => $this->PlayerInfo['gold']], $this->roomRule['rid']);
        }

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementUserGet($msg['uid'], $this->PlayerInfo['score'] - $this->PlayerInfo['curscore']);
        }

        $this->PlayerInfo['score'] = 0;
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

        if ($score >= 5000000 && isset($this->PlayerInfo['savedata']['beishu']) && $this->PlayerInfo['savedata']['beishu'] >= 20) {
            Logic::HorseLamp($this->uid, $score, $this->PlayerInfo['savedata']['beishu']);
        }
    }
    /**
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        if ($this->PlayerInfo['score'] > 0) {
            // $this->changScore($this->PlayerInfo['score']);
            $this->Msg_LDYX_Collect(['uid' => $this->uid], false);
            $this->PlayerInfo['score'] = 0;
        }

        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $this->uid]);
        DBInstance::DelUserBet($this->uid);
        Logic::SendAll('Msg_LDYX_Out', ['gold' => $gold], $this->roomRule['rid']);

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
        // if (empty($msg)) {
        // $this->disRoom = 1;
        Timer::del($this->timer);

        $this->OldRoom();
        // }
    }
}

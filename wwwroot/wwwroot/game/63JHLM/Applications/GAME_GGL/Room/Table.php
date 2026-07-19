<?php

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;

class Table
{
    private $roomRule = [];  //房间规则
    private $mUserList = [];  //玩家信息
    private $getcircle = 0;
    private $uid = 0;

    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        $this->roomRule = $msg;
        $this->mUserList = $msg['players'];
        foreach ($this->mUserList as $key => $value) {
            if ($value['client_id'] != '') {
                Gateway::joinGroup($value['client_id'], 'ROOM:' . $this->roomRule['rid']);
            }
            $this->uid = $key;
        }

        Logic::SendAll('Msg_GGL_RoomInfo', [
            'setting' => [
                'doublescore' => $this->roomRule['doublescore'],
                'level' => $this->roomRule['level'],
                'min_gold' => $this->roomRule['min_gold'],
                'max_gold' => $this->roomRule['max_gold'],
                'vals' => $this->roomRule['vals'],
            ]
        ], $this->roomRule['rid']);

        /*$this->roomRule['doublescore'] = 1;
        Timer::add(2, function () {
            $all = 0;
            for ($i = 0; $i < 100000; $i++) {
                $res = $this->GetPrizePao(1);
                $all += $res['win'];
            }
            var_dump($all);
        });*/
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_GGL_Out':
                $this->Msg_GGL_Out($message);
                break;
            case 'Msg_GGL_StageStart':
                $this->Msg_GGL_StageStart($message);
                break;
            case 'Msg_GGL_StartMore':
                $this->Msg_GGL_StartMore($message);
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
     * 金币变化
     * @param array
     */
    public function ChangeGold($message)
    {
        $uid = $message['uid'];
        $this->mUserList[$uid]['gold'] = DBInstance::GetUserOneWord('gold', $uid);
    }

    /**
     * 解散房间
     * @param array
     */
    public function DisRoom($message)
    {
        $olddata = [
            'rid' => $this->roomRule['rid'],
            'win' => [],
            'palyers' => $this->mUserList,
            'result' => [
                'uid' => []
            ], //结算消息
            'gtype' => $this->roomRule['gtype'],
        ];
        Logic::RoomOld($olddata);
    }

    /**
     * 玩家进房
     * @param array
     */
    public function EnterRoom($player)
    {

    }

    /**
     * 玩家开局
     * @param array
     */
    public function Msg_GGL_StageStart($message)
    {
        $uid = $message['uid'];
        if ($this->mUserList[$uid]['gold'] >= $this->roomRule['doublescore']) {
            $this->mUserList[$uid]['gold'] -= $this->roomRule['doublescore'];
            DBInstance::IncrementGolds('gold', $uid, -$this->roomRule['doublescore']);
        } else {
            Logic::SendError($uid, $message['event'], '金币不足');
            return;
        }

        $control = DBInstance::GetControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);
        if ($this->roomRule['level'] < 4) {
            $data = $this->GetPrizePao($control);
        } elseif ($this->roomRule['level'] < 7) {
            $data = $this->GetPrizeFish($control);
        } else {
            $data = $this->GetPrizeMJ($control);
        }

        if ($control != 2) {
            Logic::InsertProfit($this->roomRule['level'], $data['win'] - $this->roomRule['doublescore']);
        }

        $double = intval($data['win'] / $this->roomRule['doublescore']);
        if ($double >= 10) {
            Logic::HorseLamp($uid, $data['win'], $double);
        }
        $this->mUserList[$uid]['gold'] += $data['win'];
        DBInstance::IncrementGolds('gold', $uid, $data['win']);
        DBInstance::IncrementUserGet($uid, $data['win'] - $this->roomRule['doublescore']);
        Logic::SendAll('Msg_GGL_StageStart', $data, $this->roomRule['rid']);
    }

    /**
     * 自定义购买
     * @param array
     */
    public function Msg_GGL_StartMore($message)
    {
        $uid = $message['uid'];
        $data = ['golds' => []];
        $usegold = $this->roomRule['doublescore'] * $message['data']['num'];
        if ($this->mUserList[$uid]['gold'] >= $usegold) {
            $this->mUserList[$uid]['gold'] -= $usegold;
            DBInstance::IncrementGolds('gold', $uid, -$usegold);
        } else {
            Logic::SendError($uid, $message['event'], '金币不足');
            return;
        }
        $control = DBInstance::GetControl($this->uid, $this->roomRule['gtype'], $this->roomRule['level']);

        if ($this->roomRule['level'] < 4) {
            for ($i = 0; $i < $message['data']['num']; $i++) {
                $res = $this->GetPrizePao($control);
                $data['golds'][] = $res['win'];
            }
        } elseif ($this->roomRule['level'] < 7) {
            for ($i = 0; $i < $message['data']['num']; $i++) {
                $res = $this->GetPrizeFish($control);
                $data['golds'][] = $res['win'];
            }
        } else {
            for ($i = 0; $i < $message['data']['num']; $i++) {
                $res = $this->GetPrizeMJ($control);
                $data['golds'][] = $res['win'];
            }
        }
        DBInstance::IncrementUserGet($uid, array_sum($data['golds']) - $usegold);
        if ($control != 2) {
            Logic::InsertProfit($this->roomRule['level'], array_sum($data['golds']) - $usegold);
        }
        $this->mUserList[$uid]['gold'] += array_sum($data['golds']);
        DBInstance::IncrementGolds('gold', $uid, array_sum($data['golds']));
        Logic::SendAll('Msg_GGL_StartMore', $data, $this->roomRule['rid']);
    }

    /**
     * 玩家退房
     * @param array
     */
    public function Msg_GGL_Out($message)
    {
        Logic::SendAll('Msg_GGL_Out', [
            'uid' => $message['uid'],
            'gold' => DBInstance::GetUserOneWord('gold', $message['uid'])
        ], $this->roomRule['rid']);
        Logic::QuitRoom([
            'rid' => $this->roomRule['rid'],
            'uid' => $message['uid']
        ]);
        $this->DisRoom($message);
    }

    /**
     * 玩家重连
     * @param $client_id
     * @param array
     */
    public function UserOnline($client_id, $uid)
    {
        if ($client_id != '') {
            Gateway::joinGroup($client_id, 'ROOM:' . $this->roomRule['rid']);
        }
    }

    /**
     * 玩家离线
     * @param array
     */
    public function UserOff($uid)
    {
        $this->Msg_GGL_Out(['uid' => $uid]);
        Gateway::leaveGroup($this->mUserList[$uid]['client_id'], 'ROOM:' . $this->roomRule['rid']);
    }


    /**
     * 开奖结果
     */
    private function GetPrizePao($control)
    {
        $add = [0 => 2, 1 => -13, 2 => -15, -1 => 15, -2 => 18];
        $possible = [];
        $num = 1000;
        for ($i = 9; $i >= 0; $i--) {
            $possible[9 - $i] = $i > 0 ? $num - pow(2, $i) : $num;
            $num -= $possible[9 - $i];
        }

        $rand = rand(1, 1000);
        $sum = 0;
        $double = 0;
        foreach ($possible as $key => $value) {
            if ($rand > $sum && $rand <= $sum + $value) {
                $double = $key;
                break;
            } else {
                $sum += $value;
            }
        }

        $prize = [
            'flag' => [],
            'myarea' => [],
            'win' => 0,
        ];
        $all = [];
        for ($i = 1; $i < 3; $i++) {
            for ($j = 1; $j < 8; $j++) {
                if ($j == 7) {
                    $num = 5;
                } elseif ($j == 6) {
                    $num = 1;
                } else {
                    $num = 2;
                }
                for ($k = 0; $k < $num; $k++) {
                    $all[] = $i * 100 + $j;
                }
            }
        }

        if (rand(1, 2) == 1) {
            $all = array_diff($all, [101]);
        } else {
            $all = array_diff($all, [201]);
        }

        $flag = $all;

        //取出目标棋子
        for ($i = 0; $i < 2; $i++) {
            $index = array_rand($all);
            $flag = array_diff($flag, [$all[$index]]);
            $prize['flag'][] = $all[$index];
        }

        $max = 30 + $add[$control];
        if ($this->getcircle >= 10) {
            $max -= $this->getcircle;
            $max = $max < 1 ? 1 : $max;
        }

        for ($i = 0; $i < 6; $i++) {
            if (rand(1, $max) == 1) {  //中奖概率控制
                $gold = rand(1, 15) * $this->roomRule['doublescore'];
                $prize['myarea'][] = [
                    'logo' => $all[array_rand($all)],
                    'gold' => $gold,
                ];
            } else {
                $gold = rand(1, 1000) * $this->roomRule['doublescore'];
                if ($gold > ($double + 1) * 2) {
                    $prize['myarea'][] = [
                        'logo' => $flag[array_rand($flag)],
                        'gold' => $gold,
                    ];
                } else {
                    $prize['myarea'][] = [
                        'logo' => $all[array_rand($all)],
                        'gold' => $gold,
                    ];
                }
            }
        }

        if (rand(1, 10000) === 1) {
            $prize['myarea'][] = [
                'logo' => array_rand([101 => 1, 201 => 1]),
                'gold' => rand(2, 5) * $this->roomRule['doublescore'],
            ];
        }

        shuffle($prize['myarea']);
        $prize['myarea'] = array_slice($prize['myarea'], 0, 6);
        $pao = [201, 101];
        foreach ($prize['myarea'] as $key => $value) {
            if ($value['logo'] == 201 || $value['logo'] == 101) {
                $pao = array_diff($pao, [$value['logo']]);
            }
            if (in_array($value['logo'], $prize['flag'])) {
                $prize['win'] += $value['gold'];
            }
        }

        if (empty($pao)) {
            $prize['win'] = array_sum(array_column($prize['myarea'], 'gold'));
        }

        if ($prize['win'] < $this->roomRule['doublescore']) {
            $this->getcircle++;
        } else {
            $this->getcircle = 0;
        }
        return $prize;
    }

    /**
     * 开奖结果
     */
    private function GetPrizeFish($control)
    {
        $prize = [
            'fish' => 0,
            'gold' => [],
            'win' => 0,
        ];
        $prizegold = [4 => 0, 5 => 1, 6 => 1.5, 7 => 2.5, 8 => 5, 9 => 10, 10 => 50, 11 => 300, 12 => 1000];
        $possible = [12 => 0, 11 => 1, 10 => 1, 9 => 3, 8 => 5, 7 => 8, 6 => 10, 5 => 800, 4 => 10000];
        $sum = 0;
        $rand = rand(1, array_sum($possible));
        foreach ($possible as $key => $value) {
            if ($rand > $sum && $rand < $sum + $value) {
                $prize['fish'] = $key;
                $prize['win'] += round($prizegold[$key] * $this->roomRule['doublescore']);
                break;
            } else {
                $sum += $value;
            }
        }

        $add = [0 => -20, 1 => 20, 2 => 30, -1 => -50, -2 => -60];
        $max = 100 + $add[$control];
        if ($this->getcircle >= 10) {
            $max += $this->getcircle * 5;
        }

        if ($max > 150) {
            $max = 150;
        }

        for ($i = 0; $i < 3; $i++) {
            if (rand(0, 1000) <= $max) { //中奖概率控制
                if (rand(0, 100) <= 98) {
                    $prize['gold'][] = round(rand(2, 5) * $this->roomRule['doublescore'] / 2);
                } else {
                    $prize['gold'][] = round(rand(1, 100) * $this->roomRule['doublescore'] / 2);
                }
            } else {
                $prize['gold'][] = 0;
            }
        }

        $prize['win'] += array_sum($prize['gold']);
        if ($prize['win'] < $this->roomRule['doublescore']) {
            $this->getcircle++;
        } else {
            $this->getcircle = 0;
        }
        return $prize;
    }

    /**
     * 开奖结果
     */
    private function GetPrizeMJ($control)
    {
        $prize = [
            'flag' => [],
            'myarea' => [],
            'gold' => 0,
            'win' => 0,
        ];

        $add = [0 => 0, 1 => 45, 2 => 45, -1 => -25, -2 => -30];
        $max = 55 + $add[$control];
        if ($this->getcircle >= 10) {
            $max += $this->getcircle * 10;
        }

        if ($max > 200) {
            $max = 200;
        }

        if (rand(0, 1000) <= $max) {  //控制概率
            if (rand(0, 100) <= 98) {
                $prize['gold'] = round(rand(2, 8) * $this->roomRule['doublescore'] / 2);
            } else {
                $prize['gold'] = round(rand(1, 800) * $this->roomRule['doublescore'] / 2);
            }
        }

        $allmj = [];
        $allin = [];
        for ($i = 1; $i < 35; $i++) {
            $allmj[] = $i;
        }

        for ($i = 1; $i < 8; $i++) {
            for ($j = 0; $j < $i; $j++) {
                $index = array_rand($allmj);
                $allin[$allmj[$index]] = 1;
                $prize['flag'][$i][] = $allmj[$index];
                unset($allmj[$index]);
            }
        }

        for ($i = 0; $i < 2; $i++) {
            $allmj = array_merge($allmj, $allmj);
        }

        $getall = [0 => [7, 11], 1 => [8, 8], 2 => [8, 8], -1 => [4, 12]];
        for ($i = 0; $i < $getall[$control][0]; $i++) {
            $prize['myarea'][] = array_rand($allin);
        }

        for ($i = 0; $i < $getall[$control][1]; $i++) {
            if (rand(1, 100) < 3) {
                $prize['myarea'][] = array_rand($allin);
            } else {
                $prize['myarea'][] = $allmj[array_rand($allmj)];
            }
        }

        shuffle($prize['myarea']);
        $wins = [0.75, 1.25, 2, 3, 6.25, 25, 100];
        foreach ($prize['flag'] as $key => $value) {
            $flag = true;
            foreach ($value as $key1 => $value1) {
                if (!in_array($value1, $prize['myarea'])) {
                    $flag = false;
                }
            }

            if ($flag) {
                $prize['win'] += round($wins[$key - 1] * $this->roomRule['doublescore']);
            }
        }

        $prize['win'] += $prize['gold'];
        if ($prize['win'] < $this->roomRule['doublescore']) {
            $this->getcircle++;
        } else {
            $this->getcircle = 0;
        }
        return $prize;
    }
}
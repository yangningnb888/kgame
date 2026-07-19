<?php

date_default_timezone_set("Asia/Shanghai");

use app\pay\controller\Time;
use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\DbModel;
use Workerman\Lib\Timer;


define("STAGE_WAIT", 0);  //等待开始
define("STAGE_START", 1);  //下注
define("STAGE_RES", 2);  //结算
define("STAGE_OLD", 3);  //解散

define('PLAYER_ACTUAL_SCORE', 0.995); //玩家实际得分比

define('HB_INFO_BUM', 50); //页面上红包的有效个数

define('HB_VALID_TIME', 10); //红包的有效时间
define('HB_NUM', [5, 8, 10]); //红包个数

define('HB_HNM_BEISHU', [5 => 2, 8 => 1.25, 10 => 1]); //红包的赔倍数

define('HB_TYPR_BAOZI', 0.02); //奖池豹子
define('HB_TYPR_SHUNZI', 0.01); //奖池顺子抽成

define('AI_QIANG_MAX', 5); //机器人抢红包次数

define('AI_FA_MAX', 2); //机器人发红包次数

define('SAVE_DATA_NUM', 20); //机器人发红包次数

define('AI_FA_HB_NUM', [5 => 50, 8 => 30, 10 => 20]); //机器人抢红包次数

define('PLAYER_TIME', 300); //玩家离线好久踢出房间

define('PLAYER_LEVEL1_BOOM', 20); //当等级为0时拿到炸弹的概率

define('PLAYER_LEVEL0_BOOM', 80); //当等级为-1时拿到炸弹的概率

define('PLAYER_LEVEL_BOOM_AI', 80); //机器人不踩雷的概率

class Table
{
    private $roomRule = [];

    private $PlayerInfo = []; //玩家信息

    private $disRoom = -1; //退出方式

    private $hbinfo = []; //红包详情

    private $hbid = 1;

    private $AITime = 1; //机器人的定时器

    private $hbScoreArr = [200000, 400000, 600000, 800000, 1000000, 1200000, 1600000, 2000000]; //可发红包金额

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
            'vals' => $msg['vals'],
            'controls' => $msg['controls']
        ];

        $this->userEnter($msg['players']);

        $this->AITime = rand(5, 20) / 10;

        $rand = rand(0, 100);
        $this->AItimer($rand);

        for ($i = 1; $i < HB_INFO_BUM; $i++) {
            $this->hbinfo[$i] = [];
        }

        if ($msg['level'] != 5) {
            $this->hbScoreArr = [2000000, 3000000, 4000000, 5000000, 6000000, 7000000, 8000000, 10000000];
        }
    }

    /**
     * 玩家进房
     * @param int
     * */
    private function userEnter($PlayerInfo)
    {
        foreach ($PlayerInfo as $key => $val) {
            $this->addPlayer($key, $val);
        }
    }

    /**
     * 整理房间进房消息
     * @param [type] $uid
     * @param [type] $info
     * @return void
     */
    private function addPlayer($uid, $info)
    {
        $history = [];
        if ($info['client_id'] != '') {
            $data = DBInstance::selectCacheHBSL($uid, $this->roomRule['level'], $this->roomRule['gtype']);
            if ($data != false) {
                foreach ($data as $key => $val) {
                    $data[$key] =  json_decode($val, true);
                }
                $history = $data;
            }
        }

        $this->PlayerInfo[$uid] = [
            'allWinScore' => 0, //总共赢了多少
            'winScore' => 0, //当局赢了多少            
            'nickname' => $info['nickname'],
            'gold' => $info['gold'],
            'headimgurl' => $info['headimgurl'],
            'client_id' => $info['client_id'],
            'seat' => $info['seat'],
            'pictureframe' => $info['pictureframe'],
            'avoidnum' => [], //避雷数
            'hbgold' => 0, //发的红包金额
            'partake' => 0, //参与次数
            'history' => $history,
            'alivetime' => 0, //存活时间
            'onlinetime' => 0
        ];

        if ($info['client_id'] != '') {
            Gateway::joinGroup($info['client_id'], 'ROOM:' . $this->roomRule['rid']);
        } else {
            $this->PlayerInfo[$uid]['alivetime'] = time() + rand($this->roomRule['vals']['aitime'][0], $this->roomRule['vals']['aitime'][1]);
        }
        $this->roomInfo($uid);
    }

    /**
     *  刷新房间
     *
     * @param integer $uid
     * @return void
     */
    private function roomInfo($uid)
    {
        $res = [
            'doublescore' => $this->roomRule['doublescore'],
            'playerCount' => count($this->PlayerInfo),
            'level' => $this->roomRule['level']
        ];

        //判断断线重连
        Logic::SendRight($uid, 'Msg_HBSL_RoomInfo', $res);
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        switch ($message['event']) {
            case 'Msg_HBSL_Act_Fa':
                $this->Msg_HBSL_Act_Fa($message);
                break;
            case 'Msg_HBSL_Act_Qiang':
                $this->Msg_HBSL_Act_Qiang($message);
                break;
            case 'Msg_HBSL_Act_Details':
                $this->Msg_HBSL_Act_Details($message);
                break;
            case 'Msg_HBSL_Out':
                $this->Msg_HBSL_Out($message);
                break;
            case 'Msg_HBSL_GetUserList':
                $this->Msg_HBSL_GetUserList($message);
                break;
            case 'Msg_HBSL_History':
                $this->Msg_HBSL_History($message);
                break;
                // case 'Msg_HBSL_HistoryInfo':
                //     $this->Msg_HBSL_HistoryInfo($message);
                // break;
            default: {
                    Logic::SendError($message['uid'], $message['event'], '');
                    MyTools::msg('uid:' . $message['uid'] . '-----UnKnown!!!!!!!--------' . $message['event'], true);
                    break;
                }
        }
    }

    /**
     * 发红包
     * @return void
     */
    private function Msg_HBSL_Act_Fa($msg)
    {
        if ($this->disRoom == 1) {
            Logic::SendError($msg['uid'], $msg['event'], '系统即将维护');
            return;
        }

        if (!isset($msg['data']['score']) || !isset($msg['data']['thunder']) || !isset($msg['data']['num']) || !is_int($msg['data']['score']) || !is_int($msg['data']['thunder']) || !is_int($msg['data']['num'])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }

        if (!in_array($msg['data']['score'], $this->hbScoreArr) || !in_array($msg['data']['num'], HB_NUM)) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }

        if ($this->PlayerInfo[$msg['uid']]['gold'] < $msg['data']['score']) {
            Logic::SendError($msg['uid'], $msg['event'], '金币不足');
            return;
        }

        $info = $this->hbArr($msg['data']['score'], $msg['data']['num'], $msg['data']['thunder'], $msg['uid']);

        $this->hbinfo[$this->hbid] = [
            'uid' => $msg['uid'],
            'num' => $msg['data']['num'], //红包个数
            'score' => $msg['data']['score'], //红包总金额
            'thunder' => $msg['data']['thunder'], //雷点
            'time' => time() + HB_VALID_TIME, //过期时间戳
            'curnum' => $msg['data']['num'], //还剩个数
            'info' => $info['info'], //每个红包的金额
            'players' => [],
            'boom' => 0, //炸弹总分数
            'isopera' => false, //是否操作过
            'nickname' => $this->PlayerInfo[$msg['uid']]['nickname'],
            'headimgurl' => $this->PlayerInfo[$msg['uid']]['headimgurl'],
            'pictureframe' => $this->PlayerInfo[$msg['uid']]['pictureframe'],
            'date' => MyTools::GET_NOW(),
            'control' => $info['control'],
        ];

        $data = [
            'id' => $this->hbid,
            'nickname' => $this->PlayerInfo[$msg['uid']]['nickname'],
            'headimgurl' => $this->PlayerInfo[$msg['uid']]['headimgurl'],
            'pictureframe' => $this->PlayerInfo[$msg['uid']]['pictureframe'],
            'num' => $msg['data']['num'],
            'score' => $msg['data']['score'],
            'thunder' => $msg['data']['thunder'],
            'time' => HB_VALID_TIME,
            'uid' => $msg['uid'],
        ];

        Logic::SendAll('Msg_HBSL_Act_Fa',  $data, $this->roomRule['rid']);

        $this->hbid++;
        if ($this->hbid > HB_INFO_BUM) {
            $this->hbid -= HB_INFO_BUM;
        }
        $this->PlayerInfo[$msg['uid']]['hbgold'] += $msg['data']['score'];

        $this->changScore($msg['uid'], -$msg['data']['score']);
    }

    /**
     * 红包金额
     * @param int $score
     * @param int $num
     * @return void
     */
    private function hbArr($total, $num, $thunder, $uid)
    {
        $min = 10000;
        // if ($this->roomRule['level'] != 5) {
        //     $min = 100000;
        // }

        $res = [];
        for ($i = 1; $i < $num; $i++) {
            $safe_total = ($total - ($num - $i) * $min) / ($num - $i); //随机安全上限  
            $money = intval(mt_rand($min * 1000, $safe_total * 1000) / 1000);
            $total -= $money;
            $res[] = $money;
        }
        $res[] = $total;
        $res = $this->controlhb($res, $thunder, $uid);
        return $res;
    }

    /**
     * 通过炸弹控制
     *
     * @param array $res
     * @param int $thunder
     * @param integer $control 1 杀 2放
     * 
     * @return array
     */
    private function controlhb($res, $thunder, $uid)
    {
        $controlnum = DBInstance::GetControlRand($this->roomRule['gtype']);
        $rand = rand(0, 100);

        $info = DBInstance::GetTableWords('user_superior', '*', ['uid' => $uid]);
        if ($rand <= $controlnum || !empty($info) && $info['control'] < 0) {
            $control = -1;
        }

        $count = 0;
        $data = [];
        foreach ($res as $key => $val) {
            $_thunder = $val % 10;
            if ($_thunder == $thunder) {
                $count++;
                $data[$key] = $val;
            }
        }

        $number = 0;
        if (isset($control) && $control == -1 && $count == 0 && $this->PlayerInfo[$uid]['client_id'] == '') {
            $rand = rand(0, 2);
            for ($i = 0; $i > $rand; $i++) {
                $_thunder = $res[$i] % 10;
                $num = $_thunder - $thunder;
                $res[$i] -= $num;
                $number += $num;
            }

            if ($number > 0) {
                $rands = rand($rand, count($res));
                $res[$rands] += $number;
            }
        } elseif (isset($control) && $control == -1 && $count > 0 && rand(1, 100) <= 80 && $this->PlayerInfo[$uid]['client_id']) {
            $sum = 0;
            foreach ($res as $key => $val) {
                if ($val % 10 == $thunder) {
                    $rand = rand(1, 9);
                    $sum += $rand;
                    $res[$key] -= $rand;
                }
            }

            foreach ($res as $key => $val) {
                if (($val + $sum) % 10 != $thunder) {
                    $res[$key] += $sum;
                    $sum = 0;
                    break;
                }
            }

            if ($sum != 0) {
                foreach ($res as $key => $val) {
                    $res[$key] += $sum;
                    break;
                }
            }
        }
        
        if (!isset($control)) {
            if ($controlnum <= rand(0, 100)) {
                $control = 0;
            } else {
                $control = 1;
            }
        }

        shuffle($res);
        return ['info' => $res, 'control' => $control];
    }

    /**
     * 抢红包
     * @return void
     */
    private function Msg_HBSL_Act_Qiang($msg)
    {
        if (!isset($msg['data']['id'])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');
            return;
        }

        $id = $msg['data']['id'];
        if (empty($this->hbinfo[$id])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据格式错误');

            return;
        }

        if ($this->hbinfo[$id]['curnum'] <= 0 || $this->hbinfo[$id]['time'] < time()) {
            Logic::SendError($msg['uid'], $msg['event'], '红包已经过期');
            return;
        }

        if (isset($this->hbinfo[$id]['players'][$msg['uid']])) {
            Logic::SendError($msg['uid'], $msg['event'], '已经领取此红包');
            return;
        }

        $boomscore = $this->hbinfo[$id]['score'] * HB_HNM_BEISHU[$this->hbinfo[$id]['num']];
        if ($this->PlayerInfo[$msg['uid']]['gold'] < $boomscore) {
            Logic::SendError($msg['uid'], $msg['event'], '金币不足');
            return;
        }

        $score = $this->getHBScore($id, $msg['uid']);
        $thunder = $score % 10;
        $_score =  intval($score * PLAYER_ACTUAL_SCORE);
        $this->changScore($msg['uid'], $_score);

        $boom = 0;
        $code = 1;
        if ($thunder == $this->hbinfo[$id]['thunder']) {
            $boom = $boomscore;
            $code = 0;
            $this->changScore($msg['uid'], -$boom);

            $this->changScore($this->hbinfo[$id]['uid'], $boomscore * PLAYER_ACTUAL_SCORE);
        }

        if (count($this->PlayerInfo[$msg['uid']]['avoidnum']) >= 20) {
            array_shift($this->PlayerInfo[$msg['uid']]['avoidnum']);
        }

        $this->PlayerInfo[$msg['uid']]['avoidnum'][] = $code;
        $this->hbinfo[$id]['boom'] += $boom;

        $arr[] = intval($score / 100) % 10;
        $arr[] = intval($score / 10) % 10;
        $arr[] = $thunder;

        $_countarr = array_count_values($arr);
        $jackpost = 0;
        $type = 0;
        $count = count($_countarr);
        if (count($_countarr) == 1) {
            $jackposts = DBInstance::GetTableOneWord('game_jackpot', 'jackpot', ['gtype' => $this->roomRule['gtype'], 'level' => $this->roomRule['level']]);
            $jackpost = intval($jackposts * HB_TYPR_BAOZI);
            $type = 1;
        } elseif ($count == 3) {
            $shunzi = $this->getShunzi($_countarr);
            if (empty($shunzi)) {
                $shunzi = $this->getShunzi($_countarr, false);
            }

            if (!empty($shunzi)) {
                $jackposts = DBInstance::GetTableOneWord('game_jackpot', 'jackpot', ['gtype' => $this->roomRule['gtype'], 'level' => $this->roomRule['level']]);
                $jackpost = intval($jackposts * HB_TYPR_SHUNZI);
                $type = 2;
            }
        }

        if ($jackpost > 0) {
            DBInstance::SaveJackpot($this->roomRule['gtype'], $this->roomRule['level'], -$jackpost);
            $this->changScore($msg['uid'], $jackpost);
        }

        $data = [
            'id' => $id,
            'uid' => $msg['uid'],
            'score' => $score,
            'boom' => $boom,
            'gold' => $this->PlayerInfo[$msg['uid']]['gold'],
            'jackpost' => $jackpost,
            'shrinkScore' => $_score,
            'type' => $type,
            'nickname' => $this->PlayerInfo[$msg['uid']]['nickname'],
            'bankergold' => $this->PlayerInfo[$this->hbinfo[$id]['uid']]['gold'],
            'bankeruid' => $this->hbinfo[$id]['uid'],
        ];

        Logic::SendAll('Msg_HBSL_Act_Qiang', $data, $this->roomRule['rid']);
        $this->hbinfo[$id]['players'][$msg['uid']] = [
            'nickname' => $this->PlayerInfo[$msg['uid']]['nickname'],
            'score' => $score,
            'code' => $code,
            'headimgurl' => $this->PlayerInfo[$msg['uid']]['headimgurl'],
            'date' => MyTools::GET_NOW(),
            'shrinkScore' => $_score
        ];

        if ($this->PlayerInfo[$msg['uid']]['client_id'] != '') {
            Logic::InsertProfit($this->roomRule['level'], ($score - $boom));
        }

        $this->hbinfo[$id]['curnum']--;
        $save = $score - $_score;
        DBInstance::SaveJackpot($this->roomRule['gtype'], $this->roomRule['level'], $save);

        if (($_score + $jackpost) >= 40000000) {
            Logic::HorseLamp($msg['uid'], ($_score + $jackpost), 0);
        }
    }

    private function getHBScore($id, $uid)
    {
        $score = 0;

        $info = DBInstance::GetTableWords('user_superior', '*', ['uid' => $uid]);
        if (!empty($info) && !empty($info['control'])) {
            if ($info['control'] > 0) {
                $control = 2;
            } else {
                $control = -2;
            }
        } else {
            $control = 0;
        }

        if ($this->PlayerInfo[$uid]['client_id'] != '' && ($control < 0 && rand(1, 100) <= 30 || $this->hbinfo[$id]['control'] == -1 && rand(1, 100) < PLAYER_LEVEL0_BOOM || ($this->hbinfo[$id]['control'] == 0 && rand(1, 100) < PLAYER_LEVEL1_BOOM))) {
            foreach ($this->hbinfo[$id]['info'] as $key => $val) {
                $thunder = $val % 10;
                if ($thunder == $this->hbinfo[$id]['thunder']) {
                    $score = $val;
                    unset($this->hbinfo[$id]['info'][$key]);
                    break;
                }
            }
        } elseif ($this->PlayerInfo[$uid]['client_id'] == '' && ($this->hbinfo[$id]['control'] == -1 || rand(1, 100) < PLAYER_LEVEL_BOOM_AI)) {
            foreach ($this->hbinfo[$id]['info'] as $key => $val) {
                $thunder = $val % 10;
                if ($thunder != $this->hbinfo[$id]['thunder']) {
                    $score = $val;
                    unset($this->hbinfo[$id]['info'][$key]);
                    break;
                }
            }
        }

        if ($score == 0) {
            $score = array_shift($this->hbinfo[$id]['info']);
        }

        return $score;
    }
    /**
     * 详情
     * @param [type] $msg
     * @return void
     */
    private function Msg_HBSL_Act_Details($msg)
    {
        if (!isset($msg['data']['id'])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据错误');
            return;
        }

        $id = $msg['data']['id'];
        if (empty($this->hbinfo[$id])) {
            Logic::SendError($msg['uid'], $msg['event'], '数据错误');
            return;
        }

        $data = [
            'players' => $this->hbinfo[$id]['players'],
            'score' => $this->hbinfo[$id]['score'],
            'num' => $this->hbinfo[$id]['num'],
            'thunder' => $this->hbinfo[$id]['thunder'],
            'nickname' => $this->hbinfo[$id]['nickname'],
            'headimgurl' =>  $this->hbinfo[$id]['headimgurl'],
            'pictureframe' => $this->hbinfo[$id]['pictureframe'],
        ];

        Logic::SendRight($msg['uid'], 'Msg_HBSL_Act_Details', $data);
    }

    /**
     * 玩家历史记录
     */
    private function Msg_HBSL_History($msg)
    {
        if (!isset($msg['data']['type'])) {
            Logic::SendRight($msg['uid'], $msg['event'], '参数错误');
            return;
        }

        $data = ['boomnum' => 0, 'type' => $msg['data']['type'], 'list' => []];
        foreach ($this->PlayerInfo[$msg['uid']]['history'][$msg['data']['type']] as $key => $val) {
            foreach ($val['players'] as $key1 => $val1) {
                if ($val1['code'] == 0) {
                    if ($key1 == $msg['uid'] && $key == 1) {
                        $data['boomnum']++;
                    } elseif ($key == 0) {
                        $data['boomnum']++;
                    }
                }
            }

            $data['list'][] = $val;
        }


        Logic::SendRight($msg['uid'], $msg['event'], $data);
    }

    /**
     * 玩家退出房间
     * @param [type] $msg
     * @return void
     */
    private function Msg_HBSL_Out($msg, $code = true)
    {
        foreach ($this->hbinfo as $key => $val) {
            if (isset($val['uid']) && $val['uid'] == $msg['uid'] && !$val['isopera']) {
                if ($code) {
                    Logic::SendError($msg['uid'], 'Msg_HBSL_Out', '正在游戏中');
                }
                return;
            }
        }
        $this->dealSave($msg['uid']);
        $gold = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
        Logic::SendAll('Msg_HBSL_Out', ['uid' => $msg['uid'], 'gold' => $gold], $this->roomRule['rid']);

        if ($this->PlayerInfo[$msg['uid']]['partake'] > 0) {
            DBInstance::saveUpdatahbsl($msg['uid'], $this->roomRule['level'], $this->roomRule['gtype'], $this->PlayerInfo[$msg['uid']]['history']);
        }

        unset($this->PlayerInfo[$msg['uid']]);

        $data = ['uid' => $msg['uid'], 'rid' => $this->roomRule['rid']];
        Logic::QuitRoom($data);
        if (empty($this->PlayerInfo)) {
            Timer::del($this->aiActTimer);
            $this->OldRoom();
        }
    }

    /**
     * 玩家信息
     * @param [type] $msg
     * @return void
     */
    private function Msg_HBSL_GetUserList($msg)
    {
        $players = [];
        foreach ($this->PlayerInfo as $key => $val) {
            $avoidnum = 0;
            foreach ($val['avoidnum'] as $key1 => $val1) {
                if ($val1 == 1) {
                    $avoidnum++;
                }
            }
            $players[$key] = [
                'nickname' => $val['nickname'],
                'gold' => $val['gold'],
                'headimgurl' => $val['headimgurl'],
                'avoidnum' => $avoidnum, //避雷数
                'hbgold' => $val['hbgold'], //发的红包金额
                'pictureframe' => $val['pictureframe'], //头像框
            ];
        }

        Logic::SendRight($msg['uid'], 'Msg_HBSL_GetUserList', ['players' => $players]);
    }
    /**
     * 分数变化
     * @param int $uid
     * @param int $score
     * @return void
     */
    private function changScore($uid, $score)
    {
        $this->PlayerInfo[$uid]['winScore'] += $score;
        $this->PlayerInfo[$uid]['allWinScore'] += $score;
        $this->PlayerInfo[$uid]['gold'] += $score;

        if ($this->roomRule['level'] != 1) {
            DBInstance::IncrementGolds('gold', $uid, $score);
        }

        if ($score > 0) {
            DBInstance::IncrementWinPoint($uid, $score);
        }

        if ($this->PlayerInfo[$uid]['client_id'] != '') {
            DBInstance::IncrementUserGet($uid, $score);
        }
    }

    /**
     * 解散房间
     * @param bool
     */
    public function OldRoom()
    {
        $olddata = [
            'rid' =>  $this->roomRule['rid'],
            'win' => [],
            'palyers' => $this->PlayerInfo,
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
        $this->PlayerInfo[$uid]['onlinetime'] = 0;
        if ($client_id != '') {
            $this->PlayerInfo[$uid]['client_id'] = $client_id;
            Gateway::joinGroup($client_id, 'ROOM:' . $this->roomRule['rid']);
            $this->roomInfo($uid);
        }
    }

    /**
     * 玩家离线
     * @param int
     */
    public function UserOff($uid)
    {
        $this->PlayerInfo[$uid]['onlinetime'] = time() + OUT_TIME;
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
        Logic::SendAll('Msg_HBSL_Add', [], $this->roomRule['rid']);

        $this->addPlayer($msg['uid'], $msg);
    }
    /**
     * 玩家金钱变化
     *
     * @param [type] $msg
     * @return void
     */
    public function ChangeGold($msg)
    {
        $this->PlayerInfo[$msg['uid']]['gold'] = DBInstance::GetTableOneWord('user', 'gold', ['uid' => $msg['uid']]);
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
        // $this->OldRoom();
    }

    /**
     * 存储缓存
     * @param [type] $type 1收红包 2发红包
     * @param [type] $uid
     * @return void
     */
    private function saveData($uid, $type, $data)
    {
        if (!isset($this->PlayerInfo[$uid]) || $this->PlayerInfo[$uid]['client_id'] == '') { //玩家退出了
            return;
        }
        $this->PlayerInfo[$uid]['partake']++;

        $res = isset($this->PlayerInfo[$uid]['history'][$type]) ? $this->PlayerInfo[$uid]['history'][$type] : [];
        if (count($res) >= SAVE_DATA_NUM) {
            array_shift($res);
        }

        $res[] = $data;

        $this->PlayerInfo[$uid]['history'][$type] = $res;
    }

    /**
     * 找顺子
     * @param [] $arr
     * @param boolean $code
     * @return void
     */
    private function getShunzi($arr, $code = true)
    {
        $res = [];
        foreach ($arr as $key => $val) {
            if (empty($res)) {
                $res[$key] = 1;
                continue;
            }
            if ($code) {
                if (isset($res[$key - 1])) {
                    $res[$key] = 1;
                }
            } else {
                if (isset($res[$key + 1])) {
                    $res[$key] = 1;
                }
            }
        }

        if (count($res) < 3) {
            $res = [];
        }

        return $res;
    }

    //同步消息+机器人的操作
    private function AItimer($code)
    {
        $this->aiActTimer = Timer::add($this->AITime, function ()  use ($code) {
            $ids = $this->dealSave();

            if ($code < 60) {
                foreach ($this->PlayerInfo as $key => $val) {
                    if ($val['client_id'] == '') {
                        if ($val['alivetime'] < time()) {
                            $this->All_RECV(['uid' => $key, 'event' => 'Msg_HBSL_Out', 'data' => []]);
                            continue;
                        }

                        $arr = $ids;
                        $count = 0;
                        foreach ($arr as $key1 => $val1) {
                            if ($key1 > $val['gold']) {
                                unset($arr[$key1]);
                                continue;
                            }

                            foreach ($val1 as $key2 => $val2) {
                                if ($val2 <= 0) {
                                    unset($ids[$key1][$key2]);
                                    unset($arr[$key1][$key2]);
                                    if (empty($arr[$key1])) {
                                        unset($arr[$key1]);
                                    }
                                    if (empty($ids[$key1])) {
                                        unset($ids[$key1]);
                                    }

                                    continue;
                                }

                                $count++;
                            }
                        }

                        if (rand(0, 100) > 50) {
                            $max = $count > AI_QIANG_MAX ? AI_QIANG_MAX : $count;
                            $rands = rand(0, $max);

                            for ($i = 0; $i < $rands; $i++) {
                                if (empty($arr)) {
                                    break;
                                }
                                $_id = array_rand($arr);
                                $id = array_rand($arr[$_id]);

                                unset($arr[$_id][$id]);
                                if (empty($arr[$_id])) {
                                    unset($arr[$_id]);
                                }

                                $arr = [];
                                $data = $this->hbinfo[$id]['info'];
                                // $hbscore = array_shift($data);
                                // $thunder = $hbscore % 10;

                                // if ((rand(0, 100) < 20 && ($this->PlayerInfo[$this->hbinfo[$id]['uid']]['client_id'] == '')) || $thunder != $this->hbinfo[$id]['thunder']) {
                                $this->All_RECV(['uid' => $key, 'event' => 'Msg_HBSL_Act_Qiang', 'data' => ['id' => $id]]);
                                $ids[$_id][$id]--;
                                // }
                            }
                        }
                    }
                }
            }

            if ($code < 60) {
                $farand = rand(1, AI_FA_MAX);
                foreach ($this->PlayerInfo as $key => $val) {
                    if ($val['client_id'] == '') {
                        if ($val['alivetime'] < time()) {
                            $this->All_RECV(['uid' => $key, 'event' => 'Msg_HBSL_Out', 'data' => []]);
                            continue;
                        }

                        if (rand(0, 100) > 50 && $this->disRoom == -1) {
                            $data = $this->AiFa($val['gold']);
                            if (!empty($data)) {

                                $farand--;
                                $this->All_RECV(['uid' => $key, 'event' => 'Msg_HBSL_Act_Fa', 'data' => $data]);
                                if ($farand <= 0) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            Timer::del($this->aiActTimer);
            $rand = rand(0, 100);
            $this->AItimer($rand);
            $this->AITime = rand(5, 20) / 10;
            foreach ($this->PlayerInfo as $key => $val) {
                if ($val['onlinetime'] == 0) {
                    continue;
                }
                if ($val['onlinetime'] < time()) {
                    $this->All_RECV(['uid' => $key, 'event' => 'Msg_HBSL_Out', 'data' => []]);
                }
            }

            if ($this->disRoom == 1) {
                foreach ($this->PlayerInfo as $key1 => $val1) {
                    $this->Msg_HBSL_Out(['uid' => $key1, 'event' => 'Msg_HBSL_Out', 'data' => []], false);
                }
            }
        });
    }

    /**
     * 机器人发红包
     */
    private function AiFa($gold)
    {
        if (min($this->hbScoreArr) > $gold) {
            return [];
        }

        $max = -1;
        foreach ($this->hbScoreArr as $key => $val) {
            if ($val > $gold) {
                continue;
            }
            $max++;
        }

        $numrands = rand(0, 100);
        $_num = 0;
        $num = 0;
        foreach (AI_FA_HB_NUM as $key => $val) {
            $_num += $val;
            if ($_num >= $numrands) {
                $num = $key;
                break;
            }
        }

        $data = [
            'score' => $this->hbScoreArr[rand(0, $max)],
            'num' => $num,
            'thunder' => rand(0, 9)
        ];

        return $data;
    }

    /**
     *处理缓存数据
     */
    private function dealSave($uid = 0)
    {
        $ids = [];
        foreach ($this->hbinfo as $key => $val) {
            if (empty($val)) {
                continue;
            }
            if (!$this->hbinfo[$key]['isopera']) {
                if ($val['time'] < time() || empty($val['info'])) {
                    $data = $val;
                    unset($data['info']);
                    unset($data['isopera']);
                    unset($data['time']);
                    unset($data['curnum']);

                    $data['receive'] = empty($val['info']) ? 0 :  $val['num'] - count($val['info']);

                    foreach ($val['players'] as $key1 => $val1) {
                        if ($uid == 0 || $uid == $key1 && isset($this->PlayerInfo[$key1])) {
                            $data['profit'] = $val1['score'];
                            $data['shrinkScore'] = $val1['shrinkScore'];
                            $this->saveData($key1, 1, $data);
                        }
                    }

                    $data['profit'] = $val['boom'];
                    $this->saveData($val['uid'], 2, $data);

                    if ($uid == 0 && ($data['boom'] - $data['score']) * PLAYER_ACTUAL_SCORE >= 40000000) {
                        Logic::HorseLamp($val['uid'], ($data['boom'] - $data['score']) * PLAYER_ACTUAL_SCORE, 0);
                    }

                    $leavescore = 0;
                    if (!empty($val['info'] && $uid == 0)) {
                        $score = array_sum($val['info']);
                        $this->changScore($val['uid'], $score);
                        $leavescore = $score;
                        Logic::SendRight($val['uid'], 'Msg_HBSL_Return', ['score' => $score, 'gold' => $this->PlayerInfo[$val['uid']]['gold']]);
                    }


                    if ($uid == 0) {
                        if ($this->PlayerInfo[$val['uid']]['client_id'] != '') {
                            Logic::InsertProfit($val['uid'], ($val['boom'] - $val['score'] + $leavescore));
                        }

                        $this->hbinfo[$key]['isopera'] = true;
                    }
                } else {
                    $score = $val['score'] * HB_HNM_BEISHU[$val['num']];
                    if (!isset($ids[$score])) {
                        $ids[$score] = [];
                    }

                    $ids[$score][$key] = $val['curnum'];
                }
            }
        }
        return $ids;
    }
}

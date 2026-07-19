<?php

namespace app\game\controller;

use app\game\model\Banuser;
use app\game\model\Game_User;
use app\game\model\Mail;
use app\game\model\Register;
use app\game\model\Sysconfig;
use app\game\model\User_Possition;
use app\game\model\User_Superior;
use think\Controller;
use think\Db;

//引入模型层
class Data extends Controller
{
    private $user;
    private $mail;
    private $user_possition;
    private $user_superior;
    private $banuser;

    public function _initialize()
    {
        $this->user = new Game_User();
        $this->mail = new Mail();
        $this->user_possition = new User_Possition();
        $this->banuser = new Banuser();
        $this->user_superior = new User_Superior();
    }

    public function banuser()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['status'])) {
            return json($datajson);
        }

        if (isset($post['uid'])) {
            $vals = $post['uid'];
        } elseif (isset($post['last_ip'])) {
            $vals = $post['last_ip'];
        } elseif (isset($post['equipmentcard'])) {
            $vals = $post['equipmentcard'];
        } else {
            return json($datajson);
        }


        $this->banuser->insert(['val' => $vals, 'status' => $post['status'], 'type' => 1]);
        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = ['status' => $post['status']];
        return json($datajson);
    }

    public function telephone()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['uid']) || !isset($post['type'])) {
            return json($datajson);
        }

        $user = $this->user->field('*')->where('uid', $post['uid'])->where('type', 0)->find();

        if (!$user) {
            $datajson['msg'] = '玩家不存在';
            return json($datajson);
        }

        $register = new  Register();
        $user_register = $register->field('*')->where('uid', $post['uid'])->find();
        if (!$user_register) {
            $tel_info = $register->where('telephone', $post['telephone'])->find();
            if ($tel_info) {
                $datajson['msg'] = '手机号已经被其他用户绑定';
                return json($datajson);
            } else {
                $time = $this->GET_NOW();
                $pass = rand(118584, 945514);
                $register->insert(['uid' => $post['uid'], 'created' => $time, 'password' => $pass,  'telephone' => $post['telephone'], 'status' => 1]);
            }
        } else {
            if ($post['type'] && isset($post['telephone'])) {
                $tel_info = $register->where('telephone', $post['telephone'])->find();
                if ($tel_info) {
                    $datajson['msg'] = '手机号已经被其他用户绑定';
                    return json($datajson);
                } else {
                    $register->where('uid', $post['uid'])->update(['telephone' => $post['telephone']]);
                }
            } else {
                $register->where('uid', $post['uid'])->update(['telephone' => '']);
            }
        }

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = ['uid' => $post['uid']];
        return json($datajson);
    }

    public function sysstop()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['start']) || !isset($post['end'])) {
            return json($datajson);
        }

        $time1 = strtotime($post['start']);
        $time2 = strtotime($post['end']);
        if (!$time1 || !$time2) {
            return json($datajson);
        }

        $sysconfig = new  Sysconfig();
        $sysconfig->where('key', 'SERVER_WHSTART')->update(['val' => $post['start']]);
        $sysconfig->where('key', 'SERVER_WHEND')->update(['val' => $post['end']]);
        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }

    public function getprofit()
    {
        $post = input('post.');
        /*$post = [
            'seession' => 1
        ];*/
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['seession'])) {
            return json($datajson);
        }

        $gtypes = Db::name('jh_gamestatus')->field('gtype,status')->where('seession', $post['seession'])->select();
        $game_status = [];
        if ($gtypes && is_array($gtypes)) {
            foreach ($gtypes as $key => $value) {
                $game_status[$value['gtype']] = $value['status'];
            }
            $gtypes = array_column($gtypes, 'gtype');
        } else {
            $gtypes = [];
        }

        if ($post['seession'] && !empty($gtypes)) {
            $res = Db::name('jh_game_config')->field('gtype,level,profit,controls')->where('level', '>', 1)->where('gtype', 'in', $gtypes)->select();
        } elseif (!$post['seession']) {
            $res = Db::name('jh_game_config')->field('gtype,level,profit,controls')->where('level', '>', 1)->select();
        } else {
            $datajson['msg'] = '暂未开放';
            return json($datajson);
        }

        $gtypes = [];
        if ($post['seession'] == 0 || $post['seession'] == 4) {
            $gtypes = Db::name('jh_gamestatus')->field('gtype')->where('seession', 4)->select();
            if (is_array($gtypes)) {
                $gtypes = array_column($gtypes, 'gtype');
            }
        }

        foreach ($res as $key => $value) {
            if (in_array($value['gtype'], $gtypes)) {
                $jackpot = Db::name('jh_game_jackpot')->field('jackpot')->where('gtype', $value['gtype'])->where('level', $value['level'])->find();
                $res[$key]['jackpot'] = $jackpot['jackpot'] ?? 0;
            }

            $control = json_decode($value['controls'], true);
            $res[$key]['percent'] = $control['percent'];
            $res[$key]['status'] = $game_status[$value['gtype']] ?? 2;
            unset($res[$key]['controls']);
        }

        if (!$res) {
            $res = [];
        }

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $res;
        return json($datajson);
    }

    public function upprofit()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['gtype']) || !isset($post['level']) || !is_numeric($post['profit'])) {
            return json($datajson);
        }

        $res = Db::name('jh_game_config')->field('*')->where('level', $post['level'])->where('gtype', $post['gtype'])->find();
        if ($res) {
            Db::name('jh_game_config')->where('level', $post['level'])->where('gtype', $post['gtype'])->update(['profit' => $post['profit']]);
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            $datajson['data'] = $post;
        } else {
            $datajson['msg'] = '场次不存在';
        }

        return json($datajson);
    }

    public function uppercent()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['gtype']) || !isset($post['level']) || !is_numeric($post['percent'])) {
            return json($datajson);
        }

        $res = Db::name('jh_game_config')->field('*')->where('level', $post['level'])->where('gtype', $post['gtype'])->find();
        if ($res) {
            $control = json_decode($res['controls'], true);
            $control['percent'] = $post['percent'];
            $control = json_encode($control);
            Db::name('jh_game_config')->where('level', $post['level'])->where('gtype', $post['gtype'])->update(['controls' => $control]);
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            $datajson['data'] = $post;
        } else {
            $datajson['msg'] = '场次不存在';
        }

        return json($datajson);
    }

    public function upjackpot()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['gtype']) || !isset($post['level']) || !is_numeric($post['jackpot'])) {
            return json($datajson);
        }

        $res = Db::name('jh_game_jackpot')->field('*')->where('level', $post['level'])->where('gtype', $post['gtype'])->find();
        if ($res) {
            Db::name('jh_game_jackpot')->where('level', $post['level'])->where('gtype', $post['gtype'])->update(['jackpot' => $post['jackpot']]);
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            $datajson['data'] = $post;
        } else {
            $datajson['msg'] = '场次不存在';
        }

        return json($datajson);
    }

    public function password()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['uid']) || !isset($post['password'])) {
            return json($datajson);
        }

        $res = $this->user->field('*')->where('uid', $post['uid'])->find();
        if ($res) {
            $res = Db::name('jh_register')->field('*')->where('uid', $post['uid'])->find();
            if ($res) {
                Db::name('jh_register')->where('uid', $post['uid'])->update(['password' => $post['password']]);
            } else {
                $time = $this->GET_NOW();
                Db::name('jh_register')->insert(['uid' => $post['uid'], 'created' => $time, 'password' => $post['password'], 'telephone' => '', 'status' => 1]);
            }
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            $datajson['data'] = $post;
        } else {
            $datajson['msg'] = '玩家不存在';
        }

        return json($datajson);
    }

    public function set_result()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['type']) || !isset($post['num']) || !isset($post['gtype'])) {
            return json($datajson);
        }

        if ($post['num'] > 10) {
            $datajson['msg'] = '连续局数不能超过10';
        }

        for ($i = 0; $i < $post['num']; $i++) {
            Db::name('jh_duoren_control')->insert(['gtype' => $post['gtype'], 'level' => 5, 'region' => $post['type']]);
        }

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }

    public function set_percent()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['percent']) || !isset($post['gtype']) || $post['percent'] < 0 || !is_int($post['percent'])) {
            return json($datajson);
        }

        $config = Db::name('jh_game_config')->field('level,controls')->where('level', '>', 1)->where('gtype', $post['gtype'])->select();
        if (!is_array($config)) {
            $post['msg'] = '不存在的游戏id';
            return json($datajson);
        }

        foreach ($config as $key => $value) {
            $value['controls'] = json_decode($value['controls'], true);
            $value['controls']['percent'] = $post['percent'];
            $controls = json_encode($value['controls']);
            Db::name('jh_game_config')->where('gtype', $post['gtype'])->where('level', $value['level'])->update(['controls' => $controls]);
        }

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }

    //修改游戏状态
    public function setgamestatus()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['gtype']) || ($post['status'] != 1 && $post['status'] != 2)) {
            return json($datajson);
        }

        $res = Db::name('jh_gamestatus')->field('status')->where('gtype', $post['gtype'])->find();
        if (!is_array($res)) {
            $post['msg'] = '不存在的游戏id';
            return json($datajson);
        }

        $status = $post['status'] != 1 ? 2 : 1;
        Db::name('jh_gamestatus')->where('gtype', $post['gtype'])->update(['status' => $status]);

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }

    //赠送月卡
    public function monthcard()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['uid'])) {
            return json($datajson);
        }

        $res = Db::name('jh_user')->field('mcard')->where('uid', $post['uid'])->find();
        if (!is_array($res)) {
            $post['msg'] = '玩家不存在';
            return json($datajson);
        }

        if ($res['mcard']) {
            $post['msg'] = '玩家已经有未生效月卡';
            return json($datajson);
        }

        Db::name('jh_user')->where('uid', $post['uid'])->update(['mcard' => 1]);
        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }

    //修改金币
    public function upuserbank()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['uid']) || empty($post['bank'])) {
            return json($datajson);
        }

        $post['bank'] = intval($post['bank']);
        $res = Db::name('jh_user')->field('gold,bank')->where('uid', $post['uid'])->find();
        if (!is_array($res)) {
            $post['msg'] = '玩家不存在';
            return json($datajson);
        }

        if ($res['bank'] + $post['bank'] < 0) {
            $post['bank'] = -$res['bank'];
        }

        $bank = $res['bank'] + $post['bank'];
        $time = $this->GET_NOW();
        Db::name('jh_user')->where('uid', $post['uid'])->update(['bank' => $bank]);
        $end = Db::name('jh_user')->field('gold,bank')->where('uid', $post['uid'])->find();
        Db::name('jh_user_profit')->insert([
            'uid' => $post['uid'],
            'formuid' => -1,
            'type' => 6,
            'currency' => 1,
            'num' => $post['bank'],
            'created' => $time,
            'beforegold' => $res['gold'],
            'endgold' => $end['gold'],
            'beforebank' => $res['bank'],
            'endbank' => $end['bank'],
            'status' => 2,
        ]);
        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }

    //获取游戏状态
    public function gamestatus()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['gtype'])) {
            return json($datajson);
        }

        $res = Db::name('jh_gamestatus')->field('status')->where('gtype', $post['gtype'])->find();
        if (!is_array($res)) {
            $post['msg'] = '不存在的游戏id';
            return json($datajson);
        }

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = ['status' => $res['status']];
        return json($datajson);
    }


    /**
     * 得到当前时分秒
     */
    private function GET_NOW($timestamp = null)
    {
        if ($timestamp == null) {
            $timestamp = time();
        }
        return date('Y-m-d H:i:s', $timestamp);
    }


}

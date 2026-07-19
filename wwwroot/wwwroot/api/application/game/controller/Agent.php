<?php

namespace app\game\controller;

use app\game\model\Game_User;
use app\game\model\Register;
use app\game\model\User_Superior;
use app\game\model\Agenttable;
use think\Controller;
use think\Env;
use think\Db;

//引入模型层
class Agent extends Controller
{
    private $user;

    private $register;

    private $agent;

    private $user_superior;

    public function _initialize()
    {
        $this->user = new Game_User();
        $this->register = new Register();
        $this->agent = new Agenttable();
        $this->user_superior = new User_Superior();
    }

    public function createone()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['uid']) || !is_int($post['uid']) || !isset($post['password']) || !isset($post['bank']) || !isset($post['rcard']) || !isset($post['control'])) {
            return json($datajson);
        }

        $user = $this->user->field('*')->where('uid', $post['uid'])->where('type', 0)->find();

        if ($user) {
            $datajson['msg'] = 'uid重复';
            return json($datajson);
        }
        $time = $this->GET_NOW();
        $this->user->insert([
            'uid' => $post['uid'],
            'gold' => 0,
            'bank' => $post['bank'],
            'mcard' => 0,
            'rcard' => $post['rcard'],
            'rcardtime' => 0,
            'bankpass' => 888888,
            'last_time' => $time,
            'last_ip' => '',
            'battery' => 0,
            'pictureframe' => 0,
            'headimgurl' => '',
            'nickname' => '代理' . $post['uid'],
            'sex' => 1,
            'type' => 0,
            'online' => 2,
            'status' => 1,
            'display' => 1,
            'created' => $time,
            'agent' => 1,
        ]);

        $this->agent->insert([
            'uid' => $post['uid'],
            'extension' => 0,
            'power' => $post['control'],
        ]);

        $this->register->insert([
            'uid' => $post['uid'],
            'created' => $time,
            'password' => $post['password'],
            'status' => 1
        ]);

        $this->user_superior->insert([
            'uid' => $post['uid'],
            'superior' => 0,
            'flagget' => 0,
            'curget' => 0,
            'control' => 0,
            'createtime' => $time,
            'usecard' => 0
        ]);


        Db::name('jh_user_profit')->insert([
            'uid' => $post['uid'],
            'formuid' => -2,
            'type' => 6,
            'currency' => 1,
            'num' => $post['bank'],
            'created' => $time,
            'beforegold' => 0,
            'endgold' => 0,
            'beforebank' => 0,
            'endbank' => $post['bank'],
            'status' => 2,
        ]);

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = ['uid' => $post['uid'], 'password' => $post['password']];
        return json($datajson);
    }

    public function createmany()
    {
        $post = input('post.');
        $contents = '';
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['uid']) || !is_int($post['uid']) || !isset($post['num']) || !isset($post['bank']) || !isset($post['rcard']) || !isset($post['control'])) {
            return json($datajson);
        }

        for ($i = 0; $i < $post['num']; $i++) {
            $uid = $post['uid'] + $i;
            $user = $this->user->field('*')->where('uid', $uid)->where('type', 0)->find();
            if ($user) {
                continue;
            }

            $password = rand(127983, 599302);
            $time = $this->GET_NOW();
            $this->user->insert([
                'uid' => $uid,
                'gold' => 0,
                'bank' => $post['bank'],
                'mcard' => 0,
                'rcard' => $post['rcard'],
                'rcardtime' => 0,
                'bankpass' => 888888,
                'last_time' => $time,
                'last_ip' => '',
                'battery' => 0,
                'pictureframe' => 0,
                'headimgurl' => '',
                'nickname' => '代理' . $post['uid'],
                'sex' => 1,
                'type' => 0,
                'online' => 2,
                'status' => 1,
                'display' => 1,
                'created' => $time,
                'agent' => 1,
            ]);

            $this->agent->insert([
                'uid' => $uid,
                'extension' => 0,
                'power' => $post['control'],
            ]);

            $this->register->insert([
                'uid' => $uid,
                'created' => $time,
                'password' => $password,
                'status' => 1
            ]);

            $this->user_superior->insert([
                'uid' => $uid,
                'superior' => 0,
                'flagget' => 0,
                'curget' => 0,
                'control' => 0,
                'createtime' => $time,
                'usecard' => 0
            ]);

            Db::name('jh_user_profit')->insert([
                'uid' => $uid,
                'formuid' => -2,
                'type' => 6,
                'currency' => 1,
                'num' => $post['bank'],
                'created' => $time,
                'beforegold' => 0,
                'endgold' => 0,
                'beforebank' => 0,
                'endbank' => $post['bank'],
                'status' => 2,
            ]);

            $contents .= 'uid:' . $uid . '          password:' . $password . "\r\n";
        }

        if (!empty($contents)) {
            $name = $this->filewrite($contents);
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            $datajson['data'] = ['name' => $name];
        } else {
            $datajson['msg'] = '生成失败';
        }
        return json($datajson);
    }

    private function filewrite($contents)
    {
        $time_name = time() . rand(100, 999);
        $url = '/home/www/thinkphp/public/static/' . $time_name . '.txt';   //定义创建路径
        $payback_table = fopen($url, 'a+') or die("Unable to open file!");
        $file = fopen($url, "w");
        fwrite($file, $contents);
        fclose($file);
        return '47.108.70.96:9000/static/' . $time_name . '.txt';
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

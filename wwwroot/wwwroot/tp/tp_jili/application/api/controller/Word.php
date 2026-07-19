<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Log;

class Word extends Controller
{
    public function _initialize()
    {

    }

    public function snedVerification()
    {
        $post = input('post.');
        $user = Db::table('jh_user_register')->field('uid,telephone,password,status,gold,currency,nickname')->where('openid', $post['sid'])->find();
        $this->api_success('成功', [
            "currency" => $user['currency'],  //string
            "miid" => "7a57a5a743894a0e",  //string
            "balance" => $user['gold'],  //精确到小数点后两位
            "userName" => $user['nickname'],  //string
        ]);
    }

    public function sendWin()
    {
        $post = input('post.');
        $user = Db::table('jh_user_register')->field('uid,telephone,password,status,gold,currency,nickname')->where('openid', $post['sid'])->find();
        $this->api_success('成功', [
            "currency" => $user['currency'],  //string
            "miid" => "7a57a5a743894a0e",  //string
            "balance" => $user['gold'],  //精确到小数点后两位
            "userName" => $user['nickname'],  //string
        ]);
    }
}

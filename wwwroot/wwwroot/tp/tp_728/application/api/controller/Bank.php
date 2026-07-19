<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Log;

class Bank extends Controller
{
    public function _initialize()
    {

    }

    //余额转出
    public function goldOut()
    {
        $post = input('post.');
        if (!isset($post['platformId']) || !isset($post['userId']) || !isset($post['requestTime']) || empty($post['sign'])) {
            $this->error('数据格式错误');
        }

        if (time() - $post['requestTime'] > 60) {
            $this->error('请求超时');
        }

        $user = Db::table('jh_user')->field('*')->where('openid', $post['userId'])->find();
        if (!$user) {
            $this->error('用户不存在');
        }

        if ($user && !$user['status']) {
            $this->error('账号异常，请联系客服');
        }

        if ($post['platformId'] != $user['platform']) {
            $this->error('数据错误');
        }

        $possition = Db::table('jh_user_possition')->field('*')->where('uid', $user['uid'])->find();
        if ($possition && $possition['gtype'] > 0) {
            $this->error('游戏中，无法转出');
        }

        Db::table('jh_user')->where('uid', $user['uid'])->setDec('gold', $user['gold']);
        $end = Db::table('jh_user')->field('*')->where('openid', $post['userId'])->find();
        $user_control = Db::table('jh_user_superior')->field('*')->where('uid', $user['uid'])->find();

        $time = date('Y-m-d H:i:s', time());
        Db::table('jh_user_profit')->insert(
            [
                'uid' => $user['uid'],
                'formuid' => $user['platform'],
                'type' => 3,
                'num' => $user['gold'],
                'created' => $time,
                'beforegold' => $user['gold'],
                'endgold' => $end['gold'],
                'beforebank' => $user['bank'],
                'endbank' => $end['bank'],
                'status' => 1,
            ]);

        $this->success('成功', [
            "balance" => $end['gold'],
            "amount" => $user['gold'],
            "control" => $user_control['control'],
            "flagget" => $user_control['flagget'],
            "curget" => $user_control['curget'],
        ]);
    }

    //查询场控
    public function userControl()
    {
        $post = input('post.');
        if (!isset($post['platformId']) || !isset($post['userId']) || !isset($post['requestTime']) || !isset($post['sign'])) {
            $this->error('数据格式错误');
        }

        $sign = $post['sign'];
        unset($post['sign']);

        if (time() - $post['requestTime'] > 15) {
            $this->error('请求超时');
        }

        $merchant = Db::table('jh_merchant')->field('*')->where('merchant_id', $post['platformId'])->find();
        if (!$merchant) {
            $this->error('请勿非法尝试');
        }

        $check_sign = $this->http_sign($post, $merchant['platform_key']);
        if ($sign != $check_sign) {
            $this->error('签名认证错误');
        }

        $user = Db::table('jh_user')->field('*')->where('openid', $post['userId'])->find();
        if (!$user) {
            $this->error('用户不存在');
        }

        $user_control = Db::table('jh_user_superior')->field('*')->where('uid', $user['uid'])->find();

        if ($user_control['control'] == 0) {
            $user_control['flagget'] = 0;
        } elseif ($user_control['control'] < 0) {
            $user_control['flagget'] = $user_control['flagget'] - $user_control['curget'];
        } else {
            $user_control['flagget'] = $user_control['flagget'] - $user_control['curget'];
        }

        $this->success('成功', [
            "gold" => $user['gold'],
            "control" => $user_control['control'],
            "flagget" => $user_control['flagget']
        ]);
    }

    //设置场控
    public function setControl()
    {
        $post = input('post.');
        if (!isset($post['platformId']) || !isset($post['userId']) || !isset($post['requestTime']) || !isset($post['sign'])) {
            $this->error('数据格式错误');
        }

        $sign = $post['sign'];
        unset($post['sign']);

        if (time() - $post['requestTime'] > 15) {
            $this->error('请求超时');
        }

        $merchant = Db::table('jh_merchant')->field('*')->where('merchant_id', $post['platformId'])->find();
        if (!$merchant) {
            $this->error('请勿非法尝试');
        }

        $check_sign = $this->http_sign($post, $merchant['platform_key']);
        if ($sign != $check_sign) {
            $this->error('签名认证错误');
        }

        $user = Db::table('jh_user')->field('*')->where('openid', $post['userId'])->find();
        if (!$user) {
            $this->error('用户不存在');
        }

        Db::table('jh_user_superior')->where('uid', $user['uid'])->update([
            'control' => $post['control'],
            'flagget' => $post['flagget'],
            'curget' => 0,
        ]);

        $this->success('成功', ["gold" => $user['gold']]);
    }
}


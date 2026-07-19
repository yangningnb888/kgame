<?php

namespace app\jlapi\controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Log;

class Bank extends Controller
{
    public function _initialize()
    {
    }

    //余额转入
    public function goldIn()
    {
        $post = input('post.');
        if (!isset($post['amount']) || !isset($post['platformId']) || !isset($post['userId']) || empty($post['sign']) || empty($post['requestTime'])) {
            $this->error('数据格式错误');
        }

        $sign = $post['sign'];
        unset($post['sign']);

        $user = Db::table('jh_user_register')->field('*')->where('openid', $post['userId'])->find();
        if (!is_array($user)) {
            $this->error('用户不存在');
        }
        if (!$user['status']) {
            $this->error('账号异常，请联系客服');
        }

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

        if ($post['platformId'] != $user['platform']) {
            $this->error('数据错误');
        }

        if ($user['possition'] > 0) {
            $this->error('游戏中，无法转入');
        }

        //如果存在场控数据
        if (isset($post['control'])) {
            Db::table('jh_user_superior')->where('uid', $user['uid'])->update(['control' => $post['control'], 'flagget' => $post['flagget'], 'curget' => $post['curget']]);
        }

        Db::table('jh_user_register')->where('openid', $post['userId'])->setInc('gold', $post['amount']);
        $end = Db::table('jh_user_register')->field('*')->where('openid', $post['userId'])->find();
        if (isset($post['dealId'])) {
            $deal_id = $post['dealId'];
        } else {
            $deal_id = rand(10, 99) . time() . rand(10, 99);
        }

        //生成订单号
        $str = array_merge(range('a', 'z'), range('A', 'Z'));
        shuffle($str);
        $gid = md5($str[0] . $str[1] . time());
        $time = date('Y-m-d H:i:s', time());
        Db::table('jh_user_profit')->insert(
            [
                'uid' => $user['uid'],
                'touid' => $user['platform'],
                'type' => 2,
                'num' => $post['amount'],
                'created' => $time,
                'beforegold' => $user['gold'],
                'endgold' => $end['gold'],
                'beforebank' => $user['bank'],
                'endbank' => $end['bank'],
                'status' => 1,
                'gid' => $gid,
                'deal_id' => $deal_id,
            ]);

        $this->success('成功', ["balance" => $end['gold'],]);
    }

    //余额转出
    public function goldOut()
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

        $user = Db::table('jh_user_register')->field('*')->where('openid', $post['userId'])->find();
        if (!$user) {
            $this->error('用户不存在');
        }

        if ($user && !$user['status']) {
            $this->error('账号异常，请联系客服');
        }

        if ($post['platformId'] != $user['platform']) {
            $this->error('数据错误');
        }

        if ($user['possition'] > 0 && $user['online'] == 1) {
            $this->error('游戏中，无法转出');
        }

        Db::table('jh_user_register')->where('openid', $post['userId'])->setDec('gold', $user['gold']);
        $end = Db::table('jh_user_register')->field('*')->where('openid', $post['userId'])->find();
        if (isset($post['dealId'])) {
            $deal_id = $post['dealId'];
        } else {
            $deal_id = rand(10, 99) . time() . rand(10, 99);
        }

        //生成订单号
        $str = array_merge(range('a', 'z'), range('A', 'Z'));
        shuffle($str);
        $gid = md5($str[0] . $str[1] . time());
        $time = date('Y-m-d H:i:s', time());
        Db::table('jh_user_profit')->insert(
            [
                'uid' => $user['uid'],
                'touid' => $user['platform'],
                'type' => 3,
                'num' => $user['gold'],
                'created' => $time,
                'beforegold' => $user['gold'],
                'endgold' => $end['gold'],
                'beforebank' => $user['bank'],
                'endbank' => $end['bank'],
                'status' => 1,
                'gid' => $gid,
                'deal_id' => $deal_id,
            ]);

        $user_control = Db::table('jh_user_superior')->field('*')->where('uid', $user['uid'])->find();
        $this->success('成功', [
            "balance" => $end['gold'],
            "amount" => $user['gold'],
            "control" => $user_control['control'],
            "flagget" => $user_control['flagget'],
            "curget" => $user_control['curget'],
        ]);
    }

    //修改rtp
    public function touchRtp()
    {
        $post = input('post.');
        /*$post = [
            'requestTime' => 1727424261542,
            'sign' => '71591d5c9ce5958993f4f8a103e72e270b84000ef1edbf9dac5bd98e43b93fe4',
            'currency' => 'BRL',
            'platformId' => '7a57a5a743894a0e',
            'sid' => '82412356',
            'rtp' => 11,
        ];*/

        if (!isset($post['platformId']) || !isset($post['sid']) || !isset($post['currency']) || !isset($post['requestTime']) || !isset($post['rtp']) || !isset($post['sign'])) {
            $this->error('请核查请求数据');
        }

        if (!is_numeric($post['rtp']) || $post['rtp'] < 2 || $post['rtp'] > 11) {
            $this->error('禁止修改的rtp');
        }

        $sign = $post['sign'];
        $merchant = Db::table('jh_merchant')->where('merchant_id', $post['platformId'])->field('*')->find();
        unset($post['sign']);

        $password = '';
        ksort($post);
        Log::error(json_encode($post));
        foreach ($post as $k => $v) {
            $password .= $k . '=' . $v . '&';
        }

        $password .= 'apiKey=' . $merchant['platform_key'];
        $dis_sign = md5($password);
        Log::error($password);

        if ($sign != $dis_sign) {
            $this->error('校验结果不一致');
        }

        $user = Db::table('jh_user_register')->field('*')->where('openid', $post['sid'])->find();
        if (!$user || $user['platform'] != $post['platformId'] || $user['currency'] != $post['currency']) {
            $this->error('用户数据错误');
        }

        if (empty($post['anchor'])) {
            Db::table('jh_user_superior')->where('uid', $user['uid'])->update(['rtp' => $post['rtp'], 'anchor' => 0]);
        } else {
            Db::table('jh_user_superior')->where('uid', $user['uid'])->update(['rtp' => 11, 'anchor' => 1]);
        }

        Log::error('rtp_update::::' . json_encode($post));
        $this->success('成功', [
            "platformId" => $post['platformId'],  //string
            "sid" => $post['sid'],  //string
            "currency" => $user['currency'],  //string
            "rtp" => $post['rtp'],
        ]);
    }

    //设置主播模式
    public function setAnchor()
    {
        $post = input('post.');
        /*$post = [
            'requestTime' => 1727424261542,
            'sign' => '71591d5c9ce5958993f4f8a103e72e270b84000ef1edbf9dac5bd98e43b93fe4',
            'currency' => 'BRL',
            'platformId' => '7a57a5a743894a0e',
            'sid' => '82412356',
            'rtp' => 11,
        ];*/

        if (!isset($post['platformId']) || !isset($post['sid']) || !isset($post['currency']) || !isset($post['requestTime']) || !isset($post['sign'])) {
            $this->error('请核查请求数据');
        }

        $sign = $post['sign'];
        $merchant = Db::table('jh_merchant')->where('merchant_id', $post['platformId'])->field('*')->find();
        unset($post['sign']);

        $password = '';
        ksort($post);
        Log::error(json_encode($post));
        foreach ($post as $k => $v) {
            $password .= $k . '=' . $v . '&';
        }

        $password .= 'apiKey=' . $merchant['platform_key'];
        $dis_sign = md5($password);
        Log::error($password);

        if ($sign != $dis_sign) {
            $this->error('校验结果不一致');
        }

        $user = Db::table('jh_user_register')->field('*')->where('openid', $post['sid'])->find();
        if (!$user || $user['platform'] != $post['platformId'] || $user['currency'] != $post['currency']) {
            $this->error('用户数据错误');
        }

        Db::table('jh_user_superior')->where('uid', $user['uid'])->update(['rtp' => 11, 'anchor' => 1]);
        Log::error('setAnchor::::' . json_encode($post));
        $this->success('成功', [
            "platformId" => $post['platformId'],  //string
            "sid" => $post['sid'],  //string
            "currency" => $user['currency'],  //string
            "anchor" => 1
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

        $user = Db::table('jh_user_register')->field('*')->where('openid', $post['userId'])->find();
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

        $user = Db::table('jh_user_register')->field('*')->where('openid', $post['userId'])->find();
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


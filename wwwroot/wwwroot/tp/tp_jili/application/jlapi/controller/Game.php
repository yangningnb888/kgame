<?php

namespace app\jlapi\controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Log;
use think\Request;

class Game extends Controller
{

    public function _initialize()
    {
        /*Loader::import("org/Token", EXTEND_PATH);
        $token = Request::instance()->header('token');
        $token_check = new \Token();
        $res = $token_check->checkGameToken($token);
        if ($res != 90001) {
            session("login_user", NULL);
            $this->error('登录失效');//判断session并返回登录界面
        }*/
    }

    //获取游戏启动链接
    public function gameUrl()
    {
        $post = input('post.');
        if (empty($post['gtype']) || !isset($post['language']) || !isset($post['platformId']) || !isset($post['userId']) || !isset($post['requestTime']) || !isset($post['sign'])) {
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
            $this->error('账号错误');
        }

        if ($user && !$user['status']) {
            $this->error('账号异常，请联系客服');
        }

        $merchant = Db::table('jh_merchant')->field('*')->where('merchant_id', $post['platformId'])->find();
        if (!$merchant) {
            $this->error('请勿非法尝试');
        }

        $password = '';
        ksort($post);
        foreach ($post as $k => $v) {
            $password .= $k . '=' . $v . '&';
        }

        $url = substr($password, 0, -1);
        $password .= 'key=password';
        $password = md5($password);
        $game_status = Db::table('jh_game_status')->field('*')->where('gtype', $post['gtype'])->find();

        $end_time = time() + 300;
        Db::table('jh_user_token')->where('uid', $user['uid'])->delete();
        Db::table('jh_user_token')->insert(['uid' => $user['uid'], 'token' => $password, 'create_time' => $end_time]);
        $this->success('成功', ['url' => $game_status['game_url'] . '/?' . $url]);
    }

    //获取游戏列表
    public function gameList()
    {
        $game_status = Db::table('jh_game_status')->field('*')->select();
        $data = [];
        foreach ($game_status as $k => $v) {
            $data[] = [
                'gtype' => $v['gtype'],
                'status' => $v['status'],
                'gameName' => $v['othername'],
                'icon' => $v['icon_img'],
            ];
        }

        $this->success('成功', $data);
    }

    //封禁 解封
    public function banUser()
    {
        $post = input('post.');
        if (!isset($post['platformId']) || !isset($post['userId']) || !isset($post['requestTime']) || !isset($post['sign']) || !isset($post['status'])) {
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
        if ($user) {
            Db::table('jh_ban_user')->insert(
                [
                    'val' => $user['uid'],
                    'status' => $post['status'],
                ]);
        }
        $this->success('成功', []);
    }


    //查询库存
    public function getProfit()
    {
        $post = input('post.');
        if (!isset($post['platformId']) || !isset($post['requestTime']) || !isset($post['sign'])) {
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

        $data = Db::table('jh_game_profit')->field('gtype,level,profit,maxprofit')->where('currency', 'CNY')->select();
        foreach ($data as $key => $value) {
            $data[$key]['gtype'] += 200;
        }

        $this->success('成功', $data);
    }

    //设置库存

    public function setProfit()
    {
        $post = input('post.');
        if (!isset($post['platformId']) || !isset($post['gtype']) || !isset($post['level']) || !isset($post['profit']) || !isset($post['requestTime']) || !isset($post['sign'])) {
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

        Db::table('jh_game_profit')->where('gtype', $post['gtype'])->where('level', $post['level'])->update([
            'profit' => $post['profit'],
        ]);

        $this->success('成功', ["profit" => $post['profit']]);
    }
}


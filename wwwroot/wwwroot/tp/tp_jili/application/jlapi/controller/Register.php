<?php

namespace app\jlapi\controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Log;

class Register extends Controller
{
    public function _initialize()
    {

    }

    //创建用户
    public function userLogin()
    {
        $post = input('post.');
        if (empty($post['currency']) || !isset($post['platformId']) || !isset($post['userId']) || empty($post['userId']) || empty($post['requestTime'])) {
            $this->error('数据格式错误');
        }

        $sign = $post['sign'];
        unset($post['sign']);

        $user = Db::table('jh_user_register')->field('*')->where('openid', $post['userId'])->find();
        if ($user && !$user['status']) {
            $this->error('账号异常，请联系客服');
        }

        if (time() - $post['requestTime'] > 30) {
            $this->error('请求超时');
        }

        if ($user && $user['currency'] != $post['currency']) {
            $this->error('账号币种错误');
        }

        $merchant = Db::table('jh_merchant')->field('*')->where('merchant_id', $post['platformId'])->find();
        if (!$merchant) {
            $this->error('请勿非法尝试');
        }

        $check_sign = $this->http_sign($post, $merchant['platform_key']);
        if ($sign != $check_sign) {
            $this->error('签名认证错误');
        }

        if (!$user) {
            $sys = Db::table('jh_sysconfig')->field('*')->where('key', 'REGISTER_SWITCH')->find();
            if (empty($sys['val'])) {
                $this->error('注册通道关闭');
            }

            $time = date("Y-m-d H:i:s", time());

            if (is_numeric($post['userId']) && strlen($post['userId']) == 8) {
                $uid = $post['userId'];
                $check = Db::table('jh_user_register')->field('*')->where('uid', $uid)->find();
                if ($check) {
                    $uid = $this->getuid();
                }
            } else {
                $uid = $this->getuid();
            }

            if (!$uid) {
                $this->error('网络超时，请稍后重试');
            }

            $gold = $post['platformId'] == 'ufiabwbjwj' ? 5000000 : 0;
            Db::table('jh_user_register')->insert([
                'uid' => $uid,
                'gold' => $gold,
                'bank' => 0,
                'last_time' => $time,
                'last_ip' => '0.0.0.0',
                'headimgurl' => rand(1, 97),
                'pictureframe' => 1,
                'nickname' => '游客' . $uid,
                'sex' => 1,
                'type' => 0,
                'online' => 2,
                'status' => 1,
                'created' => $time,
                'agent' => 2,
                'password' => '',
                'telephone' => '',
                'possition' => 0,
                'power' => 0,
                'openid' => $post['userId'],
                'equipmentcard' => $post['equipmentcard'] ?? '',
                'platform' => $post['platformId'],
                'currency' => $post['currency'],
            ]);

            Db::table('jh_user_superior')->insert([
                'uid' => $uid,
                'created' => $time,
                'touch' => $time,
                'anchor' => 0,
                'all_bet' => 0,
                'touch_bet' => 0,
                'all_win' => 0,
                'touch_win' => 0,
                'superior' => $merchant['username'],
                'last_superior' => $merchant['superior'],
                'admin_user' => $merchant['terrace']]);
        } else {
            $uid = $user['uid'];
        }

        $user = $user ? $user : Db::table('jh_user_register')->where('uid', $uid)->find();
        $_res = Db::table('jh_user_token')->field('token')->where('uid', $user['uid'])->find();
        if ($_res) {
            Db::table('jh_user_token')->where('uid', $user['uid'])->delete();
        }

        $token = chr(rand(97, 122)) . $user['uid'];
        $rand = rand(3, 6);
        for ($i = 0; $i < $rand; $i++) {
            $token = $this->create_token($token);
        }

        $end_time = time() + 86400;
        Db::table('jh_user_token')->insert(['uid' => $user['uid'], 'http_endtime' => $end_time, 'http_token' => $token]);
        $balance = $user['gold'] + $user['bank'];
        $this->success('成功', [
            "balance" => $balance,
            "platformId" => $user['platform'],
            "userId" => $uid,
            "userToken" => $token
        ]);
    }

    //获取币种及图标
    public function currency()
    {
        $list = Db::table('jh_currency_list')->field('*')->select();
        $data = [];
        foreach ($list as $k => $v) {
            $data[] = [
                'currency' => $v['currency'],
                'logo' => $v['logo'],
            ];
        }

        $this->success('成功', $data);
    }


    //注册uid
    private function getuid()
    {
        for ($i = 0; $i < 500; $i++) {
            $uid = Db::table('jh_jili_uids')->field('*')->find();
            $info = Db::table('jh_user_register')->field('uid')->where('uid', $uid['uid'])->find();
            Db::table('jh_jili_uids')->where('uid', $uid['uid'])->delete();
            if (!$info) {
                return $uid['uid'];
            }
        }

        return 0;
    }

    //加密令牌
    private function create_token($prefix)
    {
        $chars = md5(uniqid(mt_rand(), true));

        $uuid = substr($chars, 0, 8)
            . substr($chars, 8, 4)
            . substr($chars, 12, 4)
            . substr($chars, 16, 4)
            . substr($chars, 20, 12);
        return $prefix . $uuid;
    }
}


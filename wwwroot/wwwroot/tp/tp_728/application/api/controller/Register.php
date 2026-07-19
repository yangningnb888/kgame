<?php

namespace app\api\controller;

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
        if (!isset($post['platformId']) || empty($post['userId']) || empty($post['gtype']) || !isset($post['balance']) || !isset($post['sign']) || !isset($post['gtype']) || empty($post['requestTime'])) {
            $this->error('数据格式错误');
        }

        $sign = $post['sign'];
        unset($post['sign']);

        $user = Db::table('jh_user')->field('*')->where('openid', $post['userId'])->find();
        if ($user && !$user['status']) {
            $this->error('账号异常，请联系客服');
        }

        if (time() - $post['requestTime'] > 60) {
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

        $gtype = $post['gtype'] % 100;
        $gamestatus = Db::table('jh_gamestatus')->where('gtype', $gtype)->find();
        if ($gamestatus['status'] != 1) {
            $this->error('游戏关闭，请稍后重试');
        }

        $control = 0;
        $flagget = 0;
        $curget = 0;

        if (isset($post['control']) && isset($post['flagget']) && isset($post['curget'])) {
            $control = $post['control'];
            $flagget = $post['flagget'];
            $curget = $post['curget'];
        }

        if (!$user) {
            $time = date("Y-m-d H:i:s", time());
            if (is_numeric($post['userId']) && strlen($post['userId']) == 8) {
                $uid = $post['userId'];
                $check = Db::table('jh_user')->field('*')->where('uid', $uid)->find();
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
            Db::table('jh_user')->insert([
                'uid' => $uid,
                'gold' => $gold,
                'bank' => 0,
                'last_time' => $time,
                'last_ip' => '0.0.0.0',
                'headimgurl' => rand(1, 97),
                'nickname' => '游客' . $uid,
                'sex' => 1,
                'type' => 0,
                'online' => 2,
                'status' => 1,
                'created' => $time,
                'agent' => 2,
                'display' => 1,
                'openid' => $post['userId'],
                'equipmentcard' => $post['equipmentcard'] ?? '',
                'platform' => $post['platformId'],
            ]);

            Db::table('jh_user_superior')->insert([
                'uid' => $uid,
                'createtime' => $time,
                'flagget' => $flagget,
                'curget' => $curget,
                'control' => $control,
                'usecard' => 0,
            ]);
        } else {
            $uid = $user['uid'];
            if (isset($post['control'])) {
                Db::table('jh_user_superior')->where('uid', $user['uid'])->update([
                    'flagget' => $flagget,
                    'curget' => $curget,
                    'control' => $control,
                ]);
            }
        }

        $user = Db::table('jh_user')->field('*')->where('openid', $post['userId'])->find();
        Db::table('jh_user')->where('uid', $user['uid'])->setInc('gold', $post['balance']);
        $end = Db::table('jh_user')->field('*')->where('openid', $post['userId'])->find();
        $time = date('Y-m-d H:i:s', time());
        Db::table('jh_user_profit')->insert(
            [
                'uid' => $user['uid'],
                'formuid' => $user['platform'],
                'type' => 2,
                'currency' => 1,
                'num' => $user['gold'],
                'created' => $time,
                'beforegold' => $user['gold'],
                'endgold' => $end['gold'],
                'beforebank' => $user['bank'],
                'endbank' => $end['bank'],
                'status' => 1,
            ]);

        $user = $user ? $user : Db::table('jh_user')->where('uid', $uid)->find();
        $_res = Db::table('jh_user_online')->field('token')->where('uid', $user['uid'])->find();
        if ($_res) {
            Db::table('jh_user_online')->where('uid', $user['uid'])->delete();
        }

        $token = '';
        ksort($post);
        foreach ($post as $k => $v) {
            $token .= $k . '=' . $v . '&';
        }

        $url = substr($token, 0, -1);
        $token .= 'key=password';
        $token = md5($token);
        $end_time = time() + 300;
        Db::table('jh_user_online')->insert(['uid' => $user['uid'], 'create_time' => $end_time, 'token' => $token]);
        $balance = $user['gold'] + $user['bank'];
        $url = $gamestatus['game_back_url'] . '/?' . $url;

        $this->success('成功', [
            "balance" => $balance,
            "platformId" => $user['platform'],
            "userId" => $uid,
            "url" => $url
        ]);
    }

    //注册uid
    private function getuid()
    {
        for ($i = 0; $i < 500; $i++) {
            $uid = Db::table('jh_728_uids')->field('*')->find();
            $info = Db::table('jh_user')->field('uid')->where('uid', $uid['uid'])->find();
            Db::table('jh_728_uids')->where('uid', $uid['uid'])->delete();
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


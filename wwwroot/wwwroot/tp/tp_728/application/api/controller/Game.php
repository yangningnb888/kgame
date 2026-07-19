<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Log;
use think\Request;

class Game extends Controller
{

    public function _initialize()
    {

    }

    //获取游戏启动链接
    public function gameUrl()
    {
        $post = input('post.');
        if (empty($post['gtype']) || !isset($post['language']) || !isset($post['platformId']) || !isset($post['userId'])) {
            $this->error('数据格式错误');
        }

        $user = Db::table('jh_user_register')->field('*')->where('uid', $post['userId'])->find();
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

    //获取游戏记录
    public function gameRecord()
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

        $user_possition = Db::table('jh_user_possition')->field('*')->where('uid', $user['uid'])->find();
        if ($user_possition['gtype'] > 0) {
            $this->error('游戏中，无法转出游戏记录');
        }

        $game_record = Db::table('jh_game_record')->field('*')->where('is_out', 0)->where('uid', $user['uid'])->select();
        $data = [];
        $all_ids = [];
        foreach ($game_record as $k => $v) {
            $data[] = [
                'gtype' => $v['gtype'],
                'level' => 1,
                'created' => $v['endtime'],
                'win' => $v['win'],
                'endgold' => $v['endgold'],
                'begingold' => $v['begingold'],
                'begintime' => $v['begintime'],
                'endtime' => $v['endtime'],
            ];
            $all_ids[] = $v['id'];
        }

        if (!empty($all_ids)) {
            Db::table('jh_game_record')->whereIn('id', $all_ids)->update(['is_out' => 1]);
        }
        $this->success('成功', $data);
    }

    //获取游戏列表
    public function gameList()
    {
        Log::error(1);
        $game_status = Db::table('jh_gamestatus')->field('*')->select();
        $data = [];
        foreach ($game_status as $k => $v) {
            $data[] = [
                'gtype' => $v['gtype'],
                'status' => $v['status'],
                'game_back_url' => $v['game_back_url'],
                'game_icon_url' => $v['game_icon_url'],
            ];
        }

        $this->success('成功', $data);
    }

    //设置场控值
    public function control()
    {
        if (!isset($post['platformId']) || !isset($post['userId']) || !isset($post['flagget']) || !isset($post['control']) || !isset($post['requestTime']) || !isset($post['sign'])) {
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
        foreach ($post as $k => $v) {
            $password .= $k . '=' . $v . '&';
        }

        $password .= 'apiKey=' .$merchant['platform_key'];
        $dis_sign = md5($password);

        if ($sign != $dis_sign) {
            $this->error('校验结果不一致');
        }

        $user = Db::table('jh_user_register')->field('*')->where('openid', $post['sid'])->find();
        if (!$user || $user['platform'] != $post['platformId']) {
            $this->error('用户数据错误');
        }

        Db::table('jh_user_superior')->where('uid', $user['uid'])->update(['flagget' => $post['flagget'], 'curget' => 0, 'control' => $post['control']]);
        Log::error('control_update::::' . json_encode($post));
        $this->success('成功', [
            "userId" => $post['userId'],  //string
            "flagget" => $post['flagget'],  //string
            "control" => $user['control'],  //string
        ]);
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

        $user = Db::table('jh_user')->field('*')->where('openid', $post['userId'])->find();
        if ($user) {
            Db::table('jh_banuser')->insert(
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

        $data = Db::table('jh_game_config')->field('gtype,level,profit')->select();
        foreach ($data as $key => $value) {
            $data[$key]['gtype'] += 100;
            $data[$key]['maxprofit'] = 0;
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

        Db::table('jh_game_config')->where('gtype', $post['gtype'])->where('level', $post['level'])->update([
            'profit' => $post['profit'],
        ]);

        $this->success('成功', ["profit" => $post['profit']]);
    }
}


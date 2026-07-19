<?php

namespace app\control\controller;

use think\Controller;
use think\Db;

/**
 * 商品分类管理
 * Class Cate
 * @package app\data\controller\shop
 */
class Jxlw extends Controller
{
    public function _initialize()
    {
        if (!session('id') || !session('name')) {
            $this->error('请您先进行登录，谢谢', url(''));//判断session并返回登录界面
        }
    }

    public function index()
    {
        $datajson = array(
            'status' => 1,
            'msg' => "",
            'data' => [
                'userlist' => [],
                'maplist' => []
            ]
        );
        $list = Db::name('jh_user')->field('level,jh_user.uid,nickname,gold,bank')->join('jh_user_possition', 'jh_user.uid = jh_user_possition.uid')->where('online', 1)->where('gtype', 22)->select();
        $data = [];
        foreach ($list as $key => $value) {
            $flag = Db::name('jh_user_superior')->field('flagget,curget,control')->where('uid', $value['uid'])->find();
            $used = Db::name('jh_arcade_bet')->field('used')->where('uid', $value['uid'])->find();

            if (isset($flag['flagget']) && isset($flag['curget'])) {
                $_flag = abs($flag['flagget'] - $flag['curget']) * $flag['control'];
            } else {
                $_flag = 0;
            }

            $data[] = [
                'uid' => $value['uid'],
                'nickname' => $value['nickname'],
                'level' => $value['level'],
                'gold' => $value['gold'],
                'bank' => $value['bank'],
                'flag' => $_flag,
                'used' => $used['used'] ?? 0
            ];
        }

        $datajson['data']['userlist'] = $data;

        $_arr = [
            1 => '荔枝(50 200 2000)',
            2 => '橙子(20 50 300)',
            3 => '芒果(15 25 250)',
            4 => '西瓜(10 20 200)',
            5 => '菠萝(5 15 75)',
            6 => '苹果(8 20 150)',
            7 => '樱桃(6 20 100)',
            8 => '香蕉(6 30 80)',
            9 => '铃铛(8 35 85)',
            10 => '葡萄(5 40 90)',
            11 => 'BAR(100 900 6000)',
            12 => '777(1000 3000 5000)',
            13 => '钻石(1~5 6~10 11~20)',
            14 => '宝箱(奖池)',
        ];
        $_ret = Db::table('jh_control_maps')->field('id,uid,level,value')->where('gtype', 22)->select();
        $data = [];
        foreach ($_ret as $key => $value) {
            $_value = json_decode($value['value'], true);
            $data[$key] = [
                'id' => $value['id'],
                'uid' => $value['uid'],
                'level' => $value['level'],
                'type' => $_arr[$_value['logo']],
                'num' => $_value['num'],
                'jackpot' => $_value['jackpot'] ?? 0,
            ];
        }
        $datajson['data']['maplist'] = $data;

        return json($datajson);
    }

    public function addresult()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!is_numeric($post['uid']) || !is_numeric($post['level'])) {
            return json($datajson);
        }

        $_value = json_encode([
            'logo' => $post['type'] > 100 ? 11 : $post['type'],
            'num' => $post['num'],
            'jackpot' => empty($post['jackpot']) ? 0 : $post['jackpot'],
        ]);

        Db::table('jh_control_maps')->insert([
            'uid' => $post['uid'],
            'gtype' => 22,
            'level' => $post['level'],
            'value' => $_value,
        ]);

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }

    public function delresult()
    {
        $post = input('post.');
        Db::table('jh_control_maps')->where('id', $post['id'])->delete();
        return json(array(
            'status' => 1,
            'msg' => "",
            'data' => $post
        ));
    }
}
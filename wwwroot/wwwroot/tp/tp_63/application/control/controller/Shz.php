<?php

namespace app\control\controller;

use think\Controller;
use think\Db;

/**
 * 商品分类管理
 * Class Cate
 * @package app\data\controller\shop
 */
class Shz extends Controller
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
        $list = Db::name('jh_user')->field('level,jh_user.uid,nickname,gold,bank')->join('jh_user_possition', 'jh_user.uid = jh_user_possition.uid')->where('online', 1)->where('gtype', 23)->select();
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
            1 => '水浒传(0 0 2000)',
            2 => '忠义堂(50 200 1000)',
            3 => '替天行道(20 80 400)',
            4 => '宋江(15 40 200)',
            5 => '林冲(10 30 160)',
            6 => '鲁智深(7 20 100)',
            7 => '大刀(5 15 60)',
            8 => '枪(3 10 40)',
            9 => '斧头(2 5 20)',
            10 => '全屏英雄(50)',
            11 => '全屏武器(15)',
        ];
        $_ret = Db::table('jh_control_maps')->field('id,uid,level,value')->where('gtype', 23)->select();
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
            'gtype' => 23,
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
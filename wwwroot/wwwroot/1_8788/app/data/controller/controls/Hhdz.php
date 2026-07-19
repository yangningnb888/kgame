<?php

namespace app\data\controller\controls;

use app\data\model\ShopGoodsCate;
use think\admin\Controller;
use think\admin\extend\DataExtend;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;

/**
 * 商品分类管理
 * Class Cate
 * @package app\data\controller\shop
 */
class Hhdz extends Controller
{

    public function initialize()
    {
        //判断是否存在登录session
        //如果username不存在，且Islogin不等于1，重新调回登录页面
        if (!Session::get('user')) {
            //跳转回登录页面
            $this->error('您还没有登录，请登录', 'admin/login/index');
        }
    }

    public function index()
    {
        $this->title = "红黑大战";
        $this->fetch();
    }

    public function reprofit()
    {
        $_ret = Db::table('jh_game_config')->field('profit')->where('gtype', 9)->where('level', 5)->find();
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $_ret['profit']
        ]);
    }

    public function upprofit()
    {
        $post = input('post.');
        if (isset($post['profit']) && is_numeric($post['profit'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['profit'] . ' WHERE gtype=9 AND level=5');
        }
        $_ret = Db::table('jh_game_config')->field('profit')->where('gtype', 9)->where('level', 5)->find();
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $_ret['profit']
        ]);
    }

    public function addresult()
    {
        $post = input('post.');
        for ($i = 0; $i < $post['time']; $i++) {
            Db::name('jh_duoren_control')->insert([
                'gtype' => 9,
                'level' => 5,
                'region' => $post['region']
            ]);
        }
        return json([
            'code' => 0,
            'msg' => '',
            'data' => []
        ]);
    }

    public function delresult()
    {
        $post = input('post.');
        Db::query('DELETE FROM jh_duoren_control where id=' . $post['id']);
        return json([
            'code' => 0,
            'msg' => '',
            'data' => []
        ]);
    }

    public function resultlist()
    {
        $index = [1 => '黑方', 2 => '红方'];
        $ret = Db::query('SELECT id,region FROM jh_duoren_control where gtype=9 and level=5');
        foreach ($ret as $key => $value) {
            $ret[$key]['region'] = $index[$value['region']];
        }
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $ret
        ]);
    }

    public function playerlist()
    {
        $index = [1 => 'hei', 2 => 'hong', 3=> 'luck'];
        $ret = Db::query('SELECT playerbets,endtime FROM jh_player_bet where gtype=9 and level=5');
        $all = [];
        foreach ($ret as $key => $value) {
            $all = $value;
        }
        $all['playerbets'] = json_decode($all['playerbets'], true);
        $_ret = Db::table('jh_game_config')->field('profit')->where('gtype', 9)->where('level', 5)->find();
        $data = [
            'bets' => [],
            'stage' => time() >= $all['endtime'] ? '结算中' : '未开奖',
            'profit' => $_ret['profit']
        ];

        foreach ($all['playerbets'] as $key => $value) {
            $_ret = Db::table('jh_user')->field('nickname')->where('uid', $key)->find();
            $_data = [
                'uid' => $key,
                'nickname' => $_ret['nickname'],
            ];

            foreach ($index as $key1 => $value1) {
                if ($key1) {
                    $_data[$value1] = $value[$key1] ?? 0;
                }
            }

            $data['bets'][] = $_data;
        }

        return json([
            'code' => 0,
            'msg' => '',
            'data' => $data
        ]);
    }
}
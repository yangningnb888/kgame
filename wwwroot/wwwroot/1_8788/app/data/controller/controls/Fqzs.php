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
class Fqzs extends Controller
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
        $this->title = "飞禽走兽";
        $this->fetch();
    }

    public function reprofit()
    {
        $_ret = Db::table('jh_game_config')->field('profit')->where('gtype', 1)->where('level', 5)->find();
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
            Db::query('UPDATE jh_game_config SET profit=' . $post['profit'] . ' WHERE gtype=1 AND level=5');
        }
        $_ret = Db::table('jh_game_config')->field('profit')->where('gtype', 1)->where('level', 5)->find();
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
                'gtype' => 1,
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
        $index = [11 => '鸡*6', 12 => '鸵鸟*8', 13 => '猫头鹰*8', 14 => '老鹰*12', 21 => '兔子*6', 22 => '猴子*8', 23 => '熊猫*8', 24 => '狮子*12', 31 => '银鲨*25', 32 => '金鲨*51'];
        $ret = Db::query('SELECT id,region FROM jh_duoren_control where gtype=1 and level=5');
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
        $index = [11 => 'ji', 12 => 'tn', 13 => 'mty', 14 => 'ly', 21 => 'tz', 22 => 'hz', 23 => 'xm', 24 => 'sz', 3 => 'sy', 1=> 'fq', 2 => 'zs'];
        $ret = Db::query('SELECT playerbets,endtime FROM jh_player_bet where gtype=1 and level=5');
        $all = [];
        foreach ($ret as $key => $value) {
            $all = $value;
        }
        $all['playerbets'] = json_decode($all['playerbets'], true);
        $_ret = Db::table('jh_game_config')->field('profit')->where('gtype', 1)->where('level', 5)->find();
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
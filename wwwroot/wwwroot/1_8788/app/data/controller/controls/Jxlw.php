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
class Jxlw extends Controller
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
        $this->title = "九线拉王";
        $this->fetch();
    }

    public function addresult()
    {
        $post = input('post.');

        if (!is_numeric($post['uid']) || !is_numeric($post['level'])) {
            $this->error('参数有误');
            return false;
        }

        $_value = json_encode([
            'logo' => $post['type'] > 100 ? 11 : $post['type'],
            'num' => $post['num'],
            'jackpot' => empty($post['jackpot']) ? 0 : $post['jackpot'],
        ]);

        $insert = [
            'uid' => $post['uid'],
            'gtype' => 22,
            'level' => $post['level'],
            'value' => $_value,
        ];
        Db::table('jh_control_maps')->insert($insert);
        return json([
            'code' => 0,
            'msg' => '',
            'data' => []
        ]);
    }

    public function upcontrol()
    {
        $post = input('post.');
        if (isset($post['mingdan']) && $post['mingdan'] >= -1 && $post['mingdan'] <= 1 && isset($post['flagget']) && is_numeric($post['flagget'])) {
            Db::query('UPDATE jh_user_superior SET control=' . $post['mingdan'] . ',curget=0,flagget=' . $post['flagget'] . ' WHERE uid=' . $post['uid']);
            $this->error('修改场控成功');
        } else {
            $this->error('参数错误');
        }
    }

    public function upjackpot()
    {
        $post = input('post.');
        if (isset($post['jackpot']) && is_numeric($post['jackpot'])) {
            Db::query('UPDATE jh_game_jackpot SET jackpot=' . $post['jackpot'] . ' WHERE gtype=22 AND level=' . $post['level']);
            $this->error('修改奖池成功');
        } else {
            $this->error('参数错误');
        }
    }

    public function getprofit()
    {
        $_ret = Db::table('jh_game_config')->field('level,profit')->where('gtype', 22)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $_ret
        ]);
    }

    public function upprofit()
    {
        $post = input('post.');
        if (isset($post['xsc']) && is_numeric($post['xsc'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['xsc'] . ' WHERE gtype=22 AND level=2');
        }
        if (isset($post['cjc']) && is_numeric($post['cjc'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['cjc'] . ' WHERE gtype=22 AND level=3');
        }
        if (isset($post['zjc']) && is_numeric($post['zjc'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['zjc'] . ' WHERE gtype=22 AND level=4');
        }
        if (isset($post['gjc']) && is_numeric($post['gjc'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['gjc'] . ' WHERE gtype=22 AND level=5');
        }
        $_ret = Db::table('jh_game_config')->field('level,profit')->where('gtype', 22)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $_ret
        ]);
    }

    public function getmaplist()
    {
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
        $levels = [1 => '体验场', 2 => '新手场', 3 => '初级场', 4 => '中级场', 5 => '高级场'];
        foreach ($_ret as $key => $value) {
            $_value = json_decode($value['value'], true);
            $data[$key] = [
                'id' => $value['id'],
                'uid' => $value['uid'],
                'level' => $levels[$value['level']],
                'type' => $_arr[$_value['logo']],
                'num' => $_value['num'],
                'jackpot' => $_value['jackpot'] ?? 0,
            ];
        }

        return json([
            'code' => 0,
            'msg' => '',
            'count' => count($data),
            'data' => $data
        ]);
    }

    public function getplayerbet()
    {
        $list = Db::query('SELECT `level`,jh_user.uid,nickname,gold,bank FROM jh_user INNER JOIN jh_user_possition ON jh_user.uid = jh_user_possition.uid WHERE online=1 and gtype=22');
        $data = [];
        $levels = [1 => '体验场', 2 => '新手场', 3 => '初级场', 4 => '中级场', 5 => '高级场'];
        foreach ($list as $key => $value) {
            $flag = Db::table('jh_user_superior')->field('flagget,curget,control')->where('uid', $value['uid'])->find();
            $used = Db::table('jh_arcade_bet')->field('used')->where('uid', $value['uid'])->find();

            if (isset($flag['flagget']) && isset($flag['curget'])) {
                $_flag = abs($flag['flagget'] - $flag['curget']) * $flag['control'];
            } else {
                $_flag = 0;
            }

            $data[] = [
                'uid' => $value['uid'],
                'nickname' => $value['nickname'],
                'level' => $levels[$value['level']],
                'gold' => $value['gold'] + $value['bank'],
                'flag' => $_flag,
                'used' => $used['used'] ?? 0
            ];
        }
        $_ret = Db::table('jh_game_config')->field('level,profit')->where('gtype', 22)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'count' => count($data),
            'data' => [
                'list' => $data,
                'profits' => $_ret
            ]
        ]);
    }

    public function delresult()
    {
        $post = input('post.');
        Db::query('DELETE FROM jh_control_maps where id=' . $post['id']);
        return json([
            'code' => 0,
            'msg' => '',
            'data' => []
        ]);
    }
}
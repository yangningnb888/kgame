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
class Ldyx extends Controller
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
        $this->title = "铃铛游戏";
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
            'num' => 1,
            'jackpot' => empty($post['jackpot']) ? 0 : $post['jackpot'],
        ]);

        $insert = [
            'uid' => $post['uid'],
            'gtype' => 25,
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
            Db::query('UPDATE jh_game_jackpot SET jackpot=' . $post['jackpot'] . ' WHERE gtype=25 AND level=' . $post['level']);
            $this->error('修改奖池成功');
        } else {
            $this->error('参数错误');
        }
    }

    public function getprofit()
    {
        $_ret = Db::table('jh_game_config')->field('level,profit')->where('gtype', 25)->select();
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
            Db::query('UPDATE jh_game_config SET profit=' . $post['xsc'] . ' WHERE gtype=25 AND level=2');
        }
        if (isset($post['cjc']) && is_numeric($post['cjc'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['cjc'] . ' WHERE gtype=25 AND level=3');
        }
        if (isset($post['zjc']) && is_numeric($post['zjc'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['zjc'] . ' WHERE gtype=25 AND level=4');
        }
        if (isset($post['gjc']) && is_numeric($post['gjc'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['gjc'] . ' WHERE gtype=25 AND level=5');
        }
        $_ret = Db::table('jh_game_config')->field('level,profit')->where('gtype', 25)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $_ret
        ]);
    }

    public function getmaplist()
    {
        $_arr = [
            1 => 'BAR(120)',
            2 => 'BARx3(50)',
            3 => '77(40)',
            4 => '77x3(3)',
            5 => '星星(30)',
            6 => '星星x3(3)',
            7 => '西瓜(20)',
            8 => '西瓜x3(3)',
            9 => '铃铛(20)',
            10 => '铃铛x3(3)',
            11 => '柠檬(15)',
            12 => '柠檬x3(3)',
            13 => '橘子(10)',
            14 => '橘子x3(3)',
            15 => '苹果(5)',
            16 => '橘子x3(3)',
            17 => '蓝色Lucky(0)',
            18 => '红色Lucky(0)',
        ];
        $_ret = Db::table('jh_control_maps')->field('id,uid,level,value')->where('gtype', 25)->select();
        $data = [];
        $levels = [1 => '体验场', 2 => '新手场', 3 => '初级场', 4 => '中级场', 5 => '高级场'];
        foreach ($_ret as $key => $value) {
            $_value = json_decode($value['value'], true);
            $data[$key] = [
                'id' => $value['id'],
                'uid' => $value['uid'],
                'level' => $levels[$value['level']],
                'type' => $_arr[$_value['logo']],
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
        $_arr = [1 => 'BAR', 3 => '77', 5 => '星星', 7 => '西瓜', 9 => '铃铛', 11 => '柠檬', 13 => '橘子', 15 => '苹果'];
        $list = Db::query('SELECT `level`,jh_user.uid,nickname,gold,bank FROM jh_user INNER JOIN jh_user_possition ON jh_user.uid = jh_user_possition.uid WHERE online=1 and gtype=25');
        $data = [];
        $levels = [1 => '体验场', 2 => '新手场', 3 => '初级场', 4 => '中级场', 5 => '高级场'];
        foreach ($list as $key => $value) {
            $flag = Db::table('jh_user_superior')->field('flagget,curget,control')->where('uid', $value['uid'])->find();
            $used = Db::table('jh_arcade_bet')->field('used,bet')->where('uid', $value['uid'])->find();
            $info = '';
            if (is_string($used['bet'])) {
                $used['bet'] = json_decode($used['bet'], true);
                foreach ($used['bet'] as $key1 => $value1) {
                    if (isset($_arr[$key1])) {
                        $info .= $_arr[$key1] . ' ' . $value1 . '倍  ';
                    }
                }
            }

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
                'used' => $used['used'] ?? 0,
                'info' => $info
            ];
        }
        $_ret = Db::table('jh_game_config')->field('level,profit')->where('gtype', 25)->select();
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
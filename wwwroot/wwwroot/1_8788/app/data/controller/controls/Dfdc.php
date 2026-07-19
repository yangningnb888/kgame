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
class Dfdc extends Controller
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
        $this->title = "多福多财";
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
            'jackpot_type' => $post['type'] > 100 ? $post['type'] % 100 : 0,
        ]);
        $insert = [
            'uid' => $post['uid'],
            'gtype' => 26,
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
            Db::query('UPDATE jh_game_jackpot SET jackpot=' . $post['jackpot'] . ' WHERE gtype=26');
            $this->error('修改奖池成功');
        } else {
            $this->error('参数错误');
        }
    }

    public function getprofit()
    {
        $_ret = Db::table('jh_game_config')->field('level,profit')->where('gtype', 26)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $_ret
        ]);
    }

    public function upprofit()
    {
        $post = input('post.');
        if (isset($post['jlff']) && is_numeric($post['jlff'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['jlff'] . ' WHERE gtype=26 AND level=2');
        }
        if (isset($post['zsyh']) && is_numeric($post['zsyh'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['zsyh'] . ' WHERE gtype=26 AND level=3');
        }
        if (isset($post['xmcf']) && is_numeric($post['xmcf'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['xmcf'] . ' WHERE gtype=26 AND level=4');
        }
        if (isset($post['jgfc']) && is_numeric($post['jgfc'])) {
            Db::query('UPDATE jh_game_config SET profit=' . $post['jgfc'] . ' WHERE gtype=26 AND level=5');
        }
        $_ret = Db::table('jh_game_config')->field('level,profit')->where('gtype', 26)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $_ret
        ]);
    }

    public function getmaplist()
    {
        $_arr = [
            2 => [
                5 => '龙(100 200 1000)',
                6 => '船(20 100 500)',
                7 => '鱼(100 200 1000)',
                8 => '元宝(25 50 250)',
                9 => '10次免费(5 10 50)',
                10 => '铜钱(10 20 100)',
                11 => '奖池',
                12 => 'A(3 10 50)',
                13 => 'K(3 10 50)',
                14 => 'Q(3 10 50)',
                15 => 'J(3 10 50)',
                16 => '10(3 10 50)',
                17 => '9(3 10 50)',
            ],
            3 => [
                5 => '凤凰(68 128 680)',
                6 => '莲花(38 88 380)',
                7 => '宝石(28 68 280)',
                8 => '戒指(28 68 180)',
                9 => '10次免费(5 10 50)',
                10 => '花朵(8 28 128)',
                11 => '奖池',
                12 => 'A(5 8 15)',
                13 => 'K(5 8 15)',
                14 => 'Q(5 8 15)',
                15 => 'J(5 8 15)',
                16 => '10(5 8 15)',
                17 => '9(5 8 15)',
            ],
            4 => [
                5 => '熊猫(100 150 750)',
                6 => '玄武(50 100 450)',
                7 => '金蟾(40 80 300)',
                8 => '鱼(20 50 200)',
                9 => '10次免费(5 10 50)',
                10 => '鞭炮(10 25 138)',
                11 => '奖池',
                12 => 'A(5 10 30)',
                13 => 'K(5 10 30)',
                14 => 'Q(5 10 25)',
                15 => 'J(5 10 25)',
                16 => '10(5 10 20)',
                17 => '9(5 10 20)',
            ],
            5 => [
                5 => '麒麟(100 200 800)',
                6 => '船(50 100 400)',
                7 => '发财树(30 75 200)',
                8 => '元宝(25 50 150)',
                9 => '10次免费(5 10 50)',
                10 => '铜钱(15 25 100)',
                11 => '奖池',
                12 => 'A(5 10 20)',
                13 => 'K(5 10 20)',
                14 => 'Q(5 10 20)',
                15 => 'J(5 10 15)',
                16 => '10(5 10 15)',
                17 => '9(5 10 15)',
            ],
        ];
        $_ret = Db::table('jh_control_maps')->field('id,uid,level,value')->where('gtype', 26)->select();
        $data = [];
        $levels = [2 => '金龙发发', 3 => '钻石永恒', 4 => '熊猫财富', 5 => '击鼓发财'];
        foreach ($_ret as $key => $value) {
            $_value = json_decode($value['value'], true);
            $data[$key] = [
                'id' => $value['id'],
                'uid' => $value['uid'],
                'level' => $levels[$value['level']],
                'type' => $_arr[$value['level']][$_value['logo']],
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
        $list = Db::query('SELECT `level`,jh_user.uid,nickname,gold,bank FROM jh_user INNER JOIN jh_user_possition ON jh_user.uid = jh_user_possition.uid WHERE online=1 and gtype=26');
        $data = [];
        $levels = [2 => '金龙发发', 3 => '钻石永恒', 4 => '熊猫财富', 5 => '击鼓发财'];
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

        $_ret = Db::table('jh_game_config')->field('level,profit')->where('gtype', 26)->select();
        return json([
            'code' => 0,
            'msg' => '',
            'count' => count($data),
            'data' => ['list' => $data, 'profits' => $_ret]
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
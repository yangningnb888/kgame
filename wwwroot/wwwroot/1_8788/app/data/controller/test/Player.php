<?php

namespace app\data\controller\test;

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
class Player extends Controller
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
        $this->title = "玩家列表";
        $this->fetch();
    }

    public function test()
    {
        //$list = Db::query('select * from jh_user where uid=66512689');
        $all = Db::query('SELECT * FROM jh_user WHERE type=0 AND agent=2 AND `online`=1');
        $limit = Request::instance()->param('limit');
        $page = Request::instance()->param('page');
        $start = ($page - 1) * $limit;
        $data = Db::query('SELECT uid,nickname,gold,bank,rcard,last_time,last_ip,equipmentcard,created FROM jh_user WHERE type=0 AND agent=2 AND `online`=1 LIMIT ' . $start . ',' . $limit);
        $arr = [0 => '正常', 1 => '放', -1 => '杀'];
        foreach ($data as $key => $value) {
            $_superior = Db::table('jh_user_superior')->field('superior,control')->where('uid', $value['uid'])->find();
            $tel = Db::table('jh_register')->field('telephone')->where('uid', $value['uid'])->find();
            $_game_site = Db::table('jh_user_possition')->field('gtype,level')->where('uid=' . $value['uid'])->find();
            $_game = Db::table('jh_gamename')->field('name')->where('gtype', $_game_site['gtype'])->find();
            $data[$key]['superior'] = $_superior['superior'] ?? '无';
            $data[$key]['control'] = isset($_superior['control']) ? $arr[$_superior['control']] : '正常';
            $data[$key]['income'] = Db::table('jh_user_profit')->where('type=1 AND currency=1 AND uid=' . $value['uid'])->sum('num');
            $data[$key]['expenditure'] = Db::table('jh_user_profit')->where('type=2 AND currency=1 AND uid=' . $value['uid'])->sum('num');
            $data[$key]['tel'] = $tel['telephone'] ?? '';
            if ($_game) {
                $_other = [
                    0 => [1 => '体验场', 2 => '新手场', 3 => '初级场', 4 => '中级场', 5 => '高级场'],
                    26 => [2 => '金龙发发', 3 => '钻石永恒', 4 => '财富熊猫', 5 => '击鼓发财'],
                    27 => [1 => '双响炮1', 2 => '双响炮2', 3 => '双响炮3', 4 => '捞金鱼1', 5 => '捞金鱼2', 6 => '捞金鱼3', 7 => '麻将1', 8 => '麻将2', 9 => '麻将3']
                ];
                $data[$key]['status'] = $_game['name'] . ' ';
                if (isset($_other[$_game_site['gtype']])) {
                    $data[$key]['status'] .= $_other[$_game_site['gtype']][$_game_site['level']];
                } elseif (isset($_other[0][$_game_site['level']])) {
                    $data[$key]['status'] .= $_other[0][$_game_site['level']];
                }
            } else {
                $data[$key]['status'] = '大厅';
            }
        }

        $data = [
            'code' => 0,
            'msg' => '',
            'count' => count($all),
            'data' => $data,
        ];
        return json($data);
    }

    public function select()
    {
        $status = input('status');
        $uid = input('uid');
        $superior = input('superior');
        $equipmentcard = input('equipmentcard');
        $last_ip = input('last_ip');
        $limit = Request::instance()->param('limit');
        $page = Request::instance()->param('page');
        $start = ($page - 1) * $limit;
        $arr = [0 => '正常', 1 => '放', -1 => '杀'];
        if (!empty($uid) || !empty($superior) || !empty($equipmentcard) || !empty($last_ip)) {
            $str = 'SELECT jh_user.uid,nickname,gold,bank,rcard,last_time,last_ip,equipmentcard,created FROM jh_user';
            if (!empty($superior)) {
                $str .= ' INNER JOIN jh_user_superior ON jh_user_superior.uid=jh_user.uid WHERE type=0 AND superior=' . $superior;
            } else {
                $str .= ' WHERE type=0';
            }

            if (!empty($uid)) {
                $str .= ' AND uid=' . $uid;
            }

            if (!empty($equipmentcard)) {
                $str .= ' AND equipmentcard="' . $equipmentcard . '"';
            }

            if (!empty($last_ip)) {
                $str .= ' AND last_ip="' . $last_ip . '"';
            }

        } else {
            if ($status == 3) {
                $str = 'SELECT uid,nickname,gold,bank,rcard,last_time,last_ip,equipmentcard,created FROM jh_user WHERE type=0 AND agent=2 AND status=0';
            } elseif ($status == 4) {
                $str = 'SELECT uid,nickname,gold,bank,rcard,last_time,last_ip,equipmentcard,created FROM jh_user WHERE type=0 AND agent=2  ORDER BY created DESC';
            } else {
                $str = 'SELECT uid,nickname,gold,bank,rcard,last_time,last_ip,equipmentcard,created FROM jh_user WHERE type=0 AND agent=2 AND online=' . $status;
            }
        }

        $all = Db::query($str);
        $str .= ' LIMIT ' . $start . ',' . $limit;
        $data = Db::query($str);
        foreach ($data as $key => $value) {
            $_superior = Db::table('jh_user_superior')->field('superior,control')->where('uid', $value['uid'])->find();
            $tel = Db::table('jh_register')->field('telephone')->where('uid', $value['uid'])->find();
            $_game_site = Db::table('jh_user_possition')->field('gtype,level')->where('uid=' . $value['uid'])->find();
            $_game = Db::table('jh_gamename')->field('name')->where('gtype', $_game_site['gtype'])->find();
            $data[$key]['superior'] = $_superior['superior'] ?? 0;
            $data[$key]['control'] = isset($_superior['control']) ? $arr[$_superior['control']] : '正常';
            $data[$key]['income'] = Db::table('jh_user_profit')->where('type=1 AND currency=1 AND uid=' . $value['uid'])->sum('num');
            $data[$key]['expenditure'] = Db::table('jh_user_profit')->where('type=2 AND currency=1 AND uid=' . $value['uid'])->sum('num');
            $data[$key]['tel'] = $tel['telephone'] ?? '';
            if ($_game) {
                $_other = [
                    0 => [1 => '体验场', 2 => '新手场', 3 => '初级场', 4 => '中级场', 5 => '高级场'],
                    26 => [2 => '金龙发发', 3 => '钻石永恒', 4 => '财富熊猫', 5 => '击鼓发财'],
                    27 => [1 => '双响炮1', 2 => '双响炮2', 3 => '双响炮3', 4 => '捞金鱼1', 5 => '捞金鱼2', 6 => '捞金鱼3', 7 => '麻将1', 8 => '麻将2', 9 => '麻将3']
                ];
                $data[$key]['status'] = $_game['name'] . ' ';
                if (isset($_other[$_game_site['gtype']])) {
                    $data[$key]['status'] .= $_other[$_game_site['gtype']][$_game_site['level']];
                } elseif (isset($_other[0][$_game_site['level']])) {
                    $data[$key]['status'] .= $_other[0][$_game_site['level']];
                }
            } else {
                $data[$key]['status'] = '大厅';
            }
        }

        $data = [
            'code' => 0,
            'msg' => '',
            'count' => count($all),
            'data' => $data,
        ];
        return json($data);
    }

    public function controlupdate()
    {
        $post = input('post.');
        if (isset($post['control']) && $post['control'] >= -1 && $post['control'] <= 1 && isset($post['flag']) && is_numeric($post['flag']) && isset($post['uid'])) {
            Db::query('UPDATE jh_user_superior SET control=' . $post['control'] . ',curget=0,flagget=' . $post['flag'] . ' WHERE uid=' . $post['uid']);
            $this->error('修改场控成功');
        } else {
            $this->error('参数错误');
        }
    }
}
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
class Agent extends Controller
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
        $this->title = "代理列表";
        $this->fetch();
    }

    public function agentlist()
    {
        //$list = Db::query('select * from jh_user where uid=66512689');
        $all = Db::query('SELECT * FROM jh_user WHERE type=0 AND agent=1 AND online=1');
        $limit = Request::instance()->param('limit');
        $page = Request::instance()->param('page');
        $start = ($page - 1) * $limit;
        $data = Db::query('SELECT uid,nickname,gold,bank,rcard FROM jh_user WHERE type=0 AND online=1 AND agent=1 LIMIT ' . $start . ',' . $limit);
        $arr = [0 => '正常', 1 => '放', -1 => '杀'];
        foreach ($data as $key => $value) {
            $_superior = Db::query('SELECT superior,control FROM jh_user_superior WHERE uid=' . $value['uid']);
            $data[$key]['superior'] = $_superior['superior'] ?? '无';
            $data[$key]['control'] = isset($_superior['control']) ?$arr[$_superior['control']] : '正常';
            $data[$key]['income'] = Db::table('jh_user_profit')->where('type=1 AND currency=1 AND uid=' . $value['uid'])->sum('num');
            $data[$key]['expenditure'] = Db::table('jh_user_profit')->where('type=2 AND currency=1 AND uid=' . $value['uid'])->sum('num');
            $data[$key]['playernum'] = Db::table('jh_user_superior')->where('uid > 10000000 AND superior=' . $value['uid'])->count();
            $data[$key]['agentnum'] = Db::table('jh_user_superior')->where('uid < 10000000 AND superior=' . $value['uid'])->count();
        }

        $data = [
            'code' => 0,
            'msg' => '',
            'count' => count($all),
            'data' => $data
        ];
        return json($data);
    }

    public function select()
    {
        $status = input('status');
        $uid = input('uid');
        $limit = Request::instance()->param('limit');
        $page = Request::instance()->param('page');
        $start = ($page - 1) * $limit;
        $str = '';
        if (!empty($uid)) {
            $str = 'SELECT uid,nickname,gold,bank,rcard FROM jh_user WHERE type=0 AND agent=1 AND uid=' . $uid;
        } else {
            if ($status == 3) {
                $str = 'SELECT uid,nickname,gold,bank,rcard FROM jh_user WHERE type=0 AND agent=1 AND status=0';
            } else {
                $str = 'SELECT uid,nickname,gold,bank,rcard FROM jh_user WHERE type=0 AND agent=1 AND online=' . $status;
            }
        }

        $all = Db::query($str);
        $data = Db::query($str. ' LIMIT ' . $start . ',' . $limit);
        foreach ($data as $key => $value) {
            $_superior = Db::query('SELECT superior,control FROM jh_user_superior WHERE uid=' . $value['uid']);
            $data[$key]['superior'] = $_superior['superior'] ?? 0;
            $data[$key]['control'] = $_superior['control'] ?? 0;
            $data[$key]['income'] = Db::table('jh_user_profit')->where('type=1 AND currency=1 AND uid=' . $value['uid'])->sum('num');
            $data[$key]['expenditure'] = Db::table('jh_user_profit')->where('type=2 AND currency=1 AND uid=' . $value['uid'])->sum('num');
            $data[$key]['playernum'] = Db::table('jh_user_superior')->where('uid > 10000000 AND superior=' . $value['uid'])->count();
            $data[$key]['agentnum'] = Db::table('jh_user_superior')->where('uid < 10000000 AND superior=' . $value['uid'])->count();
        }

        $data = [
            'code' => 0,
            'msg' => '',
            'count' => count($all),
            'data' => $data,
        ];
        return json($data);
    }
}
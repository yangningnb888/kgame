<?php

namespace app\data\controller\game;

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
class Profit extends Controller
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
        $this->title = "收支明细";
        $this->fetch();
    }

    public function gamelist()
    {
        $uid = input('uid');
        $str = 'SELECT id,jh_game_record.gtype,uid,begintime,begingold,endtime,endgold,name FROM jh_game_record INNER JOIN jh_gamename ON jh_game_record.gtype=jh_gamename.gtype';
        if ($uid) {
            $str .= ' WHERE uid=' . $uid;
        }
        $str .= ' ORDER BY id DESC';
        $all = Db::query($str);

        $limit = Request::instance()->param('limit');
        $page = Request::instance()->param('page');
        $start = ($page - 1) * $limit;
        $str .= ' LIMIT ' . $start . ',' . $limit;
        $data = Db::query($str);
        return [
            'code' => 0,
            'msg' => '',
            'count' => count($all),
            'data' => $data
        ];
    }
}
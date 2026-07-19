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
class Robot extends Controller
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

    /**
     * 机器人管理
     * @auth true
     */
    public function index()
    {
        $this->title = "机器人管理";
        $this->fetch();
    }

    public function robot()
    {
        //$list = Db::query('select * from jh_user where uid=66512689');
        $all = Db::query('SELECT jh_game_config.gtype,`level`,max_pnum,min_gold,ai_base,ai_max,benginroom,profit,controls,`name` FROM jh_game_config inner join jh_gamename on jh_game_config.gtype=jh_gamename.gtype ORDER BY jh_game_config.gtype,`level`');
        $limit = Request::instance()->param('limit');
        $page = Request::instance()->param('page');
        $start = ($page - 1) * $limit;
        $data = Db::query('SELECT jh_game_config.gtype,`level`,max_pnum,min_gold,ai_base,ai_max,benginroom,profit,controls,`name` FROM jh_game_config inner join jh_gamename on jh_game_config.gtype=jh_gamename.gtype ORDER BY jh_game_config.gtype,`level` LIMIT ' . $start . ',' . $limit);

        return json([
            'code' => 0,
            'msg' => '',
            'count' => count($all),
            'data' => $data
        ]);
    }

    //修改单个参数
    public function singleupdata()
    {
        if (request()->isPost()) {    //是否收到POST传参
            $data = input('post.'); //接收所有的POST值
            $ret = Db::table('jh_game_config')->where($data['where'])->update([$data['field'] => $data['value']]);
            if ($ret) {
                return json(['code' => 0, 'msg' => '成功', 'data' => $ret]);
            } else {
                return view();
            }
        } else {
            return view();
        }
    }

    //修改单条数据
    public function dataupdate()
    {
        $post = input('post.');
        $where = ['gtype' => $post['gtype'], 'level' => $post['level']];
        $curdata = Db::table('jh_game_config')->where($where)->find();
        $curdata['controls'] = json_decode($curdata['controls'], true);
        $update = [];
        $controls = ['percent', 'maxbet', 'flag', 'maxprofit', 'minprofit'];
        foreach ($post as $key => $value) {
            if ($key == 'gtype' || $key == 'level') {
                continue;
            }

            if ($key != 'profit' && ($value < 0 || $value > 100) && is_numeric($value)) {
                $this->error('请输入正确参数');
                return;
            }

            if ($value && (is_numeric($value) || is_array($value))) {
                if (is_numeric($value) && isset($curdata[$key]) && $value != $curdata[$key]) {
                    $update[$key] = $value;
                } elseif (is_numeric($value) && in_array($key, $controls)) {
                    if (empty($update['controls'])) {
                        $update['controls'] = $curdata['controls'];
                    }

                    if ($value != $update['controls'][$key]) {
                        $update['controls'][$key] = $value;
                    }
                } elseif (is_array($value) && isset($curdata['controls']['doubles'])) {
                    $index = isset($curdata['controls']['doubles'][0]['betdouble']) ? 'betdouble' : 'double';
                    $arr = [];
                    foreach ($value as $key1 => $value1) {
                        if (!empty($value1[$index]) && !empty($value1['rand'])) {
                            $arr[] = $value1;
                        }
                    }

                    if (count($arr) == 3) {
                        $update['controls']['doubles'] = $arr;
                    }
                }

                if (!empty($update['controls']) && !is_string($update['controls'])) {
                    $update['controls'] = json_encode($update['controls']);
                }

                Db::table('jh_game_config')->where($where)->update($update);
            } elseif ($value) {
                $this->error('请输入正确参数!');
                return;
            }
        }

        return json([
            'code' => 0,
            'msg' => '',
            'data' => []
        ]);
    }
}
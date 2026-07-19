<?php

namespace app\back\controller;

use app\back\model\System_Log;
use think\Controller;
use think\Db;
use think\Log;

class Merchant extends Controller
{
    /*///////////////////////////////////////////////////
    * 商户管理
    * url：{baseurl}/back/merchant
    * */
    public function index()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['limit']) || !isset($post['page'])) {
            $this->error('缺少参数', [], 201);
        }

        $where = 'id>0';
        if (!empty($post['platform_id'])) {
            $where .= ' and merchant_id = "' . $post['platform_id'] . '"';
        }

        if (!empty($post['username'])) {
            $where .= ' and username like "%' . $post['username'] . '%"';
        }

        if (!empty($post['rtp'])) {
            $where .= ' and rtp = "' . $post['rtp'] . '"';
        }


        $user = session('login_user');
        if ($user['level'] == 2) {
            $where .= ' AND superior="' . $user['username'] . '"';
        } elseif ($user['level'] >= 3) {
            $where .= ' AND username = "' . $user['username'] . '"';
        } else {
            $where .= ' AND terrace = "' . $user['terrace'] . '"';
        }

        if (!empty($post['superior'])) {
            $where .= ' and superior = "' . $post['superior'] . '"';
        }

        $count = Db::table('jh_merchant')->where($where)->field('*')->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
        $list = Db::table('jh_merchant')->where($where)->field('*')->order('created', 'desc')->limit($limit)->select();
        $res = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $v['platform'] = isset($v['merchant_id']) ?? $v['platform'];
                $res[] = $v;
            }
        }

        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '查询用户管理',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', [
            'total_page' => $total_page,
            'total_count' => $count,
            'list' => $res
        ]);
    }

    /*///////////////////////////////////////////////////
    * 修改商户信息
    * url：{baseurl}/back/merchant/update
    * */
    public function update()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['id'])) {
            $this->error('缺少参数', [], 201);
        }

        $res = Db::table('jh_merchant')->field('*')->where('id', $post['id'])->find();
        if (empty($res)) {
            $this->error('商户不存在');
        }

        $update = [];
        if (!empty($post['back_url'])) {
            $update['back_url'] = $post['back_url'];
        }

        if (!empty($post['rtp'])) {
            $update['rtp'] = $post['rtp'];
        }

        if (!empty($post['user_url'])) {
            $update['user_url'] = $post['user_url'];
        }

        if (!empty($post['merchant_name'])) {
            $update['merchant_name'] = $post['merchant_name'];
        }

        if (!empty($post['superior'])) {
            $update['superior'] = $post['superior'];
        }

        if (isset($post['is_show'])) {
            $update['is_show'] = $post['is_show'] ? 1 : 0;
        }

        if (isset($post['platform_rebate']) && is_numeric($post['platform_rebate'])) {
            $update['platform_rebate'] = $post['platform_rebate'];
        }

        if (isset($post['agent_rebate']) && is_numeric($post['agent_rebate'])) {
            $update['agent_rebate'] = $post['agent_rebate'];
        }

        if (empty($update)) {
            $this->error('未作修改');
        }

        Db::table('jh_merchant')->where('id', $post['id'])->update($update);
        $res = Db::table('jh_merchant')->field('*')->where('id', $post['id'])->find();

        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '修改用户信息',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $res);
    }
}
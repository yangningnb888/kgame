<?php

namespace app\back\controller;

use think\Controller;
use think\Db;
use think\Loader;
use think\Log;
use app\back\model\System_Log;

class Agent extends Controller
{

    /*///////////////////////////////////////////////////
    * 用户管理
    * url：{baseurl}/back/agent
    * */
    public function index()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['limit']) || !isset($post['page'])) {
            $this->error('缺少参数', [], 201);
        }

        $user = session('login_user');
        $where = 'terrace="' . $user['terrace'] . '"';
        if (!empty($post['username'])) {
            $where .= ' and username like "%' . $post['username'] . '%"';
        }

        if (!empty($post['start'])) {
            $where .= ' and create_at >= "' . $post['start'] . '"';
        }

        if (!empty($post['end'])) {
            $where .= ' and create_at <= "' . $post['end'] . '"';
        }

        if (!empty($post['status'])) {
            $where .= ' and status = ' . $post['status'];
        }

        $count = Db::table('system_user')->where($where)->field('*')->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
        $list = Db::table('system_user')->where($where)->field('id,username,superior,googlekey,level,status,create_at,qrcode')->order('create_at', 'desc')->limit($limit)->select();

        $res = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $v['created'] = $v['create_at'];
                $v['usertype'] = $v['level'];
                unset($v['level']);
                unset($v['create_at']);
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
    * 新增用户
    * url：{baseurl}/back/agent/insert
    * */
    public function insert()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['username']) || empty($post['password']) || empty($post['googlekey']) || empty($post['usertype']) || !isset($post['status'])) {
            $this->error('缺少参数', [], 201);
        }

        if (empty($post['googlekey'])) {
            $this->error('缺少身份密钥', [], 201);
        }

        $res = Db::table('system_user')->field('*')->where('username', $post['username'])->find();
        if (!empty($res)) {
            $this->error('用户名不能重复', [], 201);
        }

        $role = Db::name('auth_group')->column('*', 'id');
        if (!isset($role[$post['usertype']])) {
            $this->error('角色错误', [], 201);
        }

        $name_arr = [1 => '超级管理员', 2 => '代理', 3 => '商户'];

        if (!isset($name_arr[$post['usertype']])) {
            $this->error('目前仅能创建3种身份用户');
        }

        if ($post['usertype'] == 3 && empty($post['superior'])) {
            $this->error('请选择上级');
        }

        $user = session('login_user');
        $terrace = $user['terrace'];

        if (!empty($post['superior'])) {
            $res = Db::table('system_user')->field('*')->where('username', $post['superior'])->find();
            if (empty($res)) {
                $this->error('上级不存在');
            }
            $terrace = $res['terrace'];
        }

        $arr = [
            'username' => $post['username'],
            'password' => md5($post['password']),
            'nickname' => $name_arr[$post['usertype']],
            'level' => $post['usertype'],
            'status' => $post['status'] ? 1 : 0,
            'create_at' => date('Y-m-d H:i:s', time()),
            'last_time' => date('Y-m-d H:i:s', time()),
            'superior' => $post['superior'] ?? '',
            'googlekey' => $post['googlekey'] ?? '',
            'qrcode' => $post['qrcode'] ?? '',
            'terrace' => $terrace,
        ];

        Db::table('system_user')->insertGetId($arr);
        $res = Db::table('system_user')->field('*')->where('username', $post['username'])->find();
        Db::name('auth_group_access')->insert(['uid' => $res['id'], 'group_id' => $post['usertype']]);

        if ($post['usertype'] == 3) {
            $str = array_merge(range('a', 'z'), range('A', 'Z'));
            shuffle($str);
            $str = md5($str[0] . $str[1] . time());
            $key = md5($str[2] . time() . $str[3]);
            Db::table('jh_merchant')->insert([
                'id' => $res['id'],
                'merchant_id' => $str,
                'platform_key' => $key,
                'username' => $post['username'],
                'superior' => $post['superior'],
                'terrace' => $terrace,
                'is_show' => 0,
                'created' => date('Y-m-d H:i:s', time()),
                'touch' => date('Y-m-d H:i:s', time()),
                'platform_rebate' => 10,
                'agent_rebate' => 3,
            ]);
        }

        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '新增用户',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $post);
    }

    /*///////////////////////////////////////////////////
    * 修改用户信息
    * url：{baseurl}/back/agent/update
    * */
    public function update()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['id'])) {
            $this->error('缺少参数', [], 201);
        }

        $res = Db::table('system_user')->field('*')->where('id', $post['id'])->find();
        if (empty($res)) {
            $this->error('用户不存在');
        }

        $update = [];
        if (!empty($post['username'])) {
            $update['username'] = $post['username'];
        }

        if (!empty($post['password'])) {
            $update['password'] = md5($post['password']);
        }

        if (!empty($post['googlekey'])) {
            $update['googlekey'] = $post['googlekey'];
        }

        if (!empty($post['usertype'])) {
            $name_arr = [1 => '超级管理员', 2 => '代理', 3 => '商户'];
            $update['level'] = $post['usertype'];
            $update['nickname'] = $name_arr[$post['usertype']];
        }

        if (!empty($post['superior'])) {
            $update['superior'] = $post['superior'];
        }

        if (isset($post['status'])) {
            $update['status'] = $post['status'] ? 1 : 0;
        }

        if (empty($update)) {
            $this->error('未作修改');
        }

        Db::table('system_user')->where('id', $post['id'])->update($update);

        if ($res['level'] == 1) {

        }

        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '修改用户信息',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $post);
    }

    /*///////////////////////////////////////////////////
    * 删除用户
    * url：{baseurl}/back/agent/delete
    * */
    public function delete()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['username'])) {
            $this->error('缺少参数', [], 201);
        }

        $post['username'] = json_decode($post['username'], true);
        /*$res = Db::table('system_user')->field('*')->where('username', 'admin1')->find();
        if (empty($res)) {
            $this->error('用户不存在');
        }*/

        Db::table('system_user')->where('username', 'IN', $post['username'])->delete();
        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '删除用户',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $post);
    }

    /*///////////////////////////////////////////////////
    * 获取上级列表
    * url：{baseurl}/back/agent/superior
    * */
    public function superior()
    {
        $time1 = microtime(true);
        $list = Db::table('system_user')->where('level', '<', 3)->field('*')->select();
        $res = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $res[] = $v['username'];
            }
        }

        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '查询角色管理',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $res);
    }
}
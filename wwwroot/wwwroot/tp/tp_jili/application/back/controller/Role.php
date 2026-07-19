<?php

namespace app\back\controller;

use app\back\model\System_Log;
use think\Controller;
use think\Db;

class Role extends Controller
{
    /*///////////////////////////////////////////////////
    * 角色管理
    * url：{baseurl}/back/role
    * */
    public function index()
    {
        $post = input('post.');
        $time1 = microtime(true);
        $post['limit'] = 10;
        $post['page'] = 1;
        if (empty($post['limit']) || !isset($post['page'])) {
            $this->error('缺少参数', [], 201);
        }

        $where = 'id>0';
        if (!empty($post['title'])) {
            $where .= ' and title like "%' . $post['title'] . '%"';
        }

        if (!empty($post['start'])) {
            $where .= ' and created >= "' . $post['start'] . '"';
        }

        if (!empty($post['end'])) {
            $where .= ' and created <= "' . $post['end'] . '"';
        }

        $count = Db::name('auth_group')->where($where)->field('*')->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
        $list = Db::name('auth_group')->where($where)->field('*')->order('id', 'asc')->limit($limit)->select();
        $res = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $v['rules'] = explode(',', $v['rules']);
                $res[] = $v;
            }
        }

        $menus = Db::name('auth_rule')->select();
        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '查询角色管理',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', [
            'total_page' => $total_page,
            'total_count' => $count,
            'list' => $res,
            'rules' => $menus
        ]);
    }

    /*///////////////////////////////////////////////////
    * 新增角色
    * url：{baseurl}/back/role/insert
    * */
    public function insert()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['title'])) {
            $this->error('缺少参数', [], 201);
        }

        $res = Db::name('auth_group')->field('*')->where('title', $post['title'])->find();
        if ($res) {
            $this->error('角色不可重复');
        }

        $arr = [
            'title' => $post['title'],
            'describe' => $post['describe'] ?? '',
            'status' => 1,
            'created' => date('Y-m-d H:i:s', time()),
            'rules' => '',
        ];

        $id = Db::name('auth_group')->insert($arr);
        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '新增角色',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $post['id'] = $id;
        $post['created'] = date('Y-m-d H:i:s', time());
        $this->success('成功', $post);
    }

    /*///////////////////////////////////////////////////
    * 菜单分配
    * url：{baseurl}/back/role/setmenu
    * */
    public function setmenu()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['id'])) {
            $this->error('缺少参数', [], 201);
        }

        $rules = json_decode($post['rules'], true);
        if (empty($rules)) {
            $this->error('规则数据错误');
        }

        $rules = implode(',', $rules);
        $res = Db::name('auth_group')->field('*')->where('id', $post['id'])->find();
        if (!$res) {
            $this->error('角色不存在');
        }

        Db::name('auth_group')->where('id', $post['id'])->update(['rules' => $rules]);
        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '修改角色权限',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $post);
    }

    /*///////////////////////////////////////////////////
    * 删除角色
    * url：{baseurl}/back/role/delete
    * */
    public function delete()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['id'])) {
            $this->error('缺少参数', [], 201);
        }

        $post['id'] = json_decode($post['id'], true);
        Db::name('auth_group')->where('id', 'IN', $post['id'])->delete();
        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '删除角色',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $post);
    }

    /*///////////////////////////////////////////////////
    * 修改角色
    * url：{baseurl}/back/role/update
    * */
    public function update()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['id'])) {
            $this->error('缺少参数', [], 201);
        }

        if (empty($post['title']) && empty($post['describe'])) {
            $this->error('未作出修改', [], 201);
        }

        $res = Db::name('auth_group')->field('*')->where('id', $post['id'])->find();
        if (!$res) {
            $this->error('角色不存在');
        }

        $arr = [];
        if(!empty($post['title'])) {
            $arr['title'] = $post['title'];
        }

        if(!empty($post['describe'])) {
            $arr['describe'] = $post['describe'];
        }

        $id = Db::name('auth_group')->where('id', $post['id'])->update($arr);
        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '修改角色',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $post['id'] = $id;
        $this->success('成功', $post);

    }

    /*///////////////////////////////////////////////////
    * 获取当前所有角色列表
    * url：{baseurl}/back/role/alltype
    * */
    public function alltype()
    {
        $list = Db::name('auth_group')->column('title', 'id');
        $this->success('成功', $list);
    }
}
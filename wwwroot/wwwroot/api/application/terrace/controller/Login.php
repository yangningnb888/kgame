<?php

namespace app\terrace\controller;

use app\terrace\model\System_User;

//引入模型层
class Login
{
    public function index()
    {
        $sys_user = new System_User();
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "用户名或密码错误",
        );

        if (!isset($post['username'])) {
            return json($datajson);
        }

        $user = $sys_user->field('*')->where('username', $post['username'])->where('status', 1)->find();

        if ($user) {//查询找到用户
            if ($post['password'] == $user['password']) {
                session('name', $user['username']);
                session('id', $user['id']);
                $datajson = array(
                    'status' => '1',
                    'msg' => "登录成功",
                    'data' => [
                        'nickname' => $user['nickname'],
                        'username' => $user['username'],
                        'level' => $user['level']
                    ]
                );
            }
        } else {
            $datajson['msg'] = "用户名或密码错误";
        }

        return json($datajson);
    }
}

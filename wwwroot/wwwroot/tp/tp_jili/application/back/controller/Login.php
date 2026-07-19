<?php

namespace app\back\controller;

use app\back\model\System_Log;
use app\back\model\System_Token;
use app\back\model\System_User;
use think\Controller;
use think\Loader;
use think\Log;

//引入模型层
class Login extends Controller
{
    //
    public function _initialize()
    {
    }

    public function index()
    {
        $time1 = microtime(true);
        $sys_user = new System_User();
        $sys_token = new System_Token();
        $post = input('post.');

        if (!isset($post['username'])) {
            $this->error('用户名或密码错误', [], 201);
        }

        $user = $sys_user->field('*')->where('username', $post['username'])->where('status', 1)->find();
        Loader::import('PHPGangsta.GoogleAuthenticator',EXTEND_PATH);
        $ga = new \PHPGangsta_GoogleAuthenticator();
        (string)$code = $post['code'] ?? '';
        $checkResult = $ga->verifyCode($user['googlekey'], $code, 2);    // 2 = 2*30sec clock tolerance
        if (!$checkResult) {
            $this->error('验证码错误', [], 201);
        }

        if ($user && $post['password'] == $user['password']) {//查询找到用户
            session('login_user', $user);

            $system_log = new System_Log();
            $time = ceil(microtime(true)) - $time1 + 2;
            $save = [
                'behavior' => '用户登入',
                'times' => $time,
            ];
            $system_log->saveData($save);
            Loader::import("org/Token", EXTEND_PATH);
            $token = $this->makeToken();
            $sys_token->where('username', $post['username'])->delete();
            $endtime = time() + 900;
            $sys_token->insert([
                'username' => $post['username'],
                'token' => $token,
                'endtime' => $endtime,
            ]);
            $this->success('登录成功', ['username' => $post['username'], 'token' => $token, 'googlekey' => $user['googlekey'], 'qrcode' => $user['qrcode']]);
        } else {
            $this->error('用户名或密码错误', [], 201);
        }
    }



    private function makeToken()
    {
        $str = md5(uniqid(md5(microtime(true)), true)); //生成一个不会重复的字符串
        $str = sha1($str); //加密
        return $str;
    }

    // 退出登录
    public function logout(){
        //销毁session
        session("login_user", NULL);
        //跳转页面
        $this->redirect("http://localhost:2008/#/login");
    }
}

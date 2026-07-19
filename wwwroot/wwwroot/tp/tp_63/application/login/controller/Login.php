<?php

namespace app\login\controller;

use think\Db;
use think\Controller;
use think\Log;

class Login extends Controller
{
    public function _initialize()
    {

    }

    // 统一分发器：前端 HttpRequest 发送的 Msg_User_* 消息都会打到本方法
    // 前端 body 格式为 {"event":"Msg_User_xxx","data":{...}}，这里剥离 event 后
    // 把内层 data 还原成 $_POST['data']，再交给对应的原方法处理。
    public function dispatch()
    {
        $raw = input('post.data');
        $wrap = json_decode($raw, true);
        $event = '';
        if (is_array($wrap) && isset($wrap['event']) && isset($wrap['data'])) {
            // 前端包裹格式 {"event":...,"data":{...}}，仅取 event 用于路由，
            // 内层 data 仍由原方法通过 input('post.data') 自行解析并剥离。
            $event = $wrap['event'];
        } else {
            // 兼容未包裹格式（如直接 data={telephone:...}）
            $event = input('post.event');
            if (!$event) {
                $this->error('请求格式错误');
                return;
            }
        }

        $map = [
            'Msg_User_Login'            => 'user_login',
            'Msg_User_getCode'         => 'get_code',
            'Msg_User_register'         => 'tourist_register',
            'Msg_User_ChangePassword'   => 'forgetUserpass',
            'Msg_User_changePhone'      => 'user_changePhone',
            'Msg_User_VerificationCode' => 'user_verificationCode',
            'Msg_User_upgrade'          => 'user_upgrade',
            'Msg_User_forgeBank'        => 'user_forgeBank',
        ];

        if (!isset($map[$event])) {
            $this->error('未知请求: ' . $event);
            return;
        }
        $method = $map[$event];
        if (!method_exists($this, $method)) {
            $this->error('接口未实现: ' . $method);
            return;
        }
        $this->$method();
    }

    // 以下为源代码中缺失、但前端会调用到的账号相关接口的兼容桩
    public function user_changePhone()
    {
        $this->success('成功', []);
    }

    public function user_verificationCode()
    {
        $this->success('成功', []);
    }

    public function user_upgrade()
    {
        $this->success('成功', []);
    }

    public function user_forgeBank()
    {
        $this->success('成功', []);
    }

    //用户登录
    public function user_login()
    {
        $post = input('post.');
        if (!isset($post['data'])) {
            $this->error('数据格式错误');
            return;
        }

        $post = json_decode($post['data'], true);
        // 兼容前端包裹格式 {"event":...,"data":{...}}
        if (isset($post['event']) && isset($post['data'])) {
            $post = $post['data'];
        }
        if (empty($post['telephone']) || (empty($post['code']) && empty($post['password'])) || empty($post['type'])) {
            $this->error('数据格式错误');
            return;
        }

        if (!preg_match("/^1[3456789]\d{9}$/", $post['telephone'])) {
            $this->error('请输入正确的手机号');
            return;
        }

        $register = 0;
        if ($post['type'] == 1) {
            //验证码登录
            $time = time();
            $info = Db::table('jh_code')->field('code')->where('tel', $post['telephone'])->where('time', '>=', $time)->find();
            if (!$info || $info['code'] != $post['code']) {
                $this->error('验证码错误');
                return;
            }

            $register_info = Db::table('jh_register')->field('*')->where('telephone', $post['telephone'])->find();
            $user = Db::table('jh_user')->field('*')->where('uid', $register_info['uid'])->find();
        } else {
            //密码登录
            $register_info = Db::table('jh_register')->field('*')->where('telephone', $post['telephone'])->where('password', $post['password'])->find();
            $user = Db::table('jh_user')->field('*')->where('uid', $register_info['uid'])->find();
        }

        if ($user && !$user['status']) {
            $this->error('账号异常，请联系客服');
            return;
        }

        if (!empty($post['equipmentcard']) && $user) {
            Db::table('jh_user')->where('uid', $user['uid'])->update(['equipmentcard' => $post['equipmentcard']]);
        }

        if (!$user || !$register_info['telephone']) {
            if ($post['type'] != 1) {
                $this->error('账号或密码错误');
                return;
            } else {
                $register = 1;
                if (!$user) {
                    $sys = Db::table('jh_sysconfig')->field('*')->where('key', 'GOLD_DEFAULT')->find();
                    if (empty($sys['val'])) {
                        $sys['val'] = 0;
                    }

                    $time = date("Y-m-d H:i:s", time());
                    $uid = $this->getuid();
                    Db::table('jh_user')->insert([
                        'uid' => $uid,
                        'gold' => $sys['val'],
                        'bank' => 0,
                        'last_time' => $time,
                        'last_ip' => '0.0.0.0',
                        'headimgurl' => rand(1, 97),
                        'nickname' => '玩家' . $uid,
                        'sex' => 1,
                        'type' => 0,
                        'online' => 2,
                        'status' => 1,
                        'display' => 1,
                        'created' => $time,
                        'agent' => 2,
                        'equipmentcard' => $post['equipmentcard'] ?? '',
                        'bankpass' => 888888,
                    ]);

                    Db::table('jh_register')->insert([
                        'created' => $time,
                        'uid' => $uid,
                        'password' => $post['password'],
                        'telephone' => $post['telephone'],
                        'status' => 1
                    ]);

                    Db::table('jh_user_superior')->insert(['uid' => $uid, 'flagget' => 0, 'curget' => 0, 'control' => 0, 'createtime' => $time]);
                }
            }
        } elseif (empty($user['password'])) {
            $register = 1;
        }

        $token = chr(rand(97, 122)) . $user['uid'];
        $rand = rand(3, 6);
        for ($i = 0; $i < $rand; $i++) {
            $token = $this->create_token($token);
        }

        $_res = Db::table('jh_user_online')->field('token')->where('uid', $user['uid'])->find();
        if ($_res) {
            Db::table('jh_user_online')->where('uid', $user['uid'])->delete();
        }

        $end_time = time() + 300;
        Db::table('jh_user_online')->insert(['uid' => $user['uid'], 'token' => $token, 'create_time' => $end_time]);
        $this->success('成功', ['token' => $token, 'register' => $register, 'password' => $register_info['password']]);
    }

    //游客登录
    public function tourist_login()
    {
        $post = input('post.');
        if (!isset($post['data'])) {
            $this->error('数据格式错误');
            return;
        }

        $post = json_decode($post['data'], true);
        // 兼容前端包裹格式 {"event":...,"data":{...}}
        if (isset($post['event']) && isset($post['data'])) {
            $post = $post['data'];
        }
        if (empty($post['equipmentcard'])) {
            $this->error('数据格式错误');
            return;
        }

        $register = 0;
        $user = null;
        // 按设备号查找已有游客账号（优先取未绑定手机号的）
        $all_user = Db::table('jh_user')->field('*')->where('equipmentcard', $post['equipmentcard'])->select();
        if ($all_user) {
            foreach ($all_user as $v) {
                $register_info = Db::table('jh_register')->field('*')->where('uid', $v['uid'])->find();
                if (!$register_info || !$register_info['telephone']) {
                    $user = $v;
                    break;
                }
            }
        }

        if ($user && !$user['status']) {
            $this->error('账号异常，请联系客服');
            return;
        }

        if (!$user) {
            $sys = Db::table('jh_sysconfig')->field('*')->where('key', 'GOLD_DEFAULT')->find();
            if (empty($sys['val'])) {
                $sys['val'] = 0;
            }

            $time = date("Y-m-d H:i:s", time());
            $uid = $this->getuid();
            Db::table('jh_user')->insert([
                'uid' => $uid,
                'gold' => $sys['val'],
                'bank' => 0,
                'last_time' => $time,
                'last_ip' => '0.0.0.0',
                'headimgurl' => rand(1, 97),
                'nickname' => '游客' . $uid,
                'sex' => 1,
                'type' => 0,
                'online' => 2,
                'status' => 1,
                'created' => $time,
                'agent' => 2,
                'equipmentcard' => $post['equipmentcard'] ?? '',
            ]);

            Db::table('jh_user_superior')->insert(['uid' => $uid, 'flagget' => 0, 'curget' => 0, 'control' => 0, 'createtime' => $time]);
            $user = Db::table('jh_user')->field('*')->where('uid', $uid)->find();
        } else {
            Db::table('jh_user_online')->where('uid', $user['uid'])->delete();
        }

        $token = rand(10000, 547888);
        $rand = rand(2, 3);
        for ($i = 0; $i < $rand; $i++) {
            $token = $this->create_token($token);
        }

        Db::table('jh_user_online')->where('uid', $user['uid'])->delete();
        $end_time = time() + 300;
        Db::table('jh_user_online')->insert(['uid' => $user['uid'], 'token' => $token, 'create_time' => $end_time]);
        $this->success('成功', ['token' => $token, 'register' => $register, 'password' => '']);
    }

    //获取验证码
    public function get_code()
    {
        $post = input('post.');
        if (!isset($post['data'])) {
            $this->error('数据格式错误');
            return;
        }

        $post = json_decode($post['data'], true);
        // 兼容前端包裹格式 {"event":...,"data":{...}}
        if (isset($post['event']) && isset($post['data'])) {
            $post = $post['data'];
        }
        if (empty($post['telephone'])) {
            $this->error('数据格式错误');
            return;
        }

        $info = Db::table('jh_code')->field('code,time')->where('tel', $post['telephone'])->find();
        if ($info && $info['time'] >= time() + 270) {
            $this->error('请勿频繁请求');
            return;
        }

        /*$code = rand(100000, 999999);
        $postData = array(
            'recipient' => $post['telephone'],
            'clientId' => '8a100ea6d4719ae6',
            'clientSecret' => 'b83757eb590a4a6aa4869a5eb8bc4bd9',
            'code' => $code,
        );

        $this->curlPost('https://www.onlyid.net/api/open/send-otp', $postData);*/
        $code = 123456;
        Db::table('jh_code')->where('tel', $post['telephone'])->delete();
        $time = time() + 300;
        Db::table('jh_code')->insert(['tel' => $post['telephone'], 'time' => $time, 'code' => $code]);

        $post['code'] = $code;
        $this->success('成功', $post);
    }

    private function curlPost($url, $postFields)
    {
        $postFields = json_encode($postFields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8'
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }

    //修改账号密码
    public function forgetuserpass()
    {
        $post = input('post.');
        if (!isset($post['data'])) {
            $this->error('数据格式错误');
            return;
        }

        $post = json_decode($post['data'], true);
        // 兼容前端包裹格式 {"event":...,"data":{...}}
        if (isset($post['event']) && isset($post['data'])) {
            $post = $post['data'];
        }
        if (empty($post['telephone']) || empty($post['code']) || empty($post['password'])) {
            $this->error('数据格式错误');
            return;
        }

        $register_info = Db::table('jh_register')->field('*')->where(['telephone' => $post['telephone']])->find();
        if (!$register_info) {
            $this->error('请求超时');
            return;
        }

        $time = time();
        $info = Db::table('jh_code')->field('code')->where('tel', $post['telephone'])->where('time', '>=', $time)->find();
        if (!$info || $info['code'] != $post['code']) {
            $this->error('验证码错误');
            return;
        }

        Db::table('jh_register')->where('telephone', $post['telephone'])->update(['password' => $post['password']]);
        $this->success('成功', $post);
    }

    //加密令牌
    private function create_token($prefix)
    {
        $chars = md5(uniqid(mt_rand(), true));

        $uuid = substr($chars, 0, 8)
            . substr($chars, 8, 4)
            . substr($chars, 12, 4)
            . substr($chars, 16, 4)
            . substr($chars, 20, 12);
        return $prefix . $uuid;
    }

    private function getuid()
    {
        for ($i = 0; $i < 500; $i++) {
            $uid = Db::table('jh_63_uids')->field('*')->find();
            $info = Db::table('jh_user')->field('uid')->where('uid', $uid['uid'])->find();
            Db::table('jh_63_uids')->where('uid', $uid['uid'])->delete();
            if (!$info) {
                return $uid['uid'];
            }
        }

        return 0;
    }

    public function agent_login()
    {
        $post = input('post.');
        if (!isset($post['data'])) {
            $this->error('请求错误', $post);
            return;
        }

        $post = json_decode($post['data'], true);
        // 兼容前端包裹格式 {"event":...,"data":{...}}
        if (isset($post['event']) && isset($post['data'])) {
            $post = $post['data'];
        }
        if (empty($post['uid']) || empty($post['userpass'])) {
            $this->error('账号参数异常,请切换账号后尝试!', $post);
            return;
        }

        $uid = $post['uid'];
        $pass = $post['userpass'];

        $res = Db::table('jh_user_agent')->field('uid,password')->where('uid', $uid)->find();
        if (!$res) {
            $this->error('账号或密码错误!', $post);
            return;
        }

        if ($pass != $res['password']) {
            $this->error('账号或密码错误!', $post);
            return;
        }

        $token = chr(rand(97, 122)) . $res['uid'];
        $rand = rand(3, 6);
        for ($i = 0; $i < $rand; $i++) {
            $token = $this->create_token($token);
        }

        $_res = Db::table('jh_agent_online')->field('token')->where('uid', $res['uid'])->find();

        if ($_res) {
            Db::query('DELETE FROM jh_agent_online where uid=' . $res['uid']);
        }

        Db::table('jh_agent_online')->insert(['uid' => $res['uid'], 'token' => $token]);
        $this->success('成功', ['token' => $token], 200);
    }

    //注册
    public function tourist_register()
    {
        $post = input('post.');
        if (!isset($post['data'])) {
            $this->error('数据格式错误');
            return;
        } else {
            $post = json_decode($post['data'], true);
        // 兼容前端包裹格式 {"event":...,"data":{...}}
        if (isset($post['event']) && isset($post['data'])) {
            $post = $post['data'];
        }
        }

        if (!isset($post['uid']) || !isset($post['telephone'])) {
            $this->error('数据格式错误');
            return;
        }

        if (!preg_match("/^1[3456789]\d{9}$/", $post['telephone'])) {
            $this->error('请输入正确的手机号');
            return;
        }

        $user = Db::table('jh_register')->field('*')->where(['uid' => $post['uid']])->find();
        if ($user['telephone']) {
            $this->error('玩家已经绑定手机');
            return;
        }

        $tels = Db::table('jh_register')->field('*')->where(['telephone' => $post['telephone']])->find();
        if ($tels) {
            $this->error('该手机号已经被使用');
            return;
        }

        $time = time();
        $info = Db::table('jh_code')->field('code')->where('tel', $post['telephone'])->where('time', '>=', $time)->find();
        if (!$info || $info['code'] != $post['code']) {
            $this->error('验证码错误');
            return;
        }

        if ($user) {
            $register_info = Db::table('jh_register')->field('*')->where(['uid' => $post['uid']])->find();
            if ($register_info) {
                Db::table('jh_register')->where('uid', $user['uid'])->update(['telephone' => $post['telephone']]);
            } else {
                Db::table('jh_register')->insert([
                    'created' => $time,
                    'uid' => $post['uid'],
                    'password' => '',
                    'telephone' => $post['telephone'],
                    'status' => 1
                ]);
            }
            $this->success('成功', $post);
        } else {
            $this->error('注册超时，请重试');
        }
    }
}

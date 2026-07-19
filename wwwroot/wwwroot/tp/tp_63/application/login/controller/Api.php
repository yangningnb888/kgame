<?php

namespace app\login\controller;
use think\Controller;
use think\Db;
class Api extends Controller
{
    public function _initialize()
    {

    }

    //上传头像接口
    public function uploads()
    {
        $post = input('post.');
        $logo_data = json_decode($post['data'], true);
        $name = $logo_data['fileName'];
        $logo_data['fileData'] = str_ireplace(' ', '+', $logo_data['fileData']);

        if (!empty($logo_data['fileData'])) {
            $num = $this->get_base64_img($logo_data['fileData'], $name);
            if (!empty($num)) {
                $this->success('成功', ['headimgurl' => '/static/headimg/' . $name . '.' . $num]);
            } else {
                $this->error('上传失败');
            }
        } else {
            $this->error('上传失败');
        }
    }

    private function get_base64_img($base64, $filename, $path = 'static/headimg/')
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)) {
            $type = $result[2];
            $new_file = $path . $filename . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64)))) {
                return $type;
            } else {
                return false;
            }
        }
    }

    public function uptel ()
    {
        $post = input('post.');
        if (!isset($post['data'])) {
            $this->error('数据格式错误');
            return;
        }

        $post = json_decode($post['data'], true);
        if (empty($post['old_telephone']) || empty($post['old_code']) || empty($post['new_telephone']) || empty($post['new_code'])) {
            $this->error('数据格式错误');
            return;
        }

        $tels = Db::table('jh_register')->field('*')->where(['telephone' => $post['new_telephone']])->find();
        if ($tels) {
            $this->error('该手机号已经被使用');
            return;
        }

        /*$time = time();
        $info = Db::table('jh_tel_code')->field('code')->where('tel', $post['old_telephone'])->where('time', '<=', $time)->find();
        if (!$info || $info['code'] != $post['old_code']) {
            $this->error('旧验证码错误');
            return;
        }

        $time = time();
        $info = Db::table('jh_tel_code')->field('code')->where('tel', $post['new_telephone'])->where('time', '<=', $time)->find();
        if (!$info || $info['code'] != $post['new_code']) {
            $this->error('新验证码错误');
            return;
        }*/

        $register_info = Db::table('jh_register')->field('*')->where(['telephone' => $post['old_telephone']])->find();
        if ($register_info) {
            Db::table('jh_register')->where('uid', $register_info['uid'])->update(['telephone' => $post['new_telephone']]);
            $this->success('成功', ['new_telephone' => $post['new_telephone']]);
        } else {
            $this->error('用户不存在');
        }
    }

    //游戏反馈
    public function feedback()
    {
        $post = input('post.');
        if (!isset($post['data'])) {
            $this->error('数据格式错误');
            return;
        }

        $post = json_decode($post['data'], true);
        if (empty($post['uid']) || empty($post['content'])) {
            $this->error('数据格式错误');
            return;
        }

        $time = date('Y-m-d', time());
        Db::table('jh_feedback')->insert(['uid' => $post['uid'], 'content' =>$post['content'], 'created' => $time]);
        $this->success('成功', $post);
    }
}

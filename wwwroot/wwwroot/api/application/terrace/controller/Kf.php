<?php

namespace app\terrace\controller;

use think\Db;

class Kf
{
    public function index()
    {
        $res = Db::table('jh_kf_list')->field('*')->select();
        $datajson = array(
            'status' => '1',
            'msg' => "",
            'data' => $res
        );
        return json($datajson);
    }

    //上传头像接口
    public function add_kf()
    {
        $datajson = array(
            'status' => '0',
            'msg' => "头像上传失败",
        );

        $post = input('post.');
        if (empty($post['kf_id'])) {
            $datajson['msg'] = '参数错误';
            return json($datajson);
        }

        $info = Db::table('jh_kf_list')->field('*')->where('kf_id', $post['kf_id'])->find();
        $name = $post['kf_id'] . time();
        $post['fileData'] = str_ireplace(' ', '+', $post['fileData']);

        if (!empty($post['fileData'])) {
            $num = $this->get_base64_img($post['fileData'], $name);
            if (empty($num)) {
                return json($datajson);
            }
            $update = ['head' => '/static/kf/headimg/' . $name . '.jpeg'];
        } elseif (!empty($info)) {
            $update = [];
        } else {
            return json($datajson);
        }

        foreach ($post as $key => $value) {
            if (($key == 'name' || $key == 'appraise' || $key == 'start') && !empty($value)) {
                $update[$key] = $value;
            }
        }


        if ($info) {
            if (!empty($update)) {
                Db::name('jh_kf_list')->where('kf_id', $post['kf_id'])->update($update);
            }
            $update['kf_id'] = $post['kf_id'];
        } else {
            $update['kf_id'] = $post['kf_id'];
            Db::name('jh_kf_list')->insert($update);
        }

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $update;
        return json($datajson);
    }

    //上传头像接口
    public function uphead()
    {
        $datajson = array(
            'status' => '0',
            'msg' => "头像上传失败",
        );

        $post = input('post.');
        if (empty($post['kf_id'])) {
            $datajson['msg'] = '参数错误';
            return json($datajson);
        }

        $info = Db::table('jh_kf_list')->field('*')->where('kf_id', $post['kf_id'])->find();
        if (!$info) {
            $datajson['msg'] = '客服不存在';
            return json($datajson);
        }
        $name = $post['kf_id'] . time();
        $post['fileData'] = str_ireplace(' ', '+', $post['fileData']);

        if (!empty($post['fileData'])) {
            $num = $this->get_base64_img($post['fileData'], $name);
            if (empty($num)) {
                return json($datajson);
            }
        } else {
            return json($datajson);
        }

        $update = ['head' => '/static/kf/headimg/' . $name . '.jpeg'];
        if (!empty($update)) {
            Db::name('jh_kf_list')->where('kf_id', $post['kf_id'])->update($update);
        }

        $update['kf_id'] = $post['kf_id'];
        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $update;
        return json($datajson);
    }

    public function get_base64_img($base64, $filename, $path = 'static/kf/headimg/')
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)) {
            $type = $result[2];
            $new_file = $path . $filename . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64)))) {
                return $new_file;
            } else {
                return false;
            }
        }
    }

    public function upinfo()
    {
        $datajson = array(
            'status' => '0',
            'msg' => "参数错误",
        );

        $post = input('post.');
        if (empty($post['kf_id'])) {
            return json($datajson);
        }

        $info = Db::table('jh_kf_list')->field('*')->where('kf_id', $post['kf_id'])->find();
        $update = [];
        foreach ($post as $key => $value) {
            if (($key == 'name' || $key == 'appraise' || $key == 'start') && !empty($value)) {
                $update[$key] = $value;
            }
        }


        if ($info) {
            if (!empty($update)) {
                Db::name('jh_kf_list')->where('kf_id', $post['kf_id'])->update($update);
            }
        } else {
            $datajson['msg'] = '客服不存在';
            return json($datajson);
        }

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }

    public function del_kf()
    {
        $post = input('post.');

        if (empty($post['kf_id'])) {
            return json(array(
                'status' => '0',
                'msg' => "参数错误",
            ));
        }

        Db::table('jh_kf_list')->where('kf_id', $post['kf_id'])->delete();
        return json(array(
            'status' => 1,
            'msg' => "",
            'data' => $post
        ));
    }
}
<?php

namespace app\terrace\controller;

use think\Controller;
use think\Db;

//引入模型层
class Profit extends Controller
{
    public function _initialize()
    {
        if (!session('id') || !session('name')) {
            $this->error('请您先进行登录，谢谢', url(''));//判断session并返回登录界面
        }
    }

    public function index()
    {
        $post = input('post.');
        $datajson = array('status' => '0', 'msg' => "数据格式错误");

        if (!isset($post['page']) || !isset($post['limit'])) {
            return json($datajson);
        } else {
            $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
        }

        $where = 'formuid<0 and jh_user_profit.type=6';
        if (!empty($post['uid'])) {
            $where .= ' and jh_user_profit.uid=' . $post['uid'];
        }

        if (!empty($post['start'])) {
            $where .= ' and jh_user_profit.created>="' . $post['start'] . '"';
        }

        if (!empty($post['end'])) {
            $where .= ' and jh_user_profit.created<="' . $post['end'] . '"';
        }

        if (!empty($post['user_type']) && $post['user_type'] == 1) {
            $where .= ' and jh_user_profit.uid<10000000';
        } elseif (!empty($post['user_type']) && $post['user_type'] == 2) {
            $where .= ' and jh_user_profit.uid>10000000';
        }

        $all_num = Db::name('jh_user_profit')->where($where)->sum('num');
        $all = Db::name('jh_user_profit')->field('*')->where($where)->select();
        $res = Db::name('jh_user_profit')->join('jh_user', 'jh_user_profit.uid=jh_user.uid')->field('jh_user_profit.uid,nickname,num,endgold,endbank,beforegold,beforebank,jh_user_profit.created')->where($where)->order('jh_user_profit.created', 'DESC')->limit($limit)->select();

        $datajson['data'] = [
            'list' => [],
            'all_num' => $all ? count($all) : 0,
            'total' => $all_num ? $all_num : 0
        ];

        foreach ($res as $key => $value) {
            $datajson['data']['list'][] = $value;
        }

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        return json($datajson);
    }
}

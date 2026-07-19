<?php

namespace app\game\controller;

use think\Db;

//引入模型层
class Gametrol
{
    public function index()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        $post['page'] = 1;
        $post['limit'] = 10;

        if (!isset($post['page']) || !isset($post['limit'])) {
            return json($datajson);
        } else {
            $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
        }

        if (!empty($post['type']) && $post['type'] < 0 || !empty($post['touch']) && $post['touch'] < 0) {
            $where = 'touch=-1';
        } elseif (!empty($post['type']) && $post['type'] > 0) {
            $where = 'touch>0';
        } elseif (!empty($post['uid'])) {
            $where = 'jh_control_record.uid=' . $post['uid'];
        } else {
            $where = 'jh_control_record.id>0';
        }

        $all = Db::name('jh_control_record')->field('*')->where($where)->select();
        $res = Db::name('jh_control_record')->join([['jh_user', 'jh_control_record.uid=jh_user.uid'], ['jh_user_superior', 'jh_control_record.uid=jh_user_superior.uid']])->field('jh_control_record.uid,nickname,jh_control_record.flagget,jh_control_record.control,jh_control_record.created,curget,touch')->order('jh_control_record.created', 'DESC')->where($where)->limit($limit)->select();

        $datajson['data'] = [
            'all_num' => $all ? count($all) : 0,
            'record' => []
        ];

        foreach ($res as $key => $value) {
            $datajson['data']['record'][] = $value;
        }

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        return json($datajson);
    }
}

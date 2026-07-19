<?php

namespace app\terrace\controller;

use app\terrace\model\Therich;
use app\terrace\model\User;
use think\Db;

//引入模型层
class Rich
{

    public function index()
    {
        $t_rich = new Therich();
        $datajson = array(
            'status' => '1',
            'msg' => "",
            'data' => []
        );
        $res = $t_rich->join('jh_user', 'jh_the_rich.uid=jh_user.uid')->field('jh_user.uid,nickname,gold,bank,order,brief')->order('order', 'ASC')->limit(0, 30)->select();
        foreach ($res as $key => $value) {
            $datajson['data'][] = [
                'uid' => $value['uid'],
                'nickname' => $value['nickname'],
                'wealth' => $value['gold'] + $value['bank'],
                'brief' => $value['brief'],
                'rank' => $value['order'],
            ];
        }

        return json($datajson);
    }

    public function setturn()
    {
        $t_user = new User();
        $t_rich = new Therich();
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "错误的请求",
        );

        if (!isset($post['uid']) || !isset($post['turn']) || !isset($post['kf_id'])) {
            return json($datajson);
        }

        $post['brief'] = $post['brief'] ?? '';
        $user = $t_user->field('*')->where('uid', $post['uid'])->find();
        if (!$user) {
            $datajson['msg'] = '用户不存在';
            return json($datajson);
        }

        if ($post['turn'] > 0) {
            $the_rich_user = $t_rich->field('*')->where('uid', $post['uid'])->find();
            Db::query('UPDATE jh_the_rich SET `order`=`order`+1 WHERE `order` >= ' . $post['turn']);

            if (!$the_rich_user) {
                $t_rich->insert(['uid' => $post['uid'], 'brief' => $post['brief'], 'order' => $post['turn'], 'kf' => $post['kf_id'], 'brief' => $post['brief']]);
            } else {
                $t_rich->where('uid', $post['uid'])->update(['order' => $post['turn'], 'kf' => $post['kf_id'], 'brief' => $post['brief']]);
            }
        } else {
            $t_rich->where('uid', $post['uid'])->delete();
        }

        $datajson['status'] = 1;
        $datajson['msg'] = '';
        $datajson['data'] = $post;
        return json($datajson);
    }
}

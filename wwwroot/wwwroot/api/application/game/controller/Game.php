<?php

namespace app\game\controller;

use app\game\model\User_Profit;
use app\game\model\Game_Record;
use think\Controller;
use think\Db;

//引入模型层
class Game extends Controller
{
    public function profit()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['page']) || !isset($post['limit']) || !isset($post['type']) || !isset($post['uid']) || !isset($post['user_type'])) {
            return json($datajson);
        } else {
            $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
        }

        $user_profit = new User_Profit();
        $where = 'jh_user_profit.status!=3';

        if (!empty($post['uid'])) {
            $where .= ' and jh_user_profit.uid='. $post['uid'];
        }

        if ($post['type'] == 1) {
            $where .= ' and jh_user_profit.type=3';
        } elseif ($post['type'] == 2) {
            $where .= ' and jh_user_profit.type=2';
        } else {
            $where .= ' and jh_user_profit.type>1';
        }

        if (isset($post['start'])) {
            $where .= ' and jh_user_profit.created>="' . $post['start'] . '"';
        }

        if (isset($post['end'])) {
            $where .= ' and jh_user_profit.created<="' . $post['end'] . '"';
        }

        if ($post['user_type'] == 1) {
            $where .= ' and formuid<1000000';
        } elseif ($post['user_type'] == 2) {
            $where .= ' and formuid>1000000';
        }

        $all = $user_profit->where($where)->count();
        $all = $all ? $all : 0;
        $res = $user_profit->field('jh_user_profit.uid,formuid,jh_user_profit.type,num,jh_user_profit.created,beforegold,endgold,beforebank,endbank,t1.nickname as uid_name,t2.nickname as formuid_name')
            ->where($where)->order('jh_user_profit.created', 'DESC')->join([
                ['jh_user t1', 't1.uid=jh_user_profit.uid'],
                ['jh_user t2', 't2.uid=jh_user_profit.formuid']
            ])->limit($limit)->select();

        $sum = $user_profit->where($where)->sum('num');
        if (!$res) {
            $res = [];
        }

        if (!isset($post['start'])) {
            $post['start'] = '2023-01-01 00:00:00';
        }

        if (!isset($post['end'])) {
            $post['end'] = date('Y-m-d' . ' 00:00:00', time());
        }

        $datajson['data'] = [
            'page' => $post['page'],
            'all_page' => ceil($all / $post['limit']),
            'all_num' => $all,
            'total' => $sum ? $sum : 0,
            'list' => $res
        ];

        $sum = Db::name('jh_mail')->join('jh_user', 'jh_mail.outuid=jh_user.uid')->where('jh_mail.status=2 and agent=1 and jh_user.type=0 and jh_mail.created>="' . $post['start'] . '" and jh_mail.created<="' . $post['end'] . '"')->sum('number');
        $datajson['data']['output'] = $sum ? $sum : 0;
        $sum = Db::table('jh_mail')->join('jh_user', 'jh_mail.uid=jh_user.uid')->where('jh_mail.status=2 and agent=1 and jh_user.type=0 and jh_mail.created>="' . $post['start'] . '" and jh_mail.created<="' . $post['end'] . '"')->sum('number');
        $datajson['data']['input'] = $sum ? $sum : 0;
        $datajson['data']['difference'] = $datajson['data']['output'] - $datajson['data']['input'];
        $datajson['data']['allprofit'] = $datajson['data']['output'] + $datajson['data']['input'];

        $datajson['status'] = 1;
        $datajson['msg'] = '';

        return json($datajson);
    }

    public function record()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['page']) || !isset($post['limit']) || !isset($post['seession']) || !isset($post['uid'])) {
            return json($datajson);
        } else {
            $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
        }


        $game_record = new Game_Record();
        if (!$post['seession']) {
            $res = $game_record->field('*')->where('uid', $post['uid'])->order('endtime', 'DESC')->limit($limit)->select();
            $allpage = $game_record->where('uid', $post['uid'])->count();
        } else {
            $res = $game_record->field('*')->where('uid', $post['uid'])->where('seession', $post['seession'])->order('endtime', 'DESC')->limit($limit)->select();
            $allpage = $game_record->where('uid', $post['uid'])->where('seession', $post['seession'])->count();
        }

        if (!$allpage) {
            $allpage = 0;
        }
        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = [
            'list' => $res,
            'all_page' => ceil($allpage / $post['limit']),
            'all_num' => $allpage
        ];
        return json($datajson);
    }



    /**
     * 得到当前时分秒
     */
    private function GET_NOW($timestamp = null)
    {
        if ($timestamp == null) {
            $timestamp = time();
        }
        return date('Y-m-d H:i:s', $timestamp);
    }
}

<?php

namespace app\game\controller;

use app\game\model\Agenttable;
use app\game\model\Banuser;
use app\game\model\Game_User;
use app\game\model\Mail;
use app\game\model\Register;
use app\game\model\User_Possition;
use app\game\model\User_Superior;
use think\Controller;
use think\Db;

//引入模型层
class Userlist extends Controller
{
    private $user;
    private $mail;
    private $user_possition;
    private $user_superior;
    private $banuser;

    public function _initialize()
    {
        $this->user = new Game_User();
        $this->mail = new Mail();
        $this->user_possition = new User_Possition();
        $this->banuser = new Banuser();
        $this->user_superior = new User_Superior();
    }

    public function index()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        /*$post['page'] = 2;
        $post['limit'] = 10;
        $post['stage'] = 2;
        $post['uid'] = 0;*/
        $total_win = 0;

        if (!isset($post['page']) || !isset($post['limit'])) {
            return json($datajson);
        } else {
            $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
        }

         if (isset($post['uid']) && is_numeric($post['uid'])) {
            $datajson['data'] = [];
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            if ($post['uid'] == 0) {
                $datajson['data']['all_num'] = $this->user->where('type', 0)->where('agent', 2)->count();
                $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->where('type', 0)->where('agent', 2)->limit($limit)->select();
            } else {
                $datajson['data']['all_num'] = $this->user->where('uid', $post['uid'])->where('type', 0)->where('agent', 2)->count();
                $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->where('uid', $post['uid'])->where('type', 0)->where('agent', 2)->limit($limit)->select();
            }
        } elseif (!empty($post['equipmentcard'])) {
            $datajson['data'] = [];
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            $datajson['data']['all_num'] = $this->user->where('equipmentcard', $post['equipmentcard'])->where('type', 0)->where('agent', 2)->count();
            $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->limit($limit)->where('equipmentcard', $post['equipmentcard'])->where('type', 0)->where('agent', 2)->select();
            $sum = Db::name('jh_game_record')->join('jh_user', 'jh_game_record.uid=jh_user.uid')->where('equipmentcard', $post['equipmentcard'])->sum('win');
            $total_win = $sum ? $sum : 0;
        } elseif (!empty($post['last_ip'])) {
            $datajson['data'] = [];
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            $datajson['data']['all_num'] = $this->user->where('last_ip', $post['last_ip'])->where('type', 0)->where('agent', 2)->count();
            $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->limit($limit)->where('last_ip', $post['last_ip'])->where('type', 0)->where('agent', 2)->select();
            $sum = Db::name('jh_game_record')->join('jh_user', 'jh_game_record.uid=jh_user.uid')->where('last_ip', $post['last_ip'])->sum('win');
            $total_win = $sum ? $sum : 0;
        } elseif (isset($post['stage'])) {
             $datajson['data'] = [];
             $datajson['msg'] = '';
             $datajson['status'] = 1;
             if ($post['stage'] == 0) {
                 $datajson['data']['all_num'] = $this->user->where('type', 0)->where('agent', 2)->count();
                 $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->where('type', 0)->where('agent', 2)->limit($limit)->select();
             } else {
                 $datajson['data']['all_num'] = $this->user->where('type', 0)->where('agent', 2)->where('online', $post['stage'])->count();
                 $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->where('type', 0)->where('agent', 2)->where('online', $post['stage'])->limit($limit)->select();
             }
         } else {
            return json($datajson);
        }

        $register = new Register();
        if (!$datajson['data']['userlist']) {
            $datajson['data']['userlist'] = [];
        } else {
            foreach ($datajson['data']['userlist'] as $key => $value) {
                $datajson['data']['userlist'][$key]['all_input'] = $this->mail->where('uid', $value['uid'])->where('currency', 1)->sum('number');
                $datajson['data']['userlist'][$key]['all_input'] = $datajson['data']['userlist'][$key]['all_input'] ?? 0;
                $datajson['data']['userlist'][$key]['all_output'] = $this->mail->where('outuid', $value['uid'])->where('currency', 1)->sum('number');
                $datajson['data']['userlist'][$key]['all_output'] = $datajson['data']['userlist'][$key]['all_output'] ?? 0;
                $datajson['data']['userlist'][$key]['superior'] = 0;
                $possition = $this->user_possition->where('uid', $value['uid'])->field('gtype')->find();
                $datajson['data']['userlist'][$key]['stage'] = $possition ? $possition['gtype'] : 0;
                $control = $this->user_superior->where('uid', $value['uid'])->field('control,flagget,curget')->find();
                if (!$control) {
                    $datajson['data']['userlist'][$key]['control'] = 0;
                    $datajson['data']['userlist'][$key]['flagget'] = 0;
                } else {
                    $datajson['data']['userlist'][$key]['control'] = $control['control'];
                    $datajson['data']['userlist'][$key]['flagget'] = $control['flagget'] - $control['curget'];
                }
                $telephone = $register->where('uid', $value['uid'])->field('telephone')->find();
                $datajson['data']['userlist'][$key]['telephone'] = $telephone ? $telephone['telephone'] : '';
            }
        }

        $datajson['data']['all_win'] = $total_win;
        return json($datajson);
    }

    public function control()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['uid']) || !isset($post['control']) || !isset($post['flagget']) || !is_int($post['flagget'])) {
            return json($datajson);
        }

        if ($post['control'] != -1 && $post['control'] != 0 && $post['control'] != 1) {
            return json($datajson);
        }

        $user = $this->user->field('*')->where('uid', $post['uid'])->where('type', 0)->find();

        if (!$user) {
            $datajson['msg'] = '玩家不存在';
            return json($datajson);
        }

        $user_superior = $this->user_superior->field('*')->where('uid', $post['uid'])->find();
        $flagget = abs($post['flagget']) * $post['control'];
        if ($flagget == 0) {
            $post['control'] = 0;
        }

        $time = $this->GET_NOW();
        if ($user_superior) {
            $this->user_superior->where('uid', $post['uid'])->update(['control' => $post['control'], 'flagget' => $flagget, 'curget' => 0]);
        } else {
            $this->user_superior->insert(['uid' => $post['uid'], 'control' => $post['control'], 'flagget' => $flagget, 'superior' => 0, 'curget' => 0, 'createtime' => $time, 'usecard' => 0]);
        }

        Db::name('jh_control_record')->insert([
            'uid' => $post['uid'],
            'flagget' => $flagget,
            'control' => $post['control'],
            'created' => $time
        ]);
        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }


    public function banuser()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['uid']) || !isset($post['status'])) {
            return json($datajson);
        }

        $this->banuser->insert(['val' => $post['uid'], 'status' => $post['status'], 'type' => 1]);
        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = ['status' => $post['status']];
        return json($datajson);
    }

    public function agent()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!isset($post['uid']) || !isset($post['level'])) {
            return json($datajson);
        }

        if (!is_int($post['level']) || $post['level'] < 0 || $post['level'] > 2) {
            return json($datajson);
        }

        $user = $this->user->field('*')->where('uid', $post['uid'])->where('type', 0)->find();

        if (!$user) {
            $datajson['msg'] = '玩家不存在';
            return json($datajson);
        }


        $agent = new Agenttable();
        $user = $agent->field('*')->where('uid', $post['uid'])->find();
        if ($user) {
            $agent->where('uid', $post['uid'])->update(['power' => $post['level']]);
        } else {
            $agent->insert(['uid' => $post['uid'], 'extension' => 0, 'power' => $post['level']]);
        }
        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
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

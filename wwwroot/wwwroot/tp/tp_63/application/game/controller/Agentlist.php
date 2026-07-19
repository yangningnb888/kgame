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

//引入模型层
class Agentlist extends Controller
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

        if (!isset($post['page']) || !isset($post['limit'])) {
            return json($datajson);
        } else {
            $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
        }

        if (isset($post['stage'])) {
            $datajson['data'] = [];
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            if ($post['stage'] == 0) {
                $datajson['data']['all_num'] = $this->user->where('type', 0)->where('agent', 1)->count();
                $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->where('type', 0)->where('agent', 1)->limit($limit)->select();
            } else {
                $datajson['data']['all_num'] = $this->user->where('type', 0)->where('agent', 1)->where('online', $post['stage'])->count();
                $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->where('type', 0)->where('agent', 1)->where('online', $post['stage'])->limit($limit)->select();
            }
        } elseif (isset($post['uid'])) {
            $datajson['data'] = [];
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            if (!$post['uid']) {
                $datajson['data']['all_num'] = $this->user->where('type', 0)->where('agent', 1)->count();
                $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->where('type', 0)->where('agent', 1)->limit($limit)->select();
            } else {
                $datajson['data']['all_num'] = $this->user->where('uid', $post['uid'])->where('type', 0)->where('agent', 1)->count();
                $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->where('uid', $post['uid'])->where('type', 0)->where('agent', 1)->limit($limit)->select();
            }
        } elseif (isset($post['equipmentcard'])) {
            $datajson['data'] = [];
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            $datajson['data']['all_num'] = $this->user->where('equipmentcard', $post['equipmentcard'])->where('type', 0)->where('agent', 1)->count();
            $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->limit($limit)->where('equipmentcard', $post['equipmentcard'])->where('type', 0)->where('agent', 1)->select();
        } elseif (isset($post['last_ip'])) {
            $datajson['data'] = [];
            $datajson['msg'] = '';
            $datajson['status'] = 1;
            $datajson['data']['all_num'] = $this->user->where('last_ip', $post['last_ip'])->where('type', 0)->where('agent', 1)->count();
            $datajson['data']['userlist'] = $this->user->field('uid,nickname,gold,bank,rcard,last_time,equipmentcard,last_ip,created,status')->limit($limit)->where('last_ip', $post['last_ip'])->where('type', 0)->where('agent', 1)->select();
        } else {
            return json($datajson);
        }

        $register = new Register();
        $agent = new Agenttable();
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
                $control = $this->user_superior->where('uid', $value['uid'])->field('control,flagget')->find();
                if (!$control) {
                    $datajson['data']['userlist'][$key]['control'] = 0;
                    $datajson['data']['userlist'][$key]['flagget'] = 0;
                } else {
                    $datajson['data']['userlist'][$key]['control'] = $control['control'];
                    $datajson['data']['userlist'][$key]['flagget'] = $control['flagget'];
                }
                $telephone = $register->where('uid', $value['uid'])->field('telephone')->find();
                $datajson['data']['userlist'][$key]['telephone'] = $telephone ? $telephone['telephone'] : '';
                $datajson['data']['userlist'][$key]['agent_num'] = $this->user_superior->join('jh_user', 'jh_user_superior.uid=jh_user.uid')->where('superior', $value['uid'])->where('agent', 1)->count();
                $datajson['data']['userlist'][$key]['player_num'] = $this->user_superior->join('jh_user', 'jh_user_superior.uid=jh_user.uid')->where('superior', $value['uid'])->where('agent', 2)->count();
                if (!$datajson['data']['userlist'][$key]['agent_num']) {
                    $datajson['data']['userlist'][$key]['agent_num'] = 0;
                }

                if (!$datajson['data']['userlist'][$key]['player_num']) {
                    $datajson['data']['userlist'][$key]['player_num'] = 0;
                }

                $level = $agent->where('uid', $value['uid'])->field('power')->find();
                $datajson['data']['userlist'][$key]['level'] = $level ? $level['power'] : 0;
            }
        }

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

        $user = $this->user->field('*')->where('uid', $post['uid'])->where('type', 0)->where('agent', 1)->find();

        if (!$user) {
            $datajson['msg'] = '玩家不存在';
            return json($datajson);
        }

        $user_superior = $this->user_superior->field('*')->where('uid', $post['uid'])->find();
        $flagget = abs($post['flagget']) * $post['control'];
        if ($user_superior) {
            $this->user_superior->field('*')->where('uid', $post['uid'])->update(['control' => $post['control'], 'flagget' => $flagget]);
        } else {
            $time = $this->GET_NOW();
            $this->user_superior->insert(['uid'=> $post['uid'],'control' => $post['control'], 'flagget' => $flagget, 'superior' => 0, 'curget' => 0, 'createtime' => $time, 'usecard' => 0]);
        }

        $datajson['msg'] = '';
        $datajson['data'] = $post;
        return $datajson;
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
        $datajson['data'] = ['status' => $post['status']];
        return $datajson;
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

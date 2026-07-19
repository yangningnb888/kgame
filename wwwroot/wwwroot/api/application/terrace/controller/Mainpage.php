<?php

namespace app\terrace\controller;

use app\terrace\model\Game_User;
use app\terrace\model\System_User;
use think\Controller;
use think\Db;

//引入模型层
class Mainpage extends Controller
{
   /* public function _initialize()
    {
        if (!session('id') || !session('name')) {
            $this->error('请您先进行登录，谢谢', url(''));//判断session并返回登录界面
        }
    }*/

    public function index()
    {
        $user = new Game_User();
        $data = ['status' => 1, 'msg' => '', 'data' => []];
        $time = $this->GET_TODAY();
        $insert = $user->count('agent=1 and type=0 and created>="' . $time . '"');
        $data['data']['today_insert_agent'] = $insert;
        $insert = $user->count('agent=2 and type=0 and created>="' . $time . '"');
        $data['data']['today_insert_player'] = $insert;
        $time = $this->GET_NOW(time() - 3 * 24 * 60 * 60);
        $insert = $user->count('agent=1 and type=0 and created>="' . $time . '"');
        $data['data']['three_insert_agent'] = $insert;
        $insert = $user->count('agent=2 and type=0 and created>="' . $time . '"');
        $data['data']['three_insert_player'] = $insert;
        $time = $this->GET_NOW(time() - 7 * 24 * 60 * 60);
        $insert = $user->count('agent=1 and type=0 and created>="' . $time . '"');
        $data['data']['week_insert_agent'] = $insert;
        $insert = $user->count('agent=2 and type=0 and created>="' . $time . '"');
        $data['data']['week_insert_player'] = $insert;

        $sum = Db::name('jh_mail')->join('jh_user', 'jh_mail.outuid=jh_user.uid')->where('jh_mail.status=2 and agent=1 and jh_user.type=0')->sum('number');
        $data['data']['all_output'] = $sum ? $sum : 0;
        $sum = Db::table('jh_mail')->join('jh_user', 'jh_mail.uid=jh_user.uid')->where('jh_mail.status=2 and agent=1 and jh_user.type=0')->sum('number');
        $data['data']['all_input'] = $sum ? $sum : 0;
        $time = $this->GET_TODAY();
        $sum = Db::name('jh_mail')->join('jh_user', 'jh_mail.outuid=jh_user.uid')->where('jh_mail.status=2 and agent=1 and jh_user.type=0 and jh_mail.created>="' . $time . '"')->sum('number');
        $data['data']['today_output'] = $sum ? $sum : 0;
        $sum = Db::table('jh_mail')->join('jh_user', 'jh_mail.uid=jh_user.uid')->where('jh_mail.status=2 and agent=1 and jh_user.type=0 and jh_mail.created>="' . $time . '"')->sum('number');
        $data['data']['today_input'] = $sum ? $sum : 0;
        $golds = $user->sum('gold', 'agent=1');
        $banks = $user->sum('bank', 'agent=1');
        $data['data']['cur_agent_gold'] = $golds + $banks;
        $golds = $user->sum('gold', 'agent=2');
        $banks = $user->sum('bank', 'agent=2');
        $data['data']['cur_player_gold'] = $golds + $banks;
        $count = $user->count('agent=1 and online=1 and type=0');
        $data['data']['agent_online'] = $count;
        $count = $user->count('agent=1 and online=2 and type=0');
        $data['data']['agent_offline'] = $count;
        $count = $user->count('agent=2 and online=1 and type=0');
        $data['data']['player_online'] = $count;
        $count = $user->count('agent=2 and online=2 and type=0');
        $data['data']['player_offline'] = $count;
        $count = $user->count('type=0');
        $data['data']['all_user_num'] = $count;

        $count = Db::table('jh_user')->join('jh_user_possition', 'jh_user_possition.uid=jh_user.uid')->where('jh_user_possition.gtype=0 and jh_user.online=1')->count();
        $data['data']['hall_online'] = $count;
        $arr = ['chess' => [], 'hundred' => [], 'video' => [], 'else' => [], 'fish' => []];
        foreach ($arr as $key => $value) {
            if (!empty($value)) {
                $str = '';
                foreach ($value as $value1) {
                    $str .= $value1;
                }

                $str = substr($str, 1);
                $count = Db::table('jh_user')->join('jh_user_possition', 'jh_user_possition.uid=jh_user.uid')->where('jh_user_possition.gtype in (' . $str . ') and jh_user.online=1')->count();
                $data['data'][$key . '_online'] = $count;
            } else {
                $data['data'][$key . '_online'] = 0;
            }
        }

        return json($data);
    }

    public function cs()
    {
        $user = Db::table('jh_user')->field('uid,gold,bank')->where('type', 0)->select();
        foreach ($user as $key => $value) {
            if ($value['bank'] + $value['gold'] >= 1500000) {
                $end_game = Db::table('jh_game_record')->field('*')->where('uid', $value['uid'])->order('id', 'DESC')->find();
                $end_mail = Db::table('jh_user_profit')->field('*')->where('uid', $value['uid'])->order('id', 'DESC')->find();
                if ($value['bank'] + $value['gold'] != $end_game['endgold'] + $end_game['endbank'] && $value['bank'] + $value['gold'] != $end_mail['endgold'] + $end_mail['endbank']) {
                    var_dump($value['uid']);
                    return;
                }
            }
        }

        var_dump(0);
    }

    /**
     * 得到今天日期
     */
    private function GET_TODAY($timestamp = null)
    {
        if ($timestamp == null) {
            $timestamp = time();
        }
        return date('Y-m-d' . ' 00:00:00', $timestamp);
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

<?php

namespace app\control\controller;

use app\control\model\Player_bet;
use think\Controller;
use think\Db;

//引入模型层
class Brnn extends Controller
{
    public function _initialize()
    {
        if (!session('id') || !session('name')) {
            $this->error('请您先进行登录，谢谢', url(''));//判断session并返回登录界面
        }
    }

    public function index()
    {
        $datajson = array(
            'status' => '1',
            'msg' => "",
            'data' => []
        );
        $player_bets = new Player_bet();
        $info = $player_bets->field('playerbets,endtime,last_type,last_win')->where('gtype', 2)->where('level', 5)->find();
        if (isset($info['playerbets'])) {
            //$datajson['data']['player_bets'] = json_decode($info['playerbets'], true);
            $player_bets = json_decode($info['playerbets'], true);
        }

        $datajson['data']['userlist'] = [];
        $datajson['data']['player_bets'] = [];
        foreach ($player_bets as $key => $value) {
            $datajson['data']['userlist'][] = Db::name('jh_user')->field('uid,nickname,gold,bank')->where('uid', $key)->find();
            $save_type = [];
            foreach ($value as $key1 => $value1) {
                $save_type[] = [$key1 => $value1];
            }

            $datajson['data']['player_bets'][] = [$key => $save_type];
        }


        $datajson['data']['last_type'] = $info['last_type'] ?? 0;
        $datajson['data']['last_win'] = $info['last_win'] ?? 0;
        if (isset($info['endtime']) && $info['endtime'] < time()) {
            $datajson['data']['gamestatus'] = 2;
        } else {
            $datajson['data']['gamestatus'] = 1;
        }

        $res = Db::name('jh_game_config')->field('controls,profit')->where('level', 5)->where('gtype', 2)->find();
        $res['controls'] = json_decode($res['controls'], true);
        $datajson['data']['percent'] = $res['controls']['percent'] ?? 0;
        $datajson['data']['profit'] = $res['profit'] ?? 0;
        return json($datajson);
    }
}

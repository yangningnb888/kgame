<?php

namespace app\terrace\controller;

use app\terrace\model\Winpoint;
use think\Db;

//引入模型层
class Alllist
{
    //赢分榜列表
    public function winner()
    {
        $t_winpoint = new Winpoint();
        $res = $t_winpoint->join('jh_user', 'jh_winpoints.uid=jh_user.uid')->field('jh_winpoints.uid,nickname,gold,bank,allwin')->where('allwin', '>', 0)->where('type', 0)->order('allwin', 'DESC')->limit(0, 30)->select();
        $ret = [];
        $rank = 1;
        $time = date('Y-m-d H:i:s', time() - 30 * 24 * 60 * 60);
        foreach ($res as $key => $value) {
            $ret[$key] = [
                'uid' => $value['uid'],
                'nickname' => $value['nickname'],
                'gold' => $value['gold'],
                'bank' => $value['bank'],
                'rank' => $rank,
            ];
            $ret[$key]['all_win'] = Db::name('jh_game_record')->where('endtime>="' . $time . '" AND win>0')->sum('win');
            $rank++;
        }

        $datajson = array(
            'status' => '1',
            'msg' => "",
            'data' => $ret
        );

        return json($datajson);
    }

    //输分榜列表
    public function lose()
    {
        $t_winpoint = new Winpoint();
        $res = $t_winpoint->join('jh_user', 'jh_winpoints.uid=jh_user.uid')->field('jh_winpoints.uid,nickname,gold,bank,allwin')->where('allwin', '<', 0)->where('type', 0)->order('allwin', 'ASC')->limit(0, 30)->select();
        $ret = [];
        $rank = 1;
        $time = date('Y-m-d H:i:s', time() - 30 * 24 * 60 * 60);
        foreach ($res as $key => $value) {
            $ret[$key] = [
                'uid' => $value['uid'],
                'nickname' => $value['nickname'],
                'gold' => $value['gold'],
                'bank' => $value['bank'],
                'rank' => $rank,
            ];
            $ret[$key]['all_win'] = Db::name('jh_game_record')->where('endtime>="' . $time . '" AND win<0')->sum('win');
            $rank++;
        }

        $datajson = array(
            'status' => '1',
            'msg' => "",
            'data' => $ret
        );

        return json($datajson);
    }

    //游戏流水榜列表
    public function game()
    {
        $time = date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60);
        $res = Db::name('jh_game_record')->join('jh_user', 'jh_game_record.uid=jh_user.uid')->field('SUM(win) as game_win,jh_user.uid,nickname,gold,bank')->where('type=0 AND endtime>="' . $time . '"')->order('game_win', 'DESC')->limit(0, 30)->select();
        $ret = [];
        $rank = 1;
        $time = date('Y-m-d H:i:s', time() - 30 * 24 * 60 * 60);
        foreach ($res as $key => $value) {
            $value['rank'] = $rank++;
            $ret[$key] = $value;
            $rank++;
            $value['game_win'] = Db::name('jh_game_record')->where('endtime>="' . $time . '"')->sum('win');
        }

        $datajson = array(
            'status' => '1',
            'msg' => "",
            'data' => $ret
        );

        return json($datajson);
    }

    //银行流水榜列表
    public function bank()
    {
        $time = date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60);
        $res = Db::name('jh_user_profit')->join('jh_user', 'jh_user_profit.uid=jh_user.uid')->field('SUM(num) as profit,jh_user.uid,nickname,gold,bank')->where('(jh_user_profit.type=2 or jh_user_profit.type=3) AND jh_user_profit.status!=3 AND jh_user_profit.created>="' . $time . '"')->group('jh_user_profit.uid')->order('profit', 'DESC')->limit(0, 30)->select();
        $ret = [];
        $rank = 1;
        if ($res) {
            $time = date('Y-m-d H:i:s', time() - 30 * 24 * 60 * 60);
            foreach ($res as $key => $value) {
                $value['rank'] = $rank++;
                $ret[$key] = $value;
                $rank++;
                $value['profit'] = Db::name('jh_user_profit')->where('created>="' . $time . '"  AND status!=3')->where('currency', 1)->sum('num');
            }
        }

        $datajson = array(
            'status' => '1',
            'msg' => "",
            'data' => $ret
        );

        return json($datajson);
    }
}

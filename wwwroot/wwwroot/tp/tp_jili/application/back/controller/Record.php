<?php

namespace app\back\controller;

use app\back\model\System_Log;
use think\Controller;
use think\Db;
use think\Loader;
use think\Log;

//引入模型层
class Record extends Controller
{
    /*///////////////////////////////////////////////////
    * 游戏记录
    * url：{baseurl}/back/record
    * */
    public function index()
    {
        $time1 = microtime(true);
        $post = input('post.');
        if (empty($post['limit']) || empty($post['page']) || $post['limit'] > 200) {
            $this->error('缺少参数', [], 201);
        }

        $res = [];
        $where = 'a.id>0';

        if (!empty($post['id'])) {
            $where .= ' AND a.id = ' . $post['id'];
        }

        if (!empty($post['gtype'])) {
            $where .= ' AND a.gtype = ' . $post['gtype'];
        }

        if (!empty($post['uid'])) {
            $where .= ' AND a.uid = ' . $post['uid'];
        }

        if (!empty($post['gamename'])) {
            $where .= ' AND a.gamename LIKE "%' . $post['gamename'] . '%"';
        }

        if (!empty($post['deal_id'])) {
            $where .= ' AND a.deal_id = ' . $post['deal_id'];
        }

        if (!empty($post['win'])) {
            if ($post['win'] > 0) {
                $where .= ' AND a.win >= a.used';
            } else {
                $where .= ' AND a.win < a.used';
            }
        }

        if (!empty($post['start'])) {
            if (!strtotime($post['start'])) {
                $this->error('参数错误', [], 201);
            }
            $where .= ' AND a.created >= "' . $post['start'] . '"';
        }

        if (!empty($post['end'])) {
            if (!strtotime($post['end'])) {
                $this->error('参数错误', [], 201);
            }
            $where .= ' AND a.created < "' . $post['end'] . '"';
        }

        $user = session('login_user');
        if ($user['level'] == 2) {
            $where .= ' AND s.last_superior="' . $user['username'] . '"';
        } elseif ($user['level'] >= 3) {
            $where .= ' AND s.superior = "' . $user['username'] . '"';
        } else {
            $where .= ' AND s.admin_user = "' . $user['terrace'] . '"';
        }

        $log_title = $post['page'] > 0 ? '查询游戏记录' : '查询并导出游戏记录';
        $page = $post['page'] > 0 ? $post['page'] : 1;
        $count = Db::table('jh_game_back_record')->alias('a')
            ->join('jili.jh_game_status b', 'b.gtype=a.gtype')
            ->join('jili.jh_user_register u', 'a.uid=u.uid')
            ->join('jili.jh_user_superior s', 's.uid=a.uid')
            ->where($where)->field('a.id,a.gtype,b.gamename,u.platform,a.uid,a.deal_id,u.currency,a.win,a.used,a.betstartgold,a.created')->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($page - 1) * $post['limit'] . ',' . $post['limit'];
        $list = Db::table('jh_game_back_record')->alias('a')
            ->join('jili.jh_game_status b', 'b.gtype=a.gtype')
            ->join('jili.jh_user_register u', 'a.uid=u.uid')
            ->join('jili.jh_user_superior s', 's.uid=a.uid')
            ->where($where)->field('a.id,a.gtype,b.gamename,u.platform,a.uid,a.deal_id,u.currency,a.win,a.used,a.betstartgold,a.created')->limit($limit)->select();

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $res[] = [
                    'id' => $v['id'],
                    'gtype' => $v['gtype'],
                    'gamename' => $v['gamename'],
                    'platform' => $v['platform'],
                    'uid' => $v['uid'],
                    'deal_id' => $v['deal_id'],
                    'currency' => $v['currency'],
                    'win' => $v['win'],
                    'gameendgold' => $v['betstartgold'] - $v['used'] + $v['win'],
                    'betendgold' => $v['betstartgold'] - $v['used'],
                    'betstartgold' => $v['betstartgold'],
                    'bet' => $v['used'],
                    'betotal' => $v['used'],
                    'gamewin' => $v['win'],
                    'created' => $v['created'],
                    'touch' => $v['created'],
                ];
            }
        }

        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => $log_title,
            'times' => $time,
        ];
        $system_log->saveData($save);

        if ($post['page'] < 0) {
            Loader::import("org/Excel", EXTEND_PATH);
            $excel = new \Excel();
            $data = Db::table('jh_game_back_record')->alias('a')
                ->join('jili.jh_game_status b', 'b.gtype=a.gtype')
                ->join('jili.jh_user_register u', 'a.uid=u.uid')
                ->join('jili.jh_user_superior s', 's.uid=a.uid')
                ->where($where)->field('a.id,a.gtype,b.gamename,u.platform,a.uid,a.deal_id,u.currency,a.win,a.used,a.betstartgold,a.created,(a.betstartgold-a.used+a.win) as gameendgold,(a.betstartgold-a.used) as betendgold')->select();
            $excel->downloadExcel('游戏记录', [['id', 'ID'], ['platform', '平台ID'], ['uid', '用户ID'], ['deal_id', '订单号'], ['gamename', '游戏名称'], ['currency', '币种'], ['used', '下注金额总计'], ['win', '中将金额总计'], ['betstartgold', '下注前余额'], ['betendgold', '下注后余额'], ['betendgold', '下注后余额'], ['gameendgold', '游戏结束余额'], ['created', '创建时间'], ['created', '修改时间']], $data);
        }

        $this->success('成功', [
            'total_page' => $total_page,
            'total_count' => $count,
            'list' => $res
        ]);
    }

}
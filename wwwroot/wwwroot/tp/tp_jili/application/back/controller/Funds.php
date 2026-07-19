<?php

namespace app\back\controller;

use app\back\model\System_Log;
use think\Controller;
use think\Db;
use think\Loader;
use think\Log;

class Funds extends Controller
{
    /*///////////////////////////////////////////////////
    * 资金管理
    * url：{baseurl}/back/funds
    * */
    public function index()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['limit']) || !isset($post['page'])) {
            $this->error('缺少参数', [], 201);
        }

        $where = 'a.id>0';
        if (!empty($post['id'])) {
            $where .= ' and a.id = ' . $post['id'];
        }

        if (!empty($post['uid'])) {
            $where .= ' and a.uid="' . $post['uid'] . '"';
        }

        if (!empty($post['platform_id'])) {
            $where .= ' and u.platform="' . $post['platform_id'] . '"';
        }

        if (!empty($post['gid'])) {
            $where .= ' and a.gid="' . $post['gid'] . '"';
        }

        if (!empty($post['deal_id'])) {
            $where .= ' and a.deal_id="' . $post['deal_id'] . '"';
        }

        if (!empty($post['type'])) {
            $where .= ' and a.type="' . $post['type'] . '"';
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

        $log_title = $post['page'] > 0 ? '查询资金管理' : '查询并导出资金管理';
        $page = $post['page'] > 0 ? $post['page'] : 1;
        $count = Db::table('jh_user_profit')->alias('a')
            ->join('jili.jh_user_register u', 'a.uid=u.uid')
            ->join('jili.jh_user_superior s', 's.uid=a.uid')
            ->where($where)->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($page - 1) * $post['limit'] . ',' . $post['limit'];
        $list = Db::table('jh_user_profit')->alias('a')
            ->join('jili.jh_user_register u', 'a.uid=u.uid')
            ->join('jili.jh_user_superior s', 's.uid=a.uid')
            ->where($where)->field('a.id,a.uid,u.platform,a.beforegold,a.endgold,a.gid,a.deal_id,a.type,a.created')->order('a.created', 'desc')->limit($limit)->select();
        $res = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $res[] = $v;
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
            $data = Db::table('jh_user_profit')->alias('a')
                ->join('jili.jh_user_register u', 'a.uid=u.uid')
                ->join('jili.jh_user_superior s', 's.uid=a.uid')
                ->where($where)->field('a.id,a.uid,u.platform,a.beforegold,a.endgold,a.gid,a.deal_id,a.type,a.created')->select();
            $excel->downloadExcel('资金管理', [['id', 'ID'], ['uid', '用户ID'], ['platform', '平台ID'], ['beforegold', '操作前金额'], ['endgold', '操作后金额'], ['gid', '游戏订单号'], ['deal_id', '操作订单号'], ['type', '操作类型'], ['created', '时间']], $data);
        }

        $this->success('成功', ['list' => $res, 'total_page' => $total_page]);
    }

    /*///////////////////////////////////////////////////
    * 日报流水
    * url：{baseurl}/back/funds/day
    * */
    public function day()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['limit']) || !isset($post['page'])) {
            $this->error('缺少参数', [], 201);
        }

        $where = 'a.id>0';
        if (!empty($post['platform_id'])) {
            $where .= ' and a.platform="' . $post['platform_id'] . '"';
        }

        if (!empty($post['currency'])) {
            $where .= ' and a.currency="' . $post['currency'] . '"';
        }

        if (!empty($post['daytime'])) {
            $where .= ' and a.daytime="' . $post['daytime'] . '"';
        }

        $log_title = $post['page'] > 0 ? '查询日报流水' : '查询并导出日报流水';
        $page = $post['page'] > 0 ? $post['page'] : 1;
        $count = Db::table('jh_game_funds')->alias('a')->join('jili.jh_game_status b', 'a.gtype=b.gtype')->where($where)->field('a.id,platform,all_bet,all_win,currency,daytime,gamename')->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($page - 1) * $post['limit'] . ',' . $post['limit'];
        $list = Db::table('jh_game_funds')->alias('a')->join('jili.jh_game_status b', 'a.gtype=b.gtype')->where($where)->field('a.id,platform,all_bet,all_win,currency,daytime,gamename')->order('daytime', 'desc')->limit($limit)->select();
        $res = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $v['all_lose'] = $v['all_bet'] - $v['all_win'];
                $res[] = $v;
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
            $data = Db::table('jh_game_funds')->alias('a')->join('jili.jh_game_status b', 'a.gtype=b.gtype')->where($where)->field('a.id,platform,all_bet,all_win,currency,daytime,gamename,(all_bet-all_win) as all_lose')->select();
            $excel->downloadExcel('日报流水', [['id', 'ID'], ['daytime', '时间'], ['platform', '平台ID'], ['currency', '币种'], ['gamename', '游戏名称'], ['all_bet', '玩家总投注'], ['all_win', '玩家总中奖'], ['all_lose', '玩家总输']], $data);
        }

        $this->success('成功', [
            'total_page' => $total_page,
            'total_count' => $count,
            'list' => $res
        ]);
    }


    /*///////////////////////////////////////////////////
    * 月报流水
    * url：{baseurl}/back/funds/month
    * */
    public function month()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['limit']) || !isset($post['page'])) {
            $this->error('缺少参数', [], 201);
        }

        $where = 'a.id>0';
        if (!empty($post['platform_id'])) {
            $where .= ' and a.platform="' . $post['platform_id'] . '"';
        }

        if (!empty($post['currency'])) {
            $where .= ' and a.currency="' . $post['currency'] . '"';
        }

        if (!empty($post['daytime'])) {
            $time = $post['daytime'] . '-01';
            $time = strtotime($time);
            $where .= ' and a.daytime="' . $time . '"';
        }

        $log_title = $post['page'] > 0 ? '查询月报流水' : '查询并导出月报流水';
        $page = $post['page'] > 0 ? $post['page'] : 1;
        $count = Db::table('jh_game_month_funds')->alias('a')->join('jili.jh_game_status b', 'a.gtype=b.gtype')->where($where)->field('a.id,platform,all_bet,all_win,currency,daytime,gamename')->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($page - 1) * $post['limit'] . ',' . $post['limit'];
        $list = Db::table('jh_game_month_funds')->alias('a')->join('jili.jh_game_status b', 'a.gtype=b.gtype')->where($where)->field('a.id,platform,all_bet,all_win,currency,daytime,gamename')->order('daytime', 'desc')->limit($limit)->select();
        $res = [];
        if ($list) {
            foreach ($list as $k => $v) {
                $v['all_lose'] = $v['all_bet'] - $v['all_win'];
                $times = strtotime($v['daytime']);
                $v['daytime'] = date('Y-m', $times);
                $res[] = $v;
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
            $data = Db::table('jh_game_month_funds')->alias('a')->join('jili.jh_game_status b', 'a.gtype=b.gtype')->where($where)->field('a.id,platform,all_bet,all_win,currency,daytime,gamename,(all_bet-all_win) as all_lose')->select();
            foreach ($data as $k => $v) {
                $data[$k]['daytime'] = date('Y-m', $v['daytime']);
            }
            $excel->downloadExcel('月报流水', [['id', 'ID'], ['daytime', '时间'], ['platform', '平台ID'], ['currency', '币种'], ['gamename', '游戏名称'], ['all_bet', '玩家总投注'], ['all_win', '玩家总中奖'], ['all_lose', '玩家总输']], $data);
        }

        $this->success('成功', [
            'total_page' => $total_page,
            'total_count' => $count,
            'list' => $res
        ]);
    }
}
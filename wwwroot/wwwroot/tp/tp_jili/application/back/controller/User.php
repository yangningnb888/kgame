<?php

namespace app\back\controller;

use app\back\model\System_Log;
use think\Controller;
use think\Db;
use think\Loader;
use think\Log;

class User extends Controller
{
    /*///////////////////////////////////////////////////
    * 会员管理
    * url：{baseurl}/back/user
    * */
    public function index()
    {
        $time1 = microtime(true);
        $post = input('post.');
        if (empty($post['limit']) || empty($post['page']) || $post['limit'] > 200) {
            $this->error('缺少参数', [], 201);
        }

        $res = [];
        $where = 'u.id>0';
        if (!empty($post['uid'])) {
            $where .= ' AND u.uid="' . $post['uid']. '"';
        }

        if (!empty($post['platform_id'])) {
            $where .= ' AND u.platform_id="' . $post['platform_id'] . '"';
        }

        if (!empty($post['currency'])) {
            $where .= ' AND u.currency="' . $post['currency'] . '"';
        }

        if (!empty($post['rtp'])) {
            $where .= ' AND s.rtp="' . $post['rtp'] . '"';
        }

        $user = session('login_user');
        if ($user['level'] == 2) {
            $where .= ' AND s.last_superior="' . $user['username'] . '"';
        } elseif ($user['level'] >= 3) {
            $where .= ' AND s.superior = "' . $user['username'] . '"';
        } else {
            $where .= ' AND s.admin_user = "' . $user['terrace'] . '"';
        }

        $log_title = $post['page'] > 0 ? '查询会员管理' : '查询并导出会员管理';
        $page = $post['page'] > 0 ? $post['page'] : 1;
        $count = Db::table('jh_user_register')->alias('u')->join('jili.jh_user_superior s', 's.uid=u.uid')->where($where)->field('u.id,u.uid,u.platform,u.gold,u.bank,u.currency,s.rtp,u.created,s.touch,s.anchor')->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($page - 1) * $post['limit'] . ',' . $post['limit'];
        $list = Db::table('jh_user_register')->alias('u')->join('jili.jh_user_superior s', 's.uid=u.uid')->where($where)->field('u.id,u.uid,u.platform,u.gold,u.bank,u.currency,s.rtp,u.created,s.touch,s.anchor')->order('u.created', 'desc')->limit($limit)->select();

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $v['gold'] += $v['bank'];
                unset($v['bank']);
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
            $data = Db::table('jh_user_register')->alias('u')->join('jili.jh_user_superior s', 's.uid=u.uid')->where($where)->field('u.id,u.uid,u.platform,u.currency,s.rtp,u.created,s.touch,s.anchor,(u.gold+u.bank) as all_gold')->select();
            $excel->downloadExcel('会员管理', [['id', 'ID'], ['uid', '用户ID'], ['platform', '平台ID'], ['all_gold', '金币'], ['currency', '币种'], ['rtp', '返奖率'], ['anchor', '是否主播模式'], ['created', '创建时间'], ['touch', '修改时间']], $data);
        }
        $this->success('成功', [
            'total_page' => $total_page,
            'total_count' => $count,
            'list' => $res
        ]);
    }

    /*///////////////////////////////////////////////////
    * 修改rtp
    * url：{baseurl}/back/user/touchrtp
    * */
    public function touchrtp()
    {
        $time1 = microtime(true);
        $post = input('post.');
        if (empty($post['uid']) || !isset($post['rtp']) || !isset($post['anchor'])) {
            $this->error('缺少参数', [], 201);
        }

        if (empty($post['rtp']) && $post['anchor'] < 0) {
            $this->error('未做修改', [], 201);
        }

        $user_superior = Db::table('jh_user_superior')->where('uid', $post['uid'])->field('*')->find();
        if ($user_superior['anchor']) {
            $this->error('主播账号，无法修改', [], 201);
        }

        $arr = ['touch' => date('Y-m-d H:i:s', time())];

        if ($post['anchor'] >= 0) {
            $arr['anchor'] = $post['anchor'];
        }

        if ($post['anchor'] > 0) {
            $post['rtp'] = 11;
        }

        if ($post['rtp'] > 0) {
            $arr['rtp'] = $post['rtp'];
        }

        Db::table('jh_user_superior')->where('uid', $post['uid'])->update($arr);
        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '修改用户rtp',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $post);
    }

    /*///////////////////////////////////////////////////
    * 用户报表
    * url：{baseurl}/back/user/funds
    * */
    public function funds()
    {
        $time1 = microtime(true);
        $post = input('post.');
        if (empty($post['limit']) || !isset($post['page'])) {
            $this->error('缺少参数', [], 201);
        }

        if (empty($post['platform_id'])) {
            $this->error('请选择平台id后查询', [], 201);
        }

        $where = 'u.platform="' . $post['platform_id'] . '"';
        if (!empty($post['time'])) {
            $end = date('Y-m-d H:i:s', strtotime($post['time'] + 24 * 60 * 60));
            $where .= ' and a.created>="' . $post['time'] . '" and a.created<="' . $end . '"';
        }

        if (!empty($post['min_win'])) {
            $where .= ' and a.all_get>="' . $post['min_win'] . '"';
        }

        $user = session('login_user');
        if ($user['level'] == 2) {
            $where .= ' AND (s.superior = "' . $user['username'] .'" OR s.last_superior="' . $user['username'] . '")';
        } elseif ($user['level'] >= 3) {
            $where .= ' AND s.superior = "' . $user['username'] . '"';
        }

        $log_title = $post['page'] > 0 ? '查询用户报表' : '查询并导出用户报表';
        $page = $post['page'] > 0 ? $post['page'] : 1;
        $count = Db::table('jh_user_dayfunds')->alias('a')
            ->join('jili.jh_user_register u', 'a.uid=u.uid')
            ->join('jili.jh_user_superior s', 's.uid=a.uid')
            ->where($where)->field('a.uid,u.platform,all_bet,all_win,a.time')->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($page - 1) * $post['limit'] . ',' . $post['limit'];
        $list = Db::table('jh_user_dayfunds')->alias('a')
            ->join('jili.jh_user_register u', 'a.uid=u.uid')
            ->join('jili.jh_user_superior s', 's.uid=a.uid')
            ->where($where)->field('a.uid,u.platform,all_bet,all_win,a.time')->order('a.time', 'desc')->limit($limit)->select();
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
            $data = Db::table('jh_user_dayfunds')->alias('a')
                ->join('jili.jh_user_register u', 'a.uid=u.uid')
                ->join('jili.jh_user_superior s', 's.uid=a.uid')
                ->where($where)->field('a.uid,u.platform,all_bet,all_win,a.time')->select();
            $excel->downloadExcel('用户报表', [['platform', '平台ID'], ['uid', '用户'], ['all_bet', '玩家总投注'], ['all_win', '玩家总中奖'], ['time', '时间']], $data);
        }

        $this->success('成功', [
            'total_page' => $total_page,
            'total_count' => $count,
            'list' => $res
        ]);
    }
}


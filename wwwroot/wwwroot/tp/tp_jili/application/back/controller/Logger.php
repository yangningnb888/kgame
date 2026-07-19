<?php

namespace app\back\controller;

use app\back\model\System_Log;
use think\Controller;
use think\Db;
use think\Log;
class Logger extends Controller
{
    /*///////////////////////////////////////////////////
    * 操作日志
    * url：{baseurl}/back/logger
    * */
    public function index()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (empty($post['username'])) {
            $count = Db::table('system_log')->field('username,ip,ip_pos,browser,created')->count();
            $total_page = ceil($count / $post['limit']);
            $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
            $list = Db::table('system_log')->field('username,ip,ip_pos,browser,created')->order('created', 'desc')->limit($limit)->select();
            $res = [];
            if ($list) {
                foreach ($list as $k => $v) {
                    $res[] = $v;
                }
            }
        } else {
            $count = Db::table('system_log')->where('username', $post['username'])->field('username,ip,ip_pos,browser,created')->count();
            $total_page = ceil($count / $post['limit']);
            $limit = ($post['page'] - 1) * $post['limit'] . ',' . $post['limit'];
            $list = Db::table('system_log')->where('username', $post['username'])->field('username,ip,ip_pos,browser,created')->order('created', 'desc')->limit($limit)->select();
            $res = [];
            if ($list) {
                foreach ($list as $k => $v) {
                    $res[] = $v;
                }
            }
        }

        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '查询游戏列表',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', [
            'total_page' => $total_page,
            'total_count' => $count,
            'list' => $res
        ]);
    }
}
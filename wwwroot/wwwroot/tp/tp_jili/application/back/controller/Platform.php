<?php

namespace app\back\controller;

use app\back\model\System_Log;
use think\Controller;
use think\Db;
use think\Loader;
use think\Log;

class Platform extends Controller
{
    /*///////////////////////////////////////////////////
    * 游戏平台
    * url：{baseurl}/back/platform
    * */
    public function index()
    {
        $post = input('post.');
        if (empty($post['limit']) || !isset($post['page'])) {
            $this->error('缺少参数', [], 201);
        }

        $time1 = microtime(true);
        $log_title = $post['page'] > 0 ? '查询游戏平台' : '查询并导出游戏平台';
        $page = $post['page'] > 0 ? $post['page'] : 1;
        $count = Db::table('jh_platform_info')->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($page - 1) * $post['limit'] . ',' . $post['limit'];
        $data = Db::table('jh_platform_info')->limit($limit)->select();
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
            $data = Db::table('jh_platform_info')->select();
            $excel->downloadExcel('游戏平台', [['id', 'id'], ['key', 'key'], ['value', 'value'], ['created', '游戏状态'], ['createTime', '创建时间'], ['update', 'updateTime']], $data);
        }
        $this->success('成功', ['total_page' => $total_page, 'total_count' => $count, 'list' => $data]);
    }
}
<?php

namespace app\back\controller;

use app\back\model\System_Log;
use think\Controller;
use think\Db;
use think\Loader;
use think\Log;

//引入模型层
class Game extends Controller
{

    /*///////////////////////////////////////////////////
    * 游戏列表
    * url：{baseurl}/back/game
    * */
    public function index()
    {
        $post = input('post.');
        if (empty($post['limit']) || !isset($post['page'])) {
            $this->error('缺少参数', [], 201);
        }

        $time1 = microtime(true);
        $where = 'id!=""';
        if (!empty($post['gtype'])) {
            $where .= ' and gtype = "' . $post['gtype'] . '"';
        }

        if (!empty($post['gamename'])) {
            $where .= ' and gamename like "%' . $post['gamename'] . '%"';
        }

        if (isset($post['status']) && is_numeric($post['status'])) {
            $where .= ' and status = ' . $post['status'];
        }

        $log_title = $post['page'] > 0 ? '查询游戏列表' : '查询并导出游戏列表';
        $page = $post['page'] > 0 ? $post['page'] : 1;
        $count = Db::table('jh_game_status')->where($where)->count();
        $total_page = ceil($count / $post['limit']);
        $limit = ($page - 1) * $post['limit'] . ',' . $post['limit'];
        $data = Db::table('jh_game_status')->where($where)->order('created')->limit($limit)->select();

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
            $data = Db::table('jh_game_status')->where($where)->field('gtype,status,created,touch,gamename,othername')->select();
            $excel->downloadExcel('游戏列表', [['gtype', 'ID'], ['gamename', '游戏名称'], ['othername', '游戏英文名称'], ['status', '游戏状态'], ['created', '创建时间'], ['touch', '修改时间']], $data);
        }

        $this->success('成功', ['list' => $data, 'total_page' => $total_page, 'total_count' => $count,]);
    }

    /*///////////////////////////////////////////////////
    * 游戏名称及id
    * url：{baseurl}/back/game/list
    * */
    public function list()
    {
        $time1 = microtime(true);
        $res = Db::table('jh_game_status')->field('gtype,gamename')->order('id')->select();
        $data = [];
        foreach ($res as $key => $value) {
            $data[$value['gtype']] = $value['gamename'];
        }

        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '查询游戏名称及id',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $data);
    }

    /*///////////////////////////////////////////////////
    * 修改游戏状态
    * url：{baseurl}/back/game/touch
    * */
    public function touch()
    {
        $post = input('post.');
        $time1 = microtime(true);
        if (!isset($post['gtype']) || !isset($post['status'])) {
            $this->error('缺少参数', [], 201);
        }

        $res = Db::table('jh_game_status')->field('*')->where('gtype', $post['gtype'])->find();
        if (!$res) {
            $this->error('游戏不存在', [], 201);
        }

        $status = $post['status'] ? 1 : 0;
        Db::table('jh_game_status')->where('gtype', $post['gtype'])->update(['status' => $status]);
        $system_log = new System_Log();
        $time = ceil(microtime(true)) - $time1 + 2;
        $save = [
            'behavior' => '修改游戏状态',
            'times' => $time,
        ];
        $system_log->saveData($save);
        $this->success('成功', $post);
    }
}

<?php

namespace app\api\controller;

use think\Controller;
use think\Db;

/**
 * 游戏弹窗公告只读接口（免登录）
 * 前端 h5/63、h5/728 在登录后调用本接口拉取公告，
 * 仅在首次或后台修改文案(hash 变化)时才弹窗。
 * Class Notice
 * @package app\api\controller
 */
class Notice extends Controller
{
    public function _initialize()
    {
        // 公告为公开信息，无需登录校验（与 Register/Game 接口一致，_initialize 留空）
    }

    /**
     * 获取弹窗公告
     * 返回: {"status":1,"data":{"title":"","date":"","content":"","hash":"md5"}}
     *       无公告时 {"status":0,"data":null}
     */
    public function popup()
    {
        $row = Db::table('jh_sysconfig')->where('key', 'GAME_POPUP_NOTICE')->value('val');
        $data = $row ? @json_decode($row, true) : null;

        if (!$data || empty($data['content'])) {
            return json(['status' => 0, 'data' => null]);
        }

        $hash = md5(($data['title'] ?? '') . '|' . ($data['date'] ?? '') . '|' . ($data['content'] ?? ''));

        return json([
            'status' => 1,
            'data'   => [
                'title'   => $data['title'] ?? '',
                'date'    => $data['date'] ?? '',
                'content' => $data['content'] ?? '',
                'hash'    => $hash,
            ],
        ]);
    }
}

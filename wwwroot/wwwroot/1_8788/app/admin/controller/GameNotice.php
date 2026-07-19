<?php

// +----------------------------------------------------------------------
// | 游戏弹窗公告（公告通知）
// +----------------------------------------------------------------------
// | 数据存 `63` 库 jh_sysconfig 表（键 GAME_POPUP_NOTICE），
// | 值格式 JSON: {"title":"...","date":"...","content":"..."}
// | 游戏前端(h5/63、h5/728)通过 tp_63 的 /api/notice/popup 接口拉取，
// | 仅在「首次进入」或「后台修改了文案(内容 hash 变化)」时弹窗。
// | 修改后立即生效，玩家下次登录即可看到，无需改任何前端静态文件。
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 游戏弹窗公告（公告通知）
 * Class GameNotice
 * @package app\admin\controller
 */
class GameNotice extends Controller
{
    // 游戏服读取的公告键（与 tp_63/api/Notice.php 保持一致）
    const KEY = 'GAME_POPUP_NOTICE';

    /**
     * 游戏弹窗公告管理（页面展示）
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '公告通知';
        $row  = Db::table('jh_sysconfig')->where('key', self::KEY)->value('val');
        $data = $row ? json_decode($row, true) : ['title' => '', 'date' => '', 'content' => ''];
        $this->assign('data', $data);
        // 视图目录名为 gamenotice（无下划线），显式指定，避免框架按路由 game_notice 去找 view/game_notice/ 而 404
        $this->fetch('gamenotice/index');
    }

    /**
     * 保存弹窗公告（独立 action，供前端 JS 直接 POST）
     * @auth true
     */
    public function save()
    {
        try {
            $title   = trim(input('post.title', ''));
            $date    = trim(input('post.date', ''));
            $content = input('post.content', ''); // 正文允许换行，不 trim
            $val = json_encode([
                'title'   => $title,
                'date'    => $date,
                'content' => $content,
            ], JSON_UNESCAPED_UNICODE);

            $exists = Db::table('jh_sysconfig')->where('key', self::KEY)->count();
            if ($exists) {
                Db::table('jh_sysconfig')->where('key', self::KEY)->update(['val' => $val]);
            } else {
                Db::table('jh_sysconfig')->insert(['key' => self::KEY, 'val' => $val]);
            }
            return json(['code' => 1, 'msg' => '保存成功']);
        } catch (\Throwable $e) {
            // 任何数据库/写入异常都返回 JSON 错误信息，而不是 500 页面（避免前端解析失败）
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
}

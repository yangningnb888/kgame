<?php

namespace app\admin\controller;

use app\admin\Controller;
use think\admin\service\AdminService;
use think\facade\Db;

/**
 * 公告通知（对应游戏内弹窗公告）
 * @class GameNotice
 * @module admin
 * @auth true
 * @menu true
 */
class GameNotice extends Controller
{
    /**
     * 游戏弹窗公告读取的键
     * 63JHLM 游戏大厅通过 DBInstance::GetAgents() 读此键。
     */
    const KEY = 'OFF_MES';

    /**
     * 公告展示页
     * @auth true
     * @menu true
     */
    public function index()
    {
        $data = Db::table('jh_sysconfig')->where('key', self::KEY)->value('val') ?: '';
        $this->assign('content', $data);
        return $this->fetch('gamenotice/index');
    }

    /**
     * 保存公告
     * @auth true
     */
    public function save()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 0, 'msg' => '非法请求']);
        }

        $content = $this->request->post('content', '', 'trim');
        if ($content === '') {
            return json(['code' => 0, 'msg' => '公告内容不能为空']);
        }

        try {
            $exists = Db::table('jh_sysconfig')->where('key', self::KEY)->count();
            if ($exists) {
                Db::table('jh_sysconfig')->where('key', self::KEY)->update(['val' => $content]);
            } else {
                Db::table('jh_sysconfig')->insert([
                    'key'   => self::KEY,
                    'name'  => '弹窗公告',
                    'val'   => $content,
                ]);
            }
            return json(['code' => 1, 'msg' => '保存成功，玩家重新登录或重新连接大厅后生效']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
}

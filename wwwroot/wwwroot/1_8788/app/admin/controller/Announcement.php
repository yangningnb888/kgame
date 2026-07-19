<?php

// +----------------------------------------------------------------------
// | 游戏公告管理
// +----------------------------------------------------------------------
// | 直接读写 `63` 库的 jh_sysconfig 表（游戏服读取的 4 个公告键），
// | 修改后立即生效，玩家下次登录即可看到，无需重启任何服务。
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\admin\Controller;
use think\facade\Db;

/**
 * 游戏公告管理
 * Class Announcement
 * @package app\admin\controller
 */
class Announcement extends Controller
{
    // 游戏服读取的 4 个公告键
    const KEY_MARQUEE_TEXT = 'OFF_MES';        // 跑马灯文字
    const KEY_MARQUEE_IMG  = 'OFF_MES_IMG';   // 跑马灯图片（JSON 数组）
    const KEY_AGENT_TEXT    = 'YOU_AGENT';      // 代理公告文字
    const KEY_AGENT_IMG     = 'YOU_AGENT_IMG'; // 代理公告图片（JSON 数组）

    /**
     * 游戏公告管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title = '游戏公告管理';
        $this->marquee_text = $this->_lines(self::KEY_MARQUEE_TEXT);
        $this->marquee_imgs = $this->_imgs(self::KEY_MARQUEE_IMG);
        $this->agent_text = $this->_lines(self::KEY_AGENT_TEXT);
        $this->agent_imgs = $this->_imgs(self::KEY_AGENT_IMG);
        // 预连接好的多行文本，直接输出到 textarea
        $this->marquee_text_val = implode("\n", $this->marquee_text);
        $this->agent_text_val = implode("\n", $this->agent_text);
        $this->fetch();
    }

    // 读取文字类公告（按行拆分成多条）
    private function _lines($key)
    {
        $val = Db::table('jh_sysconfig')->where('key', $key)->value('val');
        if ($val === null || $val === '') {
            return [];
        }
        $arr = explode("\n", str_replace(["\r\n", "\r"], "\n", $val));
        return array_values(array_filter($arr, function ($v) {
            return trim($v) !== '';
        }));
    }

    // 读取图片类公告（JSON 数组）
    private function _imgs($key)
    {
        $val = Db::table('jh_sysconfig')->where('key', $key)->value('val');
        if (!$val) {
            return [];
        }
        $arr = json_decode($val, true);
        return is_array($arr) ? $arr : [];
    }

    // 写入键值（键存在则更新，不存在则插入）
    private function _set($key, $val)
    {
        $exists = Db::table('jh_sysconfig')->where('key', $key)->count();
        if ($exists) {
            Db::table('jh_sysconfig')->where('key', $key)->update(['val' => $val]);
        } else {
            Db::table('jh_sysconfig')->insert(['key' => $key, 'val' => $val]);
        }
        return json(['code' => 0, 'msg' => '保存成功']);
    }

    // 保存跑马灯文字（多行 = 多条公告）
    public function saveMarquee()
    {
        $lines = input('post.lines/a', []);
        $text = implode("\n", array_filter($lines, function ($v) {
            return trim($v) !== '';
        }));
        return $this->_set(self::KEY_MARQUEE_TEXT, $text);
    }

    // 添加跑马灯图片
    public function addMarqueeImg()
    {
        $url = trim(input('post.url', ''));
        if (!$url) {
            return json(['code' => 1, 'msg' => '图片地址不能为空']);
        }
        $imgs = $this->_imgs(self::KEY_MARQUEE_IMG);
        $imgs[] = $url;
        return $this->_set(self::KEY_MARQUEE_IMG, json_encode($imgs, JSON_UNESCAPED_UNICODE));
    }

    // 删除跑马灯图片
    public function delMarqueeImg()
    {
        $idx = intval(input('post.idx', -1));
        $imgs = $this->_imgs(self::KEY_MARQUEE_IMG);
        if ($idx >= 0 && $idx < count($imgs)) {
            array_splice($imgs, $idx, 1);
        }
        return $this->_set(self::KEY_MARQUEE_IMG, json_encode($imgs, JSON_UNESCAPED_UNICODE));
    }

    // 保存代理公告文字
    public function saveAgent()
    {
        $lines = input('post.lines/a', []);
        $text = implode("\n", array_filter($lines, function ($v) {
            return trim($v) !== '';
        }));
        return $this->_set(self::KEY_AGENT_TEXT, $text);
    }

    // 添加代理公告图片
    public function addAgentImg()
    {
        $url = trim(input('post.url', ''));
        if (!$url) {
            return json(['code' => 1, 'msg' => '图片地址不能为空']);
        }
        $imgs = $this->_imgs(self::KEY_AGENT_IMG);
        $imgs[] = $url;
        return $this->_set(self::KEY_AGENT_IMG, json_encode($imgs, JSON_UNESCAPED_UNICODE));
    }

    // 删除代理公告图片
    public function delAgentImg()
    {
        $idx = intval(input('post.idx', -1));
        $imgs = $this->_imgs(self::KEY_AGENT_IMG);
        if ($idx >= 0 && $idx < count($imgs)) {
            array_splice($imgs, $idx, 1);
        }
        return $this->_set(self::KEY_AGENT_IMG, json_encode($imgs, JSON_UNESCAPED_UNICODE));
    }
}

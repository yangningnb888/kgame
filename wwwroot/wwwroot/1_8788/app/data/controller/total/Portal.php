<?php

namespace app\data\controller\total;

use think\admin\Controller;
use think\facade\Db;

/**
 * 商城数据报表
 * Class Portal
 * @package app\data\controller\total
 */
class Portal extends Controller
{
    /**
     * 商城数据报表
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->agentNum = Db::table('jh_register')->where('uid<10000000 AND telephone!=""')->count();
        $this->playerNum = Db::table('jh_user')->where('uid>10000000 AND type=0 AND agent=2')->count();
        $this->inconme = Db::table('jh_user_profit')->where('uid<10000000 AND formuid>10000000 AND type=1 AND currency=1')->sum('num');
        $this->expenditure = Db::table('jh_user_profit')->where('uid<10000000 AND formuid>10000000 AND type=2 AND currency=1')->sum('num');
        // 近十天的用户及交易趋势
        $this->days = [];
        if (empty($this->days)) {
            for ($i = 15; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i}days"));
                $this->days[] = [
                    '当天日期' => date('m-d', strtotime("-{$i}days")),
                    '增加玩家' => Db::table('jh_user')->where('type', 0) ->where('agent', 2)->whereLike('created', "{$date}%")->count(),
                    '邮件数量' => Db::table('jh_mail')->whereLike('created', "{$date}%")->count(),
                    '下分数量' => Db::table('jh_mail')->where('uid<10000000 AND outuid>10000000 AND currency=1')->whereLike('created', "{$date}%")->sum('number'),
                    '上分数量' => Db::table('jh_mail')->where('uid>10000000 AND outuid<10000000 AND currency=1')->whereLike('created', "{$date}%")->sum('number'),
                ];
            }
            $this->app->cache->set('portals', $this->days, 60);
        }
        $this->fetch();
    }
}
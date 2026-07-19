<?php

namespace app\data\controller\game;

use app\data\model\ShopGoodsCate;
use think\admin\Controller;
use think\admin\extend\DataExtend;
use think\facade\Db;
use think\facade\Request;
use think\facade\Session;

/**
 * 商品分类管理
 * Class Cate
 * @package app\data\controller\shop
 */
class Data extends Controller
{

    public function initialize()
    {
        //判断是否存在登录session
        //如果username不存在，且Islogin不等于1，重新调回登录页面
        if (!Session::get('user')) {
            //跳转回登录页面
            $this->error('您还没有登录，请登录', 'admin/login/index');
        }
    }

    /**
     * 修改游戏参数
     * @auth true
     */
    public function index()
    {
        $this->title = "数据修改";
        $user = Session::get('user');
        $this->display = 'block';
        if (empty($user['username']) || $user['username'] != 'admin') {
            $this->display = 'none';
        }
        $this->fetch();
    }

    public function gameseession()
    {
        $post = input('post.');
        $data = Db::query('SELECT jh_gamename.gtype,`name` FROM jh_gamename INNER JOIN jh_gamestatus ON jh_gamename.gtype=jh_gamestatus.gtype WHERE seession=' . $post['value']);
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $data
        ]);
    }

    public function createagent()
    {
        $post = input('post.');
        $start = $post['startuid'];
        $num = $post['num'];
        $gold = $post['gold'];
        $rcard = $post['rcard'];
        $cpower = $post['cpower'];
        $string = '';

        for ($i = 0; $i < $num; $i++) {
            $_res = Db::query('SELECT * FROM jh_user WHERE uid=' . $start);
            if (!$_res) {
                $pass = rand(100000, 999999);
                $ret = Db::name('jh_user')->insert([
                    'uid' => $start,
                    'gold' => 0,
                    'bank' => $gold,
                    'mcard' => 0,
                    'rcard' => $rcard,
                    'rcardtime' => 0,
                    'bankpass' => '888888',
                    'last_time' => date('Y-m-d H:i:s', time()),
                    'last_ip' => '0.0.0.0',
                    'battery' => 0,
                    'pictureframe' => 0,
                    'headimgurl' => rand(1, 90),
                    'nickname' => '代理' . $start % 100,
                    'sex' => 1,
                    'type' => 0,
                    'online' => 2,
                    'status' => 1,
                    'display' => 1,
                    'created' => date('Y-m-d H:i:s', time()),
                    'agent' => 1,
                ]);

                if ($ret) {
                    Db::name('jh_register')->insert([
                        'uid' => $start,
                        'created' => date('Y-m-d H:i:s', time()),
                        'password' => $pass,
                        'status' => 1,
                    ]);

                    Db::name('jh_agent')->insert([
                        'uid' => $start,
                        'extension' => 0,
                        'power' => $cpower,
                    ]);
                    $string .= 'uid:' . $start . '          pass:' . $pass . "\r\n";
                }
            }
            $start++;
        }

        return json([
            'code' => 0,
            'msg' => '',
            'data' => $string
        ]);
    }

    public function updategameinfo()
    {
        $post = input('post.');
        if (isset($post['status']) || isset($post['percent']) || isset($post['profit'])) {
            if ($post['gtype']) {
                $gtypes = [$post['gtype']];
            } else {
                $gtypes = Db::query('SELECT gtype FROM jh_gamestatus WHERE seession=' . $post['seession']);
                $gtypes = array_column($gtypes, 'gtype');
            }

            if (isset($post['status']) && $post['status']) {
                foreach ($gtypes as $key => $value) {
                    Db::table('jh_gamestatus')->where('gtype', $value)->update(['status' => $post['status']]);
                }
            }


            foreach ($gtypes as $value) {
                $update = [];
                if (isset($post['percent']) && $post['percent']) {
                    $controls = Db::table('jh_game_config')->field('controls')->where('gtype', $value)->find();
                    $controls = json_decode($controls['controls'], true);
                    if (isset($controls['percent']) && is_numeric($controls['percent'])) {
                        $controls['percent'] = $post['percent'];
                        $update['controls'] = json_encode($controls);
                    }
                }

                if (isset($post['profit']) && is_numeric($post['profit'])) {
                    $update['profit'] = $post['profit'];
                }

                if ($update) {
                    Db::table('jh_game_config')->where('gtype', $value)->update($update);
                }
            }
        }

        return json([
            'code' => 0,
            'msg' => '',
            'data' => []
        ]);

    }

    /**
     * 修改玩家信息
     * @auth true
     */
    public function updateuserinfo()
    {
        $post = input('post.');
        $_res = Db::query('SELECT * FROM jh_user WHERE uid=' . $post['upuid']);
        if ($_res) {
            if (!empty($post['upgold']) || !empty($post['uprcard'])) {
                $post['upgold'] = $post['upgold'] ?? 0;
                $post['uprcard'] = $post['uprcard'] ?? 0;
                Db::query('UPDATE jh_user SET `bank`=`bank`+' . $post['upgold'] . ',`rcard`=`rcard`+' . $post['uprcard'] . ' WHERE uid=' . $post['upuid']);
            }

            if (!empty($post['uptel'])) {
                $_res = Db::query('SELECT * FROM jh_register WHERE telephone=' . $post['uptel']);
                if ($_res) {
                    $this->error('修改失败，手机号码重复使用');
                    return false;
                }

                $_res = Db::query('SELECT * FROM jh_register WHERE uid=' . $post['upuid']);
                if (!$_res) {
                    $this->error('该用户目前未绑定手机号');
                    return false;
                }

                Db::query('UPDATE jh_register SET telephone=' . $post['uptel'] . ' WHERE uid=' . $post['upuid']);
            }

            if (!empty($post['upsuperior'])) {
                $_res = Db::query('SELECT * FROM jh_user_superior WHERE uid=' . $post['upuid']);
                if (!$_res) {
                    Db::name('jh_user_superior')->insert([
                        'uid' => $post['upuid'],
                        'superior' => $post['upsuperior'],
                        'flagget' => 0,
                        'curget' => 0,
                        'control' => 0,
                        'createtime' => date('Y-m-d H:i:s', time()),
                        'usecard' => 0,
                    ]);
                } else {
                    Db::query('UPDATE jh_user_superior SET superior=' . $post['upsuperior'] . ' WHERE uid=' . $post['upuid']);
                }
            }

            if (isset($post['upcontrol']) && isset($post['upflag']) && $post['upcontrol'] > -2 && $post['upcontrol'] < 2 && is_numeric($post['upflag'])) {
                Db::query('UPDATE jh_user_superior SET control=' . $post['upcontrol'] . ' AND curget=0 AND flagget=' . $post['upflag'] . ' WHERE uid=' . $post['upuid']);
            }

            return json([
                'code' => 0,
                'msg' => '',
                'data' => $_res
            ]);
        }

        return json([
            'code' => 0,
            'msg' => '',
            'data' => $post
        ]);
    }

    public function banuser()
    {
        $post = $_POST;
        $where = '';
        foreach ($post as $key => $value) {
            if ($key == 'status' || empty($value)) {
                continue;
            }

            if ($where) {
                $where .= ' AND ' . $key . '="' . $value . '"';
            } else {
                $where .= $key . '="' . $value . '"';
            }
        }

        if (isset($post['last_ip']) && !empty($post['last_ip'])) {
            $_res = Db::table('jh_banuser')->field('id,val')->where('val', $post['last_ip'])->find();
            if ($_res) {
                Db::table('jh_banuser')->where('val', $post['last_ip'])->update(['status' => $post['status']]);
            } else {
                Db::name('jh_banuser')->insert([
                    'val' => $post['last_ip'],
                    'status' => $post['status']
                ]);
            }
        }

        if (isset($post['equipmentcard']) && !empty($post['equipmentcard'])) {
            $_res = Db::table('jh_banuser')->field('id,val')->where('val', $post['equipmentcard'])->find();
            if ($_res) {
                Db::table('jh_banuser')->where('val', $post['equipmentcard'])->update(['status' => $post['status']]);
            } else {
                Db::name('jh_banuser')->insert([
                    'val' => $post['equipmentcard'],
                    'status' => $post['status']
                ]);
            }
        }

        Db::table('jh_user')->where($where)->update(['status' => $post['status']]);
        return json([
            'code' => 0,
            'msg' => '',
            'data' => []
        ]);
    }

    public function agentpower()
    {
        $post = input('post.');

        if ($post['power'] >= 0 && $post['power'] <= 2) {
            Db::table('jh_agent')->where('uid=' . $post['agent'])->update(['power' => $post['power']]);
            $this->error('修改成功！');
        } else {
            $this->error('修改失败！');
        }
    }

    public function setwh()
    {
        $post = input('post.');
        Db::table('jh_sysconfig')->where('id=14')->update(['val' => $post['start']]);
        Db::table('jh_sysconfig')->where('id=15')->update(['val' => $post['end']]);
        return json([
            'code' => 0,
            'msg' => '',
            'data' => []
        ]);
    }

    /**
     * 直接给玩家加金币（写入 jh_user.gold，非 bank）
     * 支持按 uid 或手机号查询
     */
    public function addgold()
    {
        $post = input('post.');
        $account = trim($post['account'] ?? '');
        $amount = intval($post['amount'] ?? 0);
        if ($amount <= 0) {
            $this->error('金币数量必须大于0');
            return;
        }
        if (preg_match('/^1[3-9]\d{9}$/', $account)) {
            $reg = Db::name('jh_register')->where('telephone', $account)->find();
            if (empty($reg)) {
                $this->error('该手机号未注册');
                return;
            }
            $uid = $reg['uid'];
        } elseif (preg_match('/^\d+$/', $account)) {
            $uid = $account;
        } else {
            $this->error('账号格式错误，请填 uid 或手机号');
            return;
        }
        $user = Db::name('jh_user')->where('uid', $uid)->find();
        if (empty($user)) {
            $this->error('用户不存在');
            return;
        }
        Db::name('jh_user')->where('uid', $uid)->inc('gold', $amount)->update();
        $new = Db::name('jh_user')->where('uid', $uid)->value('gold');
        return json([
            'code' => 0,
            'msg' => '加金币成功，当前金币：' . $new,
            'data' => ['uid' => $uid, 'gold' => $new]
        ]);
    }
}
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
        $value = intval($post['value'] ?? 0);
        $data = Db::query('SELECT jh_gamename.gtype,`name` FROM jh_gamename INNER JOIN jh_gamestatus ON jh_gamename.gtype=jh_gamestatus.gtype WHERE seession=' . $value);
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $data
        ]);
    }

    public function createagent()
    {
        $post = input('post.');
        $start = intval($post['startuid'] ?? 0);
        $num = intval($post['num'] ?? 0);
        $gold = intval($post['gold'] ?? 0);
        $rcard = intval($post['rcard'] ?? 0);
        $cpower = intval($post['cpower'] ?? 0);

        if ($start <= 0 || $num <= 0) {
            return json(['code' => 1, 'msg' => '初始uid和生成数量都必须为正整数', 'data' => '']);
        }
        if ($num > 100) {
            return json(['code' => 1, 'msg' => '每次生成不能超过100个账号', 'data' => '']);
        }
        if ($start > 9999999) {
            return json(['code' => 1, 'msg' => '生成代理uid不得超过7位', 'data' => '']);
        }
        if ($cpower < 0 || $cpower > 2) {
            $cpower = 0;
        }

        $string = '';
        for ($i = 0; $i < $num; $i++) {
            $uid = $start + $i;
            $_res = Db::table('jh_user')->where('uid', $uid)->find();
            if (empty($_res)) {
                $pass = rand(100000, 999999);
                try {
                    Db::name('jh_user')->insert([
                        'uid' => $uid,
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
                        'nickname' => '代理' . ($uid % 100),
                        'sex' => 1,
                        'type' => 0,
                        'online' => 2,
                        'status' => 1,
                        'display' => 1,
                        'created' => date('Y-m-d H:i:s', time()),
                        'agent' => 1,
                    ]);
                    Db::name('jh_register')->insert([
                        'uid' => $uid,
                        'created' => date('Y-m-d H:i:s', time()),
                        'password' => (string)$pass,
                        'status' => 1,
                    ]);
                    Db::name('jh_agent')->insert([
                        'uid' => $uid,
                        'extension' => 0,
                        'power' => $cpower,
                    ]);
                    $string .= 'uid:' . $uid . '          pass:' . $pass . "\r\n";
                } catch (\Exception $e) {
                    // 单个账号写入失败（如唯一键冲突）跳过，继续下一个
                    continue;
                }
            }
        }

        if ($string === '') {
            return json(['code' => 1, 'msg' => '未生成任何账号（uid可能已存在或参数有误）', 'data' => '']);
        }
        return json(['code' => 0, 'msg' => '生成成功', 'data' => $string]);
    }

    public function updategameinfo()
    {
        $post = input('post.');
        if (isset($post['status']) || isset($post['percent']) || isset($post['profit'])) {
            if ($post['gtype']) {
                $gtypes = [$post['gtype']];
            } else {
                $seession = intval($post['seession'] ?? 0);
                $gtypes = Db::query('SELECT gtype FROM jh_gamestatus WHERE seession=' . $seession);
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
                $goldInc = intval($post['upgold'] ?? 0);
                $rcardInc = intval($post['uprcard'] ?? 0);
                $q = Db::table('jh_user')->where('uid', $post['upuid']);
                if ($goldInc != 0) {
                    $q->inc('bank', $goldInc);
                }
                if ($rcardInc != 0) {
                    $q->inc('rcard', $rcardInc);
                }
                $q->update();
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
                Db::table('jh_user_superior')->where('uid', $post['upuid'])->update([
                    'control' => intval($post['upcontrol']),
                    'curget' => 0,
                    'flagget' => intval($post['upflag'])
                ]);
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
        $whereArr = [];
        foreach (['uid', 'equipmentcard', 'last_ip'] as $key) {
            if (!empty($post[$key])) {
                $whereArr[$key] = $post[$key];
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

        if (!empty($whereArr)) {
            Db::table('jh_user')->where($whereArr)->update(['status' => $post['status']]);
        }
        return json([
            'code' => 0,
            'msg' => '',
            'data' => []
        ]);
    }

    public function agentpower()
    {
        $post = input('post.');

        $agent = intval($post['agent'] ?? 0);
        $power = intval($post['power'] ?? -1);
        if ($agent > 0 && $power >= 0 && $power <= 2) {
            Db::table('jh_agent')->where('uid', $agent)->update(['power' => $power]);
            $this->success('修改成功！');
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
<?php

namespace app\control\controller;

use think\Db;


class Dfdc
{
    public function index()
    {
        $datajson = array(
            'status' => 1,
            'msg' => "",
            'data' => [
                'userlist' => [],
                'maplist' => []
            ]
        );
        $list = Db::name('jh_user')->field('level,jh_user.uid,nickname,gold,bank')->join('jh_user_possition', 'jh_user.uid = jh_user_possition.uid')->where('online', 1)->where('gtype', 26)->select();
        $data = [];
        foreach ($list as $key => $value) {
            $flag = Db::name('jh_user_superior')->field('flagget,curget,control')->where('uid', $value['uid'])->find();
            $used = Db::name('jh_arcade_bet')->field('used')->where('uid', $value['uid'])->find();

            if (isset($flag['flagget']) && isset($flag['curget'])) {
                $_flag = abs($flag['flagget'] - $flag['curget']) * $flag['control'];
            } else {
                $_flag = 0;
            }

            $data[] = [
                'uid' => $value['uid'],
                'nickname' => $value['nickname'],
                'level' => $value['level'],
                'gold' => $value['gold'],
                'bank' => $value['bank'],
                'flag' => $_flag,
                'used' => $used['used'] ?? 0
            ];
        }

        $datajson['data']['userlist'] = $data;

        $_arr = [
            2 => [
                5 => '龙(100 200 1000)',
                6 => '船(20 100 500)',
                7 => '鱼(100 200 1000)',
                8 => '元宝(25 50 250)',
                9 => '10次免费(5 10 50)',
                10 => '铜钱(10 20 100)',
                11 => '奖池',
                12 => 'A(3 10 50)',
                13 => 'K(3 10 50)',
                14 => 'Q(3 10 50)',
                15 => 'J(3 10 50)',
                16 => '10(3 10 50)',
                17 => '9(3 10 50)',
            ],
            3 => [
                5 => '凤凰(68 128 680)',
                6 => '莲花(38 88 380)',
                7 => '宝石(28 68 280)',
                8 => '戒指(28 68 180)',
                9 => '10次免费(5 10 50)',
                10 => '花朵(8 28 128)',
                11 => '奖池',
                12 => 'A(5 8 15)',
                13 => 'K(5 8 15)',
                14 => 'Q(5 8 15)',
                15 => 'J(5 8 15)',
                16 => '10(5 8 15)',
                17 => '9(5 8 15)',
            ],
            4 => [
                5 => '熊猫(100 150 750)',
                6 => '玄武(50 100 450)',
                7 => '金蟾(40 80 300)',
                8 => '鱼(20 50 200)',
                9 => '10次免费(5 10 50)',
                10 => '鞭炮(10 25 138)',
                11 => '奖池',
                12 => 'A(5 10 30)',
                13 => 'K(5 10 30)',
                14 => 'Q(5 10 25)',
                15 => 'J(5 10 25)',
                16 => '10(5 10 20)',
                17 => '9(5 10 20)',
            ],
            5 => [
                5 => '麒麟(100 200 800)',
                6 => '船(50 100 400)',
                7 => '发财树(30 75 200)',
                8 => '元宝(25 50 150)',
                9 => '10次免费(5 10 50)',
                10 => '铜钱(15 25 100)',
                11 => '奖池',
                12 => 'A(5 10 20)',
                13 => 'K(5 10 20)',
                14 => 'Q(5 10 20)',
                15 => 'J(5 10 15)',
                16 => '10(5 10 15)',
                17 => '9(5 10 15)',
            ],
        ];
        $_ret = Db::table('jh_control_maps')->field('id,uid,level,value')->where('gtype', 26)->select();
        $data = [];
        foreach ($_ret as $key => $value) {
            $_value = json_decode($value['value'], true);
            $data[$key] = [
                'id' => $value['id'],
                'uid' => $value['uid'],
                'level' => $value['level'],
                'type' => $_arr[$value['level']][$_value['logo']],
                'num' => $_value['num'],
                'jackpot' => $_value['jackpot'] ?? 0,
            ];
        }
        $datajson['data']['maplist'] = $data;

        return json($datajson);
    }

    public function addresult()
    {
        $post = input('post.');
        $datajson = array(
            'status' => '0',
            'msg' => "数据格式错误",
        );

        if (!is_numeric($post['uid']) || !is_numeric($post['level'])) {
            return json($datajson);
        }

        $_value = json_encode([
            'logo' => $post['type'] > 100 ? 11 : $post['type'],
            'num' => $post['num'],
            'jackpot' => empty($post['jackpot']) ? 0 : $post['jackpot'],
        ]);

        Db::table('jh_control_maps')->insert([
            'uid' => $post['uid'],
            'gtype' => 26,
            'level' => $post['level'],
            'value' => $_value,
        ]);

        $datajson['msg'] = '';
        $datajson['status'] = 1;
        $datajson['data'] = $post;
        return json($datajson);
    }

    public function delresult()
    {
        $post = input('post.');
        Db::table('jh_control_maps')->where('id', $post['id'])->delete();
        return json(array(
            'status' => 1,
            'msg' => "",
            'data' => $post
        ));
    }
}
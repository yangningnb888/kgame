<?php

use think\Db;

class Token
{
    public function checkToken($token)
    {
        $res = Db::table('system_token')->field('endtime')->where('token', $token)->select();

        if (!empty($res)) {
            //dump(time() - $res[0]['time_out']);
            if (time() - $res[0]['endtime'] > 0) {
                return 90003; //token长时间未使用而过期，需重新登陆
            }
            $new_time_out = time() + 900;
            $res = Db::table('system_token')
                ->where('token', $token)
                ->update(['endtime' => $new_time_out]);
            if ($res) {
                return 90001; //token验证成功，time_out刷新成功，可以获取接口信息
            }
        }

        return 90002; //token错误验证失败
    }


    public function checkGameToken($token)
    {
        $res = Db::table('jh_user_token')->field('http_endtime')->where('http_token', $token)->find();

        if (!empty($res)) {
            if (time() - $res['http_endtime'] > 0) {
                return 90003; //token长时间未使用而过期，需重新登陆
            }
            $new_time_out = time() + 86400;
            $res = Db::table('jh_user_token')
                ->where('http_token', $token)
                ->update(['http_endtime' => $new_time_out]);
            if ($res) {
                return 90001; //token验证成功，time_out刷新成功，可以获取接口信息
            }
        }

        return 90002; //token错误验证失败
    }
}
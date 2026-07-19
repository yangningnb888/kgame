<?php

namespace app\back\model;

use think\Db;

//引入Db
use think\Model;

//引入Model
class System_Log extends Model
{
    public function saveData($data)
    {
        $user = session('login_user');
        $data['username'] = $user['username'];
        $data['ip'] = request()->ip();
        $data['ip_pos'] = '阿根廷';
        $data['browser'] = '未知';
        $data['created'] = date('Y-m-d H:i:s', time());
        Db::table('system_log')->insert($data);
    }
}

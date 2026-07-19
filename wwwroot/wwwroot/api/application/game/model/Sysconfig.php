<?php

namespace app\game\model;

use think\Db;

//引入Db
use think\Model;

//引入Model
class Sysconfig extends Model
{

    protected $table = 'jh_sysconfig';//表名

    //查询
    function show()
    {
        return Db::table($this->table)->select();
    }
}

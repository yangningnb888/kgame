<?php

namespace app\game\model;

use think\Db;

//引入Db
use think\Model;

//引入Model
class Register extends Model
{

    protected $table = 'jh_register';//表名

    //查询
    function show()
    {
        return Db::table($this->table)->select();
    }
}

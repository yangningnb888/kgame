<?php

namespace app\game\model;

use think\Db;

//引入Db
use think\Model;

//引入Model
class Agenttable extends Model
{

    protected $table = 'jh_agent';//表名

    //查询
    function show()
    {
        return Db::table($this->table)->select();
    }
}

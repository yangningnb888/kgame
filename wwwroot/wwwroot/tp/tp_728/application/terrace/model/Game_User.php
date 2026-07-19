<?php

namespace app\terrace\model;

use think\Db;

//引入Db
use think\Model;

//引入Model
class Game_User extends Model
{

    protected $table = 'jh_user';//表名

    //查询
    function show()
    {
        return Db::table($this->table)->select();
    }

    //查询单条
    function findData($where, $filed = '*')
    {
        return Db::table($this->table)->field($filed)->where($where)->find();
    }

    //修改
    function updateData($data, $where)
    {
        return Db::table($this->table)->where($where)->update($data);
    }

    //计数
    function count($where = '1')

    {
        return (int)Db::table($this->table)->where($where)->value('COUNT(*) AS count', 0);
    }

    //求和
    function sum($filed, $where = '1')

    {
        return Db::table($this->table)->where($where)->value('SUM(' . $filed . ') AS sum', 0);
    }
}

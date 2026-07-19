<?php
require_once __DIR__ . '/../../Config/Config.php';

class MyConfig extends Config
{
    //子进程数
    public static $serviceCount = 8;

    //进程房间数限制
    public static $maxPNum = 150;

    public static $GTYPE = GAME_LDYX;
    //项目名
    public static $GameName = 'GAME_LDYX';

    public static $localAddress = '192.168.110.6'; //本地地址
    public static $registerAddress = '127.0.0.1'; //服务注册地址
    public static $centralAddress = '127.0.0.1'; //中心服务注册地址

    //数据库心跳间隔
    public static $dbpingInterval = 60;
}
<?php
/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/1
 * Time: 18:00
 */
require_once __DIR__ . '/../Config/MyGlobal.php';

class Config
{
    //子进程数
    public static $serviceCount = 1;

    //进程房间数限制
    public static $maxPNum = 150;

    //项目名
    public static $ProjectName = 'JHLM';

    //网关通信协议
    public static $gatewayProtocol = 'websocket';

    //网关监听端口
    //测试服
    public static $gatewayPort = '16000';
    //正式服
    //public static $gatewayPort = '11110';

    //网关内部通讯起始端口
    public static $gatewayStartPort = 16001;

    //register服务监听端口
    public static $registerPort = '16002';

    //中心服务器端口
    public static $centralPort = '16003';

    public static $localAddress = '127.0.0.1';  //本地地址
    public static $registerAddress = '127.0.0.1';  //服务注册地址
    public static $centralAddress = '127.0.0.1';  //中心服务注册地址

    //数据库心跳间隔
    public static $dbpingInterval = 60;

    /*  //负载接口地址
      public static $SLBURL = '127.0.0.1.136:8081';

      //网关编号
      public static $GWID = 1;*/


    public static $dbConfig = [
        'host' => '127.0.0.1',
        'user' => 'root',

        //正式服
        'password' => '86e201113604c41f',

        'dbname' => '63',
        'port' => '3306',
        'tablepre' => 'jh_',
        'charset' => 'utf8mb4',
    ];
}
<?php

use \Workerman\Worker;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Config/MyConfigERNN.php';

// bussinessWorker 进程
$worker = new BusinessWorker();
// worker名称
$worker->name = MyConfigERNN::$ProjectName . '_BusinessWorker_' . MyConfigERNN::$GameName;
// bussinessWorker进程数量
$worker->count = MyConfigERNN::$serviceCount;
// 本机ip，分布式部署时使用内网ip
$worker->lanIp = MyConfigERNN::$localAddress;
// 服务注册地址
$worker->registerAddress = MyConfigERNN::$registerAddress . ':' . MyConfigERNN::$registerPort;
// 中心服务注册地址
$worker->centralAddress = MyConfigERNN::$centralAddress . ':' . MyConfigERNN::$centralPort;

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
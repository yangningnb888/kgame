<?php

use \Workerman\Worker;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Config/MyConfigFFC.php';

// bussinessWorker 进程
$worker = new BusinessWorker();
// worker名称
$worker->name = MyConfigFFC::$ProjectName . '_BusinessWorker_' . MyConfigFFC::$GameName;
// bussinessWorker进程数量
$worker->count = MyConfigFFC::$serviceCount;
// 本机ip，分布式部署时使用内网ip
$worker->lanIp = MyConfigFFC::$localAddress;
// 服务注册地址
$worker->registerAddress = MyConfigFFC::$registerAddress . ':' . MyConfigFFC::$registerPort;
// 中心服务注册地址
$worker->centralAddress = MyConfigFFC::$centralAddress . ':' . MyConfigFFC::$centralPort;

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
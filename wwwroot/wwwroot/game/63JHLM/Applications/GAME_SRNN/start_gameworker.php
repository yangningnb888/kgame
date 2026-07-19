<?php

use \Workerman\Worker;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Config/MyConfigSRNN.php';

// bussinessWorker 进程
$worker = new BusinessWorker();
// worker名称
$worker->name = MyConfigSRNN::$ProjectName . '_BusinessWorker_' . MyConfigSRNN::$GameName;
// bussinessWorker进程数量
$worker->count = MyConfigSRNN::$serviceCount;
// 本机ip，分布式部署时使用内网ip
$worker->lanIp = MyConfigSRNN::$localAddress;
// 服务注册地址
$worker->registerAddress = MyConfigSRNN::$registerAddress . ':' . MyConfigSRNN::$registerPort;
// 中心服务注册地址
$worker->centralAddress = MyConfigSRNN::$centralAddress . ':' . MyConfigSRNN::$centralPort;

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
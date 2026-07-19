<?php

use \GatewayWorker\Lib\DbModel;
use \Workerman\Worker;
use \Workerman\Autoloader;
use Workerman\Lib\Timer;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Config/Config.php';

// 中心服务必须是text协议
$worker = new Worker('text://0.0.0.0:' . Config::$centralPort);
$worker->count = 1;
$worker->name = Config::$ProjectName.'_CentralService';
// 事件回调
$worker->onWorkerStart = array('Events', 'onWorkerStart');
$worker->onMessage = array('Events', 'onMessage');
$worker->onConnect = array('Events', 'onConnect');
$worker->onClose = array('Events', 'onClose');
$worker->onWorkerStop = array('Events', 'onWorkerStop');

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}


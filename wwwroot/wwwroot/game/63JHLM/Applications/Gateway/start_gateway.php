<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Workerman\WebServer;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Config/Config.php';
require_once __DIR__ . '/../Config/MyGlobal.php';

// gateway 进程
$gateway = new Gateway(Config::$gatewayProtocol . '://0.0.0.0:' . Config::$gatewayPort);
// gateway名称，status方便查看
$gateway->name = Config::$ProjectName.'_Gateway_' . Config::$gatewayProtocol;
// gateway进程数
$gateway->count = Config::$serviceCount;
// 本机ip，分布式部署时使用内网ip
$gateway->lanIp = Config::$localAddress;
// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口 
$gateway->startPort = Config::$gatewayStartPort;
// 服务注册地址
$gateway->registerAddress = Config::$registerAddress . ':' . Config::$registerPort;
// ==路由绑定==
$gateway->router = function($worker_connections, $client_connection, $cmd, $buffer)
{
    $session = unserialize($client_connection->session);
    if ($session && isset($session['router']) && !empty($session['router'])) {
        $client_connection->businessworker_address = $session['router'];
    }
    if (!isset($client_connection->businessworker_address) || !isset($worker_connections[$client_connection->businessworker_address])) {
        // === 修复: 鉴权消息必须路由到有 auth 的 worker ===
        $msg = @json_decode(base64_decode($buffer), true);
        if ($msg && isset($msg['event']) && $msg['event'] === 'Msg_Hall_Connect') {
            // 查找 Hall worker (worker name 包含 "Hall")
            foreach ($worker_connections as $addr => $conn) {
                if (isset($conn->worker) && strpos($conn->worker->name ?? '', 'Hall') !== false) {
                    $client_connection->businessworker_address = $addr;
                    break;
                }
            }
        }
        if (!isset($client_connection->businessworker_address) || !isset($worker_connections[$client_connection->businessworker_address])) {
            $client_connection->businessworker_address = array_rand($worker_connections);
        }
    }
    return $worker_connections[$client_connection->businessworker_address];
};

if (MyGlobal::$pingSwitch) {
    //心跳间隔
    $gateway->pingInterval = MyGlobal::$pingInterval;
    //心跳次数
    $gateway->pingNotResponseLimit = MyGlobal::$pingNotResponseLimit;
}

/* 
// 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
$gateway->onConnect = function($connection)
{
    $connection->onWebSocketConnect = function($connection , $http_header)
    {
        // 可以在这里判断连接来源是否合法，不合法就关掉连接
        // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
        if($_SERVER['HTTP_ORIGIN'] != 'http://kedou.workerman.net')
        {
            $connection->close();
        }
        // onWebSocketConnect 里面$_GET $_SERVER是可用的
        // var_dump($_GET, $_SERVER);
    };
}; 
*/

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}


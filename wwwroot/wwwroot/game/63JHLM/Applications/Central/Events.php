<?php

use Workerman\Worker;
use Workerman\Lib\Timer;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Common/DBInstance.php';
require_once __DIR__ . '/../Common/MyTools.php';
require_once __DIR__ . '/../Common/Common.php';

class Events
{
    public static function onWorkerStart($worker)
    {
        // 初始化DB实例
        DBInstance::Init(Config::$dbConfig);
        DBInstance::UpdateUserOnline(['!=', 0], 2, false);
        DBInstance::UpdateAllIngame();
        Timer::add(Config::$dbpingInterval, function () {
            DBInstance::GetSysConfig('GAME_LOGIN_PORT');
        });

        Central::ServerBegin();
    }

    public static function onMessage($connection, $message)
    {
        $msg = json_decode($message, true);
        if (!isset($msg["event"])) {
            MyTools::msg('Recv : Event = UnKnown');
            return;
        } else {
            $uid = -11111;
            if (isset($msg['uid'])) {
                $uid = $msg['uid'];
            }
            MyTools::msg("uid : " . $uid . "        RecvFrom-" . CONCECT_NAME[$msg['area']] . $msg["event"], $msg["event"]);
        }

        //消息回调
        if (is_callable(array('Central', $msg['event']))) {
            call_user_func_array(array('Central', $msg['event']), array($connection, $msg));
        } elseif (is_callable(array('Central', 'Transpond'))) {
            call_user_func_array(array('Central', 'Transpond'), array($connection, $msg));
        }
    }

    public static function onConnect($connection)
    {
        MyTools::msg("Central New Connect! $connection->id");
    }

    public static function onClose($connection)
    {
        MyTools::msg("Central Close Connect! $connection->id");
        Central::ClientClose($connection); //服务断开
    }

    public static function onWorkerStop($worker)
    {
        MyTools::msg('Central WorkerStop');
    }
}
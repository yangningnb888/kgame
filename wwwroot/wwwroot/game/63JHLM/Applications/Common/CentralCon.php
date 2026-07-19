<?php

use Workerman\Connection\AsyncTcpConnection;

require_once __DIR__ . '/MyTools.php';
require_once __DIR__ . '/../Config/MyGlobal.php';

//发送消息
class CentralCon
{
    //中心服务器连接
    public static $centralConnection = null;

    /**
     * 连接中心服 初始化
     * @param string $address
     * @throws Exception
     */
    public static function Init($address = "127.0.0.1:1236")
    {
        self::$centralConnection = new AsyncTcpConnection('text://' . $address);
        self::$centralConnection->onConnect = function ($connection) {
            MyTools::msg('Logic centralConnection connect success');

            $connection->send(json_encode(array(
                'event' => 'LogicRegister',
                'area' => MyTools::$LTYPE,
                'gtype' => MyTools::$GTYPE,
                'businessid' => MyTools::$businessWorkerID,
            )));
        };
        self::$centralConnection->onClose = function ($connection) {
            MyTools::msg('centralConnection connection closed');
            $connection->reconnect(1); //断线重连
        };
        self::$centralConnection->onError = function ($connection, $code, $msg) {
            MyTools::msg("centralConnection Error code:$code msg:$msg");
        };
        self::$centralConnection->onMessage = array('Events', 'onCentralMessage');
        self::$centralConnection->connect();
    }

    /**
     * 发送消息到中心服
     * @param $data
     */
    public static function SendToCentral($data)
    {
        self::$centralConnection->send(json_encode($data));
    }

    /**
     * 发送消息到其他
     * @param $connection
     * @param $data
     */
    public static function SendToOther($connection, $data)
    {
        if ($connection == null) {
            MyTools::log(json_encode($data));
            return;
        }

        $connection->send(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

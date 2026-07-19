<?php

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use Workerman\Worker;
use Workerman\Lib\Timer;
use \GatewayWorker\Lib\Gateway;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Common/CentralCon.php';
require_once __DIR__ . '/../Common/DBInstance.php';
require_once __DIR__ . '/../Common/Common.php';

class Events
{
    public static function onWorkerStart($businessWorker)
    {
        MyTools::$LTYPE = LOGIC_GAME;
        MyTools::$GTYPE = GAME_SRNN;
        Logic::$businessWorkerID = MyTools::$businessWorkerID = "$businessWorker->lanIp:$businessWorker->name:$businessWorker->id";

        DBInstance::Init(Config::$dbConfig);
        Timer::add(Config::$dbpingInterval, function () {
            DBInstance::$db->table('sysconfig')->select('val')->where(array('key' => 'GAME_LOGIN_IP'))->one(true);
        });

        //连接中心服
        CentralCon::Init($businessWorker->centralAddress);
    }

    /**
     * 中心服务器消息UserHead
     * @param mixed $connection 连接对象
     * @param mixed $message 具体消息
     */
    public static function onCentralMessage($connection, $message)
    {
        $msg = json_decode($message, true);
        if (!isset($msg['event'])) {
            MyTools::msg('RecvFromCentral : UnKnown');
            return;
        } else {
            $uid = -11111;
            if (isset($msg['uid'])) {
                $uid = $msg['uid'];
            }
            MyTools::msg('uid : ' . $uid . '        RecvFromCentral  : ' . $msg['event'], $msg['event']);
        }

        $callback = $msg['event'];
        if (isset(MyGlobal::$LogicCenterCallBackList[$callback])) {
            $callback = MyGlobal::$LogicCenterCallBackList[$callback];
        }

        if (is_callable(array('Common', $callback))) {
            call_user_func_array(array('Common', $callback), array($msg));
        } elseif (is_callable(array('Logic', $callback))) {
            call_user_func_array(array('Logic', $callback), array($msg));
        } else {
            MyTools::msg('onCentralMessage Unknown message : ' . $message);
        }
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        if (!isset($_SESSION['router']) || empty($_SESSION['router'])) {
            $_SESSION['router'] = MyTools::$businessWorkerID;
        }

        $msg = json_decode(base64_decode($message), true);
        if (!isset($msg['event'])) {
            MyTools::msg('RecvFromClient : UnKnown');
            return;
        } else {
            $uid = -11111;
            if (isset($msg['uid']) && $msg['uid'] != -1) {
                $uid = $msg['uid'];
            }
            MyTools::msg('uid : ' . $uid . '        RecvFromClient  : ' . $msg['event'], $msg['event']);
        }

        $callback = $msg['event'];

        if (is_callable(array('Common', $callback))) {
            call_user_func_array(array('Common', $callback), array($client_id, $msg));
        } elseif (is_callable(array('Logic', $callback))) {
            call_user_func_array(array('Logic', $callback), array($client_id, $msg));
        } else {
            if (!isset(Logic::$UidRoom[$msg['uid']])) {
                MyTools::msg('UnknownUID : ' . $msg['uid']);
                Logic::SendError($msg['uid'], $msg['event'], '房间不存在');
                return;
            }
            $rid = Logic::$UidRoom[$msg['uid']];
            if (isset(Logic::$RoomList[$rid]) && is_callable(array(Logic::$RoomList[$rid]['room'], 'All_RECV'))) {
                call_user_func_array(array(Logic::$RoomList[$rid]['room'], 'All_RECV'), array($msg));
            } else {
                MyTools::msg('Logic onClientMessage Unknown message : ' . json_encode($msg));
            }
        }
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        MyTools::msg("New Connect: $client_id");
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        MyTools::msg("Close Connect: $client_id");
        Logic::UserClose($client_id);
    }

    /**
     * 当businessWorker进程退出时触发
     * 如果业务不需此回调可以删除onWorkerStop
     *
     * @param Worker $businessWorker 当前进程
     */
    public static function onWorkerStop($businessWorker)
    {
        MyTools::msg('WorkerStop');
    }
}

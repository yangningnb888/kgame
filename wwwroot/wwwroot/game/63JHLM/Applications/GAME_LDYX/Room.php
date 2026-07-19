<?php

date_default_timezone_set("Asia/Shanghai");

require_once __DIR__ . '/Room/Table.php';

class Room  extends Table
{
    /**
     * 构造
     * @param array
     */
    public function __construct($msg)
    {
        parent::__construct($msg);
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function All_RECV($message)
    {
        parent::All_RECV($message);
    }

    /**
     * 金币变化
     * @param array
     */
    public function ChangeGold($message)
    {
        parent::ChangeGold($message);
    }

    /**
     * 解散房间
     * @param array
     */
    public function DisRoom($message = [])
    {
        parent::DisRoom($message);
    }

    /**
     * 玩家进房
     * @param array
     */
    public function EnterRoom($message)
    {
        parent::EnterRoom($message);
    }


    /**
     * 所有消息回调
     * @param $client_id
     * @param array
     */
    public function UserOnline($client_id, $message)
    {
        parent::UserOnline($client_id, $message);
    }

    /**
     * 所有消息回调
     * @param array
     */
    public function UserOff($message)
    {
        parent::UserOff($message);
    }
}
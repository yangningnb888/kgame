<?php
class AI
{
    public static function Msg_TBNN_FaCards($uid)
    {
        return ['event' => 'Msg_TBNN_Act_Show', 'uid' => $uid, 'data' => []];
    }

    public static function Msg_TBNN_Res($uid)
    {
        return ['event' => 'Msg_TBNN_Ready', 'uid' => $uid, 'data' => []];

    }
}

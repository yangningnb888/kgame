<?php


define('BET_PROBABILITY', [0 => 30, 1 => 30, 2 => 20, 3 => 20]);
class AI
{
    public static function Msg_ERNN_CallBanKer($uid)
    {
        $type = 2;
        if (rand(1, 100) < 50) {
            $type = 1;
        }
        return ['event' => 'Msg_ERNN_Act_CallBanker', 'uid' => $uid, 'data' => ['type' => $type]];
    }

    public static function Msg_ERNN_Bet($msg, $uid)
    {
        $rand = rand(1, 100);
        $_rand = 0;
        $bet = 0;
        foreach (BET_PROBABILITY as $key => $val) {
            $_rand += $val;
            if ($_rand >= $rand) {
                $bet = $msg['bet'][$key];
                break;
            }
        }
        return ['event' => 'Msg_ERNN_Act_Bet', 'uid' => $uid, 'data' => ['bet' => $bet]];
    }

    public static function Msg_ERNN_FaCards($uid)
    {
        return ['event' => 'Msg_ERNN_Act_Show', 'uid' => $uid, 'data' => []];
    }

    public static function Msg_ERNN_Add($uid)
    {
        return ['event' => 'Msg_ERNN_Ready', 'uid' => $uid, 'data' => []];
    }

    public static function Msg_ERNN_Out($uid)
    {
        return ['event' => 'Msg_ERNN_Out', 'uid' => $uid];
    }
}

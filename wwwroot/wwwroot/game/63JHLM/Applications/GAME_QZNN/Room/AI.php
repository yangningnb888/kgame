<?php

define('CALL_BANKER_ARR_PRO', [0 => 20, 1 => 30, 2 => 30, 4 => 20]);
define('BET_ARR_PRO', [5 => 10, 10 => 30, 15 => 30, 20 => 30]);
class AI
{

    public static function Msg_QZNN_CallBanker($uid)
    {
        $rand = rand(1, array_sum(CALL_BANKER_ARR_PRO));
        $_rand = 0;
        $type = 0;
        foreach (CALL_BANKER_ARR_PRO as $key => $val) {
            $_rand += $val;
            if ($_rand >= $rand) {
                $type = $key;
                break;
            }
        }

        return ['event' => 'Msg_QZNN_Act_CallBanker', 'uid' => $uid, 'data' => ['type' => $type]];
    }

    public static function Msg_QZNN_Bet($uid)
    {
        $rand = rand(1, array_sum(BET_ARR_PRO));
        $_rand = 0;
        $bet = 0;
        foreach (BET_ARR_PRO as $key => $val) {
            $_rand += $val;
            if ($_rand >= $rand) {
                $bet = $key;
                break;
            }
        }
        return ['event' => 'Msg_QZNN_Act_Bet', 'uid' => $uid, 'data' => ['bet' => $bet]];
    }

    public static function Msg_QZNN_FaCards($uid)
    {
        return ['event' => 'Msg_QZNN_Act_Show', 'uid' => $uid, 'data' => []];
    }
}

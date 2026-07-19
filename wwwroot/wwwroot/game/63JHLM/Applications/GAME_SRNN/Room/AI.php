<?php
define('CALL_BANKER_PRO', [1 => 10, 2 => 10]);
define('BET_PRO', [0 => 10, 1 => 10, 2 => 10, 3 => 10, 40 => 10]);
class AI
{
    public static function Msg_SRNN_CallBanker($uid)
    {
        $type = 0;
        $rand = rand(1, array_sum(CALL_BANKER_PRO));
        $_rand = 0;
        foreach (CALL_BANKER_PRO as $key => $val) {
            $_rand += $val;
            if ($_rand >= $rand) {
                $type = $key;
                break;
            }
        }

        return ['event' => 'Msg_SRNN_Act_CallBanker', 'uid' => $uid, 'data' => ['type' => $type]];
    }

    public static function Msg_SRNN_Bet($uid, $data)
    {
        $bet = 0;
        $arr = [];

        $rand = rand(1, array_sum($arr));
        $_rand = 0;
        foreach (BET_PRO as $key => $val) {
            $_rand += $val;
            if ($_rand >= $rand) {
                $bet = $data['bet'][$key];
                break;
            }
        }

        return ['event' => 'Msg_SRNN_Act_Bet', 'uid' => $uid, 'data' => ['bet' => $bet]];
    }

    public static function Msg_SRNN_FaCards($uid)
    {
        return ['event' => 'Msg_SRNN_Act_Show', 'uid' => $uid, 'data' => []];
    }

    public static function Msg_SRNN_Res($uid, $gold, $doublescore, $time)
    {
        $data = ['event' => 'Msg_SRNN_Ready', 'uid' => $uid, 'data' => []];
        if ($gold < $doublescore * 5 || time() > $time) {
            $data = ['event' => 'Msg_SRNN_Out', 'uid' => $uid, 'data' => []];
        }
        return $data;
    }
}

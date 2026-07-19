<?php
define('CALL_BANKER_PRO', [0 => 10, 1 => 10]);
define('BET_PRO', [5 => 10, 15 => 10, 20 => 10, 30 => 10, 40 => 10]);
class AI
{
    public static function Msg_QZSG_CallBanker($uid)
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

        return ['event' => 'Msg_QZSG_Act_CallBanker', 'uid' => $uid, 'data' => ['type' => $type]];
    }

    public static function Msg_QZSG_Bet($uid, $doublescore, $gold)
    {
        $bet = 0;
        $arr = [];
        foreach (BET_PRO as $key => $val) {
            if ($key * $doublescore <= $gold) {
                $arr[$key] = $val;
            }
        }

        $rand = rand(1, array_sum($arr));
        $_rand = 0;
        foreach (BET_PRO as $key => $val) {
            $_rand += $val;
            if ($_rand >= $rand) {
                $bet = $key;
                break;
            }
        }

        return ['event' => 'Msg_QZSG_Act_Bet', 'uid' => $uid, 'data' => ['bet' => $bet]];
    }

    public static function Msg_QZSG_FaCards($uid)
    {
        return ['event' => 'Msg_QZSG_Act_Show', 'uid' => $uid, 'data' => []];
    }
}

<?php
class back
{
    private static $jackpot = true;
    /**
     * 回放列表
     * @param [type] $msg
     * @return void
     */
    public static function Msg_Game_Back_List($msg, $gtype)
    {
        $data = DBInstance::GetBackAll($gtype);
        Logic::SendRight($msg['uid'], 'Msg_Game_Back_List', $data);
    }

    /**
     * 回放详情
     * @param [type] $msg
     * @return void
     */
    public static function Msg_Game_Back_Info($msg)
    {
        $data = DBInstance::GetBankOne($msg['data']['id']);
        Logic::SendRight($msg['uid'], 'Msg_Game_Back_Info', $data);
    }


    /**
     * 查询奖池
     * @param [type] $msg
     * @return void
     */
    public static function Msg_Game_Jackpot($msg, $gtype, $level)
    {
        $res = DBInstance::GetTableOneWord('game_jackpot', 'jackpot', ['gtype' => $gtype, 'level' => $level]);
        if ($res === false) {
            DBInstance::SaveData('game_jackpot',  ['gtype' => $gtype, 'level' => $level]);
            $res = DBInstance::GetTableOneWord('game_jackpot', 'jackpot', ['gtype' => $gtype, 'level' => $level]);
        }
        $score = intval($res * 0.1);
        if ($res < LB_GAME_JACKPOT_MAX && self::$jackpot) {
            DBInstance::SaveJackpot($gtype, $level, $score);
        } else {
            DBInstance::SaveJackpot($gtype, $level, -$score);
        }

        if ($res > LB_GAME_JACKPOT_MAX) {
            self::$jackpot = false;
        }

        if ($res < LB_GAME_JACKPOT_MIN) {
            self::$jackpot = true;
        }
        Logic::SendRight($msg['uid'], 'Msg_Game_Jackpot', ['score' => $res]);
    }
}

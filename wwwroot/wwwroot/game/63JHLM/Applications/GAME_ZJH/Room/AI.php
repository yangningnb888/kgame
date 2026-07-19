<?php

class AI
{
    /**
     * 下注
     * @param [type] $data
     * @param [type] $deal 机器人所需数据 [uid=>'手牌权重']
     * @return array
     */
    public static function  Msg_ZJH_Bet($data, $deal)
    {
        $uid = $data['uid'];
        $num = $deal['data'][$uid];
        $top = 1;
        foreach ($deal['data'] as $key => $val) {
            if ($val > $num) {
                $top++;
            }
        }

        if ($data['betAll'] > 0) {
            $data = self::betAll($top, $uid, $deal);
        } else {
            $data = self::bet($top, $uid, $deal, $data['curbet']);
        }

        return $data;
    }

    /**
     * 全下
     * @param [type] $top
     * @param [type] $uid
     * @return void
     */
    private static function betAll($top, $uid, $deal)
    {
        $data = $deal['control'][$top][$deal['player'][$uid]['type']];
        $res = [];
        if ($deal['player'][$uid]['ishow'] == 0 &&  $data['show'] >= rand(1, 100)) {
            $data[] = self::show($uid);
        }
        if (rand(1, 100) <= $data['betAll']) {
            $res[] = ['event' => 'Msg_ZJH_Act_BetAll', 'uid' => $uid, 'data' => []];
        }

        $arr = ['betAll' => $data['betAll'], 'discard' => $data['discard']];

        $rand = rand(1, array_sum($arr));
        $_rand = 0;
        $name = '';
        foreach ($arr as $key => $val) {
            $_rand += $val;
            if ($_rand >= $rand) {
                $name = $key;
                break;
            }
        }


        if ($name == 'betAll') {
            $res[] =  ['event' => 'Msg_ZJH_Act_BetAll', 'uid' => $uid, 'data' => []];
        } else {
            $res[] = self::disCard($uid);
        }
        return $res;
    }

    /**
     * 下注
     * @param [type] $top
     * @param [type] $uid
     * @return void
     */

    private static function bet($top, $uid, $deal, $score)
    {
        $data = $deal['control'][$top][$deal['player'][$uid]['type']];
        $arr = ['bet' => $data['bet']];
        if ($deal['circle'] > $data['least']) {
            $arr['discard'] = $data['discard'];
            if (count($deal['player']) <= 2) {
                $arr['allIn'] = $data['allIn'];
            }
            $arr['compare'] = $data['compare'];
        }

        $temp = [];
        foreach ($deal['beishuarr'] as $key => $val) {
            if ($val > $score) {
                $temp[$val] = 0;
            }
        }

        if (!empty($temp)) {
            $arr['add'] = $data['add'];
        }

        $res = [];
        $show = 0;
        if ($deal['circle'] > 1 && rand(1, 100) <= $data['show']) {
            $res[] = self::show($uid);
            $show = 1;
        }

        $rand = rand(1, array_sum($arr));
        $_rand = 0;
        $name = '';

        foreach ($arr as $key => $val) {
            $_rand += $val;
            if ($_rand >= $rand) {
                $name = $key;
                break;
            }
        }

        if ($name == 'add') {
            $bet = array_rand($temp);
            if ($deal['player'][$uid]['ishow'] == 1 || $show == 1) {
                $bet *= 2;
            }
            $res[] = ['event' => 'Msg_ZJH_Act_Bet', 'uid' => $uid, 'data' => ['bet' => $bet]];
        } elseif ($name == 'bet') {
            if ($deal['player'][$uid]['ishow'] == 1 || $show == 1) {
                $score *= 2;
            }
            $res[] = ['event' => 'Msg_ZJH_Act_Bet', 'uid' => $uid, 'data' => ['bet' => $score]];
        } elseif ($name == 'compare') {
            $res[] = self::compare($deal['data'], $uid);
        } elseif ($name == 'allIn') {
            $res[] = ['event' => 'Msg_ZJH_Act_BetAll', 'uid' => $uid, 'data' => []];
        } else {
            $res[] = self::disCard($uid);
        }

        return $res;
    }
    /**
     * 看牌
     * @param [type] $uid
     * @return void
     */
    private static function show($uid)
    {
        return ['event' => 'Msg_ZJH_Act_Show', 'uid' => $uid, 'data' => []];
    }

    /**
     * 丢牌
     * @param [type] $uid
     * @return void
     */
    private static function disCard($uid)
    {
        return ['event' => 'Msg_ZJH_Act_Discard', 'uid' => $uid, 'data' => []];
    }

    /**
     * 比牌
     * @param [type] $deal
     * @param [type] $uid
     * @return void
     */
    private static function compare($deal, $uid)
    {
        unset($deal[$uid]);
        return ['event' => 'Msg_ZJH_Act_Compare', 'uid' => $uid, 'data' => ['uid' => array_rand($deal)]];
    }

    /**
     * 结算
     * @return void
     */
    public static function Msg_ZJH_Res($uid, $timer)
    {
        $data = [];

        if (rand(1, 100) > 50) {
            $data = ['event' => 'Msg_ZJH_Act_BrightCard', 'uid' => $uid, 'data' => []];
        }

        if ($timer < time()) {
            $data = ['event' => 'Msg_ZJH_Out', 'uid' => $uid, 'data' => []];
        }

        return $data;
    }
}

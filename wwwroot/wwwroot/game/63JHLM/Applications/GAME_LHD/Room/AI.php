<?php

use Workerman\Lib\Timer;

define('LOONG_TYPE_AI_LHD', 1);
define('FLAT_TYPE_AI_LHD', 2);
define('TIGER_TYPE_AI_LHD', 3);

define('AI_SCORE_BAT_AI_LHD', [1000, 10000, 100000, 1000000, 10000000, 50000000]);
define('PROBABILITY_AI_LHD', [LOONG_TYPE_AI_LHD => 480, FLAT_TYPE_AI_LHD => 1, TIGER_TYPE_AI_LHD => 480]);

class AI
{
    private  $time = 0; //机器人进房间的时间戳

    private  $survivaltime = 0; //机器人存活的时间

    public function __construct($data)
    {
        $this->time = time();
        $rand = rand($data['aitime'][0], $data['aitime'][1]);
        $this->survivaltime = $rand;
    }

    /**
     *退出房间
     * @return void
     */
    public function QuitRoom($gold)
    {
        $time = time();
        $code = false;
        if ($this->survivaltime +  $this->time <= $time) {
            $code = true;
        }

        return $code;
    }

    /**
     *下注
     * @param [type] $data
     * @return void
     */
    public static function bat()
    {
        $count = array_sum(PROBABILITY_AI_LHD);

        $rand = rand(0, $count);
        $probability = 0;
        foreach (PROBABILITY_AI_LHD as $key => $val) {
            $probability += $val;
            if ($probability >= $rand) {
                $type = $key;
                break;
            }
        }


        $score = array_rand(AI_SCORE_BAT_AI_LHD);

        $score = AI_SCORE_BAT_AI_LHD[$score];
        return  ['code' => $type, 'bat' => $score];
    }
}

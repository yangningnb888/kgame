<?php
define('TIAN_TYPE_AI_BRNN', 1);
define('DI_TYPE_AI_BRNN', 2);
define('XUAN_TYPE_AI_BRNN', 3);
define('HUANG_TYPE_AI_BRNN', 4);

define('AI_SCORE_BAT_AI_BRNN', [1000, 10000, 100000, 1000000, 10000000, 50000000]);
define('PROBABILITY_AI_BRNN', [TIAN_TYPE_AI_BRNN => 25, DI_TYPE_AI_BRNN => 25, XUAN_TYPE_AI_BRNN => 25, HUANG_TYPE_AI_BRNN => 25]);

class AI
{
    private  $time = 0; //机器人进房间的时间戳

    private  $survivaltime = 0; //机器人存活的时间

    public function __construct($data)
    {
        $this->time = time();
        $rand = rand($data['aitime'][0], $data['aitime'][1]);
        $this->survivaltime  = $rand;
    }

    /**
     *退出房间
     * @return void
     */
    public function QuitRoom($gold)
    {
        $time = time();
        $code = false;
        if ($this->survivaltime + $this->time <= $time) {
            $code = true;
        }

        return $code;
    }

    /**
     *下注
     * @param [type] $data
     * @return void
     */
    public function bat($score)
    {
        $rand = rand(0, 100);
        $probability = 0;
        foreach (PROBABILITY_AI_BRNN as $key => $val) {
            $probability += $val;

            if ($probability >= $rand) {
                $type = $key;
                break;
            }
        }

        $scorebat = AI_SCORE_BAT_AI_BRNN;
        foreach ($scorebat as $key => $val) {
            if ($val > $score) {
                unset($scorebat[$key]);
            }
        }
        $_score = 0;
        
        if(!empty($scorebat)) {
            $_score = array_rand($scorebat);
            $_score = $scorebat[$_score];
        }
        
        return  ['code' => $type, 'bat' => $_score];
    }
}

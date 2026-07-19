<?php
const STAND = 0;  //待机
const LOCK = 1;  //锁定
const LOCKGET = 2;  //锁定开炮
const GETFISH = 3;  //随机开炮

const RANDSTAND = 10;
const RANDLOCK = 10;
const RANDLOCKGET = 100;
const RANDGETFISH = 100;


const TIME_CHANGEBERRTY = [5, 10];
const TIME_CHANGETURN = [10,20];   //切换锁定时间


class StaticValue
{
    /**
     * @var array
     */
    public static $FishList = [];   //所有鱼种数据
    public static $FinshWays = [];   //所有路径
    public static $circleWays = [];   //圆圈鱼路径
    public static $fishBoom = [];     //鱼潮配置

    //随机状态
    public static function GetStatus ()
    {
        $ret = [
            'status' => STAND,
            'nexttime' => 0,
        ];
        $rand = rand(1, 100);

        if ($rand <= RANDSTAND) {
            $ret['nexttime'] = time() + rand(2, 4);
        } elseif ($rand <= RANDLOCK) {
            $ret['status'] = LOCK;
            $ret['nexttime'] = time() + rand(1, 3);
        } elseif ($rand <= RANDLOCKGET) {
            $ret['status'] = LOCKGET;
            $ret['nexttime'] = time() + rand(20, 40);
        } else {
            $ret['status'] = GETFISH;
            $ret['nexttime'] = time() + rand(9, 15);
        }

        return $ret;
    }

    //随机更改炮台
    public static function ChangeBerrty ()
    {
        $ret = ['time' => rand(TIME_CHANGEBERRTY[0], TIME_CHANGEBERRTY[1]), 'berrty' => 0];
        if (rand(1, 100) <= 35) {
            $ret['berrty'] = 1;
            if (rand(1, 100) <= 20) {
                $ret['berrty'] = -1;
            }
        }
        return $ret;
    }

    //锁定逻辑
    public static function LockFish ($fishlist)
    {
        $list = [];
        $time = MyTools::GET_MS();
        $ones = [];
        foreach ($fishlist as $key => $value) {
            if (isset($value['endtime']) && is_array($value['endtime'])) {
                continue;
            }
            if ((empty($value['createtime']) || ($time - $value['createtime'] > 1000 && $value['endtime'] - $time >= 3000)) && $value['type'] >= 17) {
                if ($value['type'] >= 300 && rand(0, 10) < 2) {
                    $list[$key] = 1;
                }
            }

            if (empty($value['fishes'])) {
                $ones[$key] = 1;
            }
        }

        if (empty($list)) {
            $list = $ones;
        }
        return empty($list) ? 0 : array_rand($list);
    }

}


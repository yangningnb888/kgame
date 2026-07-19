<?php
define('SCREEN_X', 1500);
define('SCREEN_Y', 800);
define('YUZHEN', 60);
define('YUZHENQUAN', 65);

define('FISHBOOM', 60000);
define('BOOMRAND', 5);  //鱼潮命中概率增幅

require_once __DIR__ . '/StaticValue.php';

class Algorithm
{
    private $IdList = [];     //鱼id列表
    public $BulletIdList = [];  //子弹id列表

    /**
     *  'id' => [
     *          'nexttime' => '下次生成时间',
     *          'type' => '鱼类型id',
     *          'speed' => '鱼的速度',
     *          'wayid' => '路径id',
     *          'circlepoint' => ['圆圈中心点'],
     *          'curnum' => '当前鱼阵数量',
     *          'allnum' => '目标鱼阵数量',
     *      ]
     **/
    public $FishesQueue = [];   //鱼阵队列

    //构造
    public function __construct()
    {
        $this->CreateFishBulletId();
        StaticValue::$FishList = DBInstance::GetFishList(GAME_DNTG);
        StaticValue::$FinshWays = DBInstance::GetFisWays();
        StaticValue::$fishBoom = DBInstance::GetFishBoom();
        unset(StaticValue::$fishBoom[2]);
        unset(StaticValue::$fishBoom[5]);
        unset(StaticValue::$fishBoom[6]);
        //修改为孙悟空
        /*StaticValue::$fishBoom[1]['fish_list'][181]['type'] = 16;
        StaticValue::$fishBoom[1]['fish_list'][181]['life'] = 35;*/
        foreach (StaticValue::$FinshWays[1000]['positions'] as $key => $value) {
            for ($i = 0; $i < 18; $i++) {
                StaticValue::$circleWays[$key][$i] = $this->GetCircleWay($value, $i);
            }
        }

        //鱼潮配置
        /*$arr = json_decode('', true);
        unset($arr['sceneName']);
        $this->CreateBoom($arr);*/
    }

    //创建鱼潮配置
    private function CreateBoom($arr)
    {
        $i = 1;
        $boom = [];
        foreach ($arr as $key => $value) {
            if ($key < 50) {
                $life = StaticValue::$FishList[$key]['life'];
            } else {
                $life = StaticValue::$FishList[$key % 10]['life'] * $arr[$key % 10];
            }
            $end = $i + $value;
            for ($i; $i < $end; $i++) {
                $boom[$i] = [
                    'type' => $key,
                    'life' => $life
                ];
            }
        }
        DBInstance::InsertFishBoom($boom);
    }

    //初始化id数组
    private function CreateFishBulletId()
    {
        for ($i = 1; $i <= 200; $i++) {
            $this->BulletIdList[] = $i;
        }

        for ($i = 301; $i <= 800; $i++) {
            $this->IdList[] = $i;
        }
    }

    /**
     *  计算贝塞尔曲线上的点的坐标
     **/
    public static function BezierCalculate($points, $nums)
    {
        $dimersion = 2;
        $controlpoints = count($points);
        $ret = [];
        $mi = [1, 1];
        $nums *= 2 - 1;   //数量翻倍
        for ($i = 3; $i <= $controlpoints; $i++) {
            $t = [];
            for ($j = 0; $j < $i - 1; $j++) {
                $t[$j] = $mi[$j];
            }

            $mi[0] = $mi[$i - 1] = 1;
            for ($j = 0; $j < $i - 2; $j++) {
                $mi[$j + 1] = $t[$j] + $t[$j + 1];
            }
        }

        //计算点的坐标
        for ($i = 0; $i < $nums; $i++) {
            $t = $i / $nums;
            for ($j = 0; $j < $dimersion; $j++) {
                $temp = 0;
                for ($k = 0; $k < $controlpoints; $k++) {
                    $temp += pow(1 - $t, $controlpoints - $k - 1) * $points[$k][$j] * pow($t, $k) * $mi[$k];
                }
                $ret[$i][$j] = round($temp, 6);
            }

            if ($ret[$i][0] > 1500 || $ret[$i][1] > 800) {
                unset($ret[$i]);
                break;
            }
        }

        $end = end($points);
        if ($end[0] <= 1500 && $end[1] <= 800) {
            $ret[] = [(int)$end[0], (int)$end[1]];
        }
        return $ret;
    }

    /**
     * 生成子弹id
     */
    public function GetBulletId()
    {
        return array_shift($this->BulletIdList);
    }

    /**
     * push子弹id
     */
    public function PushBulletId($id)
    {
        array_push($this->BulletIdList, $id);
    }

    /**
     * 生成鱼id
     */
    public function GetId()
    {
        return array_shift($this->IdList);
    }

    /**
     * push鱼id
     */
    public function PushId($id)
    {
        array_push($this->IdList, $id);
    }

    /**
     *  生成桌面鱼
     */
    public function GetNewFinsh($timer, $curlist)
    {
        $curall = [];  //计算所有种类鱼的数量
        $specil = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0];   //统计当前可以生成特殊鱼的数量
        $countspecil = [200 => 0, 300 => 0, 400 => 0, 500 => 0, 600 => 0, 700 => 0];   //统计特殊鱼数量
        $fishes = [1000 => ['rand' => 3, 'num' => 3], 900 => ['rand' => 5, 'num' => 7], 800 => ['rand' => 8, 'num' => 6]];  // 400直线队列  500群队列 600圆圈队列
        $ret = [];
        //统计各类鱼的当前数量
        foreach ($curlist as $key => $value) {
            $type = $value['type'] < 100 ? $value['type'] : intval($value['type'] / 100) * 100;
            if (!isset($curall[$type])) {
                $curall[$type] = 0;
            }

            $curall[$type]++;

            if ($value['type'] <= 10) {
                $specil[$value['type']]++;
            } elseif ($value['type'] > 100) {
                $countspecil[intval($value['type'] / 100) * 100]++;
            }
        }

        //对可以生成特殊效果的鱼进行排序
        arsort($specil);
        $ways = StaticValue::$FinshWays;
        $circleWay = $ways[1000]['positions'];
        unset($ways[1000]);
        //遍历所有鱼列表生成鱼
        foreach (StaticValue::$FishList as $key => $value) {
            if (empty($ways)) {
                break;
            }
            if (!isset($curall[$key])) {
                $curall[$key] = 0;
            }

            if ($key < 100) {
                //普通鱼
                if ($curall[$key] + $value['new_nums'] <= $value['max_nums'] && $timer % $value['timer'] == 0) {
                    //刷新新的鱼
                    for ($i = 0; $i < $value['new_nums']; $i++) {
                        if (mt_rand(1, 100) <= $value['new_rand']) {
                            $way = array_rand(StaticValue::$FinshWays);
                            if (empty($ways[$way])) {
                                break;
                            }

                            if (empty($this->IdList)) {
                                break;
                            }
                            $time = MyTools::GET_MS();
                            $end = ceil(StaticValue::$FinshWays[$way]['nums'] * 1000 / $value['speed']) + $time + 600;  //延时出入场时间
                            $rand = mt_rand(1, 100);

                            //鱼阵生成id
                            if ($key <= 10 && $key > 2) {
                                foreach ($fishes as $key1 => $value1) {
                                    if ($key1 == 1000) {
                                        continue;
                                    }

                                    if (($key <= 6 || $key1 == 1000 && $key > 6 && !empty($circleWay)) && $rand < $value1['rand']) {
                                        $circlepoint = [];
                                        if ($key1 == 1000 && !empty($circleWay)) {
                                            $way = 1000;
                                            $randindex = array_rand($circleWay);
                                            $circlepoint = $circleWay[$randindex];
                                            unset($circleWay[$randindex]);
                                        }
                                        $this->FishesQueue[] = [
                                            'nexttime' => MyTools::GET_MS(),
                                            'type' => $key,
                                            'speed' => $value['speed'],
                                            'wayid' => $way,
                                            'circlepoint' => $circlepoint,
                                            'curnum' => 0,
                                            'allnum' => $value1['num'],
                                        ];
                                        $rand = 0;
                                        unset($ways[$way]);
                                        break;
                                    }
                                }
                            }

                            if ($rand != 0) {
                                $id = $this->GetId();
                                $ret[$id] = [
                                    'id' => $id,
                                    'type' => $key,
                                    'speed' => $value['speed'],
                                    'couples' => [$key],   //炸弹鱼死亡类型
                                    'createtime' => $time,   //出生时间
                                    'wayid' => $way, //路径id
                                    'circlepoint' => [], //圆圈队列中心
                                    'endtime' => $end,    //游出时间
                                    'fishes' => [],  //鱼阵队列
                                ];
                                unset($ways[$way]);
                            }
                        }
                    }
                }
            } else {
                if ($curall[$key] + $value['new_nums'] <= $value['max_nums'] && $timer % $value['timer'] == 0) {
                    for ($i = 0; $i < $value['new_nums']; $i++) {
                        if (rand(1, 100) <= $value['new_rand']) {
                            if ($key != 600) {
                                $fishnums = [200 => 5, 300 => 1, 400 => 2, 500 => 3, 700 => 1];
                                $getfinsh = array_slice($specil, 0, $fishnums[$key], true);
                                $keys = array_keys($getfinsh);
                                //特殊鱼
                                $way = array_rand(StaticValue::$FinshWays);
                                if (empty($ways[$way])) {
                                    break;
                                }

                                if (empty($this->IdList)) {
                                    break;
                                }
                                $id = $this->GetId();
                                $type = $key + max($keys);
                                $time = MyTools::GET_MS();
                                $end = ceil(StaticValue::$FinshWays[$way]['nums'] * 1000 / $value['speed']) + $time + 600;  //延时出入场时间
                                $ret[$id] = [
                                    'id' => $id,
                                    'type' => $type,
                                    'speed' => $value['speed'],
                                    'couples' => $key == 300 ? [300] : array_keys($getfinsh),
                                    'createtime' => $time,
                                    'wayid' => $way,
                                    'endtime' => $end,
                                    'fishes' => [],
                                    'circlepoint' => [], //圆圈队列中心
                                ];
                                unset($ways[$way]);
                            } else {
                                $realtype = $key + rand(1, 3);
                                $way = array_rand(StaticValue::$FinshWays);
                                $time = MyTools::GET_MS();
                                $end = ceil(StaticValue::$FinshWays[$way]['nums'] * 1000 / $value['speed']) + $time + 600;  //延时出入场时间
                                $id = $this->GetId();
                                $ret[$id] = [
                                    'id' => $id,
                                    'type' => $realtype,
                                    'speed' => $value['speed'],
                                    'couples' => [$key],   //炸弹鱼死亡类型
                                    'createtime' => $time,   //出生时间
                                    'wayid' => $way, //路径id
                                    'circlepoint' => [], //圆圈队列中心
                                    'endtime' => $end,    //游出时间
                                    'fishes' => [],  //鱼阵队列
                                ];

                                unset($ways[$way]);
                            }
                        }
                    }
                }
            }
        }

        $ret += $this->CreatFishQueue();
        return $ret;
    }

    /**
     * 生成鱼阵种的鱼
     */
    public function CreatFishQueue()
    {
        $ret = [];
        foreach ($this->FishesQueue as $key => $value) {
            $time = MyTools::GET_MS();
            if (empty($this->IdList)) {
                break;
            }

            if ($time < $value['nexttime']) {
                continue;
            }

            $id = $this->GetId();
            if ($value['wayid'] == 1000) {
                //圆圈鱼
                $ret[$id] = [
                    'id' => $id,
                    'type' => $value['type'],
                    'speed' => $value['speed'],
                    'couples' => [],
                    'createtime' => $time,
                    'wayid' => $value['wayid'],
                    'circlepoint' => $value['circlepoint'], //圆圈队列中心
                    'endtime' => [],
                    'fishes' => []
                ];

                foreach (StaticValue::$FinshWays[1000]['positions'] as $key1 => $value1) {
                    if ($value1 == $value['circlepoint']) {
                        $index = $key1;
                        break;
                    }
                }

                $yw = rand(0, 17);
                for ($i = 0; $i < 18; $i++) {
                    $waypoints = StaticValue::$circleWays[$index][$i];
                    $ret[$id]['endtime'][$id * 1000 + $i] = ceil(count($waypoints) * 1000 / $value['speed']) + $time;
                    $ret[$id]['fishes'][$id * 1000 + $i] = $value['curnum'] == 2 && $yw == $i ? 2 : 1;
                }
                $this->FishesQueue[$key]['nexttime'] += 1000;
            } else {
                $ret[$id] = [
                    'id' => $id,
                    'type' => $value['type'],
                    'speed' => $value['speed'],
                    'couples' => [],
                    'createtime' => $time,
                    'wayid' => $value['wayid'],
                    'circlepoint' => $value['circlepoint'], //圆圈队列中心
                    'endtime' => ceil(800 * 1000 / $value['speed']) + $time + 600,
                    'fishes' => []
                ];
                $this->FishesQueue[$key]['nexttime'] += 800;
            }

            //鱼阵完成
            $this->FishesQueue[$key]['curnum']++;
            if ($this->FishesQueue[$key]['curnum'] >= $value['allnum']) {
                unset($this->FishesQueue[$key]);
            }
        }

        return $ret;
    }

    /**
     *  计算实时位置
     * @param $fish
     * @param $num int
     * @return array
     */
    public function CountPosition($fish, $num = 0)
    {
        if ($fish['wayid'] != 1000) {
            $start = empty($fish['starttime']) ? $fish['createtime'] : $fish['starttime'];
            $times = MyTools::GET_MS() - $start - 600;
            $pointnum = round($times * $fish['speed'] / 1000);
            if ($pointnum < 0) {
                $pointnum = 0;
            }
            if ($pointnum >= count(StaticValue::$FinshWays[$fish['wayid']]['positions'])) {
                $pointnum = count(StaticValue::$FinshWays[$fish['wayid']]['positions']);
            }
            if (!empty(StaticValue::$FinshWays[$fish['wayid']]['positions'][$pointnum - 1])) {
                return StaticValue::$FinshWays[$fish['wayid']]['positions'][$pointnum - 1];
            } else {
                return null;
            }
        } else {
            $start = empty($fish['starttime']) ? $fish['createtime'] : $fish['starttime'];
            $times = MyTools::GET_MS() - $start;
            $pointnum = round($times * $fish['speed'] / 1000);
            foreach (StaticValue::$FinshWays[1000]['positions'] as $key => $value) {
                if ($value == $fish['circlepoint']) {
                    $index = $key;
                }
            }
            if ($pointnum >= count(StaticValue::$circleWays[$index][$num])) {
                $pointnum = count(StaticValue::$circleWays[$index][$num]);
            }
            if (!empty(StaticValue::$circleWays[$index][$num][$pointnum - 1])) {
                return StaticValue::$circleWays[$index][$num][$pointnum - 1];
            } else {
                return null;
            }
        }
    }

    /**
     *  计算两点的距离
     */
    public function CountDistance($point1, $point2)
    {
        if (!empty($point2)) {
            $d1 = $point1[0] - $point2[0];
            $d2 = $point1[1] - $point2[1];
            return intval(sqrt(($d1 * $d1) + ($d2 * $d2)));
        } else {
            return 1500;
        }
    }

    /**
     * 闪电鱼随机种类
     */
    public function GetLightFishes($type)
    {
        $ret = [$type];
        $cont = mt_rand(1, 6);
        for ($i = 0; $i < $cont; $i++) {
            $ret[] = array_rand([1 => 1, 2 => 1, 3 => 1, 4 => 1, 6 => 1]);
        }
        return $ret;
    }

    /**
     * 圆圈鱼阵路线计算
     */
    public function GetRandCircle($point)
    {
        $ways = [];

        for ($i = 0; $i < 18; $i++) {
            $ways[] = $this->GetCircleWay($point, $i);
        }

        return $ways;
    }


    private function GetCircleWay($point, $circle)
    {
        $maxdis = 1500;  //设定最远距离
        $endpoint = [0, 0];
        $i = pi() / 9 * $circle;
        $endpoint[0] = intval($maxdis * cos($i) + $point[0]);
        $endpoint[1] = intval($maxdis * sin($i) + $point[1]);
        $points = [$point, $endpoint];
        return $this->BezierCalculate($points, 800);
    }
}


<?php
date_default_timezone_set("Asia/Shanghai");
if (strstr(php_uname(), 'Windows')) {
    define("ROOT", dirname(dirname(__DIR__)));
} else {
    define("ROOT", dirname($_SERVER["SCRIPT_FILENAME"]));
}
define("DS", '/');

require_once __DIR__ . '/../Config/MyGlobal.php';

class MyTools
{
    //进程类型
    public static $LTYPE = LOGIC_CENTRAL;

    //游戏类型
    public static $GTYPE = GAME_TEMPLATE;

    //逻辑服id
    public static $LID = 0;

    //本机 IP:Name:worker_id 绑定路由用
    public static $businessWorkerID = '';

    //随机UID算法 根据自增ID 生成类随机 ID
    public static $maxRandID5 = 89711; //9w
    public static $maxRandID6 = 898837; //90w
    public static $maxRandID7 = 8999999; //9bw
    public static $maxRandID8 = 89968807; //9kw

    public static function RandID5($id)
    {
        return bcmod(bcpow($id, 25), self::$maxRandID5);
    }

    public static function RandID6($id)
    {
        return bcmod(bcpow($id, 29), self::$maxRandID6);
    }

    public static function RandID7($id)
    {
        return bcmod(bcpow($id, 2999), self::$maxRandID7);
    }

    public static function RandID8($id)
    {
        return bcmod(bcpow($id, 31), self::$maxRandID8);
    }

    /**
     * 根据自增ID 生成随机UID 5位
     */
    public static function RandUID($id)
    {
        $_id = $id % self::$maxRandID5;
        $len = intval($id / self::$maxRandID5);

        $uid = self::RandID5($_id);
        if ($uid < 10000) {
            $uid += self::$maxRandID5;
        }
        $uid = $len * 100000 + $uid;
        return $uid;
    }

    /**
     * 根据自增ID 生成随机回放码 6位
     */
    public static function RandCode($id)
    {
        $_id = $id % self::$maxRandID6;
        $len = intval($id / self::$maxRandID6);

        $code = self::RandID6($_id);
        if ($code < 100000) {
            $code += self::$maxRandID6;
        }
        $code = $len * 1000000 + $code;
        return $code;
    }

    /**
     * 根据自增ID 生成随机俱乐部ID 6位
     */
    public static function RandClubID($id)
    {
        $cid = self::RandID6($id);
        if ($cid < 100000) {
            $cid += self::$maxRandID6;
        }
        return $cid;
    }

    /**
     * 随机数
     * @param $len 随机数长度
     */
    public static function roundStr($len = '4')
    {
        $array_str = '';
        $round_num = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $round_num = str_split($round_num);
        for ($i = 0; $i < $len; $i++) {
            $array_str .= $round_num[mt_rand(0, 61)];
        }
        unset($round_num);
        return $array_str;
    }


    /**
     * 得到当前毫秒级时间戳
     */
    public static function GET_NOW_MS()
    {
        return self::GET_DATETIME_MS(self::GET_MS(), true);
    }

    /**
     * 得到当前毫秒级时间戳
     */
    public static function GET_MS()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (int)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    /**
     * 毫秒级时间戳转日期
     */
    public static function GET_DATETIME_MS($msectime, $need_ms = false)
    {
        $msectime = $msectime * 0.001;
        if (strstr($msectime, '.')) {
            sprintf("%01.3f", $msectime);
            list($usec, $sec) = explode(".", $msectime);
            $sec = str_pad($sec, 3, "0", STR_PAD_RIGHT);
        } else {
            $usec = $msectime;
            $sec = "000";
        }

        if ($need_ms) {
            return str_replace('x', $sec, date("Y-m-d H:i:s.x", $usec));
        }
        return date("Y-m-d H:i:s", $usec);
    }

    /**
     * 日期转毫秒
     */
    public static function GET_MS_DATE_TIME($mescdate)
    {
        if (!self::CHECK_DATE_TIME($mescdate)) {
            return 0;
        }

        $arr = explode(".", $mescdate);
        $sec = 0;
        if (count($arr) > 1) {
            $sec = $arr[1];
        }
        $usec = $arr[0];
        $date = strtotime($usec);
        return (int)(str_pad($date . $sec, 13, "0", STR_PAD_RIGHT));
    }

    /**
     * 得到今天日期
     */
    public static function GET_TODAY($timestamp = null)
    {
        if ($timestamp == null) {
            $timestamp = time();
        }
        return date('Y-m-d', $timestamp);
    }

    /**
     * 得到当前时分秒
     */
    public static function GET_NOW($timestamp = null)
    {
        if ($timestamp == null) {
            $timestamp = time();
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * 得到当前小时
     */
    public static function GET_NOW_HOUR()
    {
        return (int)date('H');
    }

    /**
     * 得到今天日期
     */
    public static function GET_WEEK()
    {
        return (int)date('w');
    }

    /**
     * 得到明天日期
     */
    public static function GET_TOMORROW()
    {
        return date('Y-m-d', time() + 24 * 3600);
    }

    /**
     * 得到本周一
     */
    public static function GET_MONDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 1) * 24 * 3600));
    }

    /**
     * 得到上周一
     */
    public static function GET_LAST_MONDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 1) * 24 * 3600 - 7 * 24 * 3600));
    }

    /**
     * 得到下周一
     */
    public static function GET_NEXT_MONDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 1) * 24 * 3600 + 7 * 24 * 3600));
    }

    /**
     * 得到本周二
     */
    public static function GET_TUESDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 2) * 24 * 3600));
    }

    /**
     * 得到上周二
     */
    public static function GET_LAST_TUESDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 2) * 24 * 3600 - 7 * 24 * 3600));
    }

    /**
     * 得到下周二
     */
    public static function GET_NEXT_TUESDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 2) * 24 * 3600 + 7 * 24 * 3600));
    }

    /**
     * 得到本周三
     */
    public static function GET_WEDNESDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 3) * 24 * 3600));
    }

    /**
     * 得到上周三
     */
    public static function GET_LAST_WEDNESDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 3) * 24 * 3600 - 7 * 24 * 3600));
    }

    /**
     * 得到下周三
     */
    public static function GET_NEXT_WEDNESDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 3) * 24 * 3600 + 7 * 24 * 3600));
    }

    /**
     * 得到本周四
     */
    public static function GET_THURSDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 4) * 24 * 3600));
    }

    /**
     * 得到上周四
     */
    public static function GET_LAST_THURSDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 4) * 24 * 3600 - 7 * 24 * 3600));
    }

    /**
     * 得到下周四
     */
    public static function GET_NEXT_THURSDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 4) * 24 * 3600 + 7 * 24 * 3600));
    }

    /**
     * 得到本周五
     */
    public static function GET_FRIDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 5) * 24 * 3600));
    }

    /**
     * 得到上周五
     */
    public static function GET_LAST_FRIDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 5) * 24 * 3600 - 7 * 24 * 3600));
    }

    /**
     * 得到下周五
     */
    public static function GET_NEXT_FRIDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 5) * 24 * 3600 + 7 * 24 * 3600));
    }

    /**
     * 得到本周六
     */
    public static function GET_SATURDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 6) * 24 * 3600));
    }

    /**
     * 得到上周六
     */
    public static function GET_LAST_SATURDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 6) * 24 * 3600 - 7 * 24 * 3600));
    }

    /**
     * 得到下周六
     */
    public static function GET_NEXT_SATURDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 6) * 24 * 3600 + 7 * 24 * 3600));
    }

    /**
     * 得到本周日
     */
    public static function GET_SUNDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 7) * 24 * 3600));
    }

    /**
     * 得到上周日
     */
    public static function GET_LAST_SUNDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 7) * 24 * 3600 - 7 * 24 * 3600));
    }

    /**
     * 得到下周日
     */
    public static function GET_NEXT_SUNDAY()
    {
        return date('Y-m-d', (time() - ((date('w') <= 0 ? 7 : date('w')) - 7) * 24 * 3600 + 7 * 24 * 3600));
    }

    /**
     * 得到本月一日
     */
    public static function GET_1ST_MONTH()
    {
        return date('Y-m-d', strtotime(date('Y-m', time()) . '-01 00:00:00'));
    }

    /**
     * 得到上月一日
     */
    public static function GET_LAST_1ST_MONTH()
    {
        return date('Y-m-d', strtotime('-1 month', strtotime(date('Y-m', time()) . '-01 00:00:00')));
    }

    /**
     * 得到下月一日
     */
    public static function GET_NEXT_1ST_MONTH()
    {
        return date('Y-m-d', strtotime('1 month', strtotime(date('Y-m', time()) . '-01 00:00:00')));
    }

    /**
     * 得到本月最后一日
     */
    public static function GET_1LST_MONTH()
    {
        return date('Y-m-d', strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00'));
    }

    /**
     * 得到上月最后一日
     */
    public static function GET_LAST_1LST_MONTH()
    {
        return date('Y-m-d', strtotime(date('Y-m', time()) . '-01 00:00:00') - 86400);
    }

    /**
     * 得到下月最后一日
     */
    public static function GET_NEXT_1LST_MONTH()
    {
        return date('Y-m-d', strtotime('2 month', strtotime(date('Y-m', time()) . '-01 00:00:00')) - 86400);
    }

    /**
     * 得到一个30天后的日期
     */
    public static function GET_NEXT_MONTH_DAY($time = 0)
    {
        if (empty($time)) {
            $time = time();
        }

        return date('Y-m-d', $time + 60 * 60 * 24 * 30);
    }

    /**
     * 得到指定日期零点零分时间戳
     * @param $date
     * @return false|int
     */
    public static function GET_DATE_TIME($date)
    {
        return strtotime($date);
    }

    /**
     * 得到两个时间的相差分钟数
     * @param $start
     * @param $end
     * @return float
     */
    public static function GET_DIFFER_MINUTE($start, $end)
    {
        return floor((strtotime($end) - strtotime($start)) / 60);
    }

    /**
     * 得到一个过去$minute分钟的时间
     * @param $minute
     * @param string $start
     * @return false|string
     */
    public static function GET_NEW_TIME_MINUTE($minute, $start = '')
    {
        if ($start == '') {
            $start = self::GET_NOW();
        }

        return date("Y-m-d H:i:s", strtotime($start) + $minute * 60);
    }

    /**
     * 判断字符串是否为合法日期时间格式
     * @param $str
     * @return bool
     */
    public static function CHECK_DATE_TIME($str)
    {
        $arr = explode(".", $str);

        if (count($arr) > 1) {
            return date('Y-m-d H:i:s.x', strtotime($str)) == $str;
        }

        return date('Y-m-d H:i:s', strtotime($str)) == $str;
    }

    /**
     * 判断字符串是否为合法时间格式
     * @param $str
     * @return bool
     */
    public static function CHECK_TIME($str)
    {
        $arr = explode(".", $str);

        if (count($arr) > 1) {
            return date('H:i:s.x', strtotime($str)) == $str;
        }

        return date('H:i:s', strtotime($str)) == $str;
    }

    /**
     * 判断字符串是否是纯数字
     * @param $str
     * @return bool
     */
    public static function CHECK_ALLNUM($str)
    {
        return is_numeric(strval($str));
    }

    /**
     * 两个索引数组相加
     * @param $arr1
     * @param $arr2
     * @return array
     */
    public static function ARR_ADD_ARR($arr1, $arr2)
    {
        foreach ($arr2 as $key => $val) {
            $arr1[] = $val;
        }
        return $arr1;
    }

    /**
     * 将值从数组中删除
     * @param $arr
     * @param $val
     * @return array
     */
    public static function ARR_DEL_VAL($arr, $val)
    {
        return array_merge(array_diff($arr, array($val)));
    }

    /**
     * 将值从数组中删除
     * @param $arr
     * @param $val
     * @return array
     */
    public static function ARR_DEL_VAL_KEY($arr, $value)
    {
        foreach ($arr as $key => $val) {
            if ($value == $val) {
                unset($arr[$key]);
            }
        }
        return $arr;
    }

    /**
     * 添加键值对值
     * @param $arr
     * @param $key
     * @param $val
     * @param bool $isZero //true:0也会放入数组 false:0不会放入数组
     */
    public static function ARR_ADD_VAL(&$arr, $key, $val, $isZero = true)
    {
        if (isset($arr[$key])) {
            $arr[$key] += $val;
        } elseif ($isZero || $val != 0) {
            $arr[$key] = $val;
        }
    }

    /**
     * 从数组内查找值并返回键数组(默认查询不超过1000个)
     * @param $arr
     * @param $val
     */
    public static function ARR_FIND_VAL($arr, $val, $count = 1000)
    {
        $res = [];
        while ($count-- > 0) {
            $temp = array_search($val, $arr);
            if (!$temp) {
                return $res;
            }
            $res[] = $temp;
            unset($arr[$temp]);
        }

        return $res;
    }

    /**
     * 获得数量
     * @param $val
     * @return int
     */
    public static function GET_PROB_NUM($val)
    {
        if (is_array($val)) {
            return rand($val[0], $val[1]);
        }

        return $val;
    }

    /*
     * 随机拆分
     * @param $allnum int 总数
     * @param $copies int 总份数
     * @return array
     * */
    public static function GET_RAND_NUM($allnum, $copies)
    {
        $result = []; //结果
        for ($i = $copies; $i > 0; $i--) {
            if ($allnum <= 0) {
                break;
            }

            $num = 0;

            if ($i == 1) {
                $num = $allnum;
            } else {
                $num = mt_rand(0, floor($allnum / $i * 1.5));
                $allnum -= $num;
            }

            $result[] = $num;
        }
        // for ($i = $total_copies; $i > 0; $i--) {
        //     $ls_num = 0;
        //     $num = 0;
        //     if ($total_num > 0) {
        //         if ($i == 1) {
        //             $num += $total_num;
        //         } else {
        //             $max_num = floor($total_num / $i);
        //             $ls_num = mt_rand(0, $max_num);
        //             $num += $ls_num;
        //         }
        //     }
        //     $result[] = $num;
        //     $total_num -= $ls_num;
        // }
        shuffle($result); //打乱数组
        return $result; //返回数组
    }

    /**
     * 记录log
     * @param string $msg
     */
    public static function log($msg, $gtype = 0)
    {
        self::logger($msg, true, $gtype);
    }

    /**
     * 记录msg
     * @param string $msg
     * @param string $event
     */
    public static function msg($msg, $event = '')
    {
        if (MyGlobal::$buprint == 1) {
            self::logger($msg, false);
        } elseif (MyGlobal::$buprint == 2) {
            echo $msg . "\n";
        } elseif (MyGlobal::$buprint == 3) {
            self::logger($msg, false);
            echo $msg . "\n";
        } elseif (MyGlobal::$buprint == 4) {
            if ($event == '') {
                self::logger($msg, false);
                echo $msg . "\n";
            } else if (!in_array($event, MyGlobal::$AllNoEcho) && !in_array($event, MyGlobal::$LogicNoEcho[self::$LTYPE]) && !in_array($event, MyGlobal::$GameNoEcho[self::$GTYPE])) {
                self::logger($msg, false);
                echo $msg . "\n";
            }
        }
    }

    /**
     * 记录msg和log
     * @param string $msg
     * @param bool $islog
     * @param int $gtype
     */
    public static function logger($msg, $islog, $gtype = 0)
    {
        if (self::$GTYPE == GAME_TEMPLATE) {
            $path = LOGIC_NAME[self::$LTYPE];
        } else {
            $path = GAME_NAME[self::$GTYPE];
        }

        if ($gtype == 1) {
            $fp = fopen(ROOT . DS . 'ClientError' . date('Ymd') . "_error.txt", "a");
            flock($fp, LOCK_EX);
            fwrite($fp, MyTools::GET_NOW() . "\t" . $msg . "\r\n");
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            $name = $path;
            $path = $path . self::$LID;

            $fn = $islog ? 'log' : 'msg';
            if (!is_dir(ROOT . DS . $fn)) {
                mkdir(ROOT . DS . $fn, 0777, true);
            }

            if (!is_dir(ROOT . DS . $fn . DS . $name)) {
                mkdir(ROOT . DS . $fn . DS . $name, 0777, true);
            }

            //定时删除日志文件
            $dir = ROOT . DS . $fn . DS . $name;
            if ($fn == 'msg' && is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while (false !== ($file = readdir($dh))) {
                        if ($file != "." && $file != "..") {
                            $fullpath = $dir . "/" . $file;
                            if (!is_dir($fullpath)) {
                                $filedate = date("Y-m-d", filemtime($fullpath));
                                $d1 = strtotime(date("Y-m-d"));
                                $d2 = strtotime($filedate);
                                $Days = round(($d1 - $d2) / 3600 / 24);
                                if ($Days > 3) {
                                    unlink($fullpath);  ////删除文件
                                }
                            }
                        }
                    }
                }
                closedir($dh);
            }
            
            $fp = fopen(ROOT . DS . $fn . DS . $name . DS . date('Ymd') . '_' . $path . ".$fn.txt", "a");
            flock($fp, LOCK_EX);
            fwrite($fp, MyTools::GET_NOW() . "\t" . $msg . "\r\n");
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * 读取配置文件
     * @param $path
     * @return mixed
     */
    public static function GET_FILE_DATA($path)
    {
        // 从文件中读取数据到PHP变量
        $json_string = file_get_contents($path);
        // 用参数true把JSON字符串强制转成PHP数组
        return json_decode($json_string, true);
    }

    /**
     * 距离检测
     * 赤道半径6377.830 极半径6356.9088  平均半径6371.393千米
     * 地球半径有时被使用作为距离单位, 特别是在天文学和地质学中常用。它通常用RE表示。6370.856
     * 地球是一个近乎标准的椭球体，它的赤道半径为6378.140千米，极半径为 6356.755千米，平均半径6371.004千米。
     * 经度每隔0.00001度，距离相差约1米；每隔0.0001度，距离相差约10米；每隔0.001度，距离相差约100米；每隔0.01度，距离相差约1000米；每隔0.1度，距离相差约10000米。
     * 纬度每隔0.00001度，距离相差约1.1米；每隔0.0001度，距离相差约11米；每隔0.001度，距离相差约111米；每隔0.01度，距离相差约1113米；每隔0.1度，距离相差约11132米。
     * 其中，R = 6370996.81;//地球半径，pi()为圆周率π，(lng1,lat1),(lng2,lat2)分别是百度地图的两个经纬度，带入数值计算即可
     * 其中:R为地球半径，均值为6370km.A点经、纬度分别为x1和y1, ，东经为正，西经为负 B点经、纬度分别为x2和y2，北纬为正，南纬为负
     * D＝R* arccos(siny1siny2+cosy1cosy2cos(x1-x2) )
     * R*arccos(sin(lat1*pi() / 180)*sin(lat2*pi() / 180) + cos(lat1*pi() / 180)*cos(lat2*pi() / 180)*cos(lng1*pi() / 180 - lng2*pi() / 180))
     * R*ACOS(1-(POWER((SIN((90-B2)*PI()/180)*COS(A2*PI()/180)-SIN((90-D2)*PI()/180)*COS(C2*PI()/180)),2)+POWER((SIN((90-B2)*PI()/180)*SIN(A2*PI()/180)-SIN((90-D2)*PI()/180)*SIN(C2*PI()/180)),2)+POWER((COS((90-B2)*PI()/180)-COS((90-D2)*PI()/180)),2))/2)
     * @return float
     */
    public static function COUNT_RANGE($uer1, $uer2)
    {
        if (($uer1['lat'] < 0.0001 || $uer2['lat'] < 0.0001) && ($uer1['lng'] < 0.0001 || $uer2['lng'] < 0.0001)) {
            return -1;
        }

        $rad = function ($num) {
            return $num * M_PI / 180.0;
        };

        return acos(sin($rad($uer1['lat'])) * sin($rad($uer2['lat'])) + cos($rad($uer1['lat'])) * cos($rad($uer2['lat'])) * cos($rad($uer1['lng']) - $rad($uer2['lng']))) * 6370996.81;
    }
}

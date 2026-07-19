<?php

/**
 * =============================================================================
 *  日志处理
 * =============================================================================                   
 */

define("ROOT", dirname(dirname(__FILE__)));
define("DS", '/');

class mLog
{
    public static function log($msg = '', $error = false)
    {
        if (!is_dir(ROOT . DS . 'log')) {
            mkdir (ROOT . DS . 'log',0777,true);
        }
        $fp = fopen(ROOT . DS . 'log' . DS . date('Ymd') . ".log.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, date('Y-m-d H:i:s') . "\t" . $msg . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
        if ($error) {
            die($msg);
        }
    }

    public static function msg($msg = '')
    {
        if (!is_dir(ROOT . DS . 'msg')) {
            mkdir (ROOT . DS . 'msg',0777,true);
        }
        $fp = fopen(ROOT . DS . 'msg' . DS . date('Ymd') . ".log.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, date('Y-m-d H:i:s') . "\t" . $msg . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

}

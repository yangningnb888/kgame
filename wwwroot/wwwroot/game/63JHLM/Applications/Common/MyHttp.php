<?php
date_default_timezone_set("Asia/Shanghai");

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/6 0006
 * Time: 16:38
 */

define('URL_CS', 200); //测试使用
define('URL_USER_VERIFICATION', 201); //用户验证
define('URL_USER_WIN', 202); //用户中奖

class MyHttp
{
    private static $url = array( //url:接口地址 method:请求方式
        URL_CS => ['url' => '/back/agent', 'method' => 'POST', 'name' => 'URL_CS'],
        URL_USER_VERIFICATION => ['url' => '/snedVerification', 'method' => 'POST', 'name' => 'URL_USER_VERIFICATION'],
        URL_USER_WIN => ['url' => '/snedGameStart', 'method' => 'POST', 'name' => 'URL_USER_WIN'],
    );

    private static function Myksort($data)
    {
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::Myksort($value);
            }
        }
        return $data;
    }

    private static function signstr($data)
    {
        $data = self::Myksort($data);
        $key = $data['platform_key'] ?? '';
        unset($data['platform_key']);
        $paramString = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $paramString .= $key . '=' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '&';
            } else {
                $paramString .= $key . '=' . $value . '&';
            }
        }

        $paramString = substr($paramString, 0, -1);
        $sign = $paramString . 'apiKey=' . $key;
        $sign = md5($sign);
        return strtoupper($sign);
    }

    public static function request($back_url, $send_data, $urlid, $success_fun, $error_fun, $emergency_fun = null, $call_count = 3, $requestId = '')
    {
        $send_data['requestTime'] = time();
        if (self::$url[$urlid]['method'] == 'POST' || self::$url[$urlid]['method'] == 'PUT') {
            $sign = $send_data;
        }

        $signstr = self::signstr($sign);
        unset($send_data['platform_key']);
        $send_data['sign'] = $signstr;
        $url = $back_url . self::$url[$urlid]['url'];
        $sendarr = array(
            'method' => self::$url[$urlid]['method'],
            'headers' => array(
                'Connection' => 'keep-alive'
            ),
            'success' => function ($response) use ($success_fun, $error_fun, $urlid, $send_data) {
                $data = json_decode($response->getBody(), true);
                if (!isset($data['code'])) {
                    $err = json_encode($data);
                    MyTools::msg('--------------------RecvFromApi : ' . self::$url[$urlid]['name'] . '--ERROR1:' . $err);
                    MyTools::msg(json_encode($send_data));
                    $error_fun($err);
                } else {
                    if ($data['code'] == 200) {
                        MyTools::msg('--------------------RecvFromApi : ' . self::$url[$urlid]['name']);
                        MyTools::msg('--------------------RecvFromApi : ' . json_encode($data['obj']));
                        if (!isset($data['obj'])) {
                            $data['obj'] = [];
                        }

                        $success_fun($data['obj']);
                    } else {
                        MyTools::msg('--------------------RecvFromApi : ' . self::$url[$urlid]['name'] . '--ERROR2:' . $data['msg']);
                        MyTools::msg(json_encode($send_data));
                        MyTools::msg($response->getBody());
                        $error_fun($data['msg']);
                    }
                }
            },
            'error' => function ($exception) use ($success_fun, $error_fun, $send_data, $urlid, $emergency_fun, $call_count, $requestId) {
                MyTools::msg('--------------------ERROR:连接失败：' . $exception . '-over');
                MyTools::msg(self::$url[$urlid]['name'] . ': ' . json_encode($send_data));

                if ($call_count > 0) {
                    $call_count--;
                    self::request($send_data, $urlid, $success_fun, $error_fun, $emergency_fun, $call_count, $requestId);
                } else {
                    $error_fun($exception);
                    if ($emergency_fun != null) {
                        $emergency_fun();
                    }
                }
            }
        );

        if (self::$url[$urlid]['method'] == 'POST' || self::$url[$urlid]['method'] == 'PUT') {
            $sendarr['headers']['Content-Type'] = 'application/json';
            $sendarr['data'] = json_encode($send_data, true);
        }

        MyTools::msg('--------------------SendToApi : ' . self::$url[$urlid]['name']);
        MyTools::$http->request($url, $sendarr);
    }

    public static function putPost($send_data, $urlid, $success_fun, $error_fun)
    {
        $sign = [];
        if (self::$url[$urlid]['method'] == 'POST' || self::$url[$urlid]['method'] == 'PUT') {
            $sign = $send_data;
        }

        $sign['companyId'] = Config::$CompanyId;
        $sign['timestamp'] = time();
        $sign['uri'] = self::$url[$urlid]['url'];

        $signstr = self::signstr($sign);
        $url = Config::$PTAddress . $sign['uri'];
        $header = array(
            'timestamp:' . $sign['timestamp'],
            'sign:' . $signstr,
            'companyId:' . $sign['companyId'],
            'Content-Type:' . 'application/json',
        );

        $oCurl = curl_init();
        if (stripos($url, "https://") !== false) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, true); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_HEADER, 0);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, "PUT"); //设置请求方式
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);

        $data = json_encode($send_data);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $data);

        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);

        if (intval($aStatus["http_code"]) == 200) {
            $data = json_decode($sContent, true);
            if (!isset($data['code'])) {
                $err = json_encode($data);
                MyTools::msg('--------------------RecvFromApi : ' . self::$url[$urlid]['name'] . '--ERROR1:' . $err, true);
                MyTools::msg(json_encode($send_data), true);
                MyTools::msg($sContent, true);
                $error_fun($err);
            } else {
                if ($data['code'] == 200) {
                    MyTools::msg('--------------------RecvFromApi : ' . self::$url[$urlid]['name']);
                    if (!isset($data['obj'])) {
                        $data['obj'] = [];
                    }

                    $success_fun($data['obj']);
                } else {
                    MyTools::msg('--------------------RecvFromApi : ' . self::$url[$urlid]['name'] . '--ERROR2:' . $data['msg'], true);
                    MyTools::msg(json_encode($send_data), true);
                    MyTools::msg($sContent, true);
                    $error_fun($data['msg']);
                }
            }
        } else {
            $exception = json_encode($sContent);
            MyTools::msg('--------------------ERROR:连接失败：' . $exception . '-over', true);
            MyTools::msg(self::$url[$urlid]['name'] . ': ' . json_encode($send_data), true);
            $error_fun($exception);
        }
    }
}

<?php

namespace app\back\controller;


use think\Controller;
use think\Loader;
use think\Log;
use think\Request;

class Google extends Controller
{
    public function secret(){
        Loader::import('PHPGangsta.GoogleAuthenticator',EXTEND_PATH);
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        $qrcode = $ga->getQRCodeGoogleUrl('Blog', $secret);

        Loader::import("org/Token", EXTEND_PATH);
        $this->success('成功', ['googlekey' => $secret, 'qrcode' => $qrcode]);

    }
}
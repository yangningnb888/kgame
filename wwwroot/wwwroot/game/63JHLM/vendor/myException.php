<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/5 0005
 * Time: 15:06
 */

//throw new myException('errormsg');

class myException extends Exception
{
    public function errorMessage()
    {
        // 错误信息
        $errorMsg = "Error: ".$this->getMessage()."\n". $this->getFile()."\nLine: ".$this->getLine()."\n";
        echo $errorMsg;
    }
}
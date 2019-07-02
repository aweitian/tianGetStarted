<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/25
 * Time: 8:54
 */

namespace App\Provider;


use App\ServiceProvider;

class Error extends ServiceProvider
{
    protected $error = array();
    protected $message = "";
    protected $level = 0;//0 normal,1 error

    public function getLastInfo()
    {
        if ($this->hasError()) {
            return $this->getLastErr();
        }
        return $this->getMessage();
    }

    public function setMessage($msg)
    {
        $this->message = $msg;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function hasError()
    {
        return $this->level == 1;
    }

    public function addError($err)
    {
        $this->level = 1;
        if ($err instanceof \Exception) {
            $err = 'Msg:' . $err->getMessage() . "<br>File:" . $err->getFile() . '<br>Line:' . $err->getLine();
        } else if (!is_string($err)) {
            return;
        }
        $this->error[] = $err;
    }

    public function getLastErr()
    {
        return end($this->error);
    }

    public function getAllError($separator = "<br>")
    {
        return join($separator, $this->error);
    }
}
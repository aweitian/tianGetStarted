<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/25
 * Time: 15:10
 */

namespace App\Console;

use Aw\Framework\ConsoleKernel;

class Demo
{
    protected $kernel;


    public function __construct(ConsoleKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function help()
    {
        $wAction = 'GetApiId';
        $key = '0e0ede186ba2a7d71dfc6c9b50bb655b';
        $data = array(
            "time" => "1538117814",
            "FromId" => 1
        );
        $this->kernel->output(json_encode($data));
        $sign = md5($wAction . $key . json_encode($data));
        $this->kernel->output(strtoupper($sign));
    }
}
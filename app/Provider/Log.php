<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/25
 * Time: 8:54
 */

namespace App\Provider;


use App\ServiceProvider;

class Log extends ServiceProvider
{
    public function boot()
    {
        $this->app->bind('log', function () {
            return new \Aw\Log(__DIR__ . "/../../storage/logs");
        }, true);

//        $this->app->bind('log-checkout', function () {
//            $log = new \Aw\Log(__DIR__ . "/../../storage/logs");
//            $log->setLogName('checkout_{}.log');
//            return $log;
//        }, true);

    }
}
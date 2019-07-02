<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 13:35
 */

namespace App\Provider;


use App\ServiceProvider;

class Session extends ServiceProvider
{
    public function register()
    {
        $session = new \Aw\Session();
        $session->start();
        $this->app->instance('session', $session);
    }
}



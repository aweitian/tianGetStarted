<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 13:35
 */

namespace App\Provider;



use App\ServiceProvider;

class Router extends ServiceProvider
{
    public function boot()
    {
        include $this->app->bootstrapPath('routers.php');
    }
}



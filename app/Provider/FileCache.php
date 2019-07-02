<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/25
 * Time: 8:54
 */

namespace App\Provider;


use App\ServiceProvider;

class FileCache extends ServiceProvider
{
    public function boot()
    {
        $this->app->bind(
            'cache', function () {
            return new \Aw\Cache\FileCache(__DIR__ . "/../../storage/app/cache");
        }, true);
    }
}
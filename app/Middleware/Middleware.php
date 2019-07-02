<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/20
 * Time: 18:15
 */

namespace App\Middleware;


use App\Application;

class Middleware
{
    /**
     * @var Application
     */
    protected $app;

    public function __construct()
    {
        $this->app = getApp();
    }
}
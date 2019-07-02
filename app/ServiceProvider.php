<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/20
 * Time: 17:24
 */

namespace App;


class ServiceProvider extends \Aw\Framework\Providers\ServiceProvider
{
    public function __construct()
    {
        parent::__construct(getApp());
    }
}
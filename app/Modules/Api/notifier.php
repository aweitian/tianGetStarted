<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10
 * Time: 13:10
 */

namespace App\Modules\Api;



class notifier
{
    public function index()
    {
        $notifier = new \App\Data\Notifier();
        return $notifier->handle();
    }
}
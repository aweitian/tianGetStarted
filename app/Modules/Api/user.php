<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Api;

use App\Modules\Api;


class user extends Api
{
    public function index()
    {
        return $this->cmd->setData($this->user->getInfo());
    }

    public function add()
    {
        if (!isset($_POST['login'],$_POST['password'],$_POST['password']))
        {
            
        }
    }
}
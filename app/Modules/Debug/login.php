<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Debug;

use App\Modules\debug;
use App\Provider\User;
use Aw\Cmd;

class login extends debug
{
    public function index()
    {
        $user = new User();
        $cmd = $this->cmd;
        $cmd->setData($user->getInfo());
        return $cmd;
    }

    public function loginAs()
    {
        $cmd = $this->cmd;
        $user = new User();
        $f = $user->auth($_POST['login'], $_POST['pwd']);
        if ($f) {
            return $cmd->setMessage("LOGIN OK");
        }
        $cmd->markAsError()->setMessage("LOGIN FAIL");
        return $cmd;
    }
}
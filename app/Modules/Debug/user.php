<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Debug;

use App\Modules\debug;
use App\Provider\User as UserProvider;

class user extends debug
{
    public function index()
    {
        $user = new UserProvider();
        $this->cmd->setData($user->getInfo());
        return $this->cmd;
    }

    public function allRoles()
    {
        $user = new UserProvider();
        return $this->cmd->setData($user->allRoles());
    }

    public function add()
    {
        $user = new UserProvider();
        return $user->add($_POST['login'], $_POST['pwd'], $_POST['role']);
    }

    public function remove()
    {
        $user = new UserProvider();
        return $user->rm($_POST['login']);
    }

    public function updatePwd()
    {
        $user = new UserProvider();
        return $user->updatePwd($_POST['login'], $_POST['old'], $_POST['new']);
    }

    public function resetPwd()
    {
        $user = new UserProvider();
        return $user->resetPwd($_POST['login'], $_POST['pwd']);
    }

    public function updateRole()
    {
        $user = new UserProvider();
        return $user->updateRole($_POST['login'], $_POST['role']);
    }
}
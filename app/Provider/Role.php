<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/20
 * Time: 18:38
 * 负责登陆验证，登陆成功后设置SESSION
 * SESSION中包括用户基本信息，其中包括角色(数组)
 */

namespace App\Provider;


use App\ServiceProvider;
use Aw\Db\Connection\Mysql;
use Exception;

class Role extends ServiceProvider
{
    const ROLE_ROOT = 'root';
    const ROLE_ROOT_TEXT = '超级管理员';

    public function getAllRoles()
    {
        $roles = require $this->app->configPath("privilege.php");
        return $roles['role'];
    }
}
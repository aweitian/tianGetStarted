<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/14
 * Time: 17:13
 */

namespace App\Modules;

use App\Application;
use App\Provider\User;
use Aw\Cmd;

class Api
{
    /**
     * @var Application
     */
    protected $app;
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Cmd
     */
    protected $cmd;
    public function __construct()
    {
        $this->app = getApp();
        $this->user = $this->app->make("user");
        $this->cmd = new Cmd();
    }
}
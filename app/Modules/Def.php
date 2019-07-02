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
use Aw\View;

class Def
{
    /**
     * @var View
     */
    protected $view;

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
        $this->app = Application::getInstance();
        $this->view = new View($this->app->resourcePath('views'));
        $this->user = $this->app->make("user");
        $this->cmd = new Cmd();
    }
}
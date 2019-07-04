<?php
/**
 * Created by PhpStorm.
 * User: awei.tian
 * Date: 7/2/18
 * Time: 7:59 PM
 */

namespace App\Modules;


use App\Application;
use Aw\Cmd;

class debug
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Cmd
     */
    protected $cmd;
    public function __construct()
    {
        $this->app = getApp();
        $this->cmd = new Cmd();
    }
}
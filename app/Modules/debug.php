<?php
/**
 * Created by PhpStorm.
 * User: awei.tian
 * Date: 7/2/18
 * Time: 7:59 PM
 */

namespace App\Modules;


use App\Application;
use Aw\View;

class debug
{
    /**
     * @var View
     */
    protected $view;

    /**
     * @var Application
     */
    protected $app;

    public function __construct()
    {
        $this->app = getApp();
        $this->view = new View($this->app->resourcePath('views') . DIRECTORY_SEPARATOR . 'debug');
    }
}
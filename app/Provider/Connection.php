<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/25
 * Time: 8:54
 */

namespace App\Provider;


use App\ServiceProvider;
use Aw\Arr;
use Aw\Db\Connection\Mysql;
use PDO;

class Connection extends ServiceProvider
{
    public function boot()
    {
        $that = $this;
        $this->app->bind('connection', function () use ($that) {
            $env = $that->app->make("env");
            $cnf = array();
            foreach (array('host', 'port', 'database', 'user', 'password', 'charset') as $item) {
                $cnf[$item] = $env["db_" . $item];
            }
            $silent = array(
                //PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
            );
            $c = new Mysql($cnf, $silent);
            $c->setDebugMode(false);
            return $c;
        }, true);
    }
}
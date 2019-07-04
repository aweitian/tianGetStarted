<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 17:20
 */

use Aw\Routing\Router\Router;

/**
 * @var Router $router
 */
$router = $this->app->make('router');
//
//$router->any('/debug', function () {
//    if (file_exists(__DIR__ . "/../debug.php"))
//        include __DIR__ . "/../debug.php";
//});

$router->any('/api/login', "\\App\\Modules\\Api\\login");

$router->pmcai("/", array(/*'\App\Middleware\Login'*/), array(
    "namespace" => "\\App\\Modules\\Controller\\"
), array(
    "check_dispatch" => true
))->setName('default');


$router->pmcai("/api", array('\App\Middleware\ApiLogin', '\App\Middleware\ApiJsonOutput'), array(
    "namespace" => "\\App\\Modules\\Api\\",
    "ctl_tpl" => "{}",
    "act_tpl" => "{}"
), array(
//    "mask" => "mca",
    "check_dispatch" => true,
//    "module" => "api"
))->setName('api');


$router->pmcai("/debug", array('\App\Middleware\ApiLogin', '\App\Middleware\ApiJsonOutput'), array(
    "namespace" => "\\App\\Modules\\Debug\\",
    "ctl_tpl" => "{}",
    "act_tpl" => "{}"
), array(
//    "mask" => "mca",
    "check_dispatch" => true,
//    "module" => "api"
))->setName('debug');

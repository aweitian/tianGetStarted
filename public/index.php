<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 13:11
 */

error_reporting(E_ALL);
ini_set("display_errors", "On");
require_once __DIR__ . "/../vendor/autoload.php";
$app = new \App\Application(dirname(__DIR__));

$emitter = new \Aw\EventDispatcher();
$request = new \Aw\Http\Request();
$app->instance('request', $request);
$kernel = new \App\Kernel($app, new \Aw\Routing\Router\Router(), $emitter);
$kernel->bootstrap();
$response = $kernel->handle($request);
$response->send();
$kernel->terminal();
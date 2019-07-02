<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/20
 * Time: 17:32
 */

namespace App\Middleware;

use Aw\Config;
use Aw\Http\RedirectResponse;
use Aw\Session;

class Login extends Middleware
{
    public function handle($request, $next)
    {
        if (!$this->app->make('user')->isLogined()) {
            return new RedirectResponse('/privilege/login');
        }
        return $next($request);
    }
}
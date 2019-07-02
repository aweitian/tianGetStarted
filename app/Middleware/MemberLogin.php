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

class MemberLogin extends Middleware
{
    public function handle($request, $next)
    {
        /**
         * @var Session $session
         */
        $session = $this->app->make('session');
        /**
         * @var Config $config
         */
        $config = $this->app->make("config");
        if (!$config->has("session_key.member")) {
            return new RedirectResponse('/login');
        }
        if (!$session->has($config->get("session_key.member"))) {
            return new RedirectResponse('/login');
        }
        return $next($request);
    }
}
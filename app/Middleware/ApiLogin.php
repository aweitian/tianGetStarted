<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/20
 * Time: 17:32
 */

namespace App\Middleware;

use Aw\Cmd;
use Aw\Http\Request;
use Aw\Http\Response;

class ApiLogin extends Middleware
{
    public function handle(Request $request, $next)
    {
        $white_lists = array("/debug/login/loginAs");
        if (in_array($request->getPath(), $white_lists)) {
            return $next($request);
        }
        if (!$this->app->make('user')->isLogin()) {
            $cmd = new Cmd();
            $cmd->error('You are not signed in', Cmd::CODE_REDIRECT_TMP);
            return new Response($cmd->getJson(), 302);
        }
        return $next($request);
    }
}
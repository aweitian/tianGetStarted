<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/20
 * Time: 17:32
 */

namespace App\Middleware;

use Aw\Cmd;
use Aw\Http\Response;

class ApiJsonOutput extends Middleware
{
    public function handle($request, $next)
    {
        $data = $next($request);
        header('Content-Type:application/json; charset=utf-8');
        return $data;
    }
}
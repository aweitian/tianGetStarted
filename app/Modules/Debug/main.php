<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Debug;

use App\Modules\debug;
use Aw\Http\Request;
use Aw\Http\Response;

class main extends debug
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $content = '
        <a href="/debug/wzry">wzry</a><br>
        
        ';
        return new Response($content);
    }

}
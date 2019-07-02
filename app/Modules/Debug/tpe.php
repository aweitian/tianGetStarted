<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Debug;


use App\Data\Provider\TitlePriceExtra;
use App\Modules\debug;
use Aw\Http\Response;

class tpe extends debug
{
    /**
     * @return Response|string
     */
    public function index()
    {
        $data = new TitlePriceExtra(array());
        print json_encode($data->getDemoData());
    }

}
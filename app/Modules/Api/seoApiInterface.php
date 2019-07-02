<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10
 * Time: 13:10
 */

namespace App\Modules\Api;


use App\Data\SeoApiTask;
use Aw\Log;

class seoApiInterface
{
    public function index()
    {
        /**
         * @var Log $log
         */
        $log = getApp()->make('log');

        $api = new SeoApiTask();
        switch ($api->checkSign()) {
            case 1:
                return $api->response(403, "签名失败", array());
            case 2:
                return $api->response(500, "参数不正确", array());
        }
        switch ($_POST['wAction']) {
            case "GetNeed":
//                $log->debug(var_export($_REQUEST, true));
                return $api->GetNeed();
            case "GetApiId":
                return $api->GetApiId();
            case "SetApiId":
                return $api->SetApiId();
            case "ClearApiId":
                $log->debug(file_get_contents("php://input"));
                return $api->ClearApiId();

        }
        return $api->response(403, "禁止访问", array());
    }

}
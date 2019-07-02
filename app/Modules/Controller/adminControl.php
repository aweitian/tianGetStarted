<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */
namespace App\Modules\Controller;

use App\Modules\Def;

class adminControl extends Def
{
     public function indexAction(){
        return $this->view->render('/main/admin');
    }
}

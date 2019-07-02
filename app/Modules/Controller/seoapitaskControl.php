<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */
namespace App\Modules\Controller;
use App\Modules\Def;

class seoapitaskControl extends Def
{
    public function seoapitasklistAction()
    {
        return $this->view->render('/main/seoapitasklist');
    }
   
}



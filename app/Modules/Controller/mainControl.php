<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Controller;

use App\Modules\Def;
use App\Provider\User;

class mainControl extends Def
{
    public function indexAction()
    {
        /**
         * @var User $user
         */
        $user = $this->app->make("user");
        if ($user->isLogined()) {
            return $this->view->render('/main/main');
        } else {
            return $this->view->render('login');
        }

    }
}
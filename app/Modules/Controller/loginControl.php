<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Controller;

use App\Modules\Def;
use Aw\Captcha;
use Aw\Http\Request;
use Aw\Http\Response;

class loginControl extends Def
{
       public function indexAction(Request $request)
    {
        if ($this->app->make('user')->isLogined()) {
             return $this->cmd->getJson();
        }
        if ($request->getMethod() == 'GET')
            return $this->view->render('login');
        else if ($request->getMethod() == 'POST')
            return $this->auth();
        return new Response('Method not allowed', 404);
    }


    public function captchaAction()
    {
        $session = $this->app->make("session");
        $demo = new Captcha($session);
        $demo->getCode_char(6, 110, 30);
    }


       private function auth()
    {
        $env = $this->app->make("env");
        if (isset($_POST['token']) && isset($env['validator_code']) && $_POST['token'] && $_POST['token'] == $env['validator_code']) {

        } else {
            $session = $this->app->make("session");
            $demo = new Captcha($session);
            if (!$demo->check($_POST['code'])) {
                return $this->cmd->error('invalid validate code', 500)->getJson();
            }
        }

        if ($this->user->auth($_POST['username'], $_POST['password'])) {
            return $this->cmd->getJson();
        } else {
            return $this->cmd->error('invalid username or password', 500)->getJson();
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Api;

use App\Modules\Api;
use Aw\Arr;
use Aw\Captcha;
use Aw\Cmd;
use Aw\Http\Request;

class login extends Api
{
    public function index(Request $request)
    {
        if ($request->getMethod() == 'POST')
            return $this->auth();
        $this->cmd->setCode(Cmd::CODE_NOT_FOUND);
        $this->cmd->setMessage('Method not allowed');
        return $this->cmd->getJson();
    }

    public function captcha()
    {
        $session = $this->app->make("session");
        $demo = new Captcha($session);
        $demo->getCode_char(6, 110, 30);
    }

    public function debug()
    {
        $this->cmd->setData($this->user->getInfo());
        return $this->cmd->getJson();
    }

    public function logout()
    {
        $this->user->logout();
        return $this->cmd->getJson();
    }

    private function auth()
    {
        if (!Arr::get($_POST,'login','password'))
        {
            return $this->cmd->setMessage("login or password is required.")->markAsError();
        }
        if ($this->user->auth($_POST['login'], $_POST['password'])) {
            return $this->cmd->setData($this->user->getInfo())->getJson();
        } else {
            return $this->cmd->error('invalid login or password', 500)->getJson();
        }
    }
}
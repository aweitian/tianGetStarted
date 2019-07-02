<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/25
 * Time: 15:10
 */

namespace App\Console;


use App\Provider\User;
use Aw\Framework\ConsoleKernel;

class Pass
{
    protected $kernel;

    public function __construct(ConsoleKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function help()
    {
        $ret = "Pass:calc pwd";
        $this->kernel->output($ret);
        $ret = "Pass:modify old_pwd new_pwd";
        $this->kernel->output($ret);
    }

    public function calc($pass = null)
    {
        if ($pass == null) {
            $this->help();
            return;
        }

        /**
         * @var User $user
         */
        $user = new User();

        $this->kernel->output($user->calcPwd($pass));
    }

    public function modify($pass = null, $new = null)
    {
        if ($pass == null || $new == null) {
            $this->help();
            return;
        }

        /**
         * @var User $user
         */
        $user = new User();
        $pwd = 'superpass=' . $user->calcPwd($pass);
//        $this->kernel->output($pwd);
        $env = file_get_contents(".env");
        if (strpos($env, $pwd) === false) {
            $this->kernel->output("password match failed.");
            return;
        }
        $new_pass = 'superpass=' . $user->calcPwd($new);
        $env = str_replace($pwd, $new_pass, $env);
        file_put_contents(".env", $env);
        $this->kernel->output("OK,new pass is $new");
    }
}
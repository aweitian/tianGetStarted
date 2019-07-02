<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/20
 * Time: 18:38
 */

namespace App\Provider;

use App\ServiceProvider;

class Privilege extends ServiceProvider
{

    /**
     * @var User
     */
    protected $user;

    public function __construct()
    {
        parent::__construct();
        $this->user = $this->app->make('user');
    }

    public function getText($prv)
    {
        switch ($prv) {
            default:
                return "Error";
        }
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getMyRole()
    {
        return $this->user->getRole();
    }

    public function hasPriv($prv)
    {

        return false;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */
namespace App\Modules\Privilege;
use App\Modules\Def;

class admin extends Def
{
    public function add()
    {
        return $this->view->render('/prv/main/admin_add');
    }
     public function edit()
    {
        return $this->view->render('/prv/main/admin_edit');
    }
    public function index()
    {
        return $this->view->render('/prv/main/admin_list');
    }
     public function pwd()
    {
        return $this->view->render('/prv/main/admin_pwd');
    }
}
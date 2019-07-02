<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Api;

//use App\Data\Provider\RegisterCheck;
use App\Modules\Api;
use App\Provider\Privilege;

class admin extends Api
{
    protected $table = 'admin';

    /**
     * @var \App\Data\Admin
     */
    protected $admin;

    public function __construct()
    {
        parent::__construct();
        $this->admin = new \App\Data\Admin();
    }

    public function pwd()
    {
        $this->admin->pwd($_POST);
        return $this->admin->cmd;
    }

    public function test()
    {
//        $admin = new \App\Data\Admin();
//        return $admin->test();
        return $this->cmd;
    }

    public function add()
    {
        $this->admin->add($_POST);
        return $this->admin->cmd;
    }

    public function edit()
    {
        $this->admin->edit($_POST);
        return $this->admin->cmd;
    }

    public function updateMyInfo()
    {
        $this->admin->updateMyInfo($_POST);
        return $this->admin->cmd;
    }

    public function allTeamLeader()
    {
        try {
            $data = $this->admin->allTeamLeader();
            return $this->cmd->ok($data);
        } catch (\Exception $exception) {
            return $this->cmd->error($exception->getMessage());
        }
    }

    public function myWorkmate()
    {
        try {
            $data = $this->admin->myWorkmate();
            return $this->cmd->ok($data);
        } catch (\Exception $exception) {
            return $this->cmd->error($exception->getMessage());
        }
    }

    public function myInfo()
    {
        try {
            $data = $this->admin->myInfo();
            return $this->cmd->ok($data);
        } catch (\Exception $exception) {
            return $this->cmd->error($exception->getMessage());
        }
    }

    public function getUserByPid()
    {
        try {
            $data = $this->admin->getUserByPid($_GET);
            if (is_null($data))
                return $this->admin->cmd;
            return $this->cmd->ok($data);
        } catch (\Exception $exception) {
            return $this->cmd->error($exception->getMessage());
        }
    }

    public function remove()
    {
        $this->admin->remove($_POST);
        return $this->admin->cmd;
    }

    public function row()
    {
        $row = $this->admin->row($_GET);
        if (!empty($row)) {
            return $this->cmd->setData($row);
        }
        return $this->admin->cmd;
    }

    public function index()
    {
        $data = $this->admin->index($_GET);
        if (!empty($data)) {
            return $this->cmd->setData($data);
        }
        return $this->admin->cmd;
    }
}
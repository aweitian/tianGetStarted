<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 18:18
 */

namespace App\Data;


use App\Provider\Privilege;
use App\Provider\User;
use Aw\Build\Mysql\Crud;

class Admin extends Provider
{
    protected $table = 'admin';
    protected $rules = array(
        'name' => "required",
        'pass' => 'required',
        'role' => 'required|range:teamleader,workmate'
    );

    /**
     * @param $data_src
     * @return bool
     */
    public function pwd($data_src)
    {
        if ($this->user->isSupperUser()) {
            $this->cmd->setMessage("超级管理员通过命令行才能修改密码.");
            return false;
        }
        if (!$this->check($data_src, 'pass')) {
            return false;
        }
        $bind = array();
        $bind['pass'] = $this->user->calcPwd($data_src['pass']);
        $bind['admin_id'] = $this->user->getUid();
        $sql = "UPDATE admin SET pass = :pass WHERE admin_id = :admin_id";
        return $this->exec_sql($sql, $bind);
    }

    public function getPid($data_src)
    {
        if (!$this->check($data_src, 'ownerid')) {
            return null;
        }
        $bind = array();
        $bind['admin_id'] = $data_src['ownerid'];
        $sql = "SELECT `pid` FROM admin WHERE admin_id = :admin_id";
        return $this->connection->scalar($sql, $bind);
    }

    public function isWorkmate($data_src)
    {
        if (!$this->check($data_src, 'ownerid')) {
            return null;
        }
        $bind = array();
        $bind['admin_id'] = $data_src['ownerid'];
        $sql = "SELECT `admin_id` FROM admin WHERE admin_id = :admin_id AND `role` = 'workmate'";
        $ret = $this->connection->scalar($sql, $bind);
        return !is_null($ret);
    }

    /**
     * @return array
     */
    public function myInfo()
    {
        $bind = array();
        $bind['admin_id'] = $this->user->getUid();
        $sql = "SELECT * FROM admin WHERE admin_id = :admin_id";
        return $this->connection->fetch($sql, $bind);
    }

    public function allTeamLeader()
    {
        $sql = "SELECT * FROM admin WHERE `role` = 'teamleader'";
        return $this->connection->fetchAll($sql);
    }

    public function myWorkmate()
    {
        if ($this->user->isWorkmate()) {
            $sql = "SELECT * FROM admin WHERE role = '" . User::ROLE_WORKMATE . "' AND admin_id = :admin_id";
            return $this->connection->fetchAll($sql, array('admin_id' => $this->user->getUid()));
        } else if ($this->user->isTeamLeader()) {
            $sql = "SELECT * FROM admin WHERE role = '" . User::ROLE_WORKMATE . "' AND pid = :pid ";
            return $this->connection->fetchAll($sql, array('pid' => $this->user->getUid()));
        } else if ($this->user->isSupperUser()) {
            $sql = "SELECT * FROM admin WHERE role = '" . User::ROLE_WORKMATE . "'";
            return $this->connection->fetchAll($sql);
        }
        return array();
    }

    /**
     * @param $data_src
     * @return bool
     */
    public function updateMyInfo($data_src)
    {
        $dk = array('name', 'pass');
        $that = $this;
        $data_src['admin_id'] = $this->user->getUid();
        if ($this->user->isSupperUser()) {
            $this->cmd->setMessage("超级管理员通过命令行才能修改自己的信息.");
            return false;
        }
        $r = $this->update($this->rules, $dk, $data_src, function (Crud $crud, &$bind) use ($that) {
            $bind['pass'] = $that->user->calcPwd($bind['pass']);
        });
        if (!$r) {
            if ($this->connection->isDuplicateEntry()) {
                $this->cmd->setMessage("用户名已存在");
            }
        }
        return $r;
    }
//
//    public function test()
//    {
//        $data_src = $_POST;
//        if (!$this->validate($this->rules, array('name', 'pass'), $data_src)) {
//            exit('error');
//        }
//        return $data_src;//var_export($data_src,true);
//    }

    /**
     * @param $data_src
     * @return bool
     */
    public function add($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_ADMIN_MGR)) {
            return false;
        }

        $dk = $this->getColumns(array('date'));

//        if ($this->user->isTeamLeader()) {
//            $dk = $this->getColumns(array('date', 'pid'));
//        } else {
//            $dk = $this->getColumns(array('date'));
//        }

        if (!$this->check($data_src, $dk)) {
            return false;
        }

        $that = $this;
        $r = $this->append($this->rules, $dk, $data_src, function (Crud $crud, &$bind) use ($that) {
            $bind['pass'] = $that->user->calcPwd($bind['pass']);
            if ($that->user->isTeamLeader()) {
                $bind['role'] = User::ROLE_WORKMATE;
                $bind['pid'] = $that->user->getUid();
            }
        });

        if (!$r) {
            if ($this->connection->isDuplicateEntry()) {
                $this->cmd->setMessage("用户名已存在");
            }
        }
        return $r;
    }

    public function getUserByPid($data_src)
    {
        if (!$this->check($data_src, 'pid')) {
            return null;
        }
        $sql = "SELECT * FROM `admin` WHERE pid = :pid";

        return $this->connection->fetchAll($sql, arr_get($data_src, 'pid'));

    }

    public function edit($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_ADMIN_MGR)) {
            return false;
        }

        $data_key_range = $this->getColumns(array('date'));

        $that = $this;
        $r = $this->update($this->rules, $data_key_range, $data_src, function (Crud $crud, &$bind) use ($that) {
            if (isset($bind['pass'])) {
                if (strlen($bind['pass']) != 32) {
                    $bind['pass'] = $that->user->calcPwd($bind['pass']);
                }
            }
            //权限
            if ($that->user->isTeamLeader()) {
                $bind['role'] = User::ROLE_WORKMATE;
                $crud->bindField('role');
                $bind['pid'] = $that->user->getUid();
                $crud->bindField('pid');
            }
        });
        if (!$r) {
            if ($this->connection->isDuplicateEntry()) {
                $this->cmd->setMessage("用户名已存在");
            }
        }
        return $r;
    }

    public function remove($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_ADMIN_MGR)) {
            return false;
        }

        if (!$this->check($data_src, 'admin_id')) {
            return false;
        }

        $crud = new Crud('admin');
        $bind = array();
        if ($this->user->isTeamLeader()) {
            $crud->bindWhere('pid');
            $bind['pid'] = $this->user->getUid();
        }
        $bind['admin_id'] = $data_src['admin_id'];
        $crud->bindWhere('admin_id');

        return $this->exec_sql($crud->delete(), $bind);
    }

    /**
     * @param $data_src
     * @return array
     */
    public function row($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_ADMIN_MGR)) {
            return array();
        }

        if (!$this->check($data_src, 'admin_id')) {
            return array();
        }

        return $this->fetch($data_src);
    }

    public function index($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_ADMIN_MGR)) {
            return array();
        }

        $that = $this;

        return $this->search(
            http_query_get('_page', 1, $data_src),
            http_query_get('_size', 10, $data_src),
            function (Crud $crud, &$bind) use ($that) {
                if ($that->user->isTeamLeader()) {
                    $crud->bindWhere('pid');
                    $bind['pid'] = $that->user->getUid();
                } else if ($that->user->isWorkmate()) {
                    $crud->bindWhere('admin_id');
                    $bind['admin_id'] = $that->user->getUid();
                }
            }
        );
    }

    public function all()
    {
        $sql = "SELECT `admin_id`,`name` FROM admin";
        $data = $this->connection->fetchAll($sql);
        return array_column($data, 'name', 'admin_id');
    }
}
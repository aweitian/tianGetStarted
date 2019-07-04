<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/20
 * Time: 18:38
 * 负责登陆验证，登陆成功后设置SESSION
 * SESSION中包括用户基本信息，其中包括角色(数组OR字符串)
 */

namespace App\Provider;


use App\ServiceProvider;
use Aw\Arr;
use Aw\Cmd;
use Aw\Config;
use Aw\Db\Connection\Mysql;
use Exception;

class User extends ServiceProvider
{
    protected $onlyRoot = false;
    protected $session_key;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Mysql
     */
    protected $connection;

    /**
     * @var \Aw\Session
     */
    protected $session;

    /**
     * @var Role
     */
    protected $role;
    protected $env;

    /**
     * User constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->config = $this->app->make("config");
        $this->session = $this->app->make("session");
        $this->env = $this->app->make("env");
        if (!$this->config->has("app.session_key.privilege")) {
            throw new Exception("required config key app.session_key.privilege");
        }
        $this->session_key = $this->config->get("app.session_key.privilege");
        $this->role = $this->app->make("role");
    }

    public function calcPwd($pwd)
    {
        return md5('#$672782FG9iu' . $pwd);
    }

    public function isLogin()
    {
        return $this->session->has($this->session_key);
    }

    public function getRole()
    {
        $info = $this->getInfo();
        return isset($info['role']) ? $info['role'] : null;
    }

    public function getInfo()
    {
        return $this->session->get($this->session_key);
    }


    public function getUid()
    {
        $info = $this->getInfo();
        if ($info) {
            return $info['uid'];
        }
        return null;
    }

    public function getLogin()
    {
        $info = $this->getInfo();
        if ($info) {
            return $info['login'];
        }
        return null;
    }

    public function allRoles()
    {
        return $this->role->getAllRoles();
    }

    /**
     * @param $login
     * @param $password
     * @param array $roles
     * @return Cmd
     */
    public function add($login, $password, array $roles)
    {
        $cmd = new Cmd();
        if (empty($login) || empty($password)) {
            $cmd->markAsError()->setMessage("login or password is required.");
            return $cmd;
        }
        if ($login == $this->env['root']) {
            $cmd->markAsError()->setMessage("$login is reserved.");
            return $cmd;
        }
        $this->connection = $this->app->make("connection");

        $bind = compact('login');
        $bind['password'] = $this->calcPwd($password);
        $bind['last_login'] = date("Y-m-d H:i:s");
        $bind['gen_time'] = date("Y-m-d H:i:s");
        $bind['login_count'] = 1;
        $sql = "INSERT INTO `user` ( `login`, `password`, `last_login`, `gen_time`, `login_count`) VALUES ( :login, :password, :last_login, :gen_time, :login_count );";
        $uid = $this->connection->insert($sql, $bind);
        if ($uid > 0) {
            $allRoles = $this->role->getAllRoles();
            $roles = Arr::g($allRoles, $roles);
            foreach ($roles as $role => $role_txt) {
                $sql = "INSERT INTO `user_role` (`uid`, `role`) VALUES (:uid, :role);";
                $bind = compact('uid', 'role');
                $this->connection->insert($sql, $bind);
            }
            return $cmd->setData($uid);
        }
        return $cmd->markAsError()->setMessage("insert failed");
    }

    public function rm($login)
    {
        $cmd = new Cmd();
        if (empty($login)) {
            $cmd->markAsError()->setMessage("login is required.");
            return $cmd;
        }
        if ($login == $this->env['root']) {
            $cmd->markAsError()->setMessage("$login is reserved.");
            return $cmd;
        }
        $bind = compact('login');
        $this->connection = $this->app->make("connection");
        $sql = "DELETE FROM `user_role` WHERE `uid` = (SELECT `uid` FROM `user` WHERE `login` = :login)";
        $this->connection->exec($sql, $bind);
        $sql = "DELETE FROM `user` WHERE `login` = :login";
        $uid = $this->connection->exec($sql, $bind);
        if ($uid > 0) {
            return $cmd->setData($uid);
        }
        return $cmd->markAsError()->setMessage("$login not found");
    }

    public function resetPwd($login, $new_pwd)
    {
        $cmd = new Cmd();
        if (empty($login) || empty($new_pwd)) {
            $cmd->markAsError()->setMessage("login and new pwd is required.");
            return $cmd;
        }
        if ($login == $this->env['root']) {
            $cmd->markAsError()->setMessage("$login is reserved.");
            return $cmd;
        }
        $this->connection = $this->app->make("connection");

        $bind = compact('login');
        $bind['new_pwd'] = $this->calcPwd($new_pwd);
        $sql = "UPDATE `user` SET `password` = :new_pwd WHERE `login` = :login";
        $uid = $this->connection->exec($sql, $bind);
        if ($uid > 0) {
            return $cmd->setData($uid);
        }
        return $cmd->markAsError()->setMessage("reset password failed");
    }

    public function updatePwd($login, $old_pwd, $new_pwd)
    {
        $cmd = new Cmd();
        if (empty($login) || empty($old_pwd) || empty($new_pwd)) {
            $cmd->markAsError()->setMessage("login,old and new pwd is required.");
            return $cmd;
        }
        if ($login == $this->env['root']) {
            $cmd->markAsError()->setMessage("$login is reserved.");
            return $cmd;
        }
        $this->connection = $this->app->make("connection");

        $bind = compact('login');
        $bind['old_pwd'] = $this->calcPwd($old_pwd);
        $bind['new_pwd'] = $this->calcPwd($new_pwd);
        $sql = "UPDATE `user` SET `password` = :new_pwd WHERE `login` = :login and `password` = :old_pwd";
        $uid = $this->connection->exec($sql, $bind);
        if ($uid > 0) {
            return $cmd->setData($uid);
        }
        return $cmd->markAsError()->setMessage("update password failed");
    }

    public function updateRole($login, array $new_roles)
    {
        $cmd = new Cmd();
        if (empty($login)) {
            $cmd->markAsError()->setMessage("login is required.");
            return $cmd;
        }
        if ($login == $this->env['root']) {
            $cmd->markAsError()->setMessage("$login is reserved.");
            return $cmd;
        }
        $this->connection = $this->app->make("connection");

        $bind = compact('login');
        $uid = $this->connection->scalar("SELECT `uid` FROM `user` WHERE `login` = :login", $bind);
        if (!$uid) {
            return $cmd->markAsError()->setMessage("$login is not exists");
        }
        $sql = "SELECT `uid`, `role` FROM `user_role` WHERE `uid` = $uid";

        $exists_roles = $this->connection->fetchAll($sql);
        $exists_roles = array_column($exists_roles, "role");

        $delete_array = array_diff($exists_roles, $new_roles);
        $add_array = array_diff($new_roles, $exists_roles);

        foreach ($delete_array as $role) {
            $bind = compact("role", "uid");
            $sql = "DELETE FROM `user_role` WHERE `uid` = :uid AND `role` = :role";
            $this->connection->exec($sql, $bind);
        }

        foreach ($add_array as $role) {
            $sql = "INSERT INTO `user_role` (`uid`, `role`) VALUES (:uid, :role);";
            $bind = compact('uid', 'role');
            $this->connection->insert($sql, $bind);
        }

        return $cmd->setMessage("ok");
    }

    public function auth($name, $pass)
    {
        if ($this->env['root'] == $name && $this->calcPwd($pass) == $this->env['password']) {
            $this->_auth(array(Role::ROLE_ROOT => Role::ROLE_ROOT_TEXT), 0, $name);
            return true;
        }
        if (!$this->onlyRoot) {
            $this->connection = $this->app->make("connection");
            $row = $this->connection->fetch("SELECT * FROM `user` WHERE `login` = :code", array('code' => $name));

            if ($row) {
                if ($row['password'] == $this->calcPwd($pass)) {
                    $roles = $this->connection->fetchAll("SELECT * FROM user_role WHERE uid = :uid", array(
                        "uid" => $row['uid']
                    ));

                    $roles = array_column($roles, "role");
                    $allRoles = $this->role->getAllRoles();
                    $bind = array("uid" => $row['uid'], "last_login" => date("Y-m-d H:i:s"));
                    $sql = "UPDATE `user` SET `last_login` = :last_login, `login_count` = `login_count` + 1 WHERE `uid` = :uid";
                    $this->connection->exec($sql, $bind);
                    $this->_auth(Arr::g($allRoles, $roles), $row['uid'], $name);
                    return true;
                }
            }
        }
        return false;
    }

    protected function _auth(array $role, $uid, $name)
    {
        $this->_save_session(compact('role', 'uid', 'name'));
    }

    protected function _save_session(array $data)
    {
        $this->session->set($this->session_key, $data);
    }

    public function logout()
    {
        $this->session->remove($this->session_key);
    }
}
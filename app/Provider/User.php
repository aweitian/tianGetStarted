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
            foreach ($roles as $role) {
                $sql = "INSERT INTO `user_role` (`uid`, `role`) VALUES (:uid, :role);";
                $bind = compact('uid', 'role');
                $this->connection->insert($sql, $bind);
            }
            return $cmd->setData($uid);
        }
        return $cmd->markAsError()->setMessage("insert failed");
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
                if ($row['pass'] == $this->calcPwd($pass)) {
                    $roles = $this->connection->fetchAll("SELECT * FROM user_role WHERE uid = :uid", array(
                        "uid" => $row['uid']
                    ));

                    $roles = array_column($roles, "role");
                    $allRoles = $this->role->getAllRoles();

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
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/14
 * Time: 17:13
 */

namespace App\Data;

use App\Application;
use App\Provider\Privilege;
use App\Provider\User;
use Aw\Build\Mysql\Crud;
use Aw\Cmd;
use Aw\Db\Connection\Mysql;
use Aw\Db\Reflection\Mysql\Table;
use Aw\Pagination;
use Aw\Validator\Rules;

class Provider
{
    /**
     * @var Cmd
     */
    public $cmd;


    /**
     * @var Application
     */
    protected $app;
    /**
     * @var Mysql
     */
    protected $connection;

    /**
     * @var Table
     */
    protected $reflection;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Privilege
     */
    protected $privilege;
    protected $table;

    public function __construct()
    {
        $this->app = getApp();
        $this->connection = $this->app->make('connection');
        $this->cmd = new Cmd();
        $this->user = $this->app->make("user");
        $this->privilege = $this->app->make('privilege');
        $this->reflection = new Table($this->table, $this->connection);
    }

    public function check($data_src, $f)
    {
        if (is_string($f)) {
            if (!isset($data_src[$f])) {
                $this->cmd->markAsError();
                $this->cmd->setMessage("缺少参数{$f}");
                return false;
            }
            return true;
        }
        if (is_array($f)) {
            foreach ($f as $item) {
                if (!isset($data_src[$item])) {
                    $this->cmd->markAsError();
                    $this->cmd->setMessage("缺少参数{$item}");
                    return false;
                }
            }
            return true;
        }
        throw new \Exception("f must be string or array");
    }

    public function chkPrv($prv)
    {
        if (!$this->privilege->hasPriv($prv)) {
            $this->cmd->error("需要权限：{$this->privilege->getText($prv)}");
            return false;
        }
        return true;
    }

    public function str2IntArr($str, $unique = true)
    {
        if (is_string($str))
            $id = explode(',', $str);
        else
            $id = (array)$str;
        $bind = array();
        foreach ($id as $item) {
            if (is_numeric($item))
                $bind[] = $item;
        }
        return $unique ? array_unique($bind) : $bind;
    }

    protected function validate($rules = array(), $cols = null, &$data = array())
    {
        $rule = new Rules();
        if (is_null($cols))
            $cols = array_keys($rules);
        $rule->setRules(arr_get($rules, $cols));
        $rule->setData($data);
        if ($rule->validate()) {
            $data = arr_get($data, $cols);
            return true;
        }
        $this->cmd->markAsError();
        $e = $rule->getErrors();
        $this->cmd->setMessage(current($e));
        return false;
    }

    /**
     * @param $rules
     * @param array $data_src
     * @param array $max_data_key
     * @param null $before_update_callback
     * @return bool
     */
    protected function update($rules, $max_data_key = array(), $data_src = array(), $before_update_callback = null)
    {
        $dk = array_intersect($max_data_key, array_keys($data_src));
        $pk = $this->getPks();
//        var_dump($data_src, $dk,$pk);exit;
        $bind_data = arr_get($data_src, $dk);
        $bind_where = arr_get($data_src, $pk);

        if (empty($bind_where)) {
            $this->cmd->error("需要主键:" . join(",", $pk) . "作为更新条件.");
            return false;
        }

        if (empty($bind_data)) {
            $this->cmd->error("至少需要提交一个数据.");
            return false;
        }

        if (!$this->validate($rules, $dk, $bind_data)) {
            return false;
        }

        $build = new Crud($this->table);
        foreach ($dk as $field) {
            $build->bindField($field);
        }
//        var_dump($this->reflection->getPk());exit;
        foreach ($pk as $p) {
            $build->bindWhere($p);
        }

        $bind = array_merge($bind_data, $bind_where);

        if ($before_update_callback instanceof \Closure) {
            $before_update_callback($build, $bind);
        }

        $sql = $build->update();

//         var_dump($sql, $data_src);exit;
        return $this->exec_sql($sql, $bind);
    }

    /**
     * @param $rules
     * @param $dk
     * @param array $data_src
     * @param null $before_insert_callback
     * @return bool
     */
    protected function append($rules, $dk, $data_src = array(), $before_insert_callback = null)
    {

        if (!$this->validate($rules, $dk, $data_src)) {
            return false;
        }
        $build = new Crud($this->table);
        $fields = array_keys($data_src);
        foreach ($fields as $field) {
            $build->bindField($field);
        }
        if ($before_insert_callback instanceof \Closure) {
            $before_insert_callback($build, $data_src);
        }

        $sql = $build->insert();

//        var_dump($sql, $this->filed_data);exit;
        return $this->exec_sql($sql, $data_src, 'insert');
    }

    /**
     * @param $page
     * @param $size
     * @param null $before_query_callback
     * @param string $search_fields
     * @param string $where_fields
     * @param string $order_by
     * @param bool $user_default_pk_desc
     * @return array
     */
    protected function search($page, $size, $before_query_callback = null, $search_fields = "*", $where_fields = "*", $order_by = "*", $user_default_pk_desc = true)
    {
        $build = new Crud($this->table);

        // fields
        if ($search_fields != '*') {
            if (is_string($search_fields)) {
                $search_fields = explode(',', $search_fields);
            }
            if (is_array($search_fields)) {
                foreach ($search_fields as $field) {
                    if (in_array($field, $this->getColumns(array(), true))) {
                        $build->bindField($field);
                    }
                }
            }
        }

        //  order by
        if (isset($_GET['_order_by'])) {
            $order = explode("=", $_GET['_order_by']);
            if (count($order) == 2) {
                $field = $order[0];
                $asc = $order[1];
            } else {
                $field = $order;
                $asc = "desc";
            }
            $restrict = $order_by == '*'
                ? $this->getColumns(array(), true)
                : (is_array($order_by) ? $order_by : $this->getColumns(array(), true));
            if (in_array($field, $restrict)) {
                if (strtolower($asc) == 'asc') {
                    $build->bindOrderBy("{$order[0]} ASC");
                } else {
                    $build->bindOrderBy("{$order[0]} DESC");
                }
            }
        } else {
            if ($user_default_pk_desc) {
                $db_ref = $this->reflection;
                $od = array();
                foreach ($db_ref->getPk() as $pk) {
                    $od[] = "$pk DESC";
                }
                if ($od) {
                    $build->bindOrderBy(join(",", $od));
                }
            }
        }

        // where
        $restrict = $where_fields == '*'
            ? $this->getColumns(array(), true)
            : (is_array($where_fields) ? $where_fields : $this->getColumns(array(), true));

        $bind = array();

        foreach (array_keys($_GET) as $item) {
            if (in_array($item, $restrict)) {
                if (isset($_GET[$item]) && $_GET[$item] != "") {
                    $build->bindWhere($item);
                    $bind[] = $item;
                }
            }
        }

        $build->useCalcFoundRows();

        $build->bindLimit((($page - 1) * $size) . "," . $size);

        $binds = arr_get($_GET, $bind);
        if ($before_query_callback instanceof \Closure) {
            $before_query_callback($build, $binds);
        }

        $sql = $build->select();


        // var_dump($sql, $binds); exit;
        $data = $this->connection->fetchAll($sql, $binds);
//        $this->connection->closeStm();
        $cnt = $this->connection->scalar($build->count());
        $pagination = new Pagination($cnt, $page, $size, 10);

        return array(
            'data' => $data,
            'count' => $cnt,
            'pagination' => $pagination->getData()
        );
    }


    /**
     * @param $data_src
     * @param null $before_query_callback
     * @param string|array $search_fields
     * @return array
     */
    protected function fetch($data_src = array(), $before_query_callback = null, $search_fields = "*")
    {
        $build = new Crud($this->table);

        // fields
        if ($search_fields != '*') {
            if (is_string($search_fields)) {
                $search_fields = explode(',', $search_fields);
            }
            if (is_array($search_fields)) {
                foreach ($search_fields as $field) {
                    if (in_array($field, $this->getColumns(array(), true))) {
                        $build->bindField($field);
                    }
                }
            }
        }

        $db_ref = $this->reflection;
        $pks = $db_ref->getPk();
        if (array_keys($data_src) != $pks) {
            $this->cmd->markAsError();
            $this->cmd->setMessage("需要主键:" . var_export($pks, true));
            return null;
        }

        foreach ($pks as $pk) {
            $build->bindWhere($pk);
        }

        if ($before_query_callback instanceof \Closure) {
            $before_query_callback($build, $data_src);
        }

        $sql = $build->select();

//        var_dump($sql, $data_src);
//        exit;
        try {
            $data = $this->connection->fetch($sql, $data_src);
            if (empty($data)) {
                $this->cmd->error("没有找到相应的数据", Cmd::CODE_NOT_FOUND);
            }
            return $data;
        } catch (\Exception $exception) {
            $this->cmd->error($exception->getMessage());
            return array();
        }

    }

    /**
     * @param array $data_src
     * @param null $before_remove_callback
     * @return bool
     */
    protected function rm($data_src = array(), $before_remove_callback = null)
    {
        $build = new Crud($this->table);
        $db_ref = $this->reflection;
        $binds = array();
        foreach ($db_ref->getPk() as $pk) {
            if (!isset($data_src[$pk])) {
                $this->cmd->markAsError();
                $this->cmd->setMessage("$pk 值不存在");
                return false;
            }
            $binds[$pk] = $data_src[$pk];
            $build->bindWhere($pk);
        }

        if ($before_remove_callback instanceof \Closure) {
            $before_remove_callback($build, $binds);
        }

        $sql = $build->delete();
        return $this->exec_sql($sql, $binds);
    }

    protected function exec_sql($sql, $data, $mode = 'exec')
    {
        try {
            if ($mode == 'exec')
                $result = $this->connection->exec($sql, $data);
            else
                $result = $this->connection->insert($sql, $data);
            if ($result > 0) {
                $this->cmd->markAsOk();
                $this->cmd->setData($result);
                return true;
            } else {
                $this->cmd->setCode(Cmd::CODE_NO_CHANGE);
                $this->cmd->setMessage("no data updates");
                return true;
            }
        } catch (\Exception $exception) {
            $this->cmd->markAsError();
            $this->cmd->setMessage($exception->getMessage());
            if ($this->connection->isBindParaError()) {
                $this->cmd->setMessage('绑定数据有问题：' . $exception->getMessage());
            } else {
                $this->cmd->setMessage($exception->getMessage());
            }
            return false;
        }
    }

    /**
     * @param array $excepts
     * @param bool $keepPk
     * @return array
     */
    public function getColumns(array $excepts = array(), $keepPk = false)
    {
        $fields = $this->reflection->getColumnNames();
        $ret = array();
        foreach ($fields as $field) {
            if ((!$keepPk && $this->reflection->isPk($field)) || in_array($field, $excepts)) {
                continue;
            }
            $ret[] = $field;
        }
        return $ret;
    }

    /**
     * @return array
     */
    public function getPks()
    {
        return $this->reflection->getPk();
    }
}
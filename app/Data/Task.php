<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/3
 * Time: 18:18
 */

namespace App\Data;


use App\Data\SearchEngine;
use App\Provider\Privilege;
use Aw\Build\Mysql\Crud;
use Aw\Cmd;

class Task extends Provider
{
    protected $table = 'task';
    protected $rules = array(
        'pid' => "required|int",
        'ownerid' => "required|int",
        'searchengineid' => "required|int",
        'keyword' => 'required|array|string:1',
        'url' => 'required|array|string:1',
        'seo_count' => 'required|int',
        'status' => 'required|range:offline,online'//能提交的就这两种状态
    );

    protected $status = array(
        "offline" => '离线',
        'online' => '在线',
        'error' => '提交后有错误'
    );

    public function start($data_src)
    {
        return $this->change_status($data_src, array("offline", "error"), "online");
    }

    public function stop($data_src)
    {
        return $this->change_status($data_src, "online", "offline");
    }

    public function remove_batch($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return 0;
        }
        if (!$this->check_batch_task_id($data_src)) {
            return 0;
        }
        $crud = new Crud($this->table);
        $crud->bindRawWhere("`task_id` in (" . join(",", $data_src['task_id']) . ")");
        $sql = $crud->delete();
        try {
//            var_dump($sql);            exit;
            $count = $this->connection->exec($sql);
            if ($count == 0) {
                $this->cmd->ok(array(), "没有数据更新", Cmd::CODE_NO_CHANGE);
            } else {
                $this->cmd->ok($count, "成功更新{$count}条记录");
            }
        } catch (\Exception $exception) {
            $this->cmd->error($exception->getMessage());
            return 0;
        }
    }

    public function updateSeoCount($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return 0;
        }
        if (!$this->check_batch_task_id($data_src)) {
            return 0;
        }
        $rules = array("seo_count" => "required|int", "task_id" => "required|array|int");
        if (!$this->validate($rules, array("seo_count", 'task_id'), $data_src)) {
            return 0;
        }

        if (!$this->check($data_src, 'seo_count')) {
            return 0;
        }
        $crud = new Crud($this->table);
        $crud->bindField('seo_count');
        $bind = array(
            'seo_count' => $data_src['seo_count']
        );
        if ($this->user->isTeamLeader()) {
            $crud->bindWhere('pid');
            $bind['pid'] = $this->user->getUid();
        } else if ($this->user->isWorkmate()) {
            $crud->bindWhere('ownerid');
            $bind['ownerid'] = $this->user->getUid();
        }
        $crud->bindRawWhere("`task_id` in (" . join(",", $data_src['task_id']) . ")");
        $sql = $crud->update();
        try {
//            var_dump($sql, $bind);            exit;
            $count = $this->connection->exec($sql, $bind);
            if ($count == 0) {
                $this->cmd->ok(array(), "没有数据更新", Cmd::CODE_NO_CHANGE);
            } else {
                $this->cmd->ok($count, "成功更新{$count}条记录");
            }
        } catch (\Exception $exception) {
            $this->cmd->error($exception->getMessage());
            return 0;
        }
    }

    /**
     * @param $data_src
     * @return int
     */
    public function import($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return false;
        }

        $dk = array('ownerid', 'searchengineid', 'keyword', 'url', 'seo_count');
        if (!$this->check($data_src, $dk)) {
            return 0;
        }
        if (!$this->validate($this->rules, $dk, $data_src)) {
            return 0;
        }
        $admin = new Admin();
        if (!$admin->isWorkmate(arr_get($data_src, 'ownerid'))) {
            $this->cmd->error("ownerid 必须是组员。");
            return 0;
        }

        $pid = $admin->getPid(arr_get($data_src, 'ownerid'));

        if (is_null($pid)) {
            $this->cmd->error("ownerid 参数的PID不存在");
            return 0;
        }

        $kw_len = count($data_src['keyword']);
        $url_len = count($data_src['url']);

        if ($kw_len != $url_len) {
            $this->cmd->error("关键词个数和网址个数必须相等");
            return false;
        }

        $first_id = 0;
        $total = 0;
        try {
            $this->connection->beginTransaction();
            for ($i = 0; $i < $kw_len; $i++) {
                $keyword = $data_src['keyword'][$i];
                $url = $data_src['url'][$i];
                $ownerid = $data_src['ownerid'];
                $searchengineid = $data_src['searchengineid'];
                $seo_count = $data_src['seo_count'];
                $sql = "INSERT INTO `task` (keyword,url,ownerid,searchengineid,seo_count,pid) VALUES 
                  (:keyword,:url,:ownerid,:searchengineid,:seo_count,:pid)";

                $bind = compact('keyword', 'url', 'ownerid', 'searchengineid', 'seo_count', 'pid');
                $id = $this->connection->insert($sql, $bind);
                if ($first_id == 0) {
                    $first_id = $id;
                }
                if ($id > 0)
                    $total++;
            }

            $this->cmd->ok($first_id, "一共导入{$total}条数据");
            $this->connection->commit();
            return $total;
        } catch (\Exception $exception) {
            $this->connection->rollback();
            if ($this->connection->isDuplicateEntry()) {
                $err = $exception->getMessage();
                if (preg_match("/Duplicate entry \'(.+?)\'/", $err, $m)) {
                    $this->cmd->error("数据:{$m[1]}已存在");
                    return 0;
                }
            }
            $this->cmd->error($exception->getMessage());
            return 0;
        }
    }

    public function edit($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return false;
        }
        $data_key_range = $this->getColumns(array('date', 'pid', 'app_id', 'err_msg', 'date'));

        $rules = array_merge(
            $this->rules,
            array(
                'keyword' => 'required|str',
                'url' => 'required|str'
            )
        );

        $that = $this;
        $r = $this->update($rules, $data_key_range, $data_src, function (Crud $crud, &$bind) use ($that) {
            //权限
            if ($that->user->isTeamLeader()) {
                $bind['pid'] = $that->user->getUid();
                $crud->bindWhere('pid');
            } else if ($that->user->isWorkmate()) {
                $bind['ownerid'] = $that->user->getUid();
                $crud->bindWhere('ownerid');
            }
        });
        if (!$r) {
            if ($this->connection->isDuplicateEntry()) {
                $this->cmd->setMessage("数据已存在。");
            }
        }
        return $r;
    }

    public function remove($data_src)
    {
        if (!$this->check($data_src, 'task_id')) {
            return false;
        }

        $crud = new Crud('task');
        $bind = array();
        if ($this->user->isTeamLeader()) {
            $crud->bindWhere('pid');
            $bind['pid'] = $this->user->getUid();
        } else if ($this->user->isWorkmate()) {
            $crud->bindWhere('ownerid');
            $bind['ownerid'] = $this->user->getUid();
        }
        $bind['task_id'] = $data_src['task_id'];
        $crud->bindWhere('task_id');

        return $this->exec_sql($crud->delete(), $bind);
    }

    public function row($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return array();
        }

        if (!$this->check($data_src, 'task_id')) {
            return array();
        }
        $that = $this;
        return $this->fetch($data_src, function (Crud $crud, &$bind) use ($that) {
            if ($that->user->isTeamLeader()) {
                $crud->bindWhere('pid');
                $bind['pid'] = $that->user->getUid();
            } else if ($that->user->isWorkmate()) {
                $crud->bindWhere('ownerid');
                $bind['ownerid'] = $that->user->getUid();
            }
        });
    }

    public function index($data_src)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return array();
        }

        if (isset($data_src['export']) && $data_src['export'] != 'all') {
            $ids = explode(',', $data_src['export']);
            foreach ($ids as $id) {
                if (!preg_match("/^\d+$/", $id)) {
                    throw new \Exception("invalid arguments");
                }
            }
            $crud = new Crud($this->table);
            $bind = array();
            $crud->bindRawWhere("task_id in (" . $data_src['export'] . ")");
            if ($this->user->isTeamLeader()) {
                $crud->bindWhere('pid');
                $bind['pid'] = $this->user->getUid();
            } else if ($this->user->isWorkmate()) {
                $crud->bindWhere('ownerid');
                $bind['ownerid'] = $this->user->getUid();
            }
            $sql = $crud->select();
            $data = array();
            $data['data'] = $this->connection->fetchAll($sql, $bind);
        } else {
            $that = $this;
            $data = $this->search(
                http_query_get('_page', 1, $data_src),
                http_query_get('_size', 10, $data_src),
                function (Crud $crud, &$bind) use ($that, $data_src) {
                    if (isset($data_src['url']) && $data_src['url']) {
                        $crud->unBindWhere('url');
                        $crud->bindRawWhere("url like concat( '%',:url,'%')");
                    }

                    $kw = arr_get_item($data_src, 's_keyword', null);
                    if ($kw) {
                        $op = intval(arr_get_item($data_src, 'keyword_op', 0));
                        if ($op == 0) {
                            $crud->bindRawWhere("keyword like concat( '%',:s_keyword,'%')");
                            $bind['s_keyword'] = $data_src['s_keyword'];
                        } else if ($op == 1) {
                            $crud->bindRawWhere('keyword = :s_keyword');
                            $bind['s_keyword'] = $data_src['s_keyword'];
                        } else if ($op == 2) {
                            $crud->bindRawWhere("keyword like concat( '',:s_keyword,'%')");
                            $bind['s_keyword'] = $data_src['s_keyword'];
                        } else if ($op == 3) {
                            $crud->bindRawWhere("keyword like concat( '%',:s_keyword,'')");
                            $bind['s_keyword'] = $data_src['s_keyword'];
                        }
                    }

                    if ($that->user->isTeamLeader()) {
                        $crud->bindWhere('pid');
                        $bind['pid'] = $that->user->getUid();
                    } else if ($that->user->isWorkmate()) {
                        $crud->bindWhere('ownerid');
                        $bind['ownerid'] = $that->user->getUid();
                    }
                }
            );
        }
        $this->merge_index_data($data['data']);
        return $data;
    }

    protected function merge_index_data(&$data)
    {
        $se = new SearchEngine();
        $se_data = $se->get();

        $admin = new Admin();
        $admin_data = $admin->all();


        $data = arr_merge($data, array(
            'pid', 'teamleader', $admin_data
        ), array(
            'searchengineid', 'searchengine', $se_data
        ), array(
            'status', 'status_text', $this->status
        ), array(
            'ownerid', 'name', $admin_data
        ));
    }


    /**
     * @param $data_src
     * @param $from
     * @param $to
     * @return int
     */
    protected function change_status($data_src, $from, $to)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return 0;
        }
        if (!$this->check_batch_task_id($data_src)) {
            return 0;
        }
        $crud = new Crud($this->table);
        $crud->bindField('status');
        if ($from == 'online')
            $crud->bindField('api_id');
        $bind = array(
            'status' => $to,
        );
        if ($from == 'online') {
            $bind['api_id'] = null;
        }
        if (is_string($from)) {
            $crud->bindRawWhere("`status` = '" . $from . "'");
        } elseif (is_array($from)) {
            $f = array();
            foreach ($from as $item) {
                $f[] = "`status` = '{$item}'";
            }
            $crud->bindRawWhere("(" . join(" OR ", $f) . ")");
        }
        $crud->bindRawWhere("`task_id` in (" . join(",", $data_src['task_id']) . ")");
        $sql = $crud->update();
        try {
//            var_dump($sql, $bind);exit;
            $count = $this->connection->exec($sql, $bind);
            if ($count == 0) {
                $this->cmd->ok(array(), "没有数据更新", Cmd::CODE_NO_CHANGE);
            } else {
                $this->cmd->ok($count, "成功更新{$count}条记录");
            }
        } catch (\Exception $exception) {
            $this->cmd->error($exception->getMessage());
            return 0;
        }

    }

    protected function check_batch_task_id($data_src)
    {
        $rules = array(
            'task_id' => 'required|array|int'
        );
        if (!$this->check($data_src, 'task_id')) {
            return 0;
        }
        if (!$this->validate($rules, array('task_id'), $data_src)) {
            return 0;
        }
        if (empty($data_src['task_id'])) {
            $this->cmd->error("一次至少提交1个ID");
            return 0;
        }
        if (count($data_src['task_id']) > 1000) {
            $this->cmd->error("一次最多提交1000个ID");
            return 0;
        }
        return 1;
    }

}
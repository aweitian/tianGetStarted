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
use App\Provider\User;
use Aw\Build\Mysql\Crud;
use Aw\Cmd;

class Statistics extends Provider
{
    protected $table = 'task';

    /**
     * @return bool
     */
    public function taskCnt()
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return false;
        }
        try {
            $build = new Crud("task");
            $build->bindField("count(task_id)");
            $build->bindRawWhere("`status` = 'online'");
            $bind = array();
            $this->checkTaskOwner($build, $bind);
            $sql = $build->select();
            $this->cmd->setData($this->connection->scalar($sql, $bind));
            return true;
        } catch (\Exception $exception) {
            $this->cmd->markAsError()->setMessage($exception->getMessage());
            return false;
        }

    }

    /**
     * @return bool
     */
    public function todayConsumeCnt()
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return false;
        }
        $bind = array(
            'date' => date('Y-m-d')
        );
        try {
            $build = new Crud("rank_history");
            $build->bindField("count(rank_history.rank_history_id)");
            $build->bindRawWhere("rank_history.`date` = :date");
            $build->bindRawWhere("rank_history.`rank_last` <= 10");
            $build->bindJoin("LEFT JOIN `task` ON `task`.`rank_id` = `rank_history`.`rank_id`");
            $this->checkHisOwner($build, $bind);
            $sql = $build->select();
            $this->cmd->setData($this->connection->scalar($sql, $bind));
            return true;
        } catch (\Exception $exception) {
            $this->cmd->markAsError()->setMessage($exception->getMessage());
            return false;
        }
    }

    public function completionRate()
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return false;
        }
        return $this->_completionRate();
    }

    public function bdCompletionRate()
    {
        $se = new SearchEngine();
        return $this->_completionRate($se->g("百度"));
    }

    public function soCompletionRate()
    {
        $se = new SearchEngine();
        return $this->_completionRate($se->g("360"));
    }

    public function sgCompletionRate()
    {
        $se = new SearchEngine();
        return $this->_completionRate($se->g("搜狗"));
    }

    public function mbdCompletionRate()
    {
        $se = new SearchEngine();
        return $this->_completionRate($se->g("百度移动"));
    }

    public function m360CompletionRate()
    {
        $se = new SearchEngine();
        return $this->_completionRate($se->g("360移动"));
    }

    public function msgCompletionRate()
    {
        $se = new SearchEngine();
        return $this->_completionRate($se->g("搜狗移动"));
    }

    public function smCompletionRate()
    {
        $se = new SearchEngine();
        return $this->_completionRate($se->g("神马"));
    }

    public function allCompletionSum()
    {
        try {
            $bind = array();
            $build = new Crud("rank_history");
            $build->bindField("count(rank_history.`rank_id`) AS sum");
            $build->bindField("DATE_FORMAT(rank_history.`date`,'%d') as day");
            $build->bindRawWhere("rank_history.`rank_last` <= 10");
            //$build->bindRawWhere("DATE_FORMAT( rank_history.`date`, '%Y%m' ) = DATE_FORMAT( CURDATE( ) , '%Y%m' )");
            switch ($this->user->getRole()) {
                case User::ROLE_SUPERUSER:
                    break;
                case User::ROLE_TEAM_LEADER:
                    $build->bindJoin("LEFT JOIN `task` ON `task`.`rank_id` = `rank_history`.`rank_id`");
                    break;
                case User::ROLE_WORKMATE:
                    $build->bindJoin("LEFT JOIN `task` ON `task`.`rank_id` = `rank_history`.`rank_id`");
                    break;
            }
            //
            $build->bindRawWhere("PERIOD_DIFF( date_format( now() , '%Y%m' ) , date_format(rank_history.`date`, '%Y%m' ) ) = 1");
            $this->checkHisOwner($build, $bind);
            $build->bindGroupBy("date(rank_history.`date`)");
            $pre_month_data = $build->select();
//            var_dump($pre_month_data);exit;
            $data = array();
            $data['pre'] = $this->connection->fetchAll($pre_month_data, $bind);

            $bind = array();
            $build = new Crud("rank_history");
            $build->bindField("count(rank_history.`rank_id`) AS sum");
            $build->bindField("DATE_FORMAT(rank_history.`date`,'%d') as day");
            $build->bindRawWhere("rank_history.`rank_last` <= 10");
            $build->bindRawWhere("DATE_FORMAT( rank_history.`date`, '%Y%m' ) = DATE_FORMAT( CURDATE( ) , '%Y%m' )");
            switch ($this->user->getRole()) {
                case User::ROLE_SUPERUSER:
                    break;
                case User::ROLE_TEAM_LEADER:
                    $build->bindJoin("LEFT JOIN `task` ON `task`.`rank_id` = `rank_history`.`rank_id`");
                    break;
                case User::ROLE_WORKMATE:
                    $build->bindJoin("LEFT JOIN `task` ON `task`.`rank_id` = `rank_history`.`rank_id`");
                    break;
            }
            //$build->bindJoin("LEFT JOIN `task` ON `task`.`rank_id` = `rank_history`.`rank_id`");
            //$build->bindWhere("`task`.`searchengineid` = :searchengineid");
            //$bind['searchengineid'] = $seid;
            $this->checkHisOwner($build, $bind);
            $build->bindGroupBy("date(rank_history.`date`)");
            $now_data = $build->select();
//            var_dump($now_data);exit;
            $data['now'] = $this->connection->fetchAll($now_data, $bind);
            $this->cmd->setData($data);
            return true;
        } catch (\Exception $exception) {
            $this->cmd->markAsError()->setMessage($exception->getMessage());
            return false;
        }

    }


    public function bdCompletionSum()
    {
        try {
            $se = new SearchEngine();
            $se_id_mbd = $se->g("百度移动");
            $se_id_pcbd = $se->g("百度");
            $bind = array();
            $build = new Crud("rank_history");
            $build->bindField("count(rank_history.`rank_id`) AS sum");
            $build->bindField("DATE_FORMAT(rank_history.`date`,'%d') as day");
            $build->bindRawWhere("rank_history.`rank_last` <= 10");
            $build->bindJoin("LEFT JOIN `task` ON `task`.`rank_id` = `rank_history`.`rank_id`");
            $build->bindRawWhere("(`task`.`searchengineid` = $se_id_mbd OR `task`.`searchengineid` = $se_id_pcbd)");
            $build->bindRawWhere("PERIOD_DIFF( date_format( now() , '%Y%m' ) , date_format(rank_history.`date`, '%Y%m' ) ) =1");
            $this->checkHisOwner($build, $bind);
            $build->bindGroupBy("date(rank_history.`date`)");
            $pre_month_data = $build->select();
//            var_dump($pre_month_data);exit;
            $data = array();
            $data['pre'] = $this->connection->fetchAll($pre_month_data, $bind);

            $bind = array();
            $build = new Crud("rank_history");
            $build->bindField("count(rank_history.`rank_id`) AS sum");
            $build->bindField("DATE_FORMAT(rank_history.`date`,'%d') as day");
            $build->bindRawWhere("rank_history.`rank_last` <= 10");
            $build->bindRawWhere("DATE_FORMAT( rank_history.`date`, '%Y%m' ) = DATE_FORMAT( CURDATE( ) , '%Y%m' )");
            $build->bindJoin("LEFT JOIN `task` ON `task`.`rank_id` = `rank_history`.`rank_id`");
            $build->bindRawWhere("(`task`.`searchengineid` = $se_id_mbd OR `task`.`searchengineid` = $se_id_pcbd)");
            $this->checkHisOwner($build, $bind);
            $build->bindGroupBy("date(rank_history.`date`)");
            $now_data = $build->select();
//            var_dump($now_data);exit;
            $data['now'] = $this->connection->fetchAll($now_data, $bind);
            $this->cmd->setData($data);
            return true;
        } catch (\Exception $exception) {
            $this->cmd->markAsError()->setMessage($exception->getMessage());
            return false;
        }

    }


    protected function _completionRate($searchengineid = null)
    {
        if (!$this->chkPrv(Privilege::PRIVILEGE_DATA_MGR)) {
            return false;
        }
        $bind = array();
        try {
            $build = new Crud("task");
            $build->bindField("count(task_id)");
            if ($searchengineid != null) {
                $build->bindWhere('searchengineid');
                $bind['searchengineid'] = $searchengineid;
            }
            $this->checkTaskOwner($build, $bind);
            $sql_total = $build->select();

            $build->bindRawWhere("`rank_last` <= 10");
            $sql_completed = $build->select();

            $total = $this->connection->scalar($sql_total, $bind);
            if ($total == 0) {
                $this->cmd->setData(0);
                return true;
            }

            $completed = $this->connection->scalar($sql_completed, $bind);

            if (is_numeric($completed) && is_numeric($total)) {
                $this->cmd->setData($completed / $total);
                return true;
            }
            $this->cmd->markAsError()->setMessage("数据异常");
            return false;
        } catch (\Exception $exception) {
            $this->cmd->markAsError()->setMessage($exception->getMessage());
            return false;
        }
    }

    protected function checkTaskOwner(Crud $build, &$bind)
    {
        $that = $this;
        switch ($that->user->getRole()) {
            case User::ROLE_SUPERUSER:
                break;
            case User::ROLE_TEAM_LEADER:
                $build->bindRawWhere("`pid`  = :id");
                $bind['id'] = $that->user->getUid();
                break;
            case User::ROLE_WORKMATE:
                $build->bindRawWhere("`ownerid`  = :id");
                $bind['id'] = $that->user->getUid();
                break;
        }
    }
    protected function checkHisOwner(Crud $build, &$bind)
    {
        $that = $this;
        switch ($that->user->getRole()) {
            case User::ROLE_SUPERUSER:
                break;
            case User::ROLE_TEAM_LEADER:
                $build->bindRawWhere("task.`pid`  = :id");
                $bind['id'] = $that->user->getUid();
                break;
            case User::ROLE_WORKMATE:
                $build->bindRawWhere("task.`ownerid`  = :id");
                $bind['id'] = $that->user->getUid();
                break;
        }
    }
}
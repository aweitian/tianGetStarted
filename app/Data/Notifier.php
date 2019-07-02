<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 *
 * xAction = NoticeSearchTaskResult
 *
 * xParam = {
 * "userId": 100000, "time": 1492060937, "businessType": 2006, "apiExtend": 1, "value": {
 * "TaskId": 29498,
 * "RankFirst": 1,
 * "RankLast": 1,
 * "RankLastChange": 0,
 * "UpdateTime": "2017-04-13 12:01:38"
 * } }
 * xSign = 72F92213D8644FD15DEDDF08588AE52C
 */

namespace App\Data;

use Aw\Db\Connection\Mysql;
use Aw\Log;

class Notifier extends Provider
{
    protected $key = 'AC5D02C2444140E3071B1BC58F3706AD';

    public function handle()
    {
        /**
         * @var Log $log
         */
        $log = $this->app->make('log');
        $log->debug(var_export($_REQUEST, true));
        $dk = array('xAction', 'xParam', 'xSign');
        $data = arr_get($_POST, $dk);
        if (count($data) != 3)
            return '0';
        $act = $data['xAction'];
        $xParam = $data['xParam'];
        $xSign = $data['xSign'];
        //NotifySearchTaskResult
        $vSign = md5($act . $this->key . $xParam);
        $vSign = strtoupper($vSign);
        if ($vSign != $xSign) {
            $log->error('api notifier sign check failed.' . var_export($data, true));
            return '0';
        }

        $xParam = json_decode($xParam, true);
        $data = $xParam['Value'];
        $rank_id = $data['TaskId'];
        $updatetime = $data['UpdateTime'];
        $rank_first = $data["RankFirst"];
        $rank_last = $data["RankLast"];
        $rank_change = $data["RankLastChange"];

        return $this->record($rank_id, $updatetime, $rank_first, $rank_last, $rank_change);
    }


    public function sync($date = "", $only_rank = false)
    {
        if ($date == "")
            $date = date('Y-m-d');
        /**
         * @var Log $log
         */
        $log = $this->app->make('log');
        $env = $this->app->make('env');
        /**
         * @var Mysql $connection
         */
        $connection = $this->app->make("connection");
//        var_dump($env);exit;
        $rank_sync_connection = new Mysql(array(
            'host' => $env['rank_src_host'],
            'port' => $env['rank_src_port'],
            'database' => $env['rank_src_database'],
            'user' => $env['rank_src_user'],
            'password' => $env['rank_src_password'],
            'charset' => $env['rank_src_charset']));
        if ($only_rank) {
            $sql = "SELECT DISTINCT ABS(rank_id) AS rank_id FROM `task` WHERE `rank_last` IS NULL AND `status` = 'online'";
            $data = $connection->fetchAll($sql);
        } else {
            $sql = "SELECT DISTINCT ABS(rank_id) AS rank_id FROM `task` WHERE  status = 'online' AND ABS(rank_id) NOT IN (
                SELECT rank_id FROM `rank_history` WHERE `date` = :date
            )";
            $data = $connection->fetchAll($sql, compact('date'));
        }

//        $data = array(array('rank_id' => 11750236));
        foreach ($data as $datum) {
            $rank_id = $datum['rank_id'];
            $sql = "SELECT * FROM rank_history WHERE rank_id=:rank_id AND `date` = :date";
            $row = $rank_sync_connection->fetch($sql, compact('rank_id', 'date'));
            if (empty($row)) {
                $log->debug("$rank_id is not reached yet.");
                continue;
            }
            $rank_id = $row['rank_id'];
            $updatetime = $row['updatetime'];
            $rank_first = $row['rank_first'];
            $rank_last = $row['rank_last'];
            $rank_change = $row['rank_change'];

//            print "$rank_id \n";
            if ($only_rank) {
                $this->updateRankOnly($rank_id, $updatetime, $rank_first, $rank_last, $rank_change);
            } else {
                $this->record($rank_id, $updatetime, $rank_first, $rank_last, $rank_change);
            }
        }
    }


    /**
     * @param $rank_id
     * @param $updatetime
     * @param $rank_first
     * @param $rank_last
     * @param $rank_change
     * @return string
     */
    public function record($rank_id, $updatetime, $rank_first, $rank_last, $rank_change)
    {
//        $rank_id = $data['TaskId'];
//        $updatetime = $data['UpdateTime'];
        $date = explode(' ', $updatetime);
        $date = $date[0];
//        $rank_first = $data["RankFirst"];
//        $rank_last = $data["RankLast"];
//        $rank_change = $data["RankLastChange"];
        /**
         * @var Log $log
         */
        $log = $this->app->make('log');
        $this->connection->beginTransaction();
        try {
            //检查数据是否提交

            //是否存在于TASK中
            $exists_sql = "SELECT `rank_id` FROM task WHERE `rank_id` = :rank_id";
            $row = $this->connection->scalar($exists_sql, compact('rank_id'));
//            var_dump($row,$rank_id);exit;
            if (is_null($row)) {
                $log->debug("$rank_id is not in rank_up system.");
                return 1;
            }

            $exists_sql = "SELECT rank_history_id FROM rank_history WHERE rank_id = :rank_id AND date = :date";
            $row = $this->connection->scalar($exists_sql, compact('rank_id', 'date'));
            if ($row) {
                $log->debug("$rank_id is repeat posted");
                return 1;
            }

            $sql = "INSERT INTO `rank_history` (
              `rank_id`,
              `rank_first`,
              `rank_last`,
              `rank_change`,
              `updatetime`,
              `date`
            ) 
            VALUES
              (
                :rank_id,
                :rank_first,
                :rank_last,
                :rank_change,
                :updatetime,
                :date
              ) ;";
            $bind = compact('rank_id', 'rank_first', 'rank_last', 'rank_change', 'updatetime', 'date');
//            var_dump($sql,$bind);exit;
            $this->connection->insert($sql, $bind);

            //更新TASK RANK
            $sql = "UPDATE task SET rank_first = :rank_first,rank_last = :rank_last,
                    rank_update_date=:updatetime
                WHERE rank_id = :rank_id";
//            $rank_update_date = $date;
            $bind = compact('rank_id', 'rank_first', 'rank_last', 'updatetime');
//                        var_dump($sql,$bind);exit;

            $this->connection->exec($sql, $bind);

            $rank_id = -1 * $rank_id;
            $exists_sql = "SELECT task_id FROM task WHERE rank_id = :rank_id";
            $row = $this->connection->scalar($exists_sql, compact('rank_id'));
            if ($row) {
                $sql = "INSERT INTO `rank_history` (
                  `rank_id`,
                  `rank_first`,
                  `rank_last`,
                  `rank_change`,
                  `updatetime`,
                  `date`
                ) 
                VALUES
                  (
                    :rank_id,
                    :rank_first,
                    :rank_last,
                    :rank_change,
                    :updatetime,
                    :date
                  ) ;";
                $bind = compact('rank_id', 'rank_first', 'rank_last', 'rank_change', 'updatetime', 'date');
//            var_dump($sql,$bind);exit;
                $this->connection->insert($sql, $bind);

                //更新TASK RANK
                $sql = "UPDATE task SET rank_first = :rank_first,rank_last = :rank_last,
                    rank_update_date=:updatetime
                WHERE rank_id = :rank_id";
                $bind = compact('rank_id', 'rank_first', 'rank_last', 'updatetime');
//                        var_dump($sql,$bind);exit;

                $this->connection->exec($sql, $bind);
            }
            $this->connection->commit();
            return '1';
        } catch (\Exception $exception) {
            $this->connection->rollback();
            $log->error("Notifier failed." . $exception->getMessage());
            return $exception->getMessage();
        }
    }


    /**
     * @param $rank_id
     * @param $updatetime
     * @param $rank_first
     * @param $rank_last
     * @param $rank_change
     * @return string
     */
    public function updateRankOnly($rank_id, $updatetime, $rank_first, $rank_last, $rank_change)
    {
//        $rank_id = $data['TaskId'];
//        $updatetime = $data['UpdateTime'];
        $date = explode(' ', $updatetime);
        $date = $date[0];
//        $rank_first = $data["RankFirst"];
//        $rank_last = $data["RankLast"];
//        $rank_change = $data["RankLastChange"];
        /**
         * @var Log $log
         */
        $log = $this->app->make('log');
        $this->connection->beginTransaction();
        try {
            //检查数据是否提交

            //是否存在于TASK中
            $exists_sql = "SELECT `rank_id` FROM task WHERE `rank_id` = :rank_id";
            $row = $this->connection->scalar($exists_sql, compact('rank_id'));
//            var_dump($row,$rank_id);exit;
            if (is_null($row)) {
                $log->debug("$rank_id is not in rank_up system.");
                return 1;
            }

            $exists_sql = "SELECT rank_history_id FROM rank_history WHERE rank_id = :rank_id AND date = :date";
            $row = $this->connection->scalar($exists_sql, compact('rank_id', 'date'));
            if ($row) {
                $log->debug("$rank_id is repeat posted");
                return 1;
            }

            //更新TASK RANK
            $sql = "UPDATE task SET rank_first = :rank_first,rank_last = :rank_last,
                    rank_update_date=:updatetime
                WHERE rank_id = :rank_id";
//            $rank_update_date = $date;
            $bind = compact('rank_id', 'rank_first', 'rank_last', 'updatetime');
//                        var_dump($sql,$bind);exit;

            $this->connection->exec($sql, $bind);

            $rank_id = -1 * $rank_id;
            $exists_sql = "SELECT task_id FROM task WHERE rank_id = :rank_id";
            $row = $this->connection->scalar($exists_sql, compact('rank_id'));
            if ($row) {
                //更新TASK RANK
                $sql = "UPDATE task SET rank_first = :rank_first,rank_last = :rank_last,
                    rank_update_date=:updatetime
                WHERE rank_id = :rank_id";
                $bind = compact('rank_id', 'rank_first', 'rank_last', 'updatetime');
//                        var_dump($sql,$bind);exit;

                $this->connection->exec($sql, $bind);
            }
            $this->connection->commit();
            return '1';
        } catch (\Exception $exception) {
            $this->connection->rollback();
            $log->error("Notifier failed." . $exception->getMessage());
            return $exception->getMessage();
        }
    }
}
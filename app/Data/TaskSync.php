<?php
/**
 * Created by PhpStorm.
 * User: awei.tian
 * Date: 4/6/18
 * Time: 1:29 PM
 * 3.4 附录 D 搜索引擎编码(searchType) 编码值 说明
 * 1010 百度 PC
 * 1015 360 PC
 * 1030 搜狗 PC
 * 7010 百度手机
 * 1015 360 手机
 * 1030 搜狗手机
 * 7070 神马
 */

namespace App\Data;


class TaskSync extends Provider
{
    public function sync($json_from_get_all_task)
    {
        //
        /**
         * @var \Aw\Log $log
         */
        $log = $this->app->make('log-rank-sync-db');
        $data = json_decode($json_from_get_all_task, true);
        $total = 0;
        $fail = 0;
        if (isset($data['xCode']) && $data['xCode'] == 0) {
            $sql = "TRUNCATE TABLE `sync_task_cache`";
            $this->connection->exec($sql);
            $xValue = $data['xValue'];
            for ($i = 0; $i < count($xValue); $i++) {
                $sql = "INSERT INTO `sync_task_cache` (`TaskId`, `Status`)
                        VALUES
                          (:TaskId, :Status)";
                $id = $this->connection->insert($sql, $xValue[$i]);
                if ($id > 0)
                    $total++;
                else
                    $fail++;
            }
            if ($fail > 0) {
                $log->error("从服务器DUMP下来的数据插入到CACHE表中失败: $fail 条");
                return;
            }
            $log->debug("服务器上同步{$total}条记录");
        } else {
            $err = $json_from_get_all_task . var_export(func_get_args(), true);
            $log->error($err);
        }
    }
}
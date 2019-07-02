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


use Aw\Httpclient\Curl;
use Aw\Log;

class RankSubmit extends Provider
{
    protected $notify_time = 9;
    protected $key = 'AC5D02C2444140E3071B1BC58F3706AD';
    public $error = array();

    public function cron()
    {
        set_time_limit(0);
        $this->start();
    }

    public function start()
    {
        $total = 0;
        /**
         * @var Log $log
         */
        $log = $this->app->make('log-rank-submit');
        $this->error = array();
        //搜索所有正在运行的RANK_ID为NULl的任务
        $se = new SearchEngine();
        $searchengineids = $se->get();
        //var_export($searchengineids);exit;
        foreach ($searchengineids as $searchengineid => $sev) {
            $sql = "SELECT DISTINCT `keyword`,`url` FROM `task` 
                WHERE `status` = 'online'
              AND `rank_id` IS NULL
              AND  `searchengineid` = :searchengineid";
            $data = $this->connection->fetchAll($sql, compact('searchengineid'));
            if (empty($data))
                continue;
            $keyword = array_column($data, 'keyword');
            $url = array_column($data, 'url');
            $total += $this->submit($searchengineid, $keyword, $url, 10);
        }
        if ($total > 0) {
            $log->debug("提交了{$total}条记录");
        }
        return $total;
    }

    /**
     * @param $searchengineid
     * @param $keywords
     * @param $urls
     * @param $auto_time
     * @return bool|int
     */
    public function submit($searchengineid, $keywords, $urls, $auto_time)
    {
        $total = 0;
        if (empty($keywords)) {
            return 0;
        }
        $cli = new Curl();
        $se_ins = new SearchEngine();
        /**
         * @var \Aw\Log $log
         */
        $log = $this->app->make('log-rank-submit');
        $act = 'AddSearchTask';
        $data = array(
            'userId' => 105019,
            "time" => time(),
            'apiExtend' => 1,
            'businessType' => 2006,
            "keyword" => $keywords,
            'url' => $urls,
            'searchType' => $se_ins->ConvertToYouBangYun($searchengineid),
            'timeSet' => array(
                $auto_time
            ),
            'searchOnce' => true
        );
        $wParam = json_encode($data);
        $key = $this->key;
        $result = $cli->post('http://api.youbangyun.com/api/customerapi.aspx', array(
            'wAction' => $act,
            'wParam' => $wParam,
            'wSign' => strtoupper(md5($act . $key . $wParam))
        ))->send();
        //{"xCode":0,"xMessage":"success.","xValue":[[152195,""],[152196,""]]}
        //其中 xCode 返回值 0 表示用户请求已经被数据平台保存，
        //xValue 返回值 29498 代表 58 同城的 TaskId，29499 代表赶集网的 TaskId，
        //如果 TaskId 为 0 则说明，该数据提交失败，引号内 会给出错误信息。
        $data = json_decode($result, true);
        if (isset($data['xCode']) && $data['xCode'] == 0) {
            $xValue = $data['xValue'];
            for ($i = 0; $i < count($xValue); $i++) {
                if ($xValue[$i][0] == 0) {
                    $log->error("{$keywords[$i]} 添加失败:" . $xValue[$i][1]);
                } else {
                    $kw = $keywords[$i];
                    $url = $urls[$i];
                    $rank_id = $xValue[$i][0];
                    try {
                        $sql = "UPDATE 
                            `task` 
                            SET
                              `rank_id` = :rank_id
                            WHERE `keyword`=:kw AND `url`=:url AND `searchengineid` = :searchengineid;";

                        //实际情况5,6不存在,use 2,3
                        if ($searchengineid == 5 || $searchengineid == 6) {
                            $rank_id = 0 - $rank_id;
                            $total += $this->connection->exec($sql, compact('kw', 'url', 'rank_id', 'searchengineid'));
                        } else {
                            $total += $this->connection->exec($sql, compact('kw', 'url', 'rank_id', 'searchengineid'));
                        }
                    } catch (\Exception $exception) {
                        $err = $exception->getMessage() . var_export(func_get_args(), true);
                        $log->error($err);
                        $this->error[] = $err;
                    }
                }
            }
        } else {
            $err = $result . var_export(func_get_args(), true);
            $log->error($err);
            $this->error[] = $err;
        }
        return $total;
    }

    public function updateTaskTimeSet()
    {
        $total = 0;
        /**
         * @var Log $log
         */
        $log = $this->app->make('log-rank-sync');
        $this->error = array();
        //搜索所有正在运行的RANK_ID为NULl的任务
        $offset = 0;
        while (true) {
            $sql = "SELECT `rank_id` FROM `task` 
                WHERE (`status` = 'start' OR `status` = 'v_mod' OR `status` = 'v_stop')
              AND `rank_id` IS NOT NULL
              LIMIT :offset,500
              ";
            $data = $this->connection->fetchAll($sql, compact('offset'), array(
                "offset" => \PDO::PARAM_INT
            ));
            if (empty($data))
                return $total;
            $rank_id = array_column($data, 'rank_id');
//            var_dump($rank_id);exit;
            $total += $this->SetTaskWatchTime($rank_id, $this->notify_time);
            if ($total > 0) {
                $log->debug("提交了{$total}条记录");
            }
            $offset = $offset + 500;
        }

        return $total;
    }


    /**
     * @param array $rank_id
     * @param $time 10
     * @return int
     */
    public function SetTaskWatchTime(array $rank_id, $time)
    {
        $total = 0;
        if (empty($rank_id)) {
            return 0;
        }
        $cli = new Curl();
        /**
         * @var \Aw\Log $log
         */
        $log = $this->app->make('log-rank-sync');
        $act = 'SetTaskWatchTime';
        $data = array(
            'userId' => 105019,
            "time" => time(),
            'businessType' => 2006,
            "taskId" => $rank_id,
            'timeSet' => array($time),
            'searchOnce' => false
        );
        $wParam = json_encode($data);
        $key = $this->key;
        $result = $cli->post('http://api.youbangyun.com/api/customerapi.aspx', array(
            'wAction' => $act,
            'wParam' => $wParam,
            'wSign' => strtoupper(md5($act . $key . $wParam))
        ))->send();
        //{"xCode":0,"xMessage":"success.","xValue":[[152195,""],[152196,""]]}
        //其中 xCode 返回值 0 表示用户请求已经被数据平台保存，
        //xValue 返回值 29498 代表 58 同城的 TaskId，29499 代表赶集网的 TaskId，
        //如果 TaskId 为 0 则说明，该数据提交失败，引号内 会给出错误信息。
        $data = json_decode($result, true);
        if (isset($data['xCode']) && $data['xCode'] == 0) {
            $xValue = $data['xValue'];
            for ($i = 0; $i < count($xValue); $i++) {
                if ($xValue[$i][1] == false) {
                    $log->error("{$xValue[$i][0]} 操作失败:" . $xValue[$i][1]);
                } else {
                    $log->debug("{$xValue[$i][0]} 操作完成:" . $xValue[$i][1]);
                    $total++;
                }
            }
        } else {
            $err = $result . var_export(func_get_args(), true);
            $log->error($err);
            $this->error[] = $err;
        }
        return $total;
    }


    public function DelSearchTask($rank_id)
    {
        $total = 0;
        if (empty($rank_id)) {
            return 0;
        }
        $cli = new Curl();
        /**
         * @var \Aw\Log $log
         */
        $log = $this->app->make('log-rank-sync');
        $act = 'DelSearchTask';
        $data = array(
            'userId' => 105019,
            "time" => time(),
            'businessType' => 2006,
            "taskId" => $rank_id,
        );
        $wParam = json_encode($data);
        $key = $this->key;
        $result = $cli->post('http://api.youbangyun.com/api/customerapi.aspx', array(
            'wAction' => $act,
            'wParam' => $wParam,
            'wSign' => strtoupper(md5($act . $key . $wParam))
        ))->send();
        //{"xCode":0,"xMessage":"success.","xValue":[[152195,""],[152196,""]]}
        //其中 xCode 返回值 0 表示用户请求已经被数据平台保存，
        //xValue 返回值 29498 代表 58 同城的 TaskId，29499 代表赶集网的 TaskId，
        //如果 TaskId 为 0 则说明，该数据提交失败，引号内 会给出错误信息。
        $data = json_decode($result, true);
        if (isset($data['xCode']) && $data['xCode'] == 0) {
            $xValue = $data['xValue'];
            for ($i = 0; $i < count($xValue); $i++) {
                if ($xValue[$i][1] == false) {
                    $log->error("{$xValue[$i][0]} 操作失败:" . $xValue[$i][1]);
                } else {
                    $log->debug("{$xValue[$i][0]} 操作完成:" . $xValue[$i][1]);
                    $total++;
                }
            }
        } else {
            $err = $result . var_export(func_get_args(), true);
            $log->error($err);
            $this->error[] = $err;
        }
        return $total;
    }

    public function remove()
    {
        /**
         * @var \Aw\Log $log
         */
        $log = $this->app->make('log-rank-sync');
        $date = date('Y-m-d', strtotime("-1 day"));

        $sql = "SELECT `rank_id` FROM `rank_history` WHERE date = :date
        AND `rank_id` NOT IN (
            SELECT `rank_id` FROM `task`
        )";
        $data = $this->connection->fetchAll($sql, compact('date'));
        $data = array_column($data, 'rank_id');
        $result = $this->DelSearchTask($data);
        $log->debug("删除了{$result}条数据");
    }

    public function stop()
    {
        /**
         * @var \Aw\Log $log
         */
        $log = $this->app->make('log-rank-sync');
        $date = date('Y-m-d', strtotime("-1 day"));

        $sql = "SELECT `rank_id` FROM `task` WHERE `status` = 'stop' AND `rank_id` > 0";
        $data = $this->connection->fetchAll($sql, compact('date'));
        $data = array_column($data, 'rank_id');
        $result = $this->StopSearchTask($data);
        $log->debug("暂停了{$result}条数据");
    }


    public function StopSearchTask($rank_id)
    {
        $total = 0;
        if (empty($rank_id)) {
            return 0;
        }
        $cli = new Curl();
        /**
         * @var \Aw\Log $log
         */
        $log = $this->app->make('log-rank-sync');
        $act = 'StopSearchTask';
        $data = array(
            'userId' => 105019,
            "time" => time(),
            'businessType' => 2006,
            "taskId" => $rank_id,
        );
        $wParam = json_encode($data);
        $key = $this->key;
        $result = $cli->post('http://api.youbangyun.com/api/customerapi.aspx', array(
            'wAction' => $act,
            'wParam' => $wParam,
            'wSign' => strtoupper(md5($act . $key . $wParam))
        ))->send();
        //{"xCode":0,"xMessage":"success.","xValue":[[152195,""],[152196,""]]}
        //其中 xCode 返回值 0 表示用户请求已经被数据平台保存，
        //xValue 返回值 29498 代表 58 同城的 TaskId，29499 代表赶集网的 TaskId，
        //如果 TaskId 为 0 则说明，该数据提交失败，引号内 会给出错误信息。
        $data = json_decode($result, true);
        if (isset($data['xCode']) && $data['xCode'] == 0) {
            $xValue = $data['xValue'];
            for ($i = 0; $i < count($xValue); $i++) {
                if ($xValue[$i][1] == false) {
                    $log->error("{$xValue[$i][0]} 操作失败:" . $xValue[$i][1]);
                } else {
                    //任务停止成功后要清除RANK ID
                    $log->debug("{$xValue[$i][0]} 操作完成:" . $xValue[$i][1]);
                    $this->clearRankId($xValue[$i][0]);
                    $total++;
                }
            }
        } else {
            $err = $result . var_export(func_get_args(), true);
            $log->error($err);
            $this->error[] = $err;
        }
        return $total;
    }

    /**
     * @param $rank_id
     * @return int
     */
    protected function clearRankId($rank_id)
    {
        $sql = "UPDATE `task` SET `rank_id` = NULL WHERE `rank_id` = :rank_id";
        return $this->connection->exec($sql, compact('rank_id'));
    }

    public function GetAllTask()
    {
        $cli = new Curl();
        /**
         * @var \Aw\Log $log
         * -1    已删除
         * 1    待启动
         * 2    查询中
         * 3    查询完成
         * 4    已停止
         */
        $act = 'GetAllTask';
        $data = array(
            'userId' => 105019,
            "time" => time(),
            "apiExtend" => 1,
            'businessType' => 2006,
        );
        $wParam = json_encode($data);
        $key = $this->key;
        $result = $cli->post('http://api.youbangyun.com/api/customerapi.aspx', array(
            'wAction' => $act,
            'wParam' => $wParam,
            'wSign' => strtoupper(md5($act . $key . $wParam))
        ))->send();
        //{"xCode":0,"xMessage":"success.","xValue":[{"TaskId":151807,"Status":2},{"TaskId":151809,"Status":2}]}

        return $result;
    }
}
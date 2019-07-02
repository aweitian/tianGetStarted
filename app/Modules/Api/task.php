<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/13
 * Time: 18:32
 */

namespace App\Modules\Api;

//use App\Data\Provider\RegisterCheck;
use App\Data\Csv;
use App\Modules\Api;
use App\Provider\Privilege;

class task extends Api
{
    protected $table = 'task';

    /**
     * @var \App\Data\Task
     */
    protected $task;

    public function __construct()
    {
        parent::__construct();
        $this->task = new \App\Data\Task();
    }

    public function start()
    {
        $this->task->start($_POST);
        return $this->task->cmd;
    }

    public function stop()
    {
        $this->task->stop($_POST);
        return $this->task->cmd;
    }

    public function updateSeoCount()
    {
        $this->task->updateSeoCount($_POST);
        return $this->task->cmd;
    }

    public function import()
    {
        $this->task->import($_POST);
        return $this->task->cmd;
    }

    public function row()
    {
        $row = $this->task->row($_GET);
        if (!empty($row)) {
            return $this->cmd->setData($row);
        }
        return $this->task->cmd;
    }

    public function index()
    {
        if (isset($_GET['export'])) {
            $_GET['_page'] = http_query_get('_page', 1);
            $_GET['_size'] = http_query_get('_size', 100000);
        } else {
            $_GET['_page'] = http_query_get('_page', 1);
            $_GET['_size'] = http_query_get('_size', 20);
        }
        $data = $this->task->index($_GET);
        if (!empty($data)) {
            if (isset($_GET['export'])) {
                return $this->export($data['data']);
            }

            return $this->cmd->setData($data);
        }
        return $this->task->cmd;
    }


    public function remove()
    {
        $this->task->remove($_POST);
        return $this->task->cmd;
    }

    public function rm()
    {
        $this->task->remove_batch($_POST);
        return $this->task->cmd;
    }

    public function edit()
    {
        $this->task->edit($_POST);
        return $this->task->cmd;
    }

    private function export($data)
    {
        $titles = array(
            "teamleader" => "组长",
            "name" => "组员",
            "keyword" => "关键词",
            "url" => "网址",
            "searchengine" => "搜索引擎",
            "date" => "添加时间",
            "rank_update_date" => "更新时间",
            "rank_first" => "初排",
            "rank_last" => "新排",
            "seo_count" => "日优化",
            "status_text" => "状态 "
        );

        $csv = new Csv();
        $csv->buildRow($titles, false);
        foreach ($data as $datum) {
            $row = array();
            foreach ($titles as $key => $title) {
                if (array_key_exists($key, $datum)) {
                    $row[] = $datum[$key];
                }
            }
            $csv->buildRow($row);
        }
        $csv->outputHeader("sc.hxjdq.com-" . date('YmdHis') . ".csv");
        $csv->startDownload();
        exit;
    }

}
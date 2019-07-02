<?php
/**
 * Created by PhpStorm.
 * User: awei.tian
 * Date: 4/6/18
 * Time: 1:29 PM
 */

namespace App\Data;


use Aw\Build\Mysql\Crud;

class SeoApiTask extends Provider
{
    const RESPONSE_OK = 0;
    protected $key = '0e0ede186ba2a7d71dfc6c9b50bb655b';//lzlove$$
    protected $table = 'task';

    public function GetNeed()
    {
        $data = $this->search(1, 20, function (Crud $build, &$bind) {
            $build->bindWhere('status');
            $bind['status'] = 'online';
            $build->bindRawWhere('`api_id` IS NULL');
        });

        $se = new SearchEngine();
        //DATA MERGE
        $d = $data['data'];
        $ret = array();
        if (!empty($d)) {
            foreach ($d as $item) {
                $i = array();
                $i['TaskId'] = $item['task_id'];
                $i['Keyword'] = $item['keyword'];
                $i['Url'] = $item['url'];
                $i['SearchType'] = $se->ConvertToYouBangYun($item['searchengineid']);
                $i['SeoCount'] = $item['seo_count'];
                $ret[] = $i;
            }
        }
        if (empty($ret)) {
            return $this->response(302, "没有需要处理的数据", $ret);
        }
        return $this->response(self::RESPONSE_OK, "OK", $ret);
    }


    /**
     * 获取所有有效任务的ApiId（没导入的和无效的任务不算）
     * 并按照ApiId升序排列，一次性可以控制获得1000个
     * FromId参数代表ApiId从多少开始获取；我方根据获取到的ApiId列表和贵方现存的进行对比
     * 我方存在贵方不存在的我方会离线处理，贵方存在我方不存在的会回调ClearApiId请求对方重置，
     * 并进入待导入状态，确保一一对应；
     */
    public function GetApiId()
    {
        switch ($this->checkSign()) {
            case 1:
                return $this->response(403, "签名失败", array());
            case 2:
                return $this->response(500, "参数不正确", array());
        }

        $data = json_decode($_POST['wParam'], true);
        if (!is_array($data) || !isset($data['FromId'])) {
            return $this->response(501, "参数不正确,缺少FromId", array());
        }
        $from_id = intval($data['FromId']);
        $sql = "SELECT `api_id` FROM `task` WHERE `api_id` >= :from_id AND `status` = 'online' ORDER BY `api_id` ASC LIMIT 0,1000;";
        $data = $this->connection->fetchAll($sql, compact('from_id'));
        $data = array_column($data, 'api_id');
        if (empty($data)) {
            return $this->response(302, "没有需要处理的数据", $data);
        }
        return $this->response(self::RESPONSE_OK, "OK", $data);
    }


    /**
     * {
     * "time":1534576585,
     * "List":[
     * {"TaskId":1000001,"ApiId":12229879,"ErrorMsg":""},
     * {"TaskId":1000002,"ApiId":0,"ErrorMsg":"含有禁词"},
     * {"TaskId":1000003,"ApiId":12229887,"ErrorMsg":""}
     * ]
     * }
     * 参数：TaskId、ApiId、ErrorMsg对象列表
     * 设置任务的ApiId，TaskId代表贵方的任务编号
     * ApiId代表我方的TaskId，ErrorMsg代表错误信息，如果ApiId小于等于0代表未成功
     * 可以记录错误信息，并将该任务置为人工审核；
     *
     */
    public function SetApiId()
    {
        switch ($this->checkSign()) {
            case 1:
                return $this->response(403, "签名失败", array());
            case 2:
                return $this->response(500, "参数不正确", array());
        }
        $data = json_decode($_POST['wParam'], true);
        if (!is_array($data) || !isset($data['List'])) {
            return $this->response(501, "参数不正确,缺少list", array());
        }
        $count = 0;
        foreach ($data['List'] as $datum) {
            $count += $this->updateApiId($datum['TaskId'], $datum['ApiId'], $datum['ErrorMsg']);
        }
        return $this->response(0, "成功更新{$count}条记录", array());
    }

    /**
     * 清空指定ApiId的任务，说明这些任务在我方不存在
     * 重置后，可以继续进入GetNeed队列再次导入。
     *
     */
    public function ClearApiId()
    {
        switch ($this->checkSign()) {
            case 1:
                return $this->response(403, "签名失败", array());
            case 2:
                return $this->response(500, "参数不正确", array());
        }
        $data = json_decode($_POST['wParam'], true);
        if (!is_array($data) || !isset($data['List'])) {
            return $this->response(501, "参数不正确,缺少list", array());
        }
        $count = 0;
        foreach ($data['List'] as $api_id) {
            $count += $this->removeApi($api_id);
        }
        return $this->response(0, "成功删除{$count}条记录", array());
    }


    public function updateApiId($task_id, $api_id, $err_msg = '')
    {

        $sql = "UPDATE `task` SET 
            `api_id` = :api_id,
            `status` = :status,
            `err_msg` = :err_msg
            WHERE `task_id` = :task_id";
        if ($api_id > 0)
            $status = 'online';
        else
            $status = 'error';
        return $this->connection->exec($sql, compact('task_id', 'api_id', 'err_msg', 'status'));
    }

    public function removeApi($api_id)
    {
        //$sql = "DELETE FROM `task` WHERE `api_id` = :api_id;";
        $sql = "UPDATE task SET `api_id` = NULL WHERE `api_id` = :api_id";
        return $this->connection->exec($sql, compact('api_id'));
    }

    public static function getStatusText($status = null)
    {
        $a = array(
            "online" => "在线",
            "offline" => "离线",
            "error" => "错误"
        );
        if (is_null($status))
            return $a;
        return $a[$status];
    }

    public function checkSign()
    {
        if (!isset($_POST['wAction'], $_POST['wParam'], $_POST['wSign'])) {
            return 2;
        }
        $sign = md5($_POST['wAction'] . $this->key . $_POST['wParam']);
        $sign = strtoupper($sign);
        $ret = $sign === $_POST['wSign'];
        if ($ret)
            return 0;
        return 1;
    }


    public function response($code, $message, $data)
    {
        return json_encode(array(
            "xCode" => $code,
            "xMessage" => $message,
            "xValue" => $data
        ));
    }
}
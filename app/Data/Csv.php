<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/21
 * Time: 16:03
 */

namespace App\Data;


class Csv
{
    protected $output_data = array();

    public function downloadCsvData($csv_data = array(), $filename = null)
    {
        /****************************************************************************************************************************
         * 新建csv数据
         * /****************************************************************************************************************************/
        $this->output_data = array();
        foreach ($csv_data as $key => $csv_item) {
            $this->buildRow($csv_item, !!$key);
        }
        /****************************************************************************************************************************
         * 输出
         * /****************************************************************************************************************************/
        $this->outputHeader($filename);
        $this->startDownload();
    }

    public function buildRow(array $row, $wrap = true)
    {
        $current = array();
        foreach ($row AS $item) {
            $current[] = $this->buildItem($item, $wrap);
        }
        $this->output_data[] = implode("\t", $current);
        return $this;
    }

    public function buildItem($item, $wrap = true)
    {
        /**
         *  很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
         **/
        return is_numeric($item) ? $item :
            ($wrap ?
                ('"' . str_replace('"', '""', $item) . '"')
                : $item);
    }

    public function outputHeader($filename = null)
    {
        if (is_null($filename))
            $filename = "data_package." . date('Y-m-d') . ".csv";
        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=$filename");
        header('Expires:0');
        header('Pragma:public');
    }

    public function startDownload()
    {
        echo "\xFF\xFE" . mb_convert_encoding(implode("\r\n", $this->output_data), 'UCS-2LE', 'UTF-8');
    }
}
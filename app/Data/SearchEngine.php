<?php
/**
 * Created by PhpStorm.
 * User: awei.tian
 * Date: 4/6/18
 * Time: 1:29 PM
 */

namespace App\Data;


class SearchEngine extends Provider
{
    public function get($index = 0)
    {
        $a = array(
            "1" => "百度",
            "2" => "360",
            "3" => "搜狗",
            "4" => "百度移动",
            "5" => "360移动",
            "6" => "搜狗移动",
            "7" => "神马"
        );
        if (isset($a[$index]))
            return $a[$index];
        return $a;
    }

    public function g($t)
    {
        $d = $this->get();
        $a = array_combine(array_values($d),array_keys($d));
        if (isset($a[$t]))
            return $a[$t];
        return $a;
    }

    public function ConvertToYouBangYun($from)
    {
        $hash = array(
            "1" => "1010",
            "2" => "1015",
            "3" => "1030",
            "4" => "7010",
            "5" => "1015",
            "6" => "7030",
            "7" => "7070"
        );
        return isset($hash[$from]) ? $hash[$from] : -1;
    }

//    public function ReConvertToYouBangYun($from)
//    {
//        $hash = array(
//            "1010" => "1",
//            "1015" => "2",
//            "1030" => "3",
//            "7010" => "4",
//            "1015" => "5",
//            "1030" => "6",
//            "7070" => "7"
//        );
//        return isset($hash[$from]) ? $hash[$from] : -1;
//    }

}
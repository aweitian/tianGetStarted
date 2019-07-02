<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/20
 * Time: 10:40
 */
return array(
    "role" => array(
        "admin" => "管理员"
    ),
    "privilege" => array(
        "admin" => "管理员表操作"
    ),
    "relation" => array(
        //角色对应权限,权限可以为数组
        //用户可以属于一个或者多个角色
        "admin" => "admin"
    )

);
CREATE TABLE `admin` (
  `admin_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `pass` varchar(32) DEFAULT '',
  `pid` int(11) DEFAULT '0',
  `role` enum('teamleader','workmate') DEFAULT 'teamleader',
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;


CREATE TABLE `task` (
  `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `ownerid` int(11) DEFAULT NULL COMMENT '拥有者',
  `searchengineid` int(11) DEFAULT '0' COMMENT '搜索引擎',
  `keyword` varchar(32) NOT NULL COMMENT '关键词',
  `url` varchar(160) NOT NULL COMMENT '网址',
  `seo_count` int(10) unsigned DEFAULT NULL COMMENT '资源量',
  `status` enum('offline','online','error') DEFAULT 'offline',
  `api_id` int(11) DEFAULT NULL,
  `err_msg` varchar(256) DEFAULT NULL COMMENT 'SetApiId 返回结果',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `task_id` (`task_id`),
  UNIQUE KEY `searchengineid` (`searchengineid`,`keyword`,`url`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

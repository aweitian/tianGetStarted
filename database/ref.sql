
CREATE TABLE `seo_api_task` (
  `task_id` int(10) unsigned DEFAULT NULL,
  `seo_count` int(10) unsigned DEFAULT NULL COMMENT '资源量',
  `uid` int(10) unsigned DEFAULT NULL,
  `status` enum('offline','online','error') DEFAULT NULL,
  `api_id` int(11) DEFAULT NULL,
  `err_msg` varchar(256) DEFAULT NULL COMMENT 'SetApiId 返回结果',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `task` (
  `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `searchengineid` int(11) DEFAULT '0' COMMENT '搜索引擎',
  `admin_id` int(11) DEFAULT NULL,
  `aclerk_id` int(11) DEFAULT NULL,
  `ownerid` int(11) DEFAULT NULL COMMENT '客户',
  `keyword` varchar(32) NOT NULL COMMENT '关键词',
  `url` varchar(160) NOT NULL COMMENT '网址',
  `emergency` tinyint(1) DEFAULT '0' COMMENT '申请催单',
  `reject` tinyint(1) DEFAULT '0',
  `status` enum('stop','start','v_new','v_mod','v_stop') DEFAULT 'v_new' COMMENT '状态:待审核,正在优化,停止合作',
  `rankend1` int(11) DEFAULT NULL COMMENT '前N名1',
  `price1` float DEFAULT NULL COMMENT '达到目标1每天扣费数',
  `factor1` float DEFAULT NULL COMMENT '打折1',
  `type1` enum('api','manual') DEFAULT NULL COMMENT '价格获取方式1',
  `rankend2` int(11) DEFAULT NULL COMMENT '前N名2',
  `price2` float DEFAULT NULL COMMENT '达到目标2每天扣费数',
  `factor2` float DEFAULT NULL COMMENT '打折2',
  `type2` enum('api','manual') DEFAULT NULL COMMENT '价格获取方式2',
  `rank_id` int(11) DEFAULT NULL COMMENT '查询API ID',
  `rank_first` int(11) DEFAULT NULL COMMENT '初排',
  `rank_last` int(11) DEFAULT NULL COMMENT '新排',
  `consume_cnt` int(11) DEFAULT '0' COMMENT '达标天',
  `consume_sum` float DEFAULT '0' COMMENT '总消费',
  `date` datetime DEFAULT NULL COMMENT '增加时间',
  `checkout_date` datetime DEFAULT NULL COMMENT '清算日期',
  PRIMARY KEY (`task_id`),
  UNIQUE KEY `rank_id` (`rank_id`),
  UNIQUE KEY `ownerid` (`keyword`,`url`,`searchengineid`)
) ENGINE=InnoDB AUTO_INCREMENT=12323 DEFAULT CHARSET=utf8;
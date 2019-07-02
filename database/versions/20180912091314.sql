CREATE TABLE `rank_history` (
  `rank_history_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rank_id` int(11) DEFAULT NULL,
  `rank_first` int(11) DEFAULT NULL,
  `rank_last` int(11) DEFAULT NULL,
  `rank_change` int(11) DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL,
  `checkout` tinyint(1) DEFAULT '0',
  `date` date DEFAULT NULL,
  PRIMARY KEY (`rank_history_id`),
  UNIQUE KEY `rank_id` (`rank_id`,`date`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
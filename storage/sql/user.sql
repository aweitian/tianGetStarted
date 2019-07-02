CREATE TABLE `user` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(64) DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `gen_time` datetime DEFAULT NULL,
  `login_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `user_role` (
  `uid` int(11) NOT NULL,
  `role` varchar(16) NOT NULL,
  PRIMARY KEY (`uid`,`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `task` CHANGE `status` `status` ENUM('offline','online','error') CHARSET utf8 COLLATE utf8_general_ci DEFAULT 'online' NULL;
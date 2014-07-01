CREATE TABLE `prof_interes_user` (
  `prof_interes_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`prof_interes_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
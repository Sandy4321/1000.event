CREATE TABLE `target_user` (
  `target_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  UNIQUE KEY `target_id` (`target_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
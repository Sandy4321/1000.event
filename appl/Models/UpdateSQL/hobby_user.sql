CREATE TABLE `hobby_user` (
  `hobby_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  UNIQUE KEY `hobby_id` (`hobby_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
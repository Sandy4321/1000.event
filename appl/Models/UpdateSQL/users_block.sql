CREATE TABLE `users_block` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'ID пользователя',
  `block_dt` datetime NOT NULL COMMENT 'Дата и время блокировки',
  `block_stop_date` date DEFAULT NULL COMMENT 'Дата окончания блокировки',
  `cause_blocking` varchar(255) DEFAULT NULL COMMENT 'Причина блокировки',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=406 DEFAULT CHARSET=utf8;
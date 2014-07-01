CREATE TABLE `user_reports` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Отправитель репорта',
  `user_id_about` int(11) NOT NULL COMMENT 'ID пользователя о ком репорт',
  `rating` tinyint(1) DEFAULT NULL,
  `report_text` varchar(255) DEFAULT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
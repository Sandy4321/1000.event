CREATE TABLE `user_messages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id_from` int(11) DEFAULT NULL COMMENT 'Отправитель - ID от кого сообщение',
  `user_id_to` int(11) DEFAULT NULL COMMENT 'Получатель - ID для кого сообщение',
  `msg_text` text NOT NULL COMMENT 'Текст сообщения',
  `send_dt` datetime NOT NULL COMMENT 'Дата и время отправки',
  `read_dt` datetime DEFAULT NULL COMMENT 'Дата и время прочтения',
  `access_read` enum('yes','no') NOT NULL DEFAULT 'no' COMMENT 'Режим доступа к сообщению',
  `last_msg_key` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7480 DEFAULT CHARSET=utf8;


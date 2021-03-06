CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(8) NOT NULL,
  `current_status` int(2) DEFAULT NULL,
  `sex` enum('female','male') DEFAULT NULL,
  `email` varchar(100) NOT NULL DEFAULT '',
  `psw` char(32) NOT NULL DEFAULT '',
  `lang` char(2) NOT NULL DEFAULT '',
  `phone` varchar(20) DEFAULT NULL,
  `phone_check` enum('yes','no') NOT NULL DEFAULT 'no',
  `phone_verify_code` int(8) DEFAULT NULL,
  `skype` varchar(100) DEFAULT NULL,
  `promocode` char(10) DEFAULT NULL,
  `promo_key` char(32) NOT NULL DEFAULT '',
  `promo_key_friend` char(32) DEFAULT NULL,
  `activation_key` char(32) NOT NULL,
  `register_dt` datetime DEFAULT NULL COMMENT 'Дата регистрации',
  `activation_dt` datetime DEFAULT NULL COMMENT 'Дата активации аккаунта',
  `adoption_club_dt` datetime DEFAULT NULL COMMENT 'Дата принятия в клуб',
  `online` enum('yes','no') NOT NULL DEFAULT 'no',
  `online_last_dt` datetime DEFAULT NULL COMMENT 'Дата и время последней активности на сайте',
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `first_name_lat` varchar(50) DEFAULT NULL,
  `last_name_lat` varchar(50) DEFAULT NULL,
  `birthday` date DEFAULT NULL COMMENT 'День рождения',
  `city_id` int(11) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL COMMENT 'Компания',
  `position_job` varchar(255) DEFAULT NULL COMMENT 'Должность',
  `education` varchar(255) DEFAULT NULL COMMENT 'Образование',
  `link_vk` varchar(255) DEFAULT NULL,
  `link_fb` varchar(255) DEFAULT NULL,
  `link_ln` varchar(255) DEFAULT NULL,
  `about` varchar(255) DEFAULT NULL COMMENT 'О себе',
  `favorite_places` varchar(255) DEFAULT NULL COMMENT 'Любимые места',
  `lang_ru` enum('yes','no') NOT NULL DEFAULT 'no',
  `lang_en` enum('yes','no') NOT NULL DEFAULT 'no',
  `lang_fr` enum('yes','no') NOT NULL DEFAULT 'no',
  `lang_de` enum('yes','no') NOT NULL DEFAULT 'no',
  `lang_it` enum('yes','no') NOT NULL DEFAULT 'no',
  `lang_es` enum('yes','no') NOT NULL DEFAULT 'no',
  `romantic` enum('yes','no') NOT NULL DEFAULT 'no',
  `marital_status` enum('yes','no') NOT NULL DEFAULT 'no' COMMENT 'Семейное положение',
  `height` int(2) DEFAULT NULL COMMENT 'Рост',
  `children` enum('yes','no') NOT NULL DEFAULT 'no' COMMENT 'Дети',
  `smoking` enum('yes','no') NOT NULL DEFAULT 'no' COMMENT 'Курение',
  `free_day` text COMMENT 'Свободные дни для свиданий',
  `msg_admin_email` enum('yes','no') NOT NULL DEFAULT 'yes',
  `msg_admin_sms` enum('yes','no') NOT NULL DEFAULT 'no',
  `msg_communication_email` enum('yes','no') NOT NULL DEFAULT 'yes',
  `msg_communication_sms` enum('yes','no') NOT NULL DEFAULT 'no',
  `msg_invite_email` enum('yes','no') NOT NULL DEFAULT 'yes',
  `msg_invite_sms` enum('yes','no') NOT NULL DEFAULT 'yes',
  `msg_favorite_email` enum('yes','no') NOT NULL DEFAULT 'yes',
  `msg_favorite_sms` enum('yes','no') NOT NULL DEFAULT 'no',
  `balance` int(11) NOT NULL DEFAULT '0',
  `balance_bonus` int(11) NOT NULL DEFAULT '0',
  `club_card_dt` date DEFAULT NULL,
  `last_access_dt` datetime DEFAULT NULL COMMENT 'Дата и время последнего взаимодействия',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `activation_key` (`activation_key`),
  UNIQUE KEY `promo_key` (`promo_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;





UPDATE `users` SET `msg_communication_email` = 'no';
UPDATE `users` SET `msg_communication_sms` = 'no';

-- Вариант 1 (с дублированием сообщений)
CREATE TABLE `user_msg` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `box` enum('in','out') NOT NULL DEFAULT 'in',
  `msg_text` text NOT NULL,
  `create_dt` datetime NOT NULL,
  `read_dt` datetime DEFAULT NULL,
  `access_read` enum('yes','no') NOT NULL DEFAULT 'no',
  `del` enum('yes','no') NOT NULL DEFAULT 'no',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Вся моя переписка
SELECT * FROM `user_msg` WHERE `user_id` = 3040 ORDER BY `create_dt` ASC;

-- Вся моя переписка с...
SELECT * FROM `user_msg` WHERE `user_id` = 3040 AND `partner_id` = 16 ORDER BY `create_dt` ASC;

-- Последние сообщения переписки со всеми пользователями
-- EXPLAIN
SELECT * FROM
	(SELECT * FROM `user_msg` WHERE `user_id` = 3040 ORDER BY `create_dt` DESC)
AS m1
WHERE
m1.`user_id` = 3040
GROUP BY m1.`partner_id`
ORDER BY m1.create_dt DESC;

-- Общее кол-во не прочитанных мой сообщений
-- EXPLAIN
SELECT COUNT(*) AS cnt FROM `user_msg` WHERE
`user_id` = 3040
AND `box` = 'in'
AND `read_dt` IS NULL;

-- Кол-во не прочитанных мой сообщений с каждым пользователем
SELECT *, COUNT(*) AS cnt FROM `user_msg` WHERE
`user_id` = 3040
AND `box` = 'in'
AND `read_dt` IS NULL
GROUP BY `partner_id`;

-- Оптправить сообщение от меня
INSERT INTO `user_msg` SET `user_id` = 3040, `partner_id` = 16, `box` = 'out', `msg_text` = 'Альмир привет', `create_dt` = NOW(), `access_read` = 'yes';
INSERT INTO `user_msg` SET `user_id` = 16, `partner_id` = 3040, `box` = 'in', `msg_text` = 'Альмир привет', `create_dt` = NOW(), `access_read` = 'yes';

-- Оптправить сообщение мне
INSERT INTO `user_msg` SET `user_id` = 3040, `partner_id` = 16, `box` = 'in', `msg_text` = 'Саша привет!!', `create_dt` = NOW(), `access_read` = 'yes';
INSERT INTO `user_msg` SET `user_id` = 16, `partner_id` = 3040, `box` = 'out', `msg_text` = 'Саша привет!!', `create_dt` = NOW(), `access_read` = 'yes';

-- ----------------
-- Версия 2
-- Вся моя переписка
SELECT m.*, t.`msg_text`, t.`send_dt`, t.`read_dt` FROM `user_msg` AS m
JOIN `user_msg_text` AS t ON t.id=m.`msg_id`
WHERE
m.`user_id` = 3040
AND m.`del` = 'no'
ORDER BY t.`send_dt` ASC;

-- Вся моя переписка с...
SELECT m.*, t.`msg_text`, t.`send_dt`, t.`read_dt` FROM `user_msg` AS m
JOIN `user_msg_text` AS t ON t.id=m.`msg_id`
WHERE
m.`user_id` = 3040
AND m.`partner_id` = 16
AND m.`del` = 'no'
ORDER BY t.`send_dt` ASC;

-- Последние сообщения переписки со всеми пользователями
-- EXPLAIN
SELECT m.*, t.`msg_text`, t.`send_dt`, t.`read_dt` FROM
(SELECT u.* FROM `user_msg` AS u WHERE u.`user_id` = 3040 ORDER BY u.`create_dt` DESC)
AS m
JOIN `user_msg_text` AS t ON t.id=m.`msg_id`
WHERE
m.`user_id` = 3040
AND m.`del` = 'no'
GROUP BY m.`partner_id`
ORDER BY m.create_dt DESC;

-- Общее кол-во не прочитанных мой сообщений
-- EXPLAIN
SELECT COUNT(m.id) AS cnt FROM `user_msg` AS m
JOIN `user_msg_text` AS t ON t.id=m.`msg_id`
WHERE
m.`user_id` = 3040
AND m.`box` = 'in'
AND m.`del` = 'no'
AND t.`read_dt` IS NULL;

-- Кол-во не прочитанных мой сообщений с каждым пользователем
-- EXPLAIN
SELECT m.`partner_id`, COUNT(m.id) AS cnt FROM `user_msg` AS m
JOIN `user_msg_text` AS t ON t.id=m.`msg_id`
WHERE
m.`user_id` = 3040
AND m.`box` = 'in'
AND m.`del` = 'no'
AND t.`read_dt` IS NULL
GROUP BY m.`partner_id`;
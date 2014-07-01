# ************************************************************
# Sequel Pro SQL dump
# Версия 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Адрес: 127.0.0.1 (MySQL 5.6.14)
# Схема: db_onthelist
# Время создания: 2013-11-21 18:25:16 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Дамп таблицы dashboard_msg
# ------------------------------------------------------------

DROP TABLE IF EXISTS `dashboard_msg`;

CREATE TABLE `dashboard_msg` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `action_name` enum('AnswerFree','AnswerMoney','ContactExchangeNew','ContactExchangeReject','ContactExchangeRevoke','ContactExchangeYes','FavouritesAddOne','FavouritesAddPair','InviteNew','InviteReject','InviteRevoke','InviteYes','QuestionMoney','QuestionsFree','ViewProfile','LikePhoto','SendGift') NOT NULL DEFAULT 'InviteNew',
  `msg_ru` text,
  `msg_en` text,
  `module` enum('user') NOT NULL DEFAULT 'user',
  `controller` enum('index','dates','communication','people','profile') NOT NULL DEFAULT 'index',
  `action` varchar(30) NOT NULL DEFAULT 'index',
  `params` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `dashboard_msg` WRITE;
/*!40000 ALTER TABLE `dashboard_msg` DISABLE KEYS */;

INSERT INTO `dashboard_msg` (`id`, `action_name`, `msg_ru`, `msg_en`, `module`, `controller`, `action`, `params`)
VALUES
	(1,'InviteNew','приглашает Вас на свидание.','invites you for a date.','user','dates','index','1'),
	(2,'InviteReject','отклонила Ваше приглашение на свидание.','declined you invitation for a date.','user','dates','index','1'),
	(3,'InviteReject','отклонил Ваше приглашение на свидание.','declined your invitation for a date.','user','dates','index','1'),
	(4,'InviteRevoke','отозвала Ваше приглашение на свидание.','revoked your invitation for a date.','user','dates','index','1'),
	(5,'InviteRevoke','отозвал Ваше приглашение на свидание.','revoked your invitation for a date.','user','dates','index','1'),
	(6,'InviteYes','приняла Ваше приглашение на свидание.','accepted your invitation for a date.','user','dates','index','1'),
	(7,'InviteYes','принял Ваше приглашение на свидание.','accepted your invitation for a date.','user','dates','index','1'),
	(8,'ContactExchangeNew','предлагает Вам обменяться телефонами.','wants to exchange phones with you.','user','dates','index','1'),
	(9,'ContactExchangeReject','отклонила Ваше предложение обменяться телефонами.','declined your offer to exchange phones.','user','dates','index','1'),
	(10,'ContactExchangeReject','отклонил Ваше предложение обменяться телефонами.','declined your offer to exchange phones.','user','dates','index','1'),
	(11,'ContactExchangeRevoke','отозвала своё предложение обменяться с Вами телефонами.','revoked her offer to exchange phones.','user','dates','index','1'),
	(12,'ContactExchangeRevoke','отозвал своё предложение обменяться с Вами телефонами.','revoked his offer to exchange phones.','user','dates','index','1'),
	(13,'ContactExchangeYes','приняла Ваше предложение обменяться телефонами.','accepted you offer to exchange phones.','user','dates','index','1'),
	(14,'ContactExchangeYes','принял Ваше предложение обменяться телефонами.','accepted you offer to exchange phones.','user','dates','index','1'),
	(15,'QuestionMoney','задала Вам вопрос.','asked you a question.','user','communication','index','1'),
	(16,'QuestionMoney','задал Вам вопрос.','asked you a question.','user','communication','index','1'),
	(17,'AnswerMoney','ответила на Ваш вопрос.','answered your question.','user','communication','index','1'),
	(18,'AnswerMoney','ответил на Ваш вопрос.','answered your question.','user','communication','index','1'),
	(19,'QuestionsFree','задала Вам вопросы.','asks you a few questions.','user','communication','index','1'),
	(20,'QuestionsFree','задал Вам вопросы.','asks you a few questions.','user','communication','index','1'),
	(21,'AnswerFree','ответила на Ваш вопрос.','answers your question.','user','communication','index','1'),
	(22,'AnswerFree','ответил на Ваш вопрос.','answers your question.','user','communication','index','1'),
	(23,'FavouritesAddOne','добавила Вас в избранные контакты.','added you to her Favorites.','user','people','profile','1'),
	(24,'FavouritesAddOne','добавил Вас в избранные контакты.','added you to his Favorites.','user','people','profile','1'),
	(25,'FavouritesAddPair','также добавила Вас в свои избранные контакты.','also added you to her Favorites.','user','people','profile','1'),
	(26,'FavouritesAddPair','также добавил Вас в свои избранные контакты.','also added you to his Favorites.','user','people','profile','1'),
	(27,'ViewProfile','просматривал Ваш профиль.','has viewed your profile.','user','people','profile',NULL),
	(28,'ViewProfile','просматривала Ваш профиль.','has viewed your profile.','user','people','profile',NULL),
	(29,'LikePhoto',': <i>\"мне нравится Ваша фотография\"</i>',': <i>\"I like your photo\"</i>','user','people','profile',NULL),
	(30,'SendGift','подарил Вам подарок!','подарил Вам подарок!','user','profile','gifts',NULL),
	(31,'SendGift','подарила Вам подарок!','подарила Вам подарок!','user','profile','gifts',NULL);

/*!40000 ALTER TABLE `dashboard_msg` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

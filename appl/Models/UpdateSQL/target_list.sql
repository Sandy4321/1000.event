# ************************************************************
# Sequel Pro SQL dump
# Версия 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Адрес: 127.0.0.1 (MySQL 5.6.14)
# Схема: db_onthelist
# Время создания: 2013-11-20 18:22:16 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Дамп таблицы target_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `target_list`;

CREATE TABLE `target_list` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name_ru` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `target_list` WRITE;
/*!40000 ALTER TABLE `target_list` DISABLE KEYS */;

INSERT INTO `target_list` (`id`, `name_ru`, `name_en`)
VALUES
	(1,'Бросить курить','Give up smoking'),
	(2,'В кругосветное путешествие','Travel around the world'),
	(3,'Отдохнуть на море','Holidays on the beach'),
	(4,'Водить машину как профи','Drive like a professional'),
	(5,'Выучить иностранный язык','Learn a language'),
	(6,'Закрутить роман','Have an affair'),
	(7,'Играть на муз. инструменте','Play a musical instrument'),
	(8,'Изменить мир','Change the world'),
	(9,'Мастерство в покере','Become a poker master'),
	(10,'Начать новый проект','Start a new project'),
	(11,'Предложения о работе','Get a job offer'),
	(12,'Найти специалиста в команду','Find a team member'),
	(13,'Найти клад','Find a treasure'),
	(14,'Написать книгу, сценарий','Write a novel, scenario'),
	(15,'Научиться готовить','Learn  to cook'),
	(16,'Научиться дайвингу','Learn to dive'),
	(17,'Научиться играть в гольф','Learn to play golf'),
	(18,'Получить лицензию пилота','Get a pilot license'),
	(19,'Научиться рисовать','Learn to draw'),
	(20,'Научиться танцевать','Learn to dance'),
	(21,'Начать свое дело','Start a new business'),
	(22,'Освоить боевое искусство','Learn martial arts'),
	(23,'Освоить горные лыжи','Learn to ski'),
	(24,'Освоить сноуборд','Learn to snowboard'),
	(25,'Побить мировой рекорд','Set the world record'),
	(26,'Побороть страх','Defeat a fear'),
	(27,'Пожить вдалеке от всех','Live far from others'),
	(28,'Познать себя','Know myself'),
	(29,'Поймать щуку  21 кг','Catch a jackfish of 21k'),
	(30,'Покорить Эверест','Climb the Everest'),
	(31,'Помочь кому-то','Help someone'),
	(32,'Научиться петь','Learn to sing'),
	(33,'Построить дом мечты','Build a dream house'),
	(34,'Поучиться за рубежом','Study abroad'),
	(35,'Пофлиртовать','Flirt'),
	(36,'Пробежать марафон','Run a marathon'),
	(37,'Прочитать Библию','Read a Bible'),
	(38,'Прочитать Коран','Read a Koran'),
	(39,'Прыгнуть с парашютом','Jump with a parachute'),
	(40,'Разбираться в винах','Become a wine guru'),
	(41,'Завести ребенка','Make a child'),
	(42,'Сбросить вес','Lose some weight'),
	(43,'Сдать на права','Get a driver\'s license'),
	(44,'Сделать ремонт','Renovate the house'),
	(45,'Скинуться на виллу','Chip in for a villa'),
	(46,'Скинуться на яхту','Chip in for a yacht'),
	(47,'Создать ИТ-стартап','Make an IT-startup'),
	(48,'Создать муз. группу','Create a music band'),
	(49,'Создать семью','Create a family'),
	(50,'Дауншифтинг','Downshifting'),
	(51,'Стать меценатом','Become a philanthropist'),
	(52,'Стать политиком','Become a politician'),
	(53,'Сыграть в кино','Play in a movie'),
	(54,'Сыграть в театре','Play in a theatre'),
	(55,'Управлять катером','Drive a motor boat'),
	(56,'Эмигрировать','Immigrate');

/*!40000 ALTER TABLE `target_list` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

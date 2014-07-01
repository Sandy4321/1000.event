# ************************************************************
# Sequel Pro SQL dump
# Версия 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Адрес: 127.0.0.1 (MySQL 5.6.14)
# Схема: db_onthelist
# Время создания: 2013-11-21 12:35:47 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Дамп таблицы hobby_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hobby_list`;

CREATE TABLE `hobby_list` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name_ru` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_ru` (`name_ru`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `hobby_list` WRITE;
/*!40000 ALTER TABLE `hobby_list` DISABLE KEYS */;

INSERT INTO `hobby_list` (`id`, `name_ru`, `name_en`)
VALUES
	(1,'автомобили','auto'),
	(2,'архитектура','architecture'),
	(3,'балет','ballet'),
	(4,'бег','running'),
	(5,'бильярд','billiard'),
	(6,'боулинг','bowling'),
	(7,'велосипед','cycling'),
	(8,'верховая езда','horse riding'),
	(9,'виндсерфинг','wind surfing'),
	(10,'вино','wine'),
	(11,'вокал','singing'),
	(12,'волейбол','volleyball'),
	(13,'выставки','exhibitions'),
	(14,'гольф','golf'),
	(15,'горные лыжи','skiing'),
	(16,'дайвинг','diving'),
	(17,'дизайн','design'),
	(19,'животные','animals'),
	(20,'иностранные языки','languages'),
	(21,'искусство','art'),
	(22,'история','history'),
	(23,'йога','yoga'),
	(24,'караоке','karaoke'),
	(25,'кино','films'),
	(26,'концерты','concerts'),
	(27,'коньки','skating'),
	(28,'кулинария','cooking'),
	(29,'литература','literature'),
	(30,'лыжи','skiing'),
	(31,'мода','fashion'),
	(32,'музыка','music'),
	(33,'муз. инструменты','playing music'),
	(34,'ночная жизнь','night life'),
	(35,'опера','opera'),
	(36,'охота','hunting'),
	(37,'плавание','swimming'),
	(38,'покер','poker'),
	(39,'политика','politics'),
	(40,'поэзия','poems'),
	(41,'природа','nature'),
	(42,'прогулки','walking'),
	(43,'психология','psychology'),
	(44,'путешествия','travelling'),
	(45,'рисование','drawing'),
	(46,'ролики','roller-skates'),
	(47,'рыбалка','fishing'),
	(48,'серфинг','surfing'),
	(49,'сноуборд','snowboard'),
	(50,'танцы','dance'),
	(51,'театр','theatre'),
	(52,'теннис','tennis'),
	(53,'философия','philosophy'),
	(54,'фитнес','fitness'),
	(55,'фотография','photography'),
	(56,'футбол','football'),
	(57,'хоккей','hockey'),
	(58,'шахматы','chess'),
	(59,'яхтинг','yachting'),
	(60,'ЛГБТ','LGBT'),
	(61,'легкие наркотики (чай, кофе и др.)','light drugs (tea, coffee, etc.)'),
	(62,'сквош','squash'),
	(63,'пеший туризм','hiking'),
	(64,'альпинизм','mountain climbing');

/*!40000 ALTER TABLE `hobby_list` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

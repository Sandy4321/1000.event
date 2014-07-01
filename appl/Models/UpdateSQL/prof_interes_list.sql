# ************************************************************
# Sequel Pro SQL dump
# Версия 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Адрес: 127.0.0.1 (MySQL 5.6.14)
# Схема: db_onthelist
# Время создания: 2013-11-21 11:27:27 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Дамп таблицы prof_interes_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `prof_interes_list`;

CREATE TABLE `prof_interes_list` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name_ru` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `prof_interes_list` WRITE;
/*!40000 ALTER TABLE `prof_interes_list` DISABLE KEYS */;

INSERT INTO `prof_interes_list` (`id`, `name_ru`, `name_en`)
VALUES
	(1,'Сельское хозяйство','Agriculture'),
	(2,'Посевные инвестиции','Angel Investments'),
	(3,'Инвестиции в предметы искусства','Art investments'),
	(4,'Роли директора и советника','Board and Advisory Roles'),
	(5,'Бизнес в Азии','Business in Asia'),
	(6,'Организационные и инновации','Change and Innovation'),
	(7,'Строительство','Construction'),
	(8,'Корпоративное развитие','Corporate Development'),
	(9,'Корпоративные финансы','Corporate Finance'),
	(10,'Интернет и мобильная коммерция','eCommerce and mCommerce'),
	(11,'Энергоэффективность','Energy Efficiency'),
	(12,'Предпринимательство','Entrepreneurship'),
	(13,'Организация мероприятий','Event management'),
	(14,'Управление денежными средствами','Fund management'),
	(15,'Глобализация','Globalization'),
	(16,'Здравоохранение','Healthcare'),
	(17,'Информационные технологии','Information Technologies'),
	(18,'Международная торговля','International Trade'),
	(19,'Инвестиционные банковские услуги','Investment Banking'),
	(20,'Управление знаниями','Knowledge Management'),
	(21,'Юридические услуги','Legal Services'),
	(22,'Лоббирование','Lobbying'),
	(23,'Управленческий консалтинг','Management Consulting'),
	(24,'Производство и логистика','Manufacturing and Logistics'),
	(25,'Маркетинг и реклама','Marketing and Advertising'),
	(26,'Медиа и развлечения','Media and Entertainment'),
	(27,'Металлы и добыча ископаемых','Metals and Mining'),
	(28,'Некоммерческие организации','Non-profit organizations'),
	(29,'Нефть и газ','Oil and Gas'),
	(30,'Люди и лидерство','People and Leadership'),
	(31,'Управление эффективностью','Performance Management'),
	(32,'Лекарственные препараты','Pharmaceuticals'),
	(33,'Политика','Politics'),
	(34,'Частные инвестиции','Private Equity'),
	(35,'Государственно-частные партнёрства','Public Private Partnerships'),
	(36,'Государственный сектор','Public Sector'),
	(37,'Недвижимость','Real Estate'),
	(38,'Розничная торговля','Retail'),
	(39,'Розничные банковские услуги','Retail Banking'),
	(40,'Управление рисками','Risk Management'),
	(41,'Стратегический менеджмент','Strategic Management'),
	(42,'Экологичное использование ресурсов','Sustainability'),
	(43,'Телекоммуникации и связь','Telecom and Communications'),
	(44,'Торговая политика','Trade and Policy'),
	(45,'Торговля на бирже','Trading and Securities'),
	(46,'Венчурный капитал','Venture Capital'),
	(47,'Управление частными капиталами','Wealth Management');

/*!40000 ALTER TABLE `prof_interes_list` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

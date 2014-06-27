<?php

/**
 * Файл инициализации.
 *
 * @category Sas
 * @package Sas_Site
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2008 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 1.0.1
 */

#===
error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
date_default_timezone_set('Europe/Moscow');

#========================= ОСНОВНЫЕ КОНСТАНТЫ ==================================

/**
 * Текущая дата (Y-m-d)
 */
define('CURRENT_DATE', date('Y-m-d'));

/**
 * Текущая дата и время (Y-m-d H:i:s)
 */
define('CURRENT_DATETIME', date('Y-m-d H:i:s'));

/**
 * Текущая дата и время в unix
 */
define('CURRENT_UNIXTIME', time());

/**
 * Кодировка HTML страниц сайта.
 */
define('SITE_CHARSET', 'UTF-8');

/**
 * Название модуля по умолчанию.
 */
define('DEFAULT_MODULE_NAME', 'default');

/**
 * Полный путь к директории хостинга (это не DOCUMENT_ROOT).
 *
 * Подразумевается что путь находится на уровень выше файла инициализации.
 * @var string
 */
define('PATH_DIR_HOST', rtrim(realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR));

/**
 * Полный путь к директории с приложениями.
 */
define('PATH_DIR_APPL', PATH_DIR_HOST . DIRECTORY_SEPARATOR . 'appl');

/**
 * Полный путь к директории модулей.
 */
define('PATH_DIR_MODULES', PATH_DIR_APPL . DIRECTORY_SEPARATOR . 'Modules');

/**
 * Полный путь к директории моделей данных.
 */
define('PATH_DIR_MODELS', PATH_DIR_APPL . DIRECTORY_SEPARATOR . 'Models');

/**
 * Полный путь к директории с конфигурационными файлами.
 */
define('PATH_DIR_CFG', PATH_DIR_APPL . DIRECTORY_SEPARATOR . 'Config');

/**
 * Полный путь к директории библиотек.
 */
define('PATH_DIR_LIB', PATH_DIR_HOST . DIRECTORY_SEPARATOR . 'lib');

/**
 * Полный путь к макетам сайта.
 */
define('PATH_DIR_LAYOUT_SITE', PATH_DIR_MODULES . DIRECTORY_SEPARATOR . DEFAULT_MODULE_NAME . DIRECTORY_SEPARATOR . 'layouts');

/**
 * Полный путь к макетам административной части сайта.
 */
define('PATH_DIR_LAYOUT_ADMIN', PATH_DIR_MODULES . DIRECTORY_SEPARATOR . 'admyn' . DIRECTORY_SEPARATOR . 'layouts');

/**
 * Полный путь к макетам пользовательской части сайта.
 */
define('PATH_DIR_LAYOUT_USER', PATH_DIR_MODULES . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . 'layouts');

/**
 * Полный путь к логам.
 */
define('PATH_DIR_LOG', PATH_DIR_HOST . DIRECTORY_SEPARATOR . 'logs');

/**
 * Язык по умолчанию.
 */
define('LANG_DEFAULT' , 'ru');

/**
 * Название переменной для хранение языкового ключа.
 */
define('LANG_KEY' , 'lang');

/**
 * Адаптер языкового модуля.
 */
define('LANG_ADAPTER' , 'gettext');

/**
 * Полный путь к языкам.
 */
define('PATH_DIR_LANG', PATH_DIR_APPL . DIRECTORY_SEPARATOR . 'Languages');

/**
 * Email адрес почтового робота.
 */
define('EMAIL_ROBOT', 'robot@onthelist.ru');

#======================== /ОСНОВНЫЕ КОНСТАНТЫ ==================================

#========================= ОСНОВНЫЕ НАСТРОЙКИ ==================================

set_include_path('.' . PATH_SEPARATOR . PATH_DIR_LIB . PATH_SEPARATOR . PATH_DIR_APPL);

require_once PATH_DIR_LIB . DIRECTORY_SEPARATOR . 'Zend/Loader.php';
Zend_Loader::registerAutoload();

// ======================= /ОСНОВНЫЕ НАСТРОЙКИ ==================================

// ====================== НАСТРОЙКИ БАЗЫ ДАННЫХ =================================
$db = Zend_Db::factory ('Pdo_Mysql', array(
	'host'     => '127.0.0.1',
	'username' => 'onthelist',
	'password' => 'onthelistru',
	'dbname'   => 'db_onthelist'
));
#$profiler = $db->getProfiler();
#$profiler->setEnabled(true);
$db->query('SET NAMES UTF8');

//require_once 'Zend/Cache.php';
$frontendOptions = array('lifetime'  => 600, 'automatic_serialization' => true);
$backendOptions  = array('cache_dir' => PATH_DIR_HOST . '/cache/db/meta');
$cacheDb = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

Zend_Db_Table_Abstract::setDefaultMetadataCache($cacheDb);

Zend_Registry::set('db', $db);
Zend_Registry::set('cache_db', $cacheDb);

#====================== /НАСТРОЙКИ БАЗЫ ДАННЫХ =================================

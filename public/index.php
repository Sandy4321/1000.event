<?php

/**
 * SiteActionSystems (SAS)
 *
 * Стартовый файл.
 *
 * @category Sas
 * @package Sas_Site
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2014 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 1.0
 */

define('START_TIME', microtime());

// Подгружаем основной файл инициализации.
require_once 'init.php';

#====================== Настройка FrameWork ====================================

// Создаём роутер
$router = new Zend_Controller_Router_Rewrite();
$router->addRoute('default',
	new Sas_Controller_Router_Route_Multilingual(
		array('module' => DEFAULT_MODULE_NAME,
			  'controller' => 'index',
			  'action' => 'index',
			  LANG_KEY => LANG_DEFAULT,
		)
	)
);

// Устанавливаем контроллер
$frontController = Zend_Controller_Front::getInstance();

$frontController->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(array(
	'module'     => DEFAULT_MODULE_NAME,
	'controller' => 'error',
	'action'     => 'error',
	LANG_KEY => LANG_DEFAULT,
)));

$frontController->registerPlugin(new Sas_Controller_Plugin_Start());

// Задаём языковые опции.
$addLocale[] = array('locale'  => 'en', 'path' => PATH_DIR_LANG . DIRECTORY_SEPARATOR . 'lang_en.mo');
$option = array(
	'adapter' => LANG_ADAPTER,
	'data'    => PATH_DIR_LANG . DIRECTORY_SEPARATOR . 'lang_' . LANG_DEFAULT . '.mo',
	'locale'  => LANG_DEFAULT,
	'tag'     => 'LangCache',
	'options' => array(
		'langKey' => LANG_KEY,
		'disableNotices' => true
	),
	'addLocale' => $addLocale
);

$frontController->registerPlugin(new Sas_Controller_Plugin_Language($option));

// Устанавливаем модуль по умолчанию
$frontController->setDefaultModule(DEFAULT_MODULE_NAME);

// Указываем путь к директории модулей
$frontController->addModuleDirectory(PATH_DIR_MODULES);

// Устанавливаем режим вывода отображения ошибок и исключений на страницу
$frontController->throwExceptions(true);

// Устанавливаем созданный роутер
$frontController->setRouter($router);

// Помощники действий (Action_Helper)
Zend_Controller_Action_HelperBroker::addPath(PATH_DIR_LIB.'/Sas/Controller/Action/', 'Sas_Controller_Action_Helper');

#===================== /Настройка FrameWork ====================================

// Запускаем диспетчеризацию
try {
	$frontController->returnResponse(true);
	$response = $frontController->dispatch();
	$response->sendResponse();
} catch (Exception $e) {
	header('Content-Type: text/html;charset='.SITE_CHARSET);
	echo '<p style="font-weight:bolder;">Исключение: ' . $e->getMessage() . '<br/>';
	echo 'Код исключения: ' . $e->getCode() . '<br/>';
	echo 'Файл: ' . $e->getFile() . '<br/>';
	echo 'Строка: ' . $e->getLine() . "</p>\n";
	echo '<pre style="font-size:0.85em;display:block;overflow:auto;background-color:#e2e2e2;">' . $e->getTraceAsString() . '</pre>';
}

// Скорость работы сайта
$configDb['time_speed'] = 0;
if($configDb['time_speed'] == 1) {
	$tS = explode(' ', START_TIME); $tS = $tS[0] + $tS[1];
	$tE = explode(' ', microtime()); $tE = $tE[0] + $tE[1];
	echo 'Время выполнения: ' . (number_format($tE - $tS, 4)) .' сек.';
}

// Профилирование работы БД
$configDb['profiler'] = false;
if ($configDb['profiler'] == 1) {
	$profiler = $db->getProfiler();
	$profiler->setEnabled(true);
	//Sas_Debug::dump($profiler);

	$totalTime    = $profiler->getTotalElapsedSecs();
	$queryCount   = $profiler->getTotalNumQueries();
	$longestTime  = 0;
	$longestQuery = null;

	foreach ($profiler->getQueryProfiles() as $query) {
		if ($query->getElapsedSecs() > $longestTime) {
			$longestTime  = $query->getElapsedSecs();
			$longestQuery = $query->getQuery();
		}
	}

	echo '<p>Выполнено ' . $queryCount . ' запросов общим временем ' . $totalTime . ' секунд' . "<br/>\n";
	#echo 'Среднее время запроса: ' . $totalTime / $queryCount . ' секунд' . "<br/>\n";
	#echo 'Запросов в секунду: ' . $queryCount / $totalTime . "<br/>\n";
	echo 'Последний запрос: '.$query->getQuery().'<br/>';
	echo 'Самый медленный запрос: ' . $longestTime . " секунд.<br/>\n";
	echo 'Самый медленный запрос: ' . $longestQuery . "</p>\n";
}

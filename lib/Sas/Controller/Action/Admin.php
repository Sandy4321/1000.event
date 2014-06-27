<?php

/**
 * SiteActionSystems (SAS)
 * 
 * Контроллер действий.
 * 
 * @category Sas
 * @package Sas_Controller
 * @subpackage Sas_Controller_Action
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2008 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 2.0
 */

// Старт сессии
session_start();

class Sas_Controller_Action_Admin extends Sas_Controller_Action
{
	private static $_cntLoad = 0;
	
	public function initSas()
	{
		self::$_cntLoad++;

		// Обязательная проверка авторизации.
		$this->_checkAut();
		
		// Есть еще опции см. в мануале
		// Меняем путь к макетам сайта на админские макеты
		$this->_startLayout(array('layoutPath' => PATH_DIR_LAYOUT_ADMIN));
		
		$this->initSasAdmin();
	}
	
	public function initSasAdmin() {}
	
	/**
	 * Обязательная проверка авторизаци.
	 */
	final private function _checkAut()
	{
		$Admin = new Models_Admin();
		
		// Если нет авторизации и модуль, контроллер не равен index,
		// а так же если действия не равны index или quit,
		// то перенапрвляем на страницу авторизации.
		if ($Admin->isAut() === false &&
			(
				$this->getModuleStart() != 'admyn' ||
				$this->getControllerStart() != 'index' ||
					(
						$this->getActionStart() != 'index' &&
						$this->getActionStart() != 'quit'
					)
			)
		)
		{
			$this->_redirect('/admyn');
		}
		
		// Если пользователь авторизован
		if ($Admin->isAut() === true) {
			// Особый вариант для страницы выхода
			if ($this->getModuleStart() == 'admyn' && $this->getControllerStart() == 'index' && $this->getActionStart() == 'quit') {
				unset($this->view->vUserName);
			} else {
				$this->_yesAut();
			}
		}
	}
	
	private function _yesAut() {
		$this->view->vUserName = 'Admin';
		#$this->_loadMenuModule();
	}
	
	private function _loadMenuModule()
	{
		if (self::$_cntLoad > 1) return;
		
		// Грузим меню модулей
		#echo 'грузим меню модулей<br/>';
		$d = dir(PATH_DIR_MODULES);
		#echo "Дескриптор: ".$d->handle."<br>\n";
		#echo "Путь: ".$d->path."<br>\n";
		while (false !== ($entry = $d->read())) {
			if ($entry == '.' || $entry == '..' || $entry == DEFAULT_MODULE_NAME) continue;
			#echo $entry."<br>\n";
			if (file_exists(PATH_DIR_MODULES . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AdminController.php')) {
				#echo $entry."<br>\n";
				$this->_helper->actionStack('menumodule', 'admyn', $entry);
			}
		}
		$d->close();
	}

	protected function ajax() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
	}

	protected function setJson($json) {
		$sendJson = $this->_helper->json($json);
		$this->getResponse()->appendBody($sendJson);

		return $sendJson;
	}
}

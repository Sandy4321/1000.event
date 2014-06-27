<?php

/**
 * SiteActionSystems (SAS)
 *
 * Контроллер действий.
 *
 * @category Sas
 * @package Sas_Controller
 * @subpackage Sas_Controller_User
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2014 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 0.2
 */

// Старт сессии
session_start();

class Sas_Controller_Action_User extends Sas_Controller_Action
{
	/**
	 * Счётчик загрузок контроллера
	 * @var int
	 */
	private static $_cntLoad = 0;

	protected $myId;

	public function initSas()
	{
		// Меняем путь к макетам сайта на пользовательские макеты
		$this->_startLayout(array('layoutPath' => PATH_DIR_LAYOUT_USER));

		if (empty($_SESSION['user']['auth'])) {
			if ($this->getModuleStart() != 'user' || $this->getControllerStart() != 'login' || $this->getActionStart() != 'index' ) {
				$this->_redirect('/user/login');
			}
		}

		$this->myId = Models_Auth::getMyId();

		$this->initSasUser();

		self::$_cntLoad++;
		if (self::$_cntLoad > 1) {
			return;
		}

		// Далее идет код, выполняющий всегда только один раз

	}

	public function initSasUser() {}

}
<?php

class User_LoginController extends Sas_Controller_Action_User
{
	// Меняем установки по умолчанию
	public function initSasUser() {

		// Подменяем макеты
		$this->_startLayout(array('layoutPath' => PATH_DIR_LAYOUT_SITE));
		//$this->_startLayout(array('layoutPath' => PATH_DIR_LAYOUT_SITE_OLD));

		#$this->_helper->actionStack('one-quote', 'people', 'news', array('SEGMENT' => 'NewsPeopleOneQuote'));
	}

	public function indexAction()
	{
		// В случае успешной авторизации кидаем пользователя на /user
		if ($_SESSION['user']['auth'] === true) {
			$this->_redirect('/user');
			return;
		}

		// Пробуем авторизовать пользователя
		if(
			($this->getRequest()->isPost() && $this->_getParam('email') && $this->_getParam('password'))
			|| ($this->_getParam('email') && $this->_getParam('key'))
		)
		{
			$Model = new Models_User_Model();
			$resAuth = $Model->login($this->getRequest());

			// Проверяем авторизацию
			if ($Model->isAuth()) {
				$this->_redirect('/user');
			} else {
				unset($_SESSION['user']['auth']);
			}

			$this->view->assign('vAccess', $resAuth);
		}
	}

	/**
	 * Выход с перенаправление на главную страницу сайта
	 */
	public function quitAction() {
		$this->ajaxInit();
		$Model = new Models_User_Model();
		$Model->quit();
		session_destroy();
		unset($_SESSION);
		$this->_redirect('/');
	}
}
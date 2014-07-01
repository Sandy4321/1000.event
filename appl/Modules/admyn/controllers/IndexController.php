<?php

class Admyn_IndexController extends Sas_Controller_Action_Admin
{
	public function indexAction()
	{
		$ModelAdmin = new Models_Admin();

		// Проверяем, может пользователь уже авторизован?
		if ($ModelAdmin->isAut() === true) {
			$this->_redirect('/admyn/dashboard');
		}

		// Пользователь еще не авторизован
		$request = $this->getRequest();
		if ($request->isPost()) {
			if ($ModelAdmin->checkAut($request->getParam('login'), $request->getParam('password')) === true) {
				$this->_redirect('/admyn/dashboard');
			} else {
				$this->_redirect('/admyn');
			}
		}
	}

	public function quitAction()
	{
		$this->_helper->layout()->disableLayout();
		$this->noRender();

		$ModelAdmin = new Models_Admin();
		$ModelAdmin->quit();

		$this->_redirect('/admyn');
	}
}
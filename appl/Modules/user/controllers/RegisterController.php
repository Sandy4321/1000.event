<?php

class User_RegisterController extends Sas_Controller_Action
{
	private $checkRegister = false;

	// Меняем установки по умолчанию
	public function initSas()
	{
		// Подменяем макеты
		$this->_startLayout(array('layoutPath' => PATH_DIR_LAYOUT_SITE));
		//$this->_startLayout(array('layoutPath' => PATH_DIR_LAYOUT_SITE_OLD));
	}

	public function indexAction()
	{
		$request = $this->getRequest();
		if($this->getCookiePromoKey() != false) {
			$this->view->vPromoKey = $this->getCookiePromoKey();
			$request->setParam('promo-key', $this->getCookiePromoKey());
		} else {
			$this->view->vPromoKey = $request->getParam('promo-key', null);
		}
		$this->view->vPromoKey = $this->_getParam('promo-key', null);
	}

	/**
	 * Страница приветствия после успешной регистрации
	 */
	public function welcomeAction() {

	}

	/*
	 * Форма активации аккаунта
	 */
	public function activationAction()
	{
		// Старт сессии
		session_start();

		//$this->noRender();

		$request = $this->getRequest();
		$key = $request->getParam('key', null);
		//Sas_Debug::dump($key);

		// Проверить ключ
		if(!is_null($key) && strlen($key) == 32) {
			#$ModelReg = new Models_User_Register();
			#$profile = $ModelReg->checkActivationKey($key);

			$ModelRegister = new Models_Register();
			$ModelUser = $ModelRegister->checkActivationKey($key);
			if($ModelUser instanceof Models_Users) {
				$ModelRegister->activationNewUser($ModelUser);
				$this->view->vProfile = $ModelUser->getProfileToArray();

				// После успешной активации, при наличии промо куки - удаляем её
				if($this->getCookiePromoKey() != false) {
					$this->deleteCookiePromoKey();
				}
			} else {
				$this->view->vProfile = false;
			}
		} else {
			$this->view->vProfile = false;
		}
	}

}
<?php

class User_UnsubscribeController extends Sas_Controller_Action
{
	public function indexAction() {

		$ModelUsers = new Models_User_Model();

		// Получаем ключ
		$key = $this->_getParam('key');
		$key = htmlspecialchars(trim($key));
		if(strlen($key) != 32) {
			$this->_redirect('/user/unsubscribe/error-key');
		}

		$profile = $ModelUsers->isValidActivationKey($key);
		if($profile == false) {
			$this->_redirect('/user/unsubscribe/error-key');
		}

		$this->view->key = $key;
		$this->view->profile = $profile;
		//Sas_Debug::dump($profile);

		if($this->_getParam('save') == 1) {
			$params = ($this->_getParam('un_email') == 'yes') ? '&un_email=yes' : '&un_email=no';
			$params .= ($this->_getParam('un_phone') == 'yes') ? '&un_phone=yes' : '&un_phone=no';
			$this->_redirect('/user/unsubscribe/ok/?key=' . $key . $params);
		}

	}

	public function okAction() {
		$ModelUsers = new Models_User_Model();

		// Получаем ключ
		$key = $this->_getParam('key');
		$key = htmlspecialchars(trim($key));
		$profile = $ModelUsers->isValidActivationKey($key);
		if($profile == false) {
			$this->_redirect('/user/unsubscribe/error-key');
		}

		// Отписываем
		$ModelUsers->unsubscribe($key, $this->_getParam('un_email'), $this->_getParam('un_phone'));

		$this->view->key = $key;
		$this->view->profile = $ModelUsers->isValidActivationKey($key);
	}

	public function errorKeyAction() {

	}
}
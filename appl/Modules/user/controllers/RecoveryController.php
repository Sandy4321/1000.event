<?php

class User_RecoveryController extends Sas_Controller_Action
{
	public function indexAction()
	{
		$email = $this->_getParam('email');
		$email = htmlspecialchars(trim($email));

		if(!empty($email) && strlen($email) > 6 && strlen($email) < 50)
		{
			$this->view->vEmail = $email;

			$ModelRecovery = new Models_User_Recovery();

			// Пробуем получить соответствующий профиль
			$profile = $ModelRecovery->isEmail($email);
			//Sas_Debug::dump($profile);

			// Проверяем наличие профиля
			if(is_array($profile)) {
				// Проверяем текущий статус пользователя
				if($profile['current_status'] >= 50) {

					// Проверяем есть была ли уже отправка в течении текущего часа
					$dtRec = $ModelRecovery->getDtRecoveryPsw($email);
					if(!is_null($dtRec)) {
						$Date = new DateTime($dtRec);
						$Date->modify('+ 1 Hour');
						$dtRec = $Date->format('Y-m-d H:i:s');
					}

					if(CURRENT_DATETIME < $dtRec) { // Текущая дата и время должны быть меньше час полученные + 1 час
						// Да, письмо уже было отправлено
						$this->view->assign('vData', $this->view->render('recovery/already_sent.phtml'));
					} else {
						// В течении текущего часа восстановление пароля не запрашивали
						// Генерим письмо со ссылкой и пробуем его оправить
						//Sas_Debug::dump($profile);
						try {
							$ModelTplMsg = new Models_TemplatesMessage($profile, 'password_recovery');
							$ModelTplMsg->addDataReplace('key', $profile['activation_key']);
							$ModelTplMsg->send();

							// Задаем дату отправки письма для восстановления пароля
							$ModelRecovery->sendNewDtSendRecoveryPsw($profile['id']);

							// Письмо отправлено!
							$this->view->assign('vData', $this->view->render('recovery/yes-recovery.phtml'));
						} catch (Sas_Exception $e) {
							// Письмо НЕ отправлено!
							$this->view->assign('vData', $this->view->render('recovery/error-send-email.phtml'));
						}
					}

				} else {
					// Статус пользователя не позволяет восстановить ему пароль
					$this->view->assign('vData', $this->view->render('recovery/no-recovery.phtml'));
				}

			} else { // Нет такого профиля
				$this->view->assign('vData', $this->view->render('recovery/no-email.phtml'));

				// Форма для ввода email
				$this->view->assign('vData', $this->view->render('recovery/form-email.phtml'));
			}

			/*if(is_array($profile)) {
				// Генерим пароль
				#$new_pws = substr(md5(rand(100, 10000) . time()), -10);
				$new_pws = sprintf('%06d', rand(100000, 999999));

				// Отправляем пароль
				try {
					$ModelMsg = new Models_TemplatesMessage($profile, 'password_recovery');
					$ModelMsg->addDataReplace('new_password', $new_pws);
					$ModelMsg->send();

					$update['psw'] = new Zend_Db_Expr('MD5("'.$new_pws.'")');
					#$db->update('users_data', $update, $db->quoteInto('id = ?', $profile['id']));
					$db->update('users', $update, $db->quoteInto('id = ?', $profile['id']));
					Models_Actions::add(55, $profile['id']); // Запрошено восстановление пароля

					$this->view->vSendOk = true;
				} catch (Sas_Exception $e) {
					// TODO: записать лог
					//Sas_Debug::dump($e->getMessage(), $new_pws);
				}
			}*/
		} else {
			// Форма для ввода email
			$this->view->assign('vData', $this->view->render('recovery/form-email.phtml'));
		}
	}

	/**
	 * Создание нового пароля
	 */
	public function newpswAction() {

		// Если нет ключа вообще
		if(!$this->_getParam('key')) {
			$this->_redirect('/');
			exit;
		}

		// Если ключа не в формате
		$key = trim($this->_getParam('key'));
		if(strlen($key) != 32) {
			$this->view->assign('vData', $this->view->render('recovery/newpsw-error-key.phtml'));
			return;
		}

		$error = null;

		// Проверить ключ и дату
		$ModelRecovery = new Models_User_Recovery();
		$profile = $ModelRecovery->getKey($key);
		if(!is_array($profile)) {
			$this->_redirect('/');
			exit;
		}

		// Проверяем дату валидности ключа
		if($ModelRecovery->isValidDateKey($profile['recovery_psw_dt']) == false) {
			$this->view->assign('vData', $this->view->render('recovery/newpsw-error-date.phtml'));
			return;
		}

		$this->view->vEmail = $profile['email'];

		if($this->getRequest()->isPost()) {
			$psw = trim($this->_getParam('psw'));
			$psw1 = trim($this->_getParam('psw1'));

			if(strlen($psw) < 6 || strlen($psw1) < 6) {
				$error = 'SMALL_STRLEN';
			}

			if($psw == '' || $psw1 == '') {
				$error = 'NOT_NULL';
			}

			if($psw != $psw1) {
				$error = 'NE_RAVNO';
			}

			if(is_null($error)) {
				$ModelRecovery->setNewPsw($profile['id'], $psw);
				$this->view->assign('vData', $this->view->render('recovery/newpsw-ok.phtml'));
			} else {
				$this->view->vError = $error;
				$this->view->assign('vData', $this->view->render('recovery/form-newpsw.phtml'));
			}
		} else {
			$this->view->assign('vData', $this->view->render('recovery/form-newpsw.phtml'));
		}
	}
}
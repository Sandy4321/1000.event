<?php

class Admyn_UsersController extends Sas_Controller_Action_Admin
{
	// ============================== AJAX ==============================

	// В систему добавлен новый пользователь
	public function ajaxAddUserAction()
	{
		$this->ajax();

		$data = $this->getRequest()->getPost();
		if(!$data) return false;

		$userId = (int)$data['id'];
		if ($userId <=0 )
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет ID пользователя или он не корректен!';
			$this->setJson($json);
			return false;
		}

		$ModelUser = new Models_Admin_Users();
		$newUserProfile = $ModelUser->addUser($userId);
		Models_Actions::add(43, null, $userId, $userId); // Заявка на регистрацию одобрена

		// Акция! до 15 декабря. Даем КК со сроком истечения 1 декабря 2013
		#if(CURRENT_DATE <= '2013-12-15') {
		#	$ModelUser->setCardDateNew($userId, '2013-12-15');
		#}

		// Акция - ПРОМОКОДЫ по письму Альмира
		$promoArray = array(
			'VIPKUPON', 'TEZ00000', 'MIR00000', '1VPgroup', 'VIP00000', 'MOS00000'
		);
		if(in_array(strtoupper($newUserProfile['promocode']), $promoArray)) {
			$Date = new DateTime(CURRENT_DATETIME);
			$newDate = $Date->modify('+7 Day');
			$ModelUser->setCardDateNew($userId, $newDate->format('Y-m-d'));
		}

		// Модель промо
		$ModelPromo = new Models_Admin_PromoAction();

		// Акция - Убер + 30 дней / для приглащенных пользователей
		if(!is_null($newUserProfile['promo_key_friend'])) {
			$ModelPromo->uberPlus30Day($newUserProfile, $ModelUser->getFriend($newUserProfile['promo_key_friend']));
		}

		// Акция Для клуба 60 сек = Убер 2 поездки по 1000 руб
		if(strtoupper($newUserProfile['promocode']) == 'VIP60SEC') {
			$ModelPromo->uber1000drive2($newUserProfile);
		}

		// Добавляем дни в карту приглашающему
		/*if(!is_null($userProfile['promo_key_friend'])) {
			// Получаем профиль приглащающего
			$profileFriend = $ModelUser->getFriend($userProfile['promo_key_friend']);

			$cardFriend = $profileFriend['club_card_dt'];
			if($cardFriend < CURRENT_DATE) {
				$d = new DateTime(CURRENT_DATE);
				$d->modify('+' . PRICE_FRIEND . ' day');
				$cardFriendNew = $d->format("Y-m-d");
			} else {
				$d = new DateTime($cardFriend);
				$d->modify('+' . PRICE_FRIEND . ' day');
				$cardFriendNew = $d->format("Y-m-d");
			}

			$ModelUser->setCardDateNew($profileFriend['id'], $cardFriendNew);
			$ModelUser->addRecordLogNewFriend($profileFriend['id']);
			Models_Actions::add(64, null, $profileFriend['id'], $userId); // Добавленны дни к Клубной Карте за приглашение друга
		}*/


		// Отправляем уведомление
		$ModelSendMsg = new Models_TemplatesMessage($newUserProfile, 'activation_profile', 'msg_admin');
		$ModelSendMsg->addDataReplace('%first_name%', $newUserProfile['first_name']);
		#$ModelSendMsg->addDataReplace('%activation_key%', $userProfile['activation_key']);

		try {
			$ModelSendMsg->send();
		} catch (Sas_Exception $e) {
			$json['type'] = 'info';
			$json['msg'] = 'Пользователь принят в Клуб<br>Но возникла ошибка при отправке email: ' . $e->getMessage();
			$this->setJson($json);
			return false;
		}


		$json['type'] = 'ok';
		$json['msg'] = 'Пользователь принят в Клуб';

		$this->setJson($json);

		return true;
	}

	// По требованию админа пользователю задается статус Призрак
	public function ajaxSetGhostAction()
	{
		$this->ajax();

		$data = $this->getRequest()->getPost();
		if(!$data) return false;

		$userId = (int)$data['id'];
		if ($userId <=0 )
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет ID пользователя или он не корректен!';
			$this->setJson($json);
			return false;
		}

		$ModelUser = new Models_Admin_Users();
		$ModelUser->setUserGhost($userId);

		$json['type'] = 'ok';
		$json['msg'] = 'Пользователю установлен статус 50 - Призрак';

		$this->setJson($json);

		return true;
	}

	public function ajaxSetGhostAvatarDelAction()
	{
		$this->ajax();

		$data = $this->getRequest()->getPost();
		if(!$data) return false;

		$userId = (int)$data['id'];
		if ($userId <=0 )
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет ID пользователя или он не корректен!';
			$this->setJson($json);
			return false;
		}

		$ModelUser = new Models_Admin_Users();
		$userProfile = $ModelUser->setUserGhost($userId);
		$ModelUser->avatarDelete($userProfile);

		$json['type'] = 'ok';
		$json['msg'] = 'Аватарка удалена и Пользователю установлен статус 50 - Призрак';

		$this->setJson($json);

		return true;
	}

	/*
	 * Восстановление пользователя в системе
	 */
	public function ajaxRecoverUserAction()
	{
		$this->ajax();

		$data = $this->getRequest()->getPost();
		if(!$data) return false;

		$userId = (int)$data['id'];
		if ($userId <=0 )
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет ID пользователя или он не корректен!';
			$this->setJson($json);
			return false;
		}

		$ModelUser = new Models_Admin_Users();
		$userProfile = $ModelUser->recoverUser($userId);
		Models_Actions::add(56, null, $userId, $userId); // Восстановлен удалённый (заблокированный) пользователь

		$json['type'] = 'ok';
		$json['msg'] = 'Пользователь восстановлен в Клубе';

		$this->setJson($json);

		return true;
	}

	/**
	 * Запрос доп. информации по заявке
	 * @return bool
	 */
	public function ajaxRequestMoreInfoAction()
	{
		$this->ajax();

		$data = $this->getRequest()->getPost();
		if(!$data) return false;

		$userId = (int)$data['id'];
		$fio = $data['fio'];
		$email = $data['email'];
		$theme = $data['theme'];
		$text = $data['text'];

		if ($userId <=0 )
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет ID пользователя или он не корректен!';
			$this->setJson($json);
			return false;
		}

		if (empty($fio))
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет имени получателя!';
			$this->setJson($json);
			return false;
		}

		if (empty($email))
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет email получателя!';
			$this->setJson($json);
			return false;
		}

		if (empty($theme))
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет темы письма!';
			$this->setJson($json);
			return false;
		}

		if (empty($text))
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет текста письма!';
			$this->setJson($json);
			return false;
		}

		$ModelUser = new Models_Admin_Users();
		$userProfile = $ModelUser->requestMoreInfo($userId);
		Models_Actions::add(45, null, $userId, $userId); // Запрошена доп. информация по заявке на регистрацию

		// Отправляем уведомление
		/*$ModelSendMsg = new Models_TemplatesMessage($userProfile, 'get_more_info_new_user', 'msg_admin');
		$ModelSendMsg->addDataReplace('%first_name%', $userProfile['first_name']);*/

		try {
			$mail = new Zend_Mail('UTF-8');
			$mail->setSubject($theme);
			//$mail->setBodyText($text);
			$mail->setBodyHtml($text);
			$mail->setFrom(EMAIL_ROBOT, 'OnTheList');
			$mail->addTo($email, $fio);
			$mail->send();
		} catch (Exception $e) {
			$json['type'] = 'info';
			$json['msg'] = 'Пользователю изменён статус на: "Запрошена доп. информация".<br>Но возникла ошибка при отправке email: ' . $e->getMessage();
			$this->setJson($json);
			return false;
		}

		$json['type'] = 'ok';
		$json['msg'] = 'Пользователю отправлено письмо с запросом на получение дополнительной информации.<br>Получатель: ' . $fio .' адрес: ' . $email;

		$this->setJson($json);

		return true;
	}

	/*
	 * Отклонение заявки на регистрацию
	 */
	public function deleteNewUserAction()
	{
		$this->ajax();

		$data = $this->getRequest()->getPost();
		if(!$data) return false;

		$userId = (int)$data['id'];
		if ($userId <=0 )
		{
			$json['type'] = 'error';
			$json['msg'] = 'Нет ID пользователя или он не корректен!';
			$this->setJson($json);
			return false;
		}

		$ModelUser = new Models_Admin_Users();
		$userProfile = $ModelUser->deleteNewUser($userId);
		Models_Actions::add(44, null, $userId, $userId); // Заявка на регистрацию отклонена

		// Отправляем уведомление
		$ModelSendMsg = new Models_TemplatesMessage($userProfile, 'delete_registration_new_user', 'msg_admin');
		$ModelSendMsg->addDataReplace('%first_name%', $userProfile['first_name']);

		try {
			$ModelSendMsg->send();
		} catch (Sas_Exception $e) {
			$json['type'] = 'info';
			$json['msg'] = 'Пользователю изменён статус на: "Заявка отклонена".<br>Но возникла ошибка при отправке email: ' . $e->getMessage();
			$this->setJson($json);
			return false;
		}

		$json['type'] = 'ok';
		$json['msg'] = 'Пользователю отправлено письмо с отказом в регистрации.';

		$this->setJson($json);

		return true;
	}

	/**
	 * Загрузка резюме
	 * @return bool
	 */
	public function downloadResumeAction()
	{
		$this->ajax();

		$data = $this->getRequest()->getParam('id', null);
		if(!$data) return false;

		$userId = (int)$data;
		if ($userId <=0 )
		{
			echo 'Нет ID пользователя или он не корректен!';
			return false;
		}

		$ModelUser = new Models_Admin_Users();
		$userProfile = $ModelUser->getUserProfile($userId);

		$patchResume = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'people' .
		DIRECTORY_SEPARATOR . $userProfile['sex'] . DIRECTORY_SEPARATOR .
		Models_User_Model::getMyYear($userProfile['birthday']) . DIRECTORY_SEPARATOR . $userProfile['id'] .
		DIRECTORY_SEPARATOR;

		$originalResumeName = 'resume.xxx'; //оригинальное название файла
		$fullPatchOriginal = $patchResume.$originalResumeName; // Полный путь включая оригинальное название файла

		if(!file_exists($fullPatchOriginal)) {
			echo 'Запрошенного файла не существует!';
			return false;
		}

		$realResumeName = file_get_contents($patchResume.'fileNameResume.txt'); // реальное название файла
		$tmpExt = explode('.', $realResumeName);

		// Имя файла для загрузки
		$downloadName = 'Resume_UserId-' . $userProfile['id'] .'_' . date('Y-m-d') . '.' . array_pop($tmpExt);

		// Формируем заголовки ответа
		header($_SERVER["SERVER_PROTOCOL"] . ' 200 OK');
		header('Content-Type: application/octet-stream');
		header('Last-Modified: ' . gmdate('r', filemtime($fullPatchOriginal)));
		header('ETag: ' . sprintf('%x-%x-%x', fileinode($fullPatchOriginal), filesize($fullPatchOriginal), filemtime($fullPatchOriginal)));
		header('Content-Length: ' . (filesize($fullPatchOriginal)));
		header('Connection: close');
		header('Content-Disposition: attachment; filename="' . basename($downloadName) . '";');

		// Отдаем содержимое файла
		echo file_get_contents($fullPatchOriginal);
	}

	public function msgUserOkAction()
	{
		$this->ajax();

		$id = $this->getRequest()->getParam('id');
		$question = $this->getRequest()->getParam('question');
		$answer   = $this->getRequest()->getParam('answer');

		$ModelUser = new Models_Admin_Users();
		$ModelUser->saveUsersMsg($id, $question, $answer);

		$json['type'] = 'ok';
		$json['msg'] = 'Вопрос и Ответ одобреты администратором и сохранены.';

		$this->setJson($json);

		return true;
	}

	public function ajaxSendMsgAction()
	{
		$this->ajax();

		$userId = $this->_getParam('user_id');
		$userLang = $this->_getParam('user_lang');
		$userFio = $this->_getParam('user_fio');
		$userEmail = $this->_getParam('user_email');
		$userPhone = $this->_getParam('user_phone');
		$typeMsg = $this->_getParam('type_msg');
		$themeMsg = $this->_getParam('theme_msg');//
		$textMsg = $this->_getParam('text_msg');//

		#Sas_Debug::dump($this->_getAllParams());

		// email
		if ($typeMsg == 'email' || $typeMsg == 'email_and_lock')
		{
			try {
				$mail = new Zend_Mail('UTF-8');
				$mail->setSubject($themeMsg);
				// $mail->setBodyText($textMsg); // txt
				$mail->setBodyHtml($textMsg); // html
				$mail->setFrom(EMAIL_ROBOT, 'OnTheList');
				$mail->addTo($userEmail, $userFio);
				$mail->send();
				$json['type'] = 'ok';
				$json['msg'] = 'Пользователю отправлен email для: ' . $userFio .' адрес: ' . $userEmail;
				//Sas_Debug::dump($this->_getAllParams());
				#Sas_Debug::dump($mail->getBodyText(), __METHOD__);
			} catch (Zend_Mail_Exception $e) {
				$json['type'] = 'error';
				$json['msg'] = 'Программый сбой при отправке email: ' . $e->getMessage();
				#$json['msg'] .= '<br>Тема / текст: ' . $themeMsg .' / '. $textMsg;
				#$json['msg'] .= '<br>ФИО / email: ' . $userEmail .' / '. $userFio;
			}

			// email and lock
			if ($typeMsg == 'email_and_lock')
			{
				// Блокировка пользователя
				$ModelUsers = new Models_Admin_Users();
				$ModelUsers->lockUser($userId);
				$json['msg'] .= '<br>Пользователь заблокирован!';
			}
		}

		// sms
		if ($typeMsg == 'sms')
		{
			try {
				$sms = new Zelenin_SmsRu('c1a21b64-3825-7674-d968-bee81a27d285');
				$userPhone = preg_replace("/[^0-9]/", '', $userPhone);
				#$send = $sms->sms_send($userPhone, $textMsg, 'OnTheList', time(), false, true); // Тестовый режим
				$send = $sms->sms_send($userPhone, $textMsg, 'OnTheList'); // Рабочий режим

				if ($send['code'] != 100) {
					throw new Sas_Models_Exception('error send sms');
				}

				$smsBalance = $sms->my_balance();
				$json['type'] = 'ok';
				$json['msg'] = 'SMS сообщение отправлено на номер: ' . $userPhone;
				#$json['msg'] .= '<br>Статус отправки: '.$sms->sms_status($userPhone);
				#$json['msg'] .= '<br>Баланс: ' . $smsBalance['balance'];
			} catch (Sas_Models_Exception $e) {
				// TODO: записать в лог причину ошибки
				$json['type'] = 'error';
				$json['msg'] = 'Ошибка отправки SMS сообщение: ' . $e->getMessage();
			}
		}

		// Dash
		if ($typeMsg == 'dash')
		{
			$ModelMsg = new Models_User_SystemMsg();
			$ModelMsg->setMsgUser($userId, $userLang, $textMsg);
			$json['type'] = 'ok';
			$json['msg'] = 'Сообщение отправлено на Dashboard.';
		}

		$this->setJson($json);

		return true;
	}

	// ============================== USERS ==============================

	/**
	 * Отклонённые заявки
	 */
	public function rejectRequestAction()
	{
		$ModelUser = new Models_Admin_Users();
		$orderBy = 'denied_dt DESC';
		$this->view->vData = $ModelUser->getUserStatus(30, null, $orderBy, 50);
		$this->view->vCntData = $ModelUser->getCntUsers(30);
	}

	/**
	 * Ожидающие доп. информации
	 */
	public function waitingRequestAction()
	{
		$ModelUser = new Models_Admin_Users();
		$this->view->vData = $ModelUser->getUserStatus(52);
	}

	/**
	 * Одобренные но не подтвержденные пользователем
	 */
	public function approvedRequestAction()
	{
		throw new Sas_Exception('Одобренные но не подтвержденные пользователем - БОЛЬШЕ НЕБЫВАЕТ ТАКИХ ЗАЯВОК');
		#$ModelUser = new Models_Admin_Users();
		#$orderBy = 'register_dt DESC';
		#$this->view->vData = $ModelUser->getUserStatus(7, null, $orderBy);
	}

	/**
	 * Активные пользователи
	 */
	public function activeUsersAction()
	{
		$ModelUser = new Models_Admin_Users();
		$orderBy = 'online_last_dt DESC';
		$this->view->vData = $ModelUser->getUserStatus(70, null, $orderBy, 50);
		$this->view->vCntData = $ModelUser->getCntUsers(70);
	}

	/**
	 * Пользователи со скрытыми профилями
	 */
	public function hiddenUsersAction()
	{
		throw new Sas_Exception('Пользователи со скрытыми профилями - СЕЙЧАС НЕ ТАКИХ ЛЮДЕЙ В СИСТЕМЕ');
		/*$ModelUser = new Models_Admin_Users();
		$whereArray[] = array(
			'logic' => 'and',
			'operator' => '=',
			'name' => 'hide_profile',
			'val' => 'yes',
		);
		$orderBy = 'activity_time DESC';
		$this->view->vData = $ModelUser->getUserStatus(3, $whereArray, $orderBy, 50);*/
	}

	/**
	 * Заблокированные администрацией
	 */
	public function blockedUsersAction()
	{
		$ModelUser = new Models_Admin_Users();
		$orderBy = 'activity_time DESC';
		$this->view->vData = $ModelUser->getUserStatus(40, null, $orderBy);
	}

	/**
	 * Профили удалённые пользователем
	 */
	public function deleteUsersAction()
	{
		$ModelUser = new Models_Admin_Users();
		$this->view->vData = $ModelUser->getUserStatus(20);
	}

	/**
	 * Просмотр платных сообщений
	 */
	public function viewUsersMsgAction()
	{
		$ModelUser = new Models_Admin_Users();
		$this->view->vMsgMoney = $ModelUser->getUsersMsgMoney();
		$this->view->vMsgFree = $ModelUser->getUsersMsgFree();
	}

	/**
	 * Просмотр статусов
	 */
	public function viewUsersStatusAction()
	{
		$ModelUser = new Models_Admin_Users();

		if($this->_getParam('status_id')) {
			$ModelUser->deleteUsersStatus($this->_getParam('status_id'));
		}

		$this->view->vUsersStatus = $ModelUser->getUsersStatus();
	}

	/**
	 * Просмотр полного профиля пользователя
	 */
	public function viewProfileAction()
	{
		$ModelUser = new Models_Admin_Users();

		$userId = $this->_getParam('id');

		// Сохранение изменений в профиле
		if($this->_getParam('save')) {
			$id = $_POST['id'];
			unset($_POST['save']);
			$ModelUser->saveProfile($id, $_POST);
		}

		$profile = $ModelUser->getUserProfileFull($userId);

		$this->view->vData = $profile;

		$ModelMsg = new Models_User_SystemMsg();
		$this->view->vMsgDash = $ModelMsg->getMsgDash($userId, true);

		// Кол-во свиданий
		$this->view->vCntDates = $ModelUser->getCntDates($userId, 'yes');

		// Кол-во обменов контактами пользователя.
		$this->view->vCntContactExchange = $ModelUser->getCntContactExchange($userId, 'yes');

		// Лог платежей
		$this->view->vBalanceHistory = $ModelUser->getBalanceHistory($userId);

		// Фотографии в альбоме
		$this->view->vPhotoAlbum = $ModelUser->getPhotoAlbum($userId);

		// Статусы
		$this->view->vStatuses = $ModelUser->getStatus($userId);

	}

	/**
	 * Полная история действий пользователя
	 */
	public function viewHistoryAction()
	{
		$ModelUser = new Models_Admin_Users();
		$userId = $this->_getParam('id');

		$profile = $ModelUser->getUserProfileFull($userId);
		$this->view->vProfile = $profile;

		$this->view->vHistory = $ModelUser->getUserHistory($userId);
	}

	/**
	 * Ближайшие назначенные свидания
	 */
	public function inviteUsersAction()
	{
		$ModelUser = new Models_Admin_Users();
		$this->view->vData = $ModelUser->getUserInvite();
	}

	public function reportDatesAction()
	{
		$ModelUser = new Models_Admin_Users();

		$dateOne = $this->_getParam('date_one');

		if(!empty($dateOne))
		{
			switch($dateOne) {
				case 'date-current':
					$date = new DateTime();
					$dateMin = $date->format('Y-m-d');
					$dateMax = $date->modify('+1 day')->format('Y-m-d');
					break;
				case 'date-yesterday':
					$date = new DateTime();
					$dateMax = $date->format('Y-m-d');
					$dateMin = $date->modify('-1 day')->format('Y-m-d');
					break;
				case 'date-weekly':
					$date = new DateTime();
					$dateMax = $date->modify('+1 day')->format('Y-m-d');
					$dateMin = $date->modify('-7 day -1 day')->format('Y-m-d');
					break;
				case 'date-month':
					$date = new DateTime();
					$dateMax = $date->modify('+1 day')->format('Y-m-d');
					$dateMin = $date->modify('-1 month -1 day')->format('Y-m-d');
					break;
			}

			$this->view->vDateOne  = $dateOne;
			$this->view->vData = $ModelUser->getReportDates($dateMin, $dateMax);
		}


	}
}
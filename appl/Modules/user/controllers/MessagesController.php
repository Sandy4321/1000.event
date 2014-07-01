<?

class User_MessagesController extends Sas_Controller_Action_User
{
	public function indexAction()
	{
		$myId = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myId);
		$myProfile = $ModelProfile->getProfile($myId);
		$this->view->myProfile = $myProfile;

		$ModelMsg = new Models_User_Msg($myId);
		###
		$MyProfile = new Models_Users($myId);
		$ModelMsg->setProfile($MyProfile);
		###
		$msgAll = $ModelMsg->getLastAll($myId); // Все последние входящие сообщения
		//Sas_Debug::dump($msgAll);
		$this->view->vMsgAll = $msgAll;
		$this->view->vMsgNoRead = $ModelMsg->getNoRead($myId); // Кол-во непрочитанных сообщений

		// Последняя переписка
		if(!empty($msgAll[0]['partner_id'])) {
			$partnerId = (int) $msgAll[0]['partner_id'];
			$ModelProfile = new Models_User_Profile($partnerId);
			$partnerProfile = $ModelProfile->getProfile($partnerId);
			$this->view->partnerProfile = $partnerProfile;
			$this->view->partnerId = $partnerId;

			// Профиль партнера
			$PartnerProfile = new Models_Users($partnerId);

			// Вывод всей переписки с партнёром
			$talk = $ModelMsg->getTalk($MyProfile, $PartnerProfile);
			$this->view->assign('vTalk', $talk);

			// isRecordNewMsg ключ, позволяющий писать новые сообщения
			// Должна быть действующая КК
			// или Кол-во сообщений в переписке должно быть больше или равно 3
			// или последнее сообщение должно быть от партнера (не моё) - чтобы блокировать возможность писать два сообщения подряд
			// и оно должно быть прочитано мной = доступ к нему должен быть разрешен!
			$this->view->assign('isRecordNewMsg', $ModelMsg->isRecordNewMsg($MyProfile,  $PartnerProfile, $talk));
		}
	}

	/**
	 * Вся переписка с партнёром
	 */
	public function talkAction()
	{
		// отключаем только layout, view используется!
		$this->_helper->layout()->disableLayout();

		$myId = Models_User_Model::getMyId();
		$partnerId = $this->_getParam('partner_id', 0);

		if($myId > 0 && $partnerId > 0)
		{
			// Получаем профили
			$MyProfile      = new Models_Users($myId);
			$PartnerProfile = new Models_Users($partnerId);

			// Вывод всей переписки с партнёром
			$ModelMsg = new Models_User_Msg();
			$talk = $ModelMsg->getTalk($MyProfile, $PartnerProfile);
			$this->view->assign('vTalk', $talk);

			$this->view->assign('myProfile', $MyProfile->getProfileToArray());
			$this->view->assign('partnerId', $PartnerProfile->getId());
			$this->view->assign('partnerProfile', $PartnerProfile->getProfileToArray());

			// Ключ права писать новые сообщения
			$this->view->assign('isRecordNewMsg', $ModelMsg->isRecordNewMsg($MyProfile,  $PartnerProfile, $talk));
		}
	}

	/**
	 * Удаление сообщения пользователем в Общении
	 */
	public function deleteMsgAction() {
		$this->ajaxInit();

		$msgId = (int) $this->_getParam('msg_id');
		$myID = Models_User_Model::getMyId();

		if($msgId > 0 && $myID > 0) {
			$ModelMsg = new Models_User_Msg($myID);
			$ModelMsg->hideMsg($msgId);

			$json['msgId'] = $msgId;
			$json['msg'] = $this->view->t('Сообщение удалено.');
		} else {
			$json['error'] = $this->view->t('Ошибка удаления сообщения.');
		}

		$this->getJson($json);
	}

	/**
	 * Удаление всех сообщений в переписке с пользователем в Общении
	 */
	public function deleteTalkAction()
	{
		$this->ajaxInit();

		$partnerId = (int) $this->_getParam('partner_id');
		$myID = Models_User_Model::getMyId();
		if($partnerId > 0 && $myID > 0) {
			$ModelMsg = new Models_User_Msg($myID);
			$ModelMsg->hideAllMsg($partnerId);
			$json['msg'] = $this->view->t('Переписка удалена.');
		} else {
			$json['error'] = $this->view->t('Ошибка удаления сообщения.');
		}

		$this->getJson($json);
	}

	/**
	 * Отправка и запись сообщения
	 */
	public function sendAction()
	{
		$this->ajaxInit();

		$myID = Models_User_Model::getMyId();
		$partnerId = (int) $this->_getParam('partner_id');
		$msgText = Sas_Filter_Text::get($this->_getParam('msg_text'));

		if($myID > 0 && $partnerId > 0 && !empty($msgText))
		{
			// Мой профиль
			$myId = Models_User_Model::getMyId();
			$ModelProfileMy = new Models_Users($myId);
			$myProfile = $ModelProfileMy->getProfileToArray();

			// Профиль партнера
			$ModelProfilePartner = new Models_Users($partnerId);
			$partnerProfile = $ModelProfilePartner->getProfileToArray();

			/*$ModelProfileMy = new Models_User_Profile($myId);
			$myProfile = $ModelProfileMy->getProfile($myId);

			// Профиль партнера
			$ModelProfilePartner = new Models_User_Profile($partnerId);
			$partnerProfile = $ModelProfilePartner->getProfile($partnerId);*/

			// Модель общения
			$ModelMsg = new Models_User_Msg($myId);

			// Общее кол-во сообщений в переписке
			$cntMsg = $ModelMsg->getCntMsg($myId, $partnerId);

			// Определяем права доступа к прочтению этого сообщения партнером
			$accessRead = ($this->getAccessRead($myProfile, $partnerProfile, $cntMsg)) ? 'yes' : 'no';

			// Сохраняем это сообщение
			# Кстате, при написании нового сообщения, мы НЕ записываем ЭТО как дату последнего действия пользователя
			# Дата последнего действия пользователя меняется ТОЛЬКО при чтении закрытого сообщения
			$msgId = $ModelMsg->saveNewMsg($ModelProfileMy, $ModelProfilePartner, $msgText, $accessRead);

			$json['msg'] = $this->view->t('Сообщение отправлено.');

			$json['data']['msg']['id']          = $msgId;
			$json['data']['msg']['text']        = $ModelMsg->getTextMsg();
			$json['data']['msg']['dt']          = date_format(new DateTime(CURRENT_DATETIME), 'c');
			$json['data']['msg']['access_read'] = $accessRead;
			//$json['data']['msg']['box']         = 'out'; // данный параметр отпределяется НИЖЕ, после отправки в сокет

			if($ModelMsg->getTranslateIs()) {
				$json['data']['msg']['translate_text'] = $ModelMsg->getTranslateNewText();
				$json['data']['msg']['translate_lang'] = $ModelMsg->getTranslateLang();
			}

			$json['data']['profile']['id']        = $myProfile['id'];
			$json['data']['profile']['uid']       = $myProfile['uid'];
			$json['data']['profile']['url']       = ($this->getLang() == 'ru') ? '/user/profile' : $this->getLang() . '/user/profile';
			$json['data']['profile']['user_name'] = $myProfile['first_name'];
			$json['data']['profile']['avatar']    = $myProfile['avatar'];

			// Проверяем может ли пользователь писать новые сообщения после отправки этого
			$cntMsg = $ModelMsg->getCntMsg($ModelProfileMy->getId(), $ModelProfilePartner->getId());

			// Так как сообщений в переписке меньше 3-х, устраиваем жесткую проверку!
			if($cntMsg < 3) {
				$talk = $ModelMsg->getTalk($ModelProfileMy, $ModelProfilePartner);
				$json['data']['profile']['isRecordNewMsg'] = $ModelMsg->isRecordNewMsg($ModelProfileMy,  $ModelProfilePartner, $talk);
			} else {
				$json['data']['profile']['isRecordNewMsg'] = true;
			}

			$json['data']['partner']['id']        = $partnerProfile['id'];
			$json['data']['partner']['uid']       = $partnerProfile['uid'];
			$json['data']['partner']['avatar']    = $partnerProfile['avatar'];
			$json['data']['partner']['user_name'] = $partnerProfile['first_name'];

			// Отправляем сообщение в сокет партнеру если он онлайн
			if($partnerProfile['online'] == 'yes') {
				$json['data']['msg']['box'] = 'in'; // а для партнера (если он online) это входящее сообщение!
				Models_Socket::send('sendNewMsg', $json);
			} else {
				// Если пользователь оффлайн и он в настоящее время ЧК - отправляем ему уведомление
				if($partnerProfile['current_status'] >= 70) {
					try {
						$ModelSendMsg = new Models_TemplatesMessage($partnerProfile, 'new_message_notification', 'msg_communication_email');
						$ModelSendMsg->addDataReplace('my_name', $myProfile['first_name']);
						$ModelSendMsg->send();
					} catch (Sas_Exception $e) {
						// Игнорируем возможную ошибку отправки письма
						// TODO: записать в лог
					}
				}
			}
			$json['data']['msg']['box'] = 'out';
		} else {
			$json['error'] = $this->view->t('Ошибка отправки сообщения.');
		}

		$this->getJson($json);
	}

	/**
	 * Возвращает ключ доступа к сообщению для получателя.
	 *
	 * @param array $myProfile Профиль отправителя.
	 * @param array $partnerProfile Профиль получателя.
	 * @param int $cntMsg Общее кол-во сообщений в переписке.
	 * @return bool
	 */
	private function getAccessRead($myProfile, $partnerProfile, $cntMsg)
	{
		// Было принятое свидание
		#$ModelDates = new Models_User_Dates();
		#$datesStatus = $ModelDates->getLastStatus($myProfile['id'], $partnerProfile['id']); // yes|no

		//Был ли обмен контактами
		$ModelContact = new Models_User_ContactExchange();
		$contactStatus = $ModelContact->getLastStatus($myProfile['id'], $partnerProfile['id']); // yes|no

		return (
			($myProfile['club_card_dt'] >= CURRENT_DATE || $partnerProfile['club_card_dt'] >= CURRENT_DATE) // у меня или у партнера есть действущая КК
			|| ($cntMsg >= 2) // Кол-во сообщений >= 2 (так как это 3-е сообщение, значит это полноценная переписка)
		#	|| ($datesStatus == 'yes') // Было свидание
			|| ($contactStatus == 'yes') // Был обмен контактами
		) ? true : false;
	}
}
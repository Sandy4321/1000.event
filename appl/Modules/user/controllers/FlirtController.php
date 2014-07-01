<?php

class User_FlirtController extends Sas_Controller_Action_User
{
	public function indexAction()
	{
		// Редирект для призраков для заполнения анкеты
		if(Models_User_Model::getMyCurrentStatus() < 70) {$this->_redirect('user/profile/wizard');}

		$myId = Models_User_Model::getMyId();

		$ModelProfile = new Models_User_Profile($myId);
		$myProfile = $ModelProfile->getProfile($myId);
		$this->view->myProfile = $myProfile;

		$ModelFlirt = new Models_Games_Flirt();
		$ModelFlirt->initGames($ModelProfile);

		$gamesData = $ModelFlirt->getGamesData(20);
		$this->view->vData = $gamesData;

		$this->view->vCntYes      = $ModelFlirt->getResultCnt($myId, 'yes'); // Кол-во сколько я нажал Флирт
		$this->view->vCntNo       = $ModelFlirt->getResultCnt($myId, 'no'); // Кол-во сколько я нажал Далее
		$this->view->vCntMatch    = $ModelFlirt->getSympathyCnt($myId); // Кол-во совпадений
		$this->view->vCntILiked   = $ModelFlirt->getILikedCnt($myId); // Кол-во людей которым я понравился
		$this->view->vSympathyAll = $ModelFlirt->getSympathyAll($myProfile); // Профили совпавших людей

		Models_Actions::add(66, $myId); // Открыта игра Флирт
	}

	public function dataListAction()
	{
		$this->_helper->layout()->disableLayout();

		$myId = Models_User_Model::getMyId();

		$ModelProfile = new Models_User_Profile($myId);
		$myProfile = $ModelProfile->getProfile($myId);
		$this->view->myProfile = $myProfile;

		$ModelFlirt = new Models_Games_Flirt();
		$ModelFlirt->initGames($ModelProfile);

		$gamesData = $ModelFlirt->getGamesData(20);
		$this->view->vData = $gamesData;
	}

	public function choiceAction()
	{
		$this->ajaxInit();

		$myId = Models_User_Model::getMyId();

		if($myId > 0 && is_numeric($this->_getParam('p_id')) && $this->_getParam('p_id') > 0
			&&
			($this->_getParam('choice') == 'yes' || $this->_getParam('choice') == 'no')
		)
		{
			$choice = ($this->_getParam('choice') == 'yes') ? 'yes' : 'no';
			$partnerId = $this->_getParam('p_id');

			$ModelFlirt = new Models_Games_Flirt();

			$ModelFlirt->saveChoice($partnerId, $choice); // Сохраняем выбор игрока

			$json['yes'] = $ModelFlirt->getResultCnt($myId, 'yes'); // Кол-во ДА
			$json['no'] = $ModelFlirt->getResultCnt($myId, 'no'); // Кол-во НЕТ

			// Проверяем наличие совпадения
			if($choice == 'yes') { // Игроком выбрано ДА
				$partnerProfile = $ModelFlirt->isMatch($myId, $partnerId);
				if($partnerProfile != 0) // Есть совпадение
				{
					$ModelFlirt->setSympathy($myId, $partnerId); // Фиксируем общую симпатию

					$json['match'] = 'yes';
					$json['first_name'] = $partnerProfile['first_name'];

					$ModelProfile = new Models_User_Profile($myId);
					$myProfile = $ModelProfile->getProfile($myId);
					if($myProfile['club_card_dt'] >= CURRENT_DATE) {
						$json['club_card'] = true;
						$json['phone'] = $ModelFlirt->phoneFormat($partnerProfile['phone']);
						$json['uid'] = $partnerProfile['uid'];
					} else {
						$json['club_card'] = false;
						$json['phone'] = substr($ModelFlirt->phoneFormat($partnerProfile['phone']), 0, -5) . 'XX-XX';
					}

					// Отправляем письмо партнеру
					$partnerProfile['msg_flirt_email'] = 'yes'; // Всегда отправляем на почту
					$url = ($partnerProfile['lang'] == 'ru') ? '' : '/' . $partnerProfile['lang'];

					if($partnerProfile['club_card_dt'] >= CURRENT_DATE) {
						$partnerProfile['msg_flirt_sms'] = 'yes'; // При наличии КК так же отправляем СМС

						$ModelSendMsg = new Models_TemplatesMessage($partnerProfile, 'flirt_sympathy_card_yes', 'msg_flirt');
						$ModelSendMsg->addDataReplace('my_name', $myProfile['first_name']);
						$ModelSendMsg->addDataReplace('phone', $ModelFlirt->phoneFormat($myProfile['phone']));
						$ModelSendMsg->addDataReplace('ur_profile', 'http://onthelist.ru'.$url.'/user/people/profile/view/' . $myProfile['uid']);
					} else {
						$ModelSendMsg = new Models_TemplatesMessage($partnerProfile, 'flirt_sympathy_card_no');
						$ModelSendMsg->addDataReplace('my_name', $myProfile['first_name']);
						$ModelSendMsg->addDataReplace('phone', substr($ModelFlirt->phoneFormat($myProfile['phone']), 0, -5) . 'XX-XX');

						$ModelSendMsg->addDataReplace('ur_balance', 'http://onthelist.ru'.$url.'/user/profile/balance');
					}

					try {
						$ModelSendMsg->send();
					} catch (Sas_Exception $e) {
						// TODO: записать в лог
					}

					// Отправляем сообщение в модуль Общение
					if($partnerProfile['club_card_dt'] >= CURRENT_DATE && $myProfile['club_card_dt'] >= CURRENT_DATE) {
						// Модель сообщений
						$ModelMsg = new Models_User_Msg($myId);
						$text = $myProfile['first_name'] . ', ' . $partnerProfile['first_name'] .',<br>';
						$text .= $this->t('Поздравляем, у Вас совпадения симпатий в игре "Флирт"').'<br>';
						$text .= $this->t('Телефоны').': '. $partnerProfile['first_name'] .' <strong>'. $ModelFlirt->phoneFormat($partnerProfile['phone']) .'</strong>, '. $myProfile['first_name'] .' <strong>'. $ModelFlirt->phoneFormat($myProfile['phone']) .'</strong><br>';
						$text .= $this->t('Желаем Вам удачного свидания!');
						$ModelMsg->saveNewMsg($partnerProfile['id'], $myId, $text, 'yes', 'game_flirt');
					}
				}
			}

			$json['sympathy'] = $ModelFlirt->getSympathyCnt($myId);

		} else {
			$json['error'] = 'error';
		}

		$this->getJson($json);
	}

	public function popupMatchAction()
	{
		$this->ajaxInit();

		$this->view->vTitle = $this->t('Совпадения в игре');

		$json = array();

		$myId = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myId);
		$myProfile = $ModelProfile->getProfile($myId);

		$ModelFlirt = new Models_Games_Flirt();
		$data = $ModelFlirt->getSympathyAll($myProfile);
		if(!empty($data)) {
			$urlLang = ($this->getLang() == 'ru') ? '' : '/'.$this->getLang();
			$i = 0;
			foreach ($data as $row) {
				$json[$i]['uid'] = $row['uid'];
				//$json[$i]['url'] = $urlLang. '/user/people/profile/view/'.$row['uid'];
				$json[$i]['url'] = $row['url_profile'];
				$json[$i]['title'] = $row['first_name'];
				$json[$i]['avatar'] = (empty($row['avatar'])) ? $row['img'].'thumbnail.jpg' : $row['avatar'];
				$i++;
			}

			$this->view->vTitleCnt = count($json);
		}

		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}
}
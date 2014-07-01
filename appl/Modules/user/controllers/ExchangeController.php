<?php

class User_ExchangeController extends Sas_Controller_Action_User
{
	/**
	 * Отправка запроса на обмен телефонами
	 */
	public function sendAction()
	{
		$this->ajaxInit();
		$json = array();
		$myId = Models_User_Model::getMyId();

		if($this->_getParam('partner_id') > 0 && $myId > 0) {
			try {
				$MyProfile = new Models_Users($myId);
				$PartnerProfile = new Models_Users($this->_getParam('partner_id'));

				// Обмен телефонами доступен пользователям с подтверждеными телефоны и только ЧК
				if($MyProfile->getCurrentStatus() >= 70
					&& $MyProfile->isPhoneCheck()
					&& $PartnerProfile->getCurrentStatus() >= 70
					//&& $PartnerProfile->isPhoneCheck()
					)
				{
					$ModelExchange = new Models_User_ExchangePhone($MyProfile, $PartnerProfile);

					// Проверяем был ли ранее обмен
					if(!$ModelExchange->isExchange()) {
						// Обмена еще небыло
						if($ModelExchange->sendExchange()) {
							$json['msg'] = $this->t('Запрос на обмен телефонными номерами отправлен');
						} else {
							$json['error'] = $this->t('Во время отправки запроса на обмен телефонами произошла ошибка. Пожалуйста обратитесь к администратору.');
						}
					} else {
						// Обмен уже был
						$json['error'] = $this->t('Вы уже отправляли запрос на обмен телефонными номерами.');
					}
				} else {
					$urlProfile = ($this->getLang() == 'ru') ? '/user/profile/settings' : '/'.$this->getLang() .'/user/profile/settings';
					$json['error'] = sprintf($this->t('Чтобы меняться телефонами на сайте, Вы должны подтвердить Ваш номер телефона в <a href="%s">настройках</a> своего профиля.'), $urlProfile);
				}
			} catch (Sas_Models_Exception $e) {
				$json['error'] = $e->getMessage();
				$this->getJson($json);
			}

		} else {
			$json['error'] = $this->t('Профиль пользователя не найден.');
		}

		$this->getJson($json);
	}

	/**
	 * Принять предложение по обмену телефонными номерами
	 */
	public function yesAction()
	{
		$this->ajaxInit();
		$json = array();
		$myId = Models_User_Model::getMyId();
		$msgId = $this->_getParam('msg_id', 0);

		if($msgId > 0 && $this->_getParam('partner_id') > 0 && $myId > 0) {
			try {
				$MyProfile = new Models_Users($myId);
				$PartnerProfile = new Models_Users($this->_getParam('partner_id'));

				// Проверка - подтвержден ли у меня телефон, чтобы я мог принять обмен
				if(!$MyProfile->isPhoneCheck()) {
					$urlProfile = ($this->getLang() == 'ru') ? '/user/profile/settings' : '/'.$this->getLang() .'/user/profile/settings';
					$msgError = sprintf($this->t('Чтобы обменяться номерами телефонов, Вы должны подтвердить Ваш номер телефона в <a href="%s">настройках</a> своего профиля.'), $urlProfile);
					throw new Sas_Models_Exception($msgError, 0);
				}

				// Модель обмена телефонными номерами
				$ModelExchange = new Models_User_ExchangePhone($MyProfile, $PartnerProfile);

				// Если статус уже НЕ = yes (принято) - отказываем
				if($ModelExchange->getStatus() == 'new') {
					if($ModelExchange->exchangeYes($msgId)) {
						$json['user']['first_name'] = $PartnerProfile->getFirstName();
						$json['user']['phone'] = $ModelExchange->getPhone();
						if($MyProfile->getClubCard() >= CURRENT_DATE) {
							$info_text = $this->t('Обмен телефонными номерами выполнен.');
						} else {
							$info_text = $this->t('Обмен телефонными номерами выполнен. Полный номер телефона Вы сможете увидеть в профиле пользователя только при наличии <a href="javascript:void(0);" onclick="goBuyCard(\'Просмотр номера телефона доступен только владельцам Клубных карт.\')">Клубной карты</a>.');
						}
						$json['msg'] = $info_text;
					} else {
						$json['error'] = $this->t('При обмене телефонами произошла ошибка. Пожалуйста обратитесь к администратору.');
					}
				} else {
					$json['error'] = $this->t('Вы уже отказались обменяться телефонными номерами ранее.');
				}

			} catch (Sas_Models_Exception $e) {
				$json['error'] = $e->getMessage();
				$this->getJson($json);
			}
		} else {
			$json['error'] = $this->t('Профиль пользователя не найден.');
		}

		$this->getJson($json);
	}

	/**
	 * Отклонить предложение по обмену телефонными номерами
	 */
	public function noAction()
	{
		$this->ajaxInit();
		$json = array();
		$myId = Models_User_Model::getMyId();
		$msgId = $this->_getParam('msg_id', 0);

		if($msgId > 0 && $this->_getParam('partner_id') > 0 && $myId > 0) {
			try {
				$MyProfile = new Models_Users($myId);
				$PartnerProfile = new Models_Users($this->_getParam('partner_id'));

				// Модель обмена телефонными номерами
				$ModelExchange = new Models_User_ExchangePhone($MyProfile, $PartnerProfile);

				// Если статус уже НЕ = yes (принято) - отказываем
				if($ModelExchange->getStatus() != 'yes') {
					if($ModelExchange->exchangeNo($msgId)) {
						$json['msg'] = $this->t('Вы отказались обменяться телефонными номерами.');
					} else {
						$json['error'] = $this->t('При отказе от обмена телефонными номерами произошла ошибка.');
					}
				} else {
					$json['error'] = $this->t('Вы уже согласились обменяться телефонными номерами ранее.');
				}

			} catch (Sas_Models_Exception $e) {
				$json['error'] = $e->getMessage();
				$this->getJson($json);
			}
		} else {
			$json['error'] = $this->t('Профиль пользователя не найден.');
		}

		$this->getJson($json);
	}

}



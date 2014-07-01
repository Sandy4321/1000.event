<?php

/**
 * API для внешнего сайта
 */
class Api_SiteController extends Sas_Controller_Action
{
	/**
	 * Возвращает страны в формате json (id name)
	 * {id:140, country_name:Монако}
	 */
	public function searchCountryAction()
	{
		$ModelCountry = new Models_CountriesCities();
		$json = $ModelCountry->searchCountry($this->_getParam('query'));
		$this->getJson($json);
	}

	/**
	 * Возвращает города и страны в формате json (id name) пример: Москва - Россия
	 * {city_id:2, city_name:Москва, country_id:"176" ,country_name:Россия}
	 */
	public function searchCityCountryAction()
	{
		$ModelCountry = new Models_CountriesCities();
		$json = $ModelCountry->searchCityCountry($this->_getParam('query'));
		$this->getJson($json);
	}

	/**
	 * Возвращает города и страны в формате json (id name) пример: Москва - Россия
	 * {city_id:2, city_name:Москва}
	 */
	public function searchCityAction()
	{
		$ModelCountry = new Models_CountriesCities();
		$json = $ModelCountry->searchCity($this->_getParam('query'));
		$this->getJson($json);
	}

	/**
	 * По ID города возвращает информацию по нему
	 * {city_id: "2", country_id: "176", city_name: "Москва", time_zone: "+4"}
	 */
	public function getCityAction()
	{
		$ModelCountry = new Models_CountriesCities();
		$json = $ModelCountry->getCity($this->_getParam('id'));
		$this->getJson($json);
	}

	/**
	 * Регистрация на сайте
	 */
	public function registerAction()
	{
		$json = array();
		$request = $this->getRequest();
		try {

			if(!$request->getParam('agree')) {
				$json['error']['field'] = 'agree';
				throw new Sas_Models_Exception($this->t('Для вступления в Клуб, Вы обязаны принять Правила пользования.'));
			}

			$ModelRegister = new Models_Register();

			// Email
			try {
				$ModelRegister->setEmail($request->getParam('email'));
			} catch(Sas_Models_Exception $e) {
				$json['error']['field'] = 'email';
				throw new Sas_Exception($e->getMessage(), $e->getCode());
			}

			// Пароль
			try {
				$ModelRegister->setPassword($request->getParam('psw'));
				if($ModelRegister->getPassword() != $request->getParam('psw_repeat')) {
					throw new Sas_Models_Exception($this->t('Введённые пароли не совпадают'));
				}
			} catch(Sas_Models_Exception $e) {
				$json['error']['field'] = 'psw';
				throw new Sas_Exception($e->getMessage(), $e->getCode());
			}

			// Sex
			try {
				$ModelRegister->setSex($request->getParam('sex'));
			} catch(Sas_Models_Exception $e) {
				$json['error']['field'] = 'sex';
				throw new Sas_Exception($e->getMessage(), $e->getCode());
			}

			// Промокод
			try {
				$ModelRegister->setPromocode($request->getParam('promocode'));
			} catch(Sas_Models_Exception $e) {
				$json['error']['field'] = 'promocode';
				throw new Sas_Exception($e->getMessage(), $e->getCode());
			}

			// Промо-ключ (ключ друга или рекламной компании)
			try {
				$ModelRegister->setPromoKeyFriend($request->getParam('promo-key'));
			} catch(Sas_Models_Exception $e) {
				$json['error']['field'] = 'promo-key';
				throw new Sas_Exception($e->getMessage(), $e->getCode());
			}

			$ModelRegister->save();

			$url = ($ModelRegister->getLang() == 'ru') ? '' : '/' . $ModelRegister->getLang();
			$json['redirect'] = $url .'/user/register/welcome';
			$json['msg']['text1'] = $this->t('Поздравляем! Вы успешно зарегистрированы.');
			$json['msg']['text2'] = sprintf($this->t('На Ваш email %s отправлено письмо, содержащее ссылку для активации аккаунта.'), $ModelRegister->getEmail());

		} catch(Sas_Exception $e) {
			$json['error']['msg']  = $e->getMessage();
			$json['error']['code'] = $e->getCode();
		}

		$this->getJson($json);
	}

	/**
	 * Восстановление пароля к сайту.
	 *
	 * Ожидает на вход: email для отправки на него инструкций и ключа для восстановления/смены пароля.
	 */
	public function recoveryAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			if(!$request->getParam('email')) throw new Sas_Exception($this->t('Чтобы восстановить пароль введите Ваш email адрес.'), 1);

			$email = $request->getParam('email');
			$ModelRecovery = new Models_User_Recovery();

			// Пробуем получить соответствующий профиль
			$profile = $ModelRecovery->isEmail($email);
			// Проверяем наличие профиля
			if(is_array($profile)) {
				// Активный аккаунт - будем пробовать восстановить пароль
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
						$json['msg'] = $this->t('На адрес электронной почты: %s в течении этого часа уже было отправлено письмо с инструкцией для восстановления пароля. Пожалуйста, проверьте Вашу почту на наличие письма от Клуба OnTheList.');
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
							$json['msg'] = sprintf($this->t('Письмо с инструкцией для восстановления пароля было отправлено по адресу %s. Высланная Вам ссылка для восстановления пароля действительна в течение одного часа.'), $email);
						} catch (Sas_Exception $e) {
							// Письмо НЕ отправлено!
							$this->view->assign('vData', $this->view->render('recovery/error-send-email.phtml'));
							throw new Sas_Exception(sprintf($this->t('К сожалению, произошла ошибка при попытке отправить электронное письмо на Ваш адрес: %s. Приносим Вам свои извинения за доставленные неудобства.'), $email));
						}
					}
				} elseif ($profile['current_status'] == 10) {
					// Аккаунт есть, но он еще не активен.
					try {
						$ModelRegister = new Models_Register();
						$ModelRegister->sendRegisterEmail($profile);
					} catch (Sas_Exception $e) {
						throw new Sas_Exception(sprintf($this->t('Адрес эл. почты для Вашего аккаунта %s не был подтвержден. Мы попробовали снова отправить Вам письмо с ссылкой для завершения регистрации, но, к сожалению, вновь произошла ошибка при отправке письма. Пожалуйста, обратитесь напрямую к нашему администратору.'), $email));
					}

					throw new Sas_Exception(sprintf($this->t('Указанный Вами адрес эл. почты уже зарегистрирован в системе, но регистрация не была завершена. Сейчас мы отправили Вам новое письмо, содержащее ссылку для завершения регистрации.'), $email));
				} else {
					// Статус пользователя не позволяет восстановить ему пароль
					throw new Sas_Exception(sprintf($this->t('Ваш аккаунт заблокирован или удален. Если Вы удалили свой аккаунт по ошибке, обратитесь к <a href="mailto:%s">администрации Клуба</a> с просьбой о восстановлении.'), 'info@onthelist.ru'));
				}
			} else { // Нет такого профиля
				throw new Sas_Exception(sprintf($this->t('Указанный Вами email: %s не найден.'), $email), 1);
			}


		} catch(Sas_Exception $e) {
			$json['error']['msg']  = $e->getMessage();
			$json['error']['code'] = $e->getCode();
		}

		$this->getJson($json);
	}

	public function sendMailSupportAction()
	{
		$json = array();

		$email     = $this->_getParam('email');
		$user_name = $this->_getParam('user_name');
		$msg_text  = $this->_getParam('msg_text');

		$user_name = Sas_Filter_Text::get($user_name);
		$msg_text  = Sas_Filter_Text::get($msg_text);

		try {
			if(!$email) throw new Sas_Exception($this->t('Email адрес является обязательным.'), 1);

			$validator = new Zend_Validate_EmailAddress();
			if(!$validator->isValid($email)) throw new Sas_Exception($this->t('Email адрес указан не корректно.'), 1);

			if(!$user_name) throw new Sas_Exception($this->t('Имя отправителя является обязательным.'), 1);

			if(!$msg_text) throw new Sas_Exception($this->t('Текст письма не может быть пустым.'), 1);

			try {
				$mail = new Zend_Mail('UTF-8');
				$mail->setSubject('Письмо с сайта, со страницы Контакты');
				$mail->setBodyText($msg_text);
				$mail->setFrom($email, $user_name); // от кого
				$mail->addTo('info@onthelist.ru');
				$mail->send();
			} catch (Zend_Mail_Exception $e) {
				throw new Sas_Exception($e->getMessage());
			}

			$json['msg'] = $this->t('Ваше письмо отправлено.');

		} catch(Sas_Exception $e) {
			$json['error']['msg']  = $e->getMessage();
			$json['error']['code'] = $e->getCode();
		}

		$this->getJson($json);
	}

	#############################
	public function preDispatch()
	{
		$this->ajaxInit();
	}
}

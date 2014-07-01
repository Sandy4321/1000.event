<?php

/**
 * API пользователей для закрытой части сайта
 */
class Api_UserController extends Sas_Controller_Action_User
{
	/**
	 *
	 */
	public function saveAction()
	{
		//sleep(3);
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			$ModelUser = new Models_Users($myId);

			// Имя
			if($request->getParam('first_name')) {
				try {
					$ModelUser->setFirstName($request->getParam('first_name'))->save();
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getFirstName();
				} catch(Sas_Models_Exception $e) {
					$json['field'] = 'first_name';
					throw new Sas_Models_Exception($e->getMessage(), $e->getCode());
				}
			}

			// Имя
			if($request->getParam('last_name')) {
				try {
					$ModelUser->setLastName($request->getParam('last_name'))->save();
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getLastName();
				} catch(Sas_Models_Exception $e) {
					$json['field'] = 'last_name';
					throw new Sas_Models_Exception($e->getMessage(), $e->getCode());
				}
			}

			// День рождения
			if($request->getParam('birthday_year') && $request->getParam('birthday_month') && $request->getParam('birthday_day')) {
				try {
					$y = $request->getParam('birthday_year');
					$m = $request->getParam('birthday_month');
					$d = $request->getParam('birthday_day');
					$birthday = sprintf('%04d-%02d-%02d', $y, $m, $d);

					$ModelUser->setBirthday($birthday)->save();
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getBirthday();
				} catch(Sas_Models_Exception $e) {
					$json['field'] = 'birthday';
					throw new Sas_Models_Exception($e->getMessage(), $e->getCode());
				}
			}

			# Соц. сети
			// Facebook
			if($request->getParam('link_fb')) {
				try {
					$ModelUser->setLinkFb($request->getParam('link_fb'))->save();
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getLinkFb();
				} catch(Sas_Models_Exception $e) {
					$json['field'] = 'link_fb';
					throw new Sas_Models_Exception($e->getMessage(), $e->getCode());
				}
			}
			// вКонтакте
			if($request->getParam('link_vk')) {
				try {
					$ModelUser->setLinkVk($request->getParam('link_vk'))->save();
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getLinkVk();
				} catch(Sas_Models_Exception $e) {
					$json['field'] = 'link_vk';
					throw new Sas_Models_Exception($e->getMessage(), $e->getCode());
				}
			}
			// LinkedIn
			if($request->getParam('link_ln')) {
				try {
					$ModelUser->setLinkLn($request->getParam('link_ln'))->save();
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getLinkLn();
				} catch(Sas_Models_Exception $e) {
					$json['field'] = 'link_ln';
					throw new Sas_Models_Exception($e->getMessage(), $e->getCode());
				}
			}

			# END Соц. сети

			// Город
			if(is_numeric($request->getParam('city_id'))) {
				if($ModelUser->setCityId($request->getParam('city_id'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении города');
				}
			}

			// Уведомления
			if($request->getParam('msg_admin_email') == 'yes' || $request->getParam('msg_admin_email') == 'no') {
				if($ModelUser->setNotice('msg_admin_email', $request->getParam('msg_admin_email'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}
			if($request->getParam('msg_admin_sms') == 'yes' || $request->getParam('msg_admin_sms') == 'no') {
				if($ModelUser->setNotice('msg_admin_sms', $request->getParam('msg_admin_sms'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}

			if($request->getParam('msg_communication_email') == 'yes' || $request->getParam('msg_communication_email') == 'no') {
				if($ModelUser->setNotice('msg_communication_email', $request->getParam('msg_communication_email'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}
			if($request->getParam('msg_communication_sms') == 'yes' || $request->getParam('msg_communication_sms') == 'no') {
				if($ModelUser->setNotice('msg_communication_sms', $request->getParam('msg_communication_sms'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}

			if($request->getParam('msg_invite_email') == 'yes' || $request->getParam('msg_invite_email') == 'no') {
				if($ModelUser->setNotice('msg_invite_email', $request->getParam('msg_invite_email'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}
			if($request->getParam('msg_invite_sms') == 'yes' || $request->getParam('msg_invite_sms') == 'no') {
				if($ModelUser->setNotice('msg_invite_sms', $request->getParam('msg_invite_sms'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}

			if($request->getParam('msg_favorite_email') == 'yes' || $request->getParam('msg_favorite_email') == 'no') {
				if($ModelUser->setNotice('msg_favorite_email', $request->getParam('msg_favorite_email'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}
			if($request->getParam('msg_favorite_sms') == 'yes' || $request->getParam('msg_favorite_sms') == 'no') {
				if($ModelUser->setNotice('msg_favorite_sms', $request->getParam('msg_favorite_sms'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}

			if($request->getParam('msg_news_email') == 'yes' || $request->getParam('msg_news_email') == 'no') {
				if($ModelUser->setNotice('msg_news_email', $request->getParam('msg_news_email'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}
			if($request->getParam('msg_news_sms') == 'yes' || $request->getParam('msg_news_sms') == 'no') {
				if($ModelUser->setNotice('msg_news_sms', $request->getParam('msg_news_sms'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении настроек уведомлений');
				}
			}
			// END Уведомления

			// Мне интересны романтические знакомства.
			if($request->getParam('romantic')) {
				if($ModelUser->setRomantic($request->getParam('romantic'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getRomantic();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Дети
			if($request->getParam('children')) {
				if($ModelUser->setChildren($request->getParam('children'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getChildren();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Курение
			if($request->getParam('smoking')) {
				if($ModelUser->setSmoking($request->getParam('smoking'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getSmoking();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Рост
			if($request->getParam('height')) {
				if($ModelUser->setHeight($request->getParam('height'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getHeight();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Общая информация о себе
			if($request->getParam('about')) {
				if($ModelUser->setAbout($request->getParam('about'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getAbout();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Компания
			if($request->getParam('company')) {
				if($ModelUser->setCompany($request->getParam('company'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getCompany();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Должность
			if($request->getParam('position_job')) {
				if($ModelUser->setPositionJob($request->getParam('position_job'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getPositionJob();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Образование
			if($request->getParam('education')) {
				if($ModelUser->setEducation($request->getParam('education'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getEducation();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Любимые места
			if($request->getParam('favorite_places')) {
				if($ModelUser->setFavoritePlaces($request->getParam('favorite_places'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getFavoritePlaces();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			# Языки на которых говорит пользователь
			// Русский
			if($request->getParam('lang_ru')) {
				if($ModelUser->setLangRu($request->getParam('lang_ru'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении русского языка');
				}
			}
			// Английский
			if($request->getParam('lang_en')) {
				if($ModelUser->setLangEn($request->getParam('lang_en'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении английского языка');
				}
			}
			// Немецкий
			if($request->getParam('lang_de')) {
				if($ModelUser->setLangDe($request->getParam('lang_de'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении немецкого языка');
				}
			}
			// Французский
			if($request->getParam('lang_fr') == 'yes' || $request->getParam('lang_fr') == 'no') {
				if($ModelUser->setLangFr($request->getParam('lang_fr'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении французского языка');
				}
			}
			// Итальянский
			if($request->getParam('lang_it') == 'yes' || $request->getParam('lang_it') == 'no') {
				if($ModelUser->setLangIt($request->getParam('lang_it'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении итальянского языка');
				}
			}
			// Испанский
			if($request->getParam('lang_es')) {
				if($ModelUser->setLangEs($request->getParam('lang_es'))->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении испанского языка');
				}
			}
			# END Языки на которых говорит пользователь

			// Автоматический перевод текстов
			if($request->getParam('automatic_translation')) {
				if($ModelUser->setAutomaticTranslation($request->getParam('automatic_translation'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getAutomaticTranslation();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Язык интерфейса
			if($request->getParam('user_lang')) {
				if($ModelUser->setLang($request->getParam('user_lang'))->save()) {
					$json['msg'] = $this->t('Сохранено');
					$json['save-data'] = $ModelUser->getLang();
				} else {
					$json['error'] = $this->t('Ошибка при сохранении');
				}
			}

			// Телефонный номер
			if(is_numeric($request->getParam('phone_number'))) {
				if($ModelUser->setPhone($request->getParam('phone_number'))->setPhoneCheck('no')->save()) {
					$json['msg'] = $this->t('Сохранено');
				} else {
					$json['error'] = $this->t('Ошибка при сохранении телефонного номера.');
				}
			}

			// Код подтверждения
			if(is_numeric($request->getParam('phone_verify_code'))) {
				if($ModelUser->isPhoneVerifyCode($request->getParam('phone_verify_code'))->save()) {
					$json['msg'] = $this->t('Номер телефона подтвержден.');
				} else {
					$json['error'] = $this->t('Код подтверждения номера телефона не верный.');
				}
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Смена пароля пользователя
	 */
	public function changePasswordAction() {
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			if(strlen($request->getParam('psw_new')) < 6 || strlen($request->getParam('psw_new')) > 32) {
				throw new Sas_Models_Exception($this->t('Новый пароль не может быть менее 6 и более 32 символов.'));
			}
			if($request->getParam('psw_new') != $request->getParam('psw_new_confirm')) {
				throw new Sas_Models_Exception($this->t('Пароль не совпадает с подтверждением.'));
			}

			$ModelUser = new Models_Users($myId);

			if(!$ModelUser->isPasswordCorrect($request->getParam('psw_current'))) {
				throw new Sas_Models_Exception($this->t('Текущий пароль введён не верно.'));
			}

			if($ModelUser->setPasswordNew($request->getParam('psw_new'))->save()) {
				$json['msg'] = $this->t('Пароль изменен');
			} else {
				$json['error'] = $this->t('Ошибка при сохранении нового пароля');
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Добавление хобби пользователю
	 *
	 * @param array|int hobby
	 *
	 * Возвращает json.msg|json.error
	 */
	public function addHobbyAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			if($myId <= 0) {
				throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			}
			if(!$request->getParam('hobby')) {
				throw new Sas_Models_Exception($this->t('Отсутствует параметр интересов (hobby) для добавления.'));
			}
			if(!is_array($request->getParam('hobby')) && !is_numeric($request->getParam('hobby'))) {
				throw new Sas_Models_Exception($this->t('Список передаваемых интересов должен быть числом или массивом.'));
			}

			$ModelUser = new Models_Users($myId);
			$ModelHobby = new Models_User_Hobby();

			// Если интересы это массив
			if(is_array($request->getParam('hobby'))) {
				$hobbyArray = $request->getParam('hobby');
				$ModelHobby->addList($hobbyArray, $ModelUser->getId());
				$json['msg'] = $this->t('Интересы добавлены');
			} else {
				$hobbyId = $request->getParam('hobby');
				$ModelHobby->add($hobbyId, $ModelUser->getId());
				$json['msg'] = $this->t('Интерес добавлен');
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Удаление хобби пользователю
	 *
	 * Принимает: int hobby
	 *
	 * Возвращает: json.msg|json.error
	 */
	public function deleteHobbyAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			if($myId <= 0) {
				throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			}
			if(!$request->getParam('hobby')) {
				throw new Sas_Models_Exception($this->t('Отсутствует параметр интересов (hobby) для удаления.'));
			}
			if(!is_array($request->getParam('hobby')) && !is_numeric($request->getParam('hobby'))) {
				throw new Sas_Models_Exception($this->t('Список передаваемых интересов должен быть числом или массивом.'));
			}

			$ModelUser = new Models_Users($myId);
			$ModelHobby = new Models_User_Hobby();

			if(is_array($request->getParam('hobby'))) {
				$hobbyId = $request->getParam('hobby');
				$ModelHobby->delete($hobbyId[0], $ModelUser->getId()); // небольшой хак массива
				$json['msg'] = $this->t('Удаление выполнено.');
			} else {
				$hobbyId = $request->getParam('hobby');
				$ModelHobby->delete($hobbyId, $ModelUser->getId());
				$json['msg'] = $this->t('Удаление выполнено.');
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Добавление жизненных целей пользователю
	 *
	 * @param array|int target
	 *
	 * Возвращает json.msg|json.error
	 */
	public function addTargetAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			if($myId <= 0) {
				throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			}
			if(!$request->getParam('target')) {
				throw new Sas_Models_Exception($this->t('Отсутствует параметр цели (target) для добавления.'));
			}
			if(!is_array($request->getParam('target')) && !is_numeric($request->getParam('target'))) {
				throw new Sas_Models_Exception($this->t('Список передаваемых целей должен быть числом или массивом.'));
			}

			$ModelUser = new Models_Users($myId);
			$ModelTarget = new Models_User_Target();

			// Если цели это массив
			if(is_array($request->getParam('target'))) {
				$targetArray = $request->getParam('target');
				$ModelTarget->addList($targetArray, $ModelUser->getId());
				$json['msg'] = $this->t('Цели добавлены');
			} else {
				$targetId = $request->getParam('target');
				$ModelTarget->add($targetId, $ModelUser->getId());
				$json['msg'] = $this->t('Цель добавлена');
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Удаление жизненной цели пользователя
	 *
	 * Принимает: int target
	 *
	 * Возвращает json.msg|json.error
	 */
	public function deleteTargetAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			if($myId <= 0) {
				throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			}
			if(!$request->getParam('target')) {
				throw new Sas_Models_Exception($this->t('Отсутствует параметр цели (target) для удаления.'));
			}
			if(!is_array($request->getParam('target')) && !is_numeric($request->getParam('target'))) {
				throw new Sas_Models_Exception($this->t('Список передаваемых целей должен быть числом или массивом.'));
			}

			$ModelUser = new Models_Users($myId);
			$ModelTarget = new Models_User_Target();

			if(is_array($request->getParam('target'))) {
				$targetId = $request->getParam('target');
				$ModelTarget->delete($targetId[0], $ModelUser->getId());
			} else {
				$targetId = $request->getParam('target');
				$ModelTarget->delete($targetId, $ModelUser->getId());
			}

			$json['msg'] = $this->t('Удаление выполнено.');


		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Добавление профессиональных интересов пользователю
	 *
	 * @param array|int prof_interest
	 *
	 * Возвращает json.msg|json.error
	 */
	public function addProfInterestAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			if($myId <= 0) {
				throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			}
			if(!$request->getParam('prof_interest')) {
				throw new Sas_Models_Exception($this->t('Отсутствует параметр проф-интереса (prof_interest) для добавления.'));
			}
			if(!is_array($request->getParam('prof_interest')) && !is_numeric($request->getParam('prof_interest'))) {
				throw new Sas_Models_Exception($this->t('Список передаваемых проф-интересов должен быть числом или массивом.'));
			}

			$ModelUser = new Models_Users($myId);
			$ModelProfInteres = new Models_User_ProfInteres();

			// Если профессиональные интересы это массив
			if(is_array($request->getParam('prof_interest'))) {
				$profArray = $request->getParam('prof_interest');
				$ModelProfInteres->addList($profArray, $ModelUser->getId());
				$json['msg'] = $this->t('Профессиональные интересы добавлены');
			} else {
				$profId = $request->getParam('prof_interest');
				$ModelProfInteres->add($profId, $ModelUser->getId());
				$json['msg'] = $this->t('Профессиональный интерес добавлен');
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Удаление профессионального интереса пользователю
	 *
	 * Принимает: int prof_interest
	 *
	 * Возвращает json.msg|json.error
	 */
	public function deleteProfInterestAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			if($myId <= 0) {
				throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			}
			if(!$request->getParam('prof_interest')) {
				throw new Sas_Models_Exception($this->t('Отсутствует параметр профессионального интереса (prof_interest) для удаления.'));
			}
			if(!is_array($request->getParam('prof_interest')) && !is_numeric($request->getParam('prof_interest'))) {
				throw new Sas_Models_Exception($this->t('Список передаваемых проф-интересов должен быть числом или массивом.'));
			}

			$ModelUser = new Models_Users($myId);
			$ModelProfInteres = new Models_User_ProfInteres();

			if(is_array($request->getParam('prof_interest'))) {
				$profId = $request->getParam('prof_interest');
				$ModelProfInteres->delete($profId[0], $ModelUser->getId());
			} else {
				$profId = $request->getParam('prof_interest');
				$ModelProfInteres->delete($profId, $ModelUser->getId());
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Добавление текущего статуса пользователю
	 *
	 * Принимает: string status_text
	 *
	 * Возвращает json.msg|json.error
	 */
	public function addStatusAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		$statusText = Sas_Filter_Text::get($request->getParam('status_text'));
		#$statusVip = ($request->getParam('vip') == 'yes') ? 'yes' : 'no';// В старой модели был vip статус, сейчас он отлючен!

		try {
			if($myId <= 0) {
				throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			}
			if(!$request->getParam('status_text')) {
				throw new Sas_Models_Exception($this->t('Отсутствует параметр текста статуса (status_text) для добавления.'));
			}
			if(empty($statusText)) {
				throw new Sas_Models_Exception($this->t('Текст статуса не может быть пустым.'));
			}

			$ModelStatus = new Models_User_Status($myId);
			//$save = $ModelStatus->saveNewStatus($statusText, $statusVip);
			$save = $ModelStatus->saveNewStatus($statusText, 'no'); // В старой модели был vip статус, сейчас он отлючен!
			if($save == 'ok') {
				$json['msg'] = $statusText;
			}
			// В старой модели был vip статус, сейчас он отлючен!
			#if($save == 'no-karat' && $statusVip == 'yes') {
			#	$json['error'] = $this->t('Не достаточно карат для добавления VIP статуса');
			#}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Возврат кода подтверждения телефонного номера
	 */
	public function generatePhoneVerifyCodeAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();

		try {
			if($myId <= 0) {
				throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			}

			$ModelUser = new Models_Users($myId);

			// Генерим код подтверждения
			if($ModelUser->createPhoneVerifyCode()) {
				// Отправляем его по смс
				if($ModelUser->sendPhoneVerifyCodeSms()) {
					$json['msg'] = $this->t('Код подтверждения сгенерирован и отправлен на Ваш телефонный номер по СМС.');
				}
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Отправка адекты администратору
	 * ВНИМАНИЕ! В настоящее время данный функционал не используется
	 */
	public function sendProfileAdminAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();

		try {
			if($myId <= 0) {
				throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			}

			$ModelUser = new Models_Users($myId);
			$is_avatar = (file_exists($_SERVER['DOCUMENT_ROOT'].$ModelUser->getAvatar())) ? true : false;
			$is_resume = (file_exists($_SERVER['DOCUMENT_ROOT'].$ModelUser->getImgPath().'fileNameResume.txt')) ? true : false;

			// Провеярем заполненность данных
			if(
				!is_null($ModelUser->getFirstName())
				&& !is_null($ModelUser->getLastName())
				&& !is_null($ModelUser->getBirthday())
				&& !is_null($ModelUser->getCityId())
				&& $is_avatar == true
				&& ($is_resume == true || (!is_null($ModelUser->getLinkFb()) || !is_null($ModelUser->getLinkVk()) || !is_null($ModelUser->getLinkLn())))
				&& (!is_null($ModelUser->getEducation()) || (!is_null($ModelUser->getCompany()) && !is_null($ModelUser->getPositionJob())))
			) {
				// Отправляем анкету админ на проверку
				$ModelUser->sendProfileAdmin();
				$json['msg']['text'] = $this->t('Ваша заявка на вступление в Клуб отправлена Администратору.');
			} else {
				throw new Sas_Models_Exception('Заполните все необходимые поля.', 0);
			}

		} catch (Sas_Models_Exception $e) {
			$json['error']['msg'] = $e->getMessage();
			$json['error']['code'] = $e->getCode();
		}

		$this->getJson($json);
	}

	/**
	 * Кроп аватарки пользователя.
	 *
	 * @param x
	 * @param y
	 * @param w
	 * @param h
	 */
	public function avatarCropAction()
	{
		$myId = Models_User_Model::getMyId();
		$json = array();
		if(is_numeric($this->_getParam('x')) && is_numeric($this->_getParam('y')) && is_numeric($this->_getParam('w')) && is_numeric($this->_getParam('h')))
		{
			$ModelPhoto = new Models_User_Photo($myId);

			$targ_w = 500;
			$targ_h = 500;
			$jpeg_quality = 90;

			$pathAvatar = $ModelPhoto->getPatchAvatar();
			$src = $_SERVER['DOCUMENT_ROOT'] . $pathAvatar .'original.jpg';

			$img_r = imagecreatefromjpeg($src);
			$dst_r = ImageCreateTrueColor($targ_w, $targ_h);

			imagecopyresampled($dst_r, $img_r, 0, 0,
				$this->_getParam('x'), $this->_getParam('y'),
				$targ_w, $targ_h,
				$this->_getParam('w'), $this->_getParam('h'));

			imagejpeg($dst_r, $_SERVER['DOCUMENT_ROOT'] . $pathAvatar .'thumbnail.jpg', $jpeg_quality);
			$json['msg'] = $this->t('Аватарка профиля изменена.');
			$json['img-avatar'] = $pathAvatar.'thumbnail.jpg?'.time();

		} else {
			$json['error'] = $this->t('При редактировании аватарки произошла ошибка.');
		}

		$this->getJson($json);
	}

	/**
	 * Поворот аватарки пользователя
	 *
	 * Ожидает параметр rotate со значением 90
	 */
	public function avatarRotateAction()
	{
		$myId = Models_User_Model::getMyId();
		$json = array();

		if($this->_getParam('rotate') == 90)
		{
			$ModelPhoto = new Models_User_Photo($myId);
			$pathAvatar = $ModelPhoto->getPatchAvatar();
			$pathImg  = $_SERVER['DOCUMENT_ROOT'].$pathAvatar;

			$photoOptimal   = $pathImg.'optimal.jpg';
			$photoOriginal  = $pathImg.'original.jpg';
			$photoThumbnail = $pathImg.'thumbnail.jpg';

			// Поворачиваем фото превью
			$source = imagecreatefromjpeg($photoThumbnail);
			$rotate = imagerotate($source, -90, 0);
			imagejpeg($rotate, $photoThumbnail, 90);

			// Поворачиваем оптимальную фото
			$source = imagecreatefromjpeg($photoOptimal);
			$rotate = imagerotate($source, -90, 0);
			imagejpeg($rotate, $photoOptimal, 90);

			// Поворачиваем Оригинал фото
			$source = imagecreatefromjpeg($photoOriginal);
			$rotate = imagerotate($source, -90, 0);
			imagejpeg($rotate, $photoOriginal, 90);

			$json['msg'] = 'ok';
			$json['img-avatar']   = $pathAvatar.'thumbnail.jpg?'.time();
			$json['img-optimal']  = $pathAvatar.'optimal.jpg?'.time();
			$json['img-original'] = $pathAvatar.'original.jpg?'.time();
		} else {
			$json['error'] = $this->t('Ошибка при повороте аватарки.');
		}

		$this->getJson($json);
	}

	/**
	 * Базовое сохранение НОВОЙ фотографии аватара.
	 *
	 * Ожидаем: $_FILES['userNewPhoto']
	 */
	public function avatarUploadAction()
	{
		$myId = Models_User_Model::getMyId();
		$json = array();

		$ModelProfile = new Models_Users($myId);
		$ModelPhoto = new Models_User_Photo($ModelProfile);

		$validFormats = array('image/jpeg');

		if(isset($_POST) && $_SERVER['REQUEST_METHOD'] == 'POST'
			&& $_FILES['userNewPhoto']['error'] == 0
			&& $_FILES['userNewPhoto']['size'] < $ModelPhoto->getLimitSize()
			&& in_array($_FILES['userNewPhoto']['type'], $validFormats))
		{

			$pathImg  = $ModelPhoto->getPatchAvatar();

			$img = new Sas_Image();

			$img->configSaveOriginal('original', 'jpg');
			$img->configSaveOptimal(800, 600, 'optimal', 'jpg');
			$img->configSaveCrop(500, 'thumbnail', 'jpg');

			$img->setImgDir($pathImg, true);

			$saveImg = $img->save($_FILES['userNewPhoto']['tmp_name']);
			if($saveImg != false)
			{
				$json['msg'] = $this->t('Аватарка профиля загружена.');
				$json['img-original'] = $pathImg.'original.jpg?'.time();
				$json['img-optimal'] = $pathImg.'optimal.jpg?'.time();
				$json['img-avatar'] = $pathImg.'thumbnail.jpg?'.time();

				Models_Actions::add(33, $myId); // Загружен новый аватар
			} else {
				#$data['error'] = 'Error save file. ' . $img->getError();
				$json['error'] = $this->t('При загрузке фотографии произошла ошибка.');
			}
		} else {
			$json['error'] = $this->t('Фотография не соответствует формату или превышен максимальный размер снимка (10 Мб).');
		}

		$this->getJson($json);
	}

	/**
	 * Загрузка резюме
	 */
	public function resumeUploadAction()
	{
		$myId = Models_User_Model::getMyId();
		$json = array();
		$limitMb = 1024 * 1024 * 1024 * 10; // 10Mb

		try {
			if($myId <= 0) throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			if(!$_FILES['file_resume']) throw new Sas_Models_Exception($this->t('Файл с резюме отсутствует.'));
			if($_FILES['file_resume']['error'] != 0) throw new Sas_Models_Exception($this->t('Файл с резюме содержит ошибки.'));
			if($_FILES['file_resume']['size'] > $limitMb) throw new Sas_Models_Exception($this->t('Файл с резюме превышает допустимый размер для загрузки.'));

			$ModelProfile = new Models_Users($myId);

			$path = $_SERVER['DOCUMENT_ROOT'].$ModelProfile->getImgPath();

			if(!file_exists($path)) {
				@mkdir($path, 0777, true);
			}

			$userResumeNewName = 'resume.xxx';

			if(@move_uploaded_file($_FILES['file_resume']['tmp_name'], $path . $userResumeNewName)) {
				file_put_contents($path . 'fileNameResume.txt', $_FILES['file_resume']['name']);
				$json['msg'] = 'ok';
			} else {
				throw new Sas_Models_Exception($this->t('Ошибка сохранения резюме'), 1);
			}

		} catch (Sas_Models_Exception $e) {
			$json['error']['msg'] = $e->getMessage();
			$json['error']['code'] = $e->getCode();
		}


		$limitMb = 1024 * 1024 * 1024 * 10; // 10Mb
		if($_FILES['file_resume']['error'] == 0 && $_FILES['file_resume']['size'] < $limitMb)
		{
			$pathHost = PATH_DIR_HOST . DIRECTORY_SEPARATOR . 'public';
			$pathFile = DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'people'.DIRECTORY_SEPARATOR.
				Models_User_Model::getMySex().DIRECTORY_SEPARATOR.Models_User_Model::getMyYear(Models_User_Model::getMyBirthday()).
				DIRECTORY_SEPARATOR .Models_User_Model::getMyId().DIRECTORY_SEPARATOR;

			if(!file_exists($pathHost . $pathFile)) {
				@mkdir($pathHost . $pathFile, 0777, true);
			}

			$userResumeNewName = 'resume.xxx';
			if(@move_uploaded_file($_FILES['file_resume']['tmp_name'], $pathHost . $pathFile . $userResumeNewName)) {
				file_put_contents($pathHost . $pathFile . 'fileNameResume.txt', $_FILES['file_resume']['name']);
			}
		}

		$this->getJson($json);
	}

	/**
	 * Отзыв о пользователе
	 */
	public function reviewAction()
	{
		$json    = array();
		$myId    = Models_User_Model::getMyId();
		$request = $this->getRequest();

		$partnerId = $request->getParam('partner_id');
		$rating = $request->getParam('rating');
		$reportText = Sas_Filter_Text::get($request->getParam('report_text', null));

		try {
			if ($myId <= 0) throw new Sas_Models_Exception($this->t('Пользователя не существует.'));

			if ($partnerId <= 0) throw new Sas_Models_Exception($this->t('Партнер не определен.'), 1);
			if (!$rating) throw new Sas_Models_Exception($this->t('Выберите категорию отзыва.'), 1);

			$ModelReport = new Models_User_Report($myId);
			if($ModelReport->save($partnerId, $rating, $reportText)) {
				$json['msg'] = $this->t('Ваш отзыв отправлен администрации и будет рассмотрен в ближайшее время. Спасибо за то, что помогаете работе Клуба!');
			} else {
				throw new Sas_Models_Exception($this->t('Ошибка отправки отзыва.'), 1);
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	/**
	 * Пригласить на мероприятие
	 */
	public function eventInviteAction()
	{
		$json    = array();
		$myId    = Models_User_Model::getMyId();
		$request = $this->getRequest();

		$partnerId = $request->getParam('partner_id');
		$eventId = $request->getParam('event_id');

		try {
			if ($myId <= 0)      throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			if ($partnerId <= 0) throw new Sas_Models_Exception($this->t('Партнер не определен.'), 1);
			if ($eventId <= 0)   throw new Sas_Models_Exception($this->t('Мероприятие не определено.'), 1);

			// Мой профиль
			$MyProfile = new Models_Users($myId);
			$myProfile = $MyProfile->getProfileToArray();

			// Профиль партнера
			$PartnerProfile = new Models_Users($partnerId);
			$partnerProfile = $PartnerProfile->getProfileToArray();

			// Получаем само мероприятие
			$ModelEvent = new Models_User_Event($myId);
			$ModelEvent->setEventId($eventId);
			$event = $ModelEvent->getEvent($eventId);

			// Отправляем партнеру сообщение
			$ModelMsg = new Models_User_Msg($myId);
			$msgId = $ModelMsg->eventInvite($MyProfile, $PartnerProfile, $ModelEvent);

			$json['data']['msg']['id']          = $msgId;
			$json['data']['msg']['text']        = $ModelMsg->getTextMsg();
			$json['data']['msg']['dt']          = date_format(new DateTime(CURRENT_DATETIME), 'c');
			$json['data']['msg']['access_read'] = 'yes';

			if($ModelMsg->getTranslateIs()) {
				$json['data']['msg']['translate_text'] = $ModelMsg->getTranslateNewText();
				$json['data']['msg']['translate_lang'] = $ModelMsg->getTranslateLang();
			}

			$json['data']['profile']['id']        = $myProfile['id'];
			$json['data']['profile']['uid']       = $myProfile['uid'];
			$json['data']['profile']['url']       = ($this->getLang() == 'ru') ? '/user/profile' : $this->getLang() . '/user/profile';
			$json['data']['profile']['user_name'] = $myProfile['first_name'];
			$json['data']['profile']['avatar']    = $myProfile['avatar'];
			$json['data']['profile']['isRecordNewMsg'] = true;

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
						$ModelSendMsg = new Models_TemplatesMessage($partnerProfile, 'invite_event', 'msg_communication_email');
						$ModelSendMsg->addDataReplace('my_name', $myProfile['first_name']);
						$ModelSendMsg->addDataReplace('event_name', $event['title']);
						$ModelSendMsg->send();
					} catch (Sas_Exception $e) {
						// Игнорируем возможную ошибку отправки письма
						// TODO: записать в лог
					}
				}
			}
			$json['data']['msg']['box'] = 'out';
			$json['data']['ok'] = $this->t('Приглашение на мероприятие отправлено.');

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	public function eventBuyTicketAction()
	{
		$json    = array();
		$myId    = Models_User_Model::getMyId();
		$request = $this->getRequest();
		$eventId = $request->getParam('event_id');

		try {
			if ($myId <= 0)      throw new Sas_Models_Exception($this->t('Пользователя не существует.'));
			if ($eventId <= 0)   throw new Sas_Models_Exception($this->t('Мероприятие не определено.'), 1);

			// Мой профиль
			$MyProfile = new Models_Users($myId);
			$myProfile = $MyProfile->getProfileToArray();

			// Получаем само мероприятие
			$ModelEvent = new Models_User_Event($myId);
			$ModelEvent->setEventId($eventId);
			$event = $ModelEvent->getEvent($eventId);

			if($event['otl'] == 'yes' && $event['price'] > 0 && $event['money_type'] == 'karat') {
				$ModelEvent->buyTicket($MyProfile);
				$json['data']['ok'] = $this->t('Куплен билен #' . 1234213);
			} else {
				$json['error'] = $this->t('На данное мероприятие билеты не продаются.');
			}

		} catch (Sas_Models_Exception $e) {
			$json['error'] = $e->getMessage();
		}

		$this->getJson($json);
	}

	#############################
	public function preDispatch()
	{
		$this->ajaxInit();
	}
}
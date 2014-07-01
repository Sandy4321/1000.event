<?php

class Models_User_Profile
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	private $myId = null;

	//private $tableProfile = 'users_data';
	private $tableProfile = 'users';
	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');
	private $columnProfileAvatar = array('avatar' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');

	private $tableLanguages = 'user_languages';

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : (int) $myId;

		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	/**
	 * Создаёт и записывает код подтверждения в профиль пользователя
	 * @param $phone
	 * @return bool|int
	 */
	public function createPhoneVerifyCode($phone)
	{
		// генерим код
		$code = rand(100000, 999999);
		$code = sprintf('%06d', $code);

		$update['phone'] = $phone;
		$update['phone_verify_code'] = $code;
		$update['phone_check'] = 'no';

		$where = $this->db->quoteInto('id = ?', $this->myId);

		if ($this->db->update($this->tableProfile, $update, $where) == 1) {
			return $code;
		}

		return false;
	}

	/**
	 * Подтверждение номера телефона
	 *
	 * @param $code
	 * @return bool
	 */
	public function checkConfirmPhoneCode($code)
	{
		$select = $this->db->select()
			->from($this->tableProfile, 'phone_verify_code')
			->where('id = ?', $this->myId)
			->limit(1);
		$checkRes = $this->db->fetchOne($select);
		if ($checkRes == $code) {
			// Код верен, удаляем код и отмечаем что номер подтвержден
			$update['phone_verify_code'] = null;
			$update['phone_check'] = 'yes';

			// Автоматом подписываем на новости по смс
			$update['msg_news_sms'] = 'yes';

			$where = $this->db->quoteInto('id = ?', $this->myId);

			$this->db->update($this->tableProfile, $update, $where);

			Models_Actions::add(29, $this->myId); // Подтвержден номер телефона

			return true;
		}

		return false;
	}

	/**
	 * Проверка пароля на соответствие введённому
	 * @param $psw
	 * @return string
	 */
	public function checkPassword($psw)
	{
		$select = $this->db->select()
			->from($this->tableProfile, 'id')
			->where('id = ?', $this->myId)
			->where('psw = ?', md5($psw))
			->limit(1);
		$row = $this->db->fetchOne($select);

		return $row;
	}

	/**
	 * Удаляет (реально просто скрывает профиль пользователя)
	 */
	public function deleteProfile()
	{
		// Получаем мыло
		$profile = $this->getProfile($this->myId);
		$update = array(
			'current_status' => 20,
			'email' => $profile['email'].'-'.md5(time())
		);
		$this->db->update($this->tableProfile, $update, $this->db->quoteInto('id = ?', $this->myId));
		Models_Actions::add(27, $this->myId, null, $this->myId); // Профиль удален пользователем
	}

	/**
	 * Возвращает полную информацию о пользователе.
	 *
	 * @param null $userId Если не передать ID, вернёт инфо по "мне" (мой профиль).
	 * @return mixed
	 */
	public function getProfile($userId = null)
	{
		if (is_null($userId)) {
			$userId = $this->myId;
		#	Models_Actions::add(20, $this->myId); // Открыт свой профиль
		}

		$select = $this->db->select()
			->from(array('profile'=>$this->tableProfile), '*')
			->columns(array('userId'=>'profile.id'))
			->columns($this->columnProfileImg)
			->columns($this->columnProfileAvatar)
			->limit(1);

		if(is_numeric($userId)) {
			$select->where('profile.id = ?', $userId);
		} else {
			$select->where('uid = ?', $userId);
		}

		$select->joinLeft('cities', 'cities.id=profile.city_id', array('city_name'=>'name_'.$this->lang));

		#Sas_Debug::sql($select);
		$row = $this->db->fetchRow($select);

		$this->club_card = $row['club_card_dt'];

		return $row;
	}

	/**
	 * Возвращает город пользователя
	 * @param $profile Поный профиль пользователя
	 * @return array
	 */
	public function getCity($profile) {
		$select = $this->db->select()
			->from('cities', array('id', 'name'=>'name_'.$this->lang))
			->where('id = ?', $profile['city_id'])
			->limit(1);

		return $this->db->fetchRow($select);
	}

	/**
	 * Возвращает языки на которых говорит пользователь
	 *
	 * @param null $userId
	 * @return mixed
	 */
	public function getLanguagesUser($userId = null) {

		if (is_null($userId)) $userId = $this->myId;

		$select = new Zend_Db_Select($this->db);
		$select->from('user_languages', array('userId'=>'user_id', 'langId'=>'language_id'));
		$select->joinLeft('languages', 'languages.id = user_languages.language_id', array('langName'=>$this->lang));
		$select->where('user_languages.user_id = ?', $userId);
		#Sas_Debug::dump($select->__toString());
		$row = $this->db->fetchAll($select);
		#Sas_Debug::dump($row);
		return $row;
	}

	public function saveLanguages($data)
	{
		$myId = Models_User_Model::getMyId();
		// Удаляем предыдущие данные
		$where = $this->db->quoteInto('user_id = ?', $myId);
		$this->db->delete($this->tableLanguages, $where);

		// Вставляем новые данные
		foreach($data as $k => $langId)
		{
			$insertData = array(
				'user_id'     => $myId,
				'language_id' => $langId
			);

			$this->db->insert($this->tableLanguages, $insertData);
		}
	}

	/**
	 * Возвращает настройки поиска по умолчанию
	 *
	 * @param null $userId
	 * @return mixed
	 */
	public function getSearchSettings($userId = null) {
		if (is_null($userId)) $userId = $userId = $this->myId;

		$select = new Zend_Db_Select($this->db);
		$select->from('user_search_criterias')
			->where('user_id = ?', (int) $userId)
			->limit(1);
		$row = $this->db->fetchRow($select);

		return $row;
	}

	/**
	 * Возвращает потенциально возможное кол-во людей которые могут найти запрашиваемый профиль
	 * @param $profile
	 * @return string
	 */
	public function getPotentialMaxSearch($profile)
	{
		$select = $this->db->select()
			->from($this->tableProfile, 'COUNT(id)')
			->where('sex != ?', $profile['sex']);

		// Рост
		$select->where('height <= ' . $profile['filter_height_max'] . ' AND height >= ' . $profile['filter_height_min']);

		// Дети
		if(!is_null($profile['filter_children'])) {
			$select->where('children = ?', $profile['filter_children']);
		}

		// Курение
		if(!is_null($profile['filter_smoking'])) {
			$select->where('smoking = ?', $profile['filter_smoking']);
		}

		// Возраст
		$yearOfBirth1 = $profile['filter_age_min'];
		$yearOfBirth2 = $profile['filter_age_max'];
		$date1 = date((date('Y') - $yearOfBirth2 - 1) . '-m-d');
		$date2 = date((date('Y') - $yearOfBirth1) . '-m-d');
		$select->where('`birthday` BETWEEN "' . $date1 .'" AND "' . $date2 .'"');

		//Sas_Debug::dump($select->__toString());
		$row = $this->db->fetchOne($select);
		return $row;
	}

	/**
	 * Сохранение нового пароля пользователя
	 *
	 * @param $newPsw
	 */
	public function saveNewPassword($newPsw)
	{
		// TODO: ВНИМАНИЕ!!! ВАЖНО!!! Оптимизировать обработку паролей!!! Возможен пробой системы!
		$data = array(
			'psw' => new Zend_Db_Expr('MD5("'.$newPsw.'")')
		);

		#$this->db->update('users_data', $data, 'id = ' . $userId = $this->myId);
		$this->db->update('users', $data, 'id = ' . $userId = $this->myId);
		Models_Actions::add(32, $this->myId, null, $this->myId); // Пароль изменён
	}

	/**
	 * Сохранение пользовательского профиля
	 * @param $data
	 * @return int
	 */
	public function saveProfile($data)
	{
		return $this->db->update($this->tableProfile, $data, 'id = ' . $userId = $this->myId);
	}

	/**
	 * Сохранение параметров пользовательского поиска
	 *
	 * @param $serializeData
	 */
	public function saveSearchSetting($serializeData)
	{
		$data = array('search_setting' => $serializeData);
		$this->db->update($this->tableProfile, $data, 'id = ' . $userId = $this->myId);
	}
	
	/**
	 * Сохраняет свободные дни для свиданий
	 * 
	 * @param array $dataArray Массив содержащий свободные дни для свиданий
	 */
	public function saveFreeDay($dataArray)
	{
		$data = array('free_day' => serialize($dataArray));
		$this->db->update($this->tableProfile, $data, 'id = ' . $userId = $this->myId);
	}

	/**
	 * Скрывает профиль пользователя
	 */
	public function hideProfile()
	{
		$update = array('hide_profile' => 'yes');
		$this->db->update($this->tableProfile, $update, $this->db->quoteInto('id = ?', $this->myId));
		Models_Actions::add(25, $this->myId, null, $this->myId); // Профиль скрыт пользователем
	}

	/**
	 * Проверяет скрыт ли профиль
	 * @return string yes|no
	 */
	public function isHideProfile()
	{
		$select = $this->db->select()
			->from($this->tableProfile, 'hide_profile')
			->where('id = ?', $this->myId)
			->limit(1);
		return $this->db->fetchOne($select);
	}

	/**
	 * Отменяет скрытие профиля пользователя
	 */
	public function visibleProfile()
	{
		$this->db->update($this->tableProfile, array('hide_profile' => 'no'), $this->db->quoteInto('id = ?', $this->myId));
		Models_Actions::add(26, $this->myId, null, $this->myId); // Отменено скрытие профиля
	}

	/**
	 * Задает новую дату последнего доступа к закытой информации
	 * @param $userId
	 */
	public function setLastAccessData($userId) {
		$this->db->update($this->tableProfile, array('last_access_dt'=>CURRENT_DATETIME), $this->db->quoteInto('id = ?', $userId));
	}

	/**
	 * Установить рекурентные платежи
	 * @param $userId
	 * @param $key
	 * @return int
	 */
	public function setRecurrent($userId, $key) {

		$userInfo = $this->getProfile();

		$data['recurrent_payment'] = $key;

		if(!is_null($userInfo['recurrent_dt'])) {
			$data['recurrent_dt'] = CURRENT_DATETIME;

			$ModelBalance = new Models_User_Balance();
			$ModelBalance->init($userId);
			if($key == 'yes') {
				$ModelBalance->addBonus(200, 'Bonus karat');
			} else {
				$ModelBalance->minusBalanceBonus(200, 'Bonus karat');
			}
		}

		if($key == 'yes') {
			Models_Actions::add(69, $userId);
		} else {
			Models_Actions::add(70, $userId);
		}

		return $this->db->update($this->tableProfile, $data, $this->db->quoteInto('id = ?', $userId));
	}

	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'profile', 'action'=>'index'),
			'name'  => $tr->translate('Профиль'),
			'check' => 'user/profile',
			'style' => ' active',
			'icon' => 'Profile',
			'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'profile', 'action' => 'settings'),
					'name'  => $tr->translate('Редактировать'),
					'check' => 'user/profile/settings',
					'style' => ' active',
					'icon'  => 'Settings',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'profile', 'action' => 'photo-album'),
					'name'  => $tr->translate('Альбом'),
					'check' => 'user/profile/photo-album',
					'style' => ' active',
					'icon'  => 'Album',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'profile', 'action'=>'balance'),
					'name'  => $tr->translate('Счет'),
					'check' => 'user/profile/balance',
					'style' => ' active',
					'icon' => 'Balance',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'profile', 'action'=>'friend'),
					'name'  => $tr->translate('Пригласить'),
					'check' => 'user/profile/friend',
					'style' => ' active',
					'icon'  => 'Invite'
				),
				/*array(
					'url'   => array('module' => 'user', 'controller' => 'profile', 'action' => 'gifts'),
					'name'  => $tr->translate('Подарки'),
					'check' => 'user/profile/gifts',
					'style' => ' class="active"',
				),*/
			)
		);

		return $menu;
	}
	//============ /MENU ===========
}
<?php

class Models_User_Model
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	#private $table = 'users_data';
	private $table = 'users';

	/**
	 * ID текущего пользователя
	 *
	 * @var null
	 */
	/*static private $myId = null;
	static private $myBirthday = null;
	static private $myHeight = null;
	static private $myChildren = null;
	static private $mySmoking = null;
	static private $mySex = null;
	static private $mySearchSetting = array();

	private $userId;
	private $userFirstName;
	private $userLastName;
	private $userSex;*/

	private $email;
	private $password;
	private $key;

	private $checkAuth = false;

	private $menu;

	public function __construct() {
		$this->db = Zend_Registry::get('db');
	}

	/**
	 * @return mixed
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @return null
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * $user = array(10) {
	["email"] => string(17) "sasha@klabukov.ru"
	["network"] => string(8) "facebook"
	["bdate"] => string(10) "dd.mm.yyyy"
	["first_name"] => string(9) "Alexander"
	["identity"] => string(45) "https://www.facebook.com/alexander.klabukov"
	["profile"] => string(45) "https://www.facebook.com/alexander.klabukov"
	["last_name"] => string(8) "Klabukov"
	["verified_email"] => string(1) "1"
	["sex"] => string(1) "male"
	["uid"] => string(15) "000000000000000"
	["birthday"] => string(10) "yyyy-mm-dd"
	}
	 */
	public function socLogin($user)
	{
		$select = $this->db->select()
			->from($this->table, '*')
			->where('`email` = ?', $user['email'])
			->where('current_status >= ?', 50)
			->limit(1);
		$result = $this->db->fetchRow($select);
		if ($result !== false) {
			$this->email = $result['email'];;
			$this->key   = $result['activation_key'];

			// Логируем вход
			$code = 0;
			if($user['network'] == 'facebook')   $code = 71;
			if($user['network'] == 'twitter')    $code = 72;
			if($user['network'] == 'googleplus') $code = 73;
			if($user['network'] == 'google')     $code = 74;
			if($user['network'] == 'vkontakte')  $code = 75;
			if($user['network'] == 'linkedin')   $code = 76;
			if($code != 0) Models_Actions::add($code, $result['id']);

			return true;
		}

		return false;
	}

	/**
	 * Авторизация пользователя
	 *
	 * @param Zend_Controller_Request_Abstract $request
	 * @return bool
	 */
	public function login(Zend_Controller_Request_Abstract $request) {
		$this->email    = trim($request->getParam('email', null));
		$this->password = trim($request->getParam('password', null));
		$this->key = trim($request->getParam('key', null));
		$rememberMe = $request->getParam('rememberMe');

		#Sas_Debug::dump($request->getParams());
		#exit;

		// Если это пользователь с первичной регистрации или логинится через соц сеть
		if($this->validateKey())
		{
			#Sas_Debug::dump('new user');
			if($this->validateEmail() && $this->validateKey()) {

				$select = $this->db->select();
				$select->from($this->table, '*');
				$select->where('`email` = ?', $this->email);
				$select->where('`activation_key` = ?', $this->key);
				$select->where('current_status >= ?', 50);
				$select->limit(1);
				$result = $this->db->fetchRow($select);
				if ($result !== false) {
					$this->setSessionData($result, $request);
					$this->checkAuth = true;

					// Запомнить меня ОБЯЗАТЕЛЬНО
					self::rememberMe();

					return true; // Пользователь найден
				}
			}
		}

		// Обычный вход используя логин и пароль
		else {
			#Sas_Debug::dump('standart user');
			if($this->validateEmail() && $this->validatePsw()) {

				$select = new Zend_Db_Select($this->db);
				$select->from($this->table, '*');
				$select->where('`email` = ?', $this->email);
				$select->where('`psw` = MD5(?)', $this->password);
				$select->where('current_status >= ?', 50);
				$select->limit(1);
				$result = $this->db->fetchRow($select);
				#Sas_Debug::dump($select->__toString());
				#Sas_Debug::dump($result, 'Sas2');
				#exit;
				if ($result !== false) {
					$this->setSessionData($result, $request);
					$this->checkAuth = true;

					// Запомнить меня
					if($rememberMe == 1) {
						self::rememberMe();
					}

					return true; // Пользователь найден
				}
			}
		}

		return false; // Пользователь НЕ найден
	}

	private function setSessionData($data, Zend_Controller_Request_Abstract $request)
	{
		$_SESSION['user']['auth'] = true;
		$_SESSION['user']['id']   = (int) $data['id'];
		$_SESSION['user']['uid']   = $data['uid'];
		$_SESSION['user']['sex']  = $data['sex'];
		$_SESSION['user']['firstName']   = $data['first_name'];
		$_SESSION['user']['lastName']    = $data['last_name'];
		$_SESSION['user']['birthday']    = $data['birthday'];
		$_SESSION['user']['height']      = (int) $data['height'];
		$_SESSION['user']['children']    = $data['children'];
		$_SESSION['user']['smoking']     = $data['smoking'];
		$_SESSION['user']['phone_check'] = $data['phone_check'];
		$_SESSION['user']['current_status'] = $data['current_status'];
		$_SESSION['user']['club_card_dt']   = $data['club_card_dt'];

		// Настройки поиска по умолчанию
		#if (is_null($data['search_setting'])) {
		#	$data['search_setting'] = $this->setDefaultSearchSetting($data);
		#}
		#$_SESSION['user']['search_setting'] = $data['search_setting'];

		// Отмечаем в БД что пипл залогинился
		// и тут же меняем ему язык если он отличается от заданного ранее
		$upData = array('online' => 'yes');
		if($data['lang'] != $request->getParam('lang') && ($request->getParam('lang') == 'ru' || $request->getParam('lang') == 'en')) {
			$upData['lang'] = $request->getParam('lang');
		}
		$this->db->update($this->table, $upData, 'id = ' . (int) $data['id']);
		Models_Actions::add(53, (int) $data['id']);
	}

	static public function rememberMe()
	{
		session_set_cookie_params(864000); // 86400 * 30 = 30 дней
		session_regenerate_id(true);
		#$cookieParams = session_get_cookie_params();
		#Sas_Debug::dump($cookieParams, 'cookieParams');
		#Sas_Debug::dump($_COOKIE[session_name()], 'session_name');
		#Sas_Debug::dump($_COOKIE, 'COOKIE');
		#exit;
	}

	/**
	 * Возвращает ID зарегистрированного пользователя от имени которого идёт работа.
	 *
	 * @return null|int
	 */
	static public function getMyId() {
		return $_SESSION['user']['id'];
	}
	static public function getMyUid() {
		return $_SESSION['user']['uid'];
	}
	static public function getMyFirstName() {
		return $_SESSION['user']['firstName'];
	}
	static public function getMyLastName() {
		return $_SESSION['user']['lastName'];
	}
	static public function getMyBirthday() {
		return $_SESSION['user']['birthday'];
	}
	static public function getMyHeight() {
		return $_SESSION['user']['height'];
	}
	static public function getMySmoking() {
		return $_SESSION['user']['smoking'];
	}
	static public function getMyChildren() {
		return $_SESSION['user']['children'];
	}
	static public function getMySex() {
		return $_SESSION['user']['sex'];
	}
	static public function getMyPhoneCheck() {
		return $_SESSION['user']['phone_check'];
	}
	static public function getMyCurrentStatus() {
		return $_SESSION['user']['current_status'];
	}
	static public function getMyClubCard() {
		return $_SESSION['user']['club_card_dt'];
	}

	static public function getAvatarUser() {
		$img = '/img/people/'.Models_User_Model::getMySex() . '/' . Models_User_Model::getMyYear(Models_User_Model::getMyBirthday()). '/' . Models_User_Model::getMyId() . '/thumbnail.jpg';
		return (file_exists($_SERVER['DOCUMENT_ROOT'].$img)) ? $img : '/img/people/thumbnail.jpg';
	}

	#static public function getMySearchSetting() {
	#	return $_SESSION['user']['search_setting'];
	#}
	#static public function setMySearchSetting($data) {
	#	if(is_array($data)) {
	#		$_SESSION['user']['search_setting'] = serialize($data);
	#	} else {
	#		$_SESSION['user']['search_setting'] = $data;
	#	}
	#}

	/**
	 * Задаёт настройки для поиска по умолчанию
	 *
	 * @param $data
	 * @return string
	 */
	/*private function setDefaultSearchSetting($data)
	{
		$myAge = $this->getAge($data['birthday']);

		$newSetting = array(
			"age_from" => $myAge - 3,
			"age_to"   => $myAge + 3,
			"height_from" => round($data['height'], -1) - 5,
			"height_to"   => round($data['height'], -1) + 5,
			"children" => null,
			"smoking"  => null,
			"languages" => array(
				"ru" => null,
				"en" => null,
				"fr" => null,
				"de" => null,
				"it" => null,
				"es" => null
			)
		);
		return serialize($newSetting);
	}*/

	#------ VALUDATE AUTH DATA ----
	private function validateEmail() {
		return $this->email;
	}

	private function validatePsw() {
		return $this->password;
	}

	private function validateKey() {
		return (strlen($this->key) == 32) ? $this->key : false;
	}

	/**
	 * Проверка аудентификации
	 *
	 * @return bool
	 */
	public function isAuth() {

		return $this->checkAuth;
	}

	public function quit()
	{
		if(!is_null(self::getMyId())) {
			// Отмечаем в БД что пипл разлогинился
			$this->db->update($this->table, array('online' => 'no'), 'id = ' . self::getMyId());
			Models_Actions::add(54, self::getMyId());
		}
	}

	// === HELPERS ===
	static public function getAge($date) {
		$ar = explode('-', $date);
		$y = $ar[0];
		$m = $ar[1];
		$d = $ar[2];
		if($m > date('m') || $m == date('m') && $d > date('d'))
			return (date('Y') - $y - 1);
		else
			return (date('Y') - $y);
	}

	/**
	 * Возвращает год
	 * @param $date
	 * @return int
	 */
	static public function getMyYear($date) {
		$ar = explode('-', $date);
		$y = $ar[0];

		return $y;
	}

	/**
	 * Проверка ключа активации нового пользоввтеля
	 *
	 * @param $key
	 * @return array Возвращаем профиль при верном ключе | FALSE ключ не найден
	 */
	public function isValidActivationKey($key) {
		$select = $this->db->select()
			->from('users', '*')
			->where('activation_key = ?', $key)
			->limit(1);

		$res = $this->db->fetchRow($select);

		return ($res) ? $res : false;
	}

	public function unsubscribe($key, $unEmail = 'yes', $unPhone = 'yes')
	{
		//$un = array();
		$un['msg_news_email'] = ($unEmail == 'yes') ? 'yes' : 'no';
		$un['msg_news_sms']   = ($unPhone == 'yes') ? 'yes' : 'no';
		/*if($unEmail != false) {
			$un['msg_news_email'] = 'no';
		}
		if($unPhone != false) {
			$un['msg_news_sms'] = 'no';
		}*/
		$this->db->update('users', $un, 'activation_key = "'.$key.'"');
	}

	//============ MENU ===========
	private function addMenu($menu) {
		$this->menu[] = $menu;
	}

	public function getMenuAll() {
		$this->addMenu(Models_User_Dashboard::getMenu());
		$this->addMenu(Models_User_Search::getMenu()); // Поиск
		$this->addMenu(Models_User_Dates::getMenu()); // Свидания
		$this->addMenu(Models_User_Event::getMenu()); // Мероприятия
		//$this->addMenu(Models_User_Posts::getMenu()); // Посты
		$this->addMenu(Models_User_Communication::getMenu()); // Общение
		#$this->addMenu(Models_User_Notifications::getMenu()); // Системные сообщения
		#$this->addMenu(Models_User_Invite::getMenu()); // Пригласить друзей
		$this->addMenu(Models_User_Profile::getMenu()); // Профиль
		#$this->addMenu(Models_Games_Fortune::getMenu()); // Игра Фортуна
		#$this->addMenu(Models_User_Balance::getMenu()); // Счет
		return $this->menu;
	}
	public function getMenuAllPhone() {
		$this->menu = array();
		$this->addMenu(Models_User_Dashboard::getMenu()); // Главная
		$this->addMenu(Models_User_Search::getMenuPhone()); // Поиск
		$this->addMenu(Models_User_Communication::getMenu()); // Общение
		$this->addMenu(Models_User_Event::getMenu()); // Мероприятия
		//$this->addMenu(Models_User_Dates::getMenu()); // Свидания
		$this->addMenu(Models_User_Profile::getMenu()); // Профиль
		return $this->menu;
	}
	//============ /MENU ===========
}
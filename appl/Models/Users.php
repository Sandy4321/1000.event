<?php

class Models_Users
{
	private $myId = null;

	private $id = null;
	private $uid = null;

	private $current_status = null;

	private $sex = null;

	private $email = null;
	private $password = null;

	private $lang = null;

	private $phone = null;
	private $phone_check = null;
	private $phone_verify_code = null;

	private $skype = null;

	private $promocode = null;
	private $promo_key = null;
	private $promo_key_friend = null;

	private $activation_key = null;
	private $register_dt = null;
	private $activation_dt = null;
	private $adoption_club_dt = null;

	private $online = null;
	private $online_last_dt = null;

	private $first_name = null;
	private $last_name = null;
	private $first_name_lat = null;
	private $last_name_lat = null;
	private $birthday = null;

	private $city_id = null;
	private $city_name = null;

	private $company = null; // Компания
	private $position_job = null; // Должность
	private $education = null; // Образование
	private $link_vk = null;
	private $link_fb = null;
	private $link_ln = null;
	private $link_tw = null;
	private $link_gp = null;
	private $about = null;
	private $favorite_places = null;

	private $lang_ru = null;
	private $lang_en = null;
	private $lang_fr = null;
	private $lang_de = null;
	private $lang_it = null;
	private $lang_es = null;
	private $automatic_translation = null; // Автоматический перевод

	private $romantic = null;
	private $marital_status = null; // Семейное положение
	private $height = null;
	private $children = null;
	private $smoking = null;
	private $free_day = null;

	private $msg_admin_email = null;
	private $msg_admin_sms = null;
	private $msg_communication_email = null;
	private $msg_communication_sms = null;
	private $msg_invite_email = null;
	private $msg_invite_sms = null;
	private $msg_favorite_email = null;
	private $msg_favorite_sms = null;
	private $msg_news_email = null;
	private $msg_news_sms = null;

	private $error_email = null;

	private $balance = null;
	private $balance_bonus = null;
	private $club_card_dt = null;

	private $recurrent_payment = null; // yes|no
	private $recurrent_dt = null;
	private $recurrent_bonus = null;
	private $last_access_dt = null;
	private $recovery_psw_dt = null;

	private $avatar = null; // сгенерированное поле, полный путь к аватарке пользователя
	private $url_profile = null; // сгенерированное поле, полный url к профилю пользователя
	private $img_path = null; // сгенерированное поле, путь относительно корня сервера к картинкам пользователя

	private $profileToArray = null;


	/** @var Zend_Db_Adapter_Abstract */
	private $db;
	private $langInterface = LANG_DEFAULT;

	/** @var Zend_Translate */
	private $translate;

	private $tblProfile = array('profile' => 'users');
	private $colAvatar  = array('avatar' => 'CONCAT( "/img/people/", `profile`.`sex`, "/", YEAR(`profile`.`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');
	private $colImgPath = array('img_path' => 'CONCAT( "/img/people/", `profile`.`sex`, "/", YEAR(`profile`.`birthday`), "/", `profile`.`id`, "/" )');

	public function __construct($userId)
	{
		$this->db = Zend_Registry::get('db');

		$this->langInterface = Zend_Controller_Front::getInstance()->getPlugin('Sas_Controller_Plugin_Language')->getLocale();
		if (empty($this->langInterface)) $this->langInterface = LANG_DEFAULT;
		$this->translate     = Zend_Registry::get('Zend_Translate');

		$this->setMyId(Models_User_Model::getMyId());

		if ((is_numeric($userId) && $userId > 0) || strlen($userId) == 8) {
			$this->setProfile($userId);
		} else {
			throw new Sas_Models_Exception('No user id', 404);
		}

	}

	/**
	 * Перевод текстов через Zend_Translate
	 * @param $msg
	 * @return mixed
	 */
	public function t($msg) {
		return $this->translate->translate($msg);
	}

	private function getMethodRename($name) {
		$newName = '';
		$n = explode('_', $name);
		foreach($n as $nn) {
			$newName .= ucfirst($nn);
		}

		return $newName;
	}
	/**
	 * Установка профиля.
	 *
	 * @param $id
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	private function setProfile($id)
	{
		if(is_int($this->id) && $this->id > 0) {
			return $this;
		}

		$select = $this->db->select()
			->from($this->tblProfile, '*')
			->limit(1);

		if(is_numeric($id)) {
			$select->where('profile.id = ?', $id);
		} else {
			$select->where('profile.uid = ?', $id);
		}

		$select->columns($this->colAvatar);
		$select->columns($this->colImgPath);
		$select->joinLeft('cities', 'cities.id=profile.city_id', array('city_name'=>'name_'.$this->langInterface));

		$row = $this->db->fetchRow($select);

		if(is_array($row)) {
			foreach($row as $key => $value) {
				$methodName = 'set'.$this->getMethodRename($key);
				$this->$methodName($value);
			}

			$url = ($this->langInterface == 'ru') ? '' : '/' . $this->langInterface;
			if($this->getMyId() == $this->getId()) {
				$this->setUrlProfile($url . '/user/profile');
			} else {
				$this->setUrlProfile($url . '/user/people/profile/view/' . $this->getUid());
			}

			$this->setProfileToArray($row);

		} else {
			throw new Sas_Models_Exception('No user profile.', 404);
		}

		return $this;
	}

	/**
	 * Сохранение профиля.
	 *
	 * @return true
	 * @throws Sas_Models_Exception
	 */
	public function save()
	{
		// Корректировка языков на которых говорит пользователь. Как минимум на языке интерфейса он разговаривает!
		$this->correctionLanguageTalk();

		// Делаем резерв массива профиля
		$profile_tmp = $this->getProfileToArray();

		// Удаляем нестандартные элементы
		unset($this->profileToArray['url_profile']);
		unset($this->profileToArray['avatar']);
		unset($this->profileToArray['city_name']);
		unset($this->profileToArray['img_path']);

		try {
			$this->db->update($this->tblProfile, $this->getProfileToArray(), 'id = ' . $this->getId());

			// Восстанавливаем массив профиля возвращаяя из резервной копии созданной ранее.
			$this->setProfileToArray($profile_tmp);
			return true;
		} catch (Zend_Db_Exception $e) {
			// TODO: записать в лог $e->getMessage()
			throw new Sas_Models_Exception('ERROR save profile.'.$e->getMessage(), 404);
		}
	}

	public function getPhoneFormat()
	{
		$number = $this->getPhone();
		$phone  = '';
		$phone .= substr($number, 0, -10) . ' ';
		$phone .= '(' . substr($number, -10, 3) . ') ';
		$phone .= substr($number, -7, 3) . '-';
		$phone .= substr($number, -4, 2) . '-';
		$phone .= substr($number, -2, 2);

		return $phone;
	}

	public function getPhoneFormatHide()
	{
		return substr($this->getPhoneFormat(), 0, -5) . 'XX-XX';
	}

	/**
	 * @param null $row
	 */
	public function setProfileToArray($row)
	{
		$this->profileToArray = $row;
		$this->profileToArray['url_profile'] = $this->getUrlProfile();
	}

	/**
	 * @return null
	 */
	public function getProfileToArray()
	{
		return $this->profileToArray;
	}

	/**
	 * Проверка подтвержденности телефона.
	 * @return bool
	 */
	public function isPhoneCheck()
	{
		return ($this->getPhoneCheck() == 'yes') ? true : false;
	}

	/**
	 * @param null $avatar
	 */
	public function setAvatar($avatar) {
		$this->avatar = $avatar;
	}

	/**
	 * @return null
	 */
	public function getAvatar()
	{
		return $this->avatar;
	}

	/**
	 * @return null
	 */
	public function saveAvatar()
	{
		// TODO: разобраться с параметром
		return $this->avatar;
	}

	/**
	 * @param $club_card_dt
	 */
	public function setClubCardDt($club_card_dt)
	{
		$this->club_card_dt = $club_card_dt;
	}

	/**
	 * @param null $club_card
	 */
	/*public function setClubCard($club_card)
	{
		$this->club_card = $club_card;
	}*/

	/**
	 * @return null
	 */
	public function getClubCard()
	{
		return $this->club_card_dt;
	}

	/**
	 * @param array $colAvatar
	 */
	public function setColAvatar($colAvatar)
	{
		$this->colAvatar = $colAvatar;
	}

	/**
	 * @return array
	 */
	public function getColAvatar()
	{
		return $this->colAvatar;
	}

	/**
	 * @param $current_status
	 * @return $this
	 */
	public function setCurrentStatus($current_status) {
		$this->current_status = (int) $current_status;
		$this->profileToArray['current_status'] = $this->current_status;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getCurrentStatus()
	{
		return $this->current_status;
	}

	/**
	 * @param null $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * @return null
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param $first_name
	 * @return $this|bool
	 * @throws Sas_Models_Exception
	 */
	public function setFirstName($first_name)
	{
		if(is_null($first_name)) return null;

		if($this->getCurrentStatus() < 70) {
			$first_name = Sas_Filter_Text::get($first_name);

			if(strlen($first_name) < 3) throw new Sas_Models_Exception($this->t('Имя не может быть менее трёх букв'));
			if(strlen($first_name) > 50) throw new Sas_Models_Exception($this->t('Имя не может быть более 50 букв'));
		}

		$this->profileToArray['first_name'] = $first_name;
		$this->first_name = $first_name;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getFirstName()
	{
		return $this->first_name;
	}

	/**
	 * @param null $id
	 */
	public function setId($id) {
		$this->id = (int) $id;
	}

	/**
	 * @return null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param $lang
	 * @return $this|null
	 * @throws Sas_Models_Exception
	 */
	public function setLang($lang)
	{
		if(is_null($lang)) return null;
		if($lang != 'ru' && $lang != 'en') throw new Sas_Models_Exception($this->t('Выбранный язык не доступен.'));

		$this->profileToArray['lang'] = $lang;
		$this->lang = $lang;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLang()
	{
		return $this->lang;
	}

	/**
	 * @param $automatic_translation
	 * @return $this
	 */
	public function setAutomaticTranslation($automatic_translation)
	{
		if(is_null($automatic_translation)) $automatic_translation = 'yes';
		if ($automatic_translation != 'yes' && $automatic_translation != 'no') $automatic_translation = 'yes';

		$this->profileToArray['automatic_translation'] = $automatic_translation;

		$this->automatic_translation = $automatic_translation;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getAutomaticTranslation()
	{
		return $this->automatic_translation;
	}

	/**
	 * @param int $myId
	 */
	public function setMyId($myId) {
		$this->myId = (int)$myId;
	}

	/**
	 * @return null
	 */
	public function getMyId()
	{
		return $this->myId;
	}

	/**
	 * @param $phone
	 * @return $this|bool
	 * @throws Sas_Models_Exception
	 */
	public function setPhone($phone)
	{
		if(is_null($phone)) return false;

		$phone = preg_replace("/[^0-9]/", '', $phone);

		if(!is_numeric($phone)) throw new Sas_Models_Exception('В качестве номера телефона можно использовать только цифы.');
		if(strlen($phone) < 11) throw new Sas_Models_Exception('Номер телефона содержит менее 11 цифр.');

		$this->phone = '+' . $phone;
		$this->profileToArray['phone'] = $this->getPhone();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getPhone()
	{
		return $this->phone;
	}

	/**
	 * @param null $uid
	 */
	public function setUid($uid)
	{
		$this->uid = $uid;
	}

	/**
	 * @return null
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param null $url_profile
	 */
	public function setUrlProfile($url_profile)
	{
		$this->url_profile = $url_profile;
	}

	/**
	 * @return null
	 */
	public function getUrlProfile()
	{
		return $this->url_profile;
	}

	/**
	 * @param $birthday
	 * @return null
	 * @throws Sas_Models_Exception
	 */
	public function setBirthday($birthday)
	{
		if(is_null($birthday)) return null;

		// Проверяем
		if(!$this->validateDate($birthday, 'Y-m-d')) {
			throw new Sas_Models_Exception($this->t('Дата рождения указана неверно.'));
		}

		$this->birthday = $birthday;
		$this->profileToArray['birthday'] = $this->getBirthday();

		// Только лично для "меня"
		if($this->getId() == $this->getMyId()) {
			$_SESSION['user']['birthday'] = $birthday;
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getBirthday()
	{
		return $this->birthday;
	}

	/**
	 * @param null $sex
	 */
	public function setSex($sex)
	{
		$this->sex = $sex;
	}

	/**
	 * @return null
	 */
	public function getSex()
	{
		return $this->sex;
	}

	/**
	 * Проверка на мужской пол
	 * @return bool
	 */
	public function isMale() {
		return ($this->getSex() == 'male') ? true : false;
	}

	/**
	 * Проверка на женский пол
	 * @return bool
	 */
	public function isFemale() {
		return ($this->getSex() == 'female') ? true : false;
	}

	/**
	 * @param $city_id
	 * @return $this
	 */
	public function setCityId($city_id)
	{
		$this->city_id = (int)$city_id;
		$this->profileToArray['city_id'] = $this->city_id;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getCityId()
	{
		return $this->city_id;
	}

	/**
	 * @param null $city_name
	 */
	public function setCityName($city_name)
	{
		$this->city_name = $city_name;
	}

	/**
	 * @return null
	 */
	public function getCityName()
	{
		return $this->city_name;
	}

	/**
	 * @param $balance
	 * @return $this
	 */
	public function setBalance($balance)
	{
		$this->profileToArray['balance'] = $balance;
		$this->balance = $balance;

		return $this;
	}

	/**
	 * @return null|int
	 */
	public function getBalance()
	{
		return $this->balance;
	}

	/**
	 * @param $balance_bonus
	 * @return $this
	 */
	public function setBalanceBonus($balance_bonus)
	{
		$this->profileToArray['balance_bonus'] = $balance_bonus;
		$this->balance_bonus = $balance_bonus;

		return $this;
	}

	/**
	 * @return null|int
	 */
	public function getBalanceBonus()
	{
		return $this->balance_bonus;
	}

	/**
	 * Общий баланс счёта (реал + бонусы)
	 * @return int|null
	 */
	public function getBalanceAll()
	{
		return $this->getBalance() + $this->getBalanceBonus();
	}

	/**
	 * @param null $online
	 */
	public function setOnline($online)
	{
		$this->profileToArray['online'] = $online;
		$this->online = $online;
	}

	/**
	 * @return null
	 */
	public function getOnline()
	{
		return $this->online;
	}

	/**
	 * @param null $online_last_dt
	 */
	public function setOnlineLastDt($online_last_dt)
	{
		$this->profileToArray['online_last_dt'] = $online_last_dt;
		$this->online_last_dt = $online_last_dt;
	}

	/**
	 * @return null
	 */
	public function getOnlineLastDt()
	{
		return $this->online_last_dt;
	}

	/**
	 * @param $phone_check
	 * @return $this
	 */
	public function setPhoneCheck($phone_check)
	{
		$this->phone_check = ($phone_check == 'yes') ? 'yes' : 'no';
		$this->profileToArray['phone_check'] = $this->getPhoneCheck();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getPhoneCheck()
	{
		return $this->phone_check;
	}

	/**
	 * @param $phone_verify_code
	 * @return $this
	 */
	public function setPhoneVerifyCode($phone_verify_code)
	{
		$this->profileToArray['phone_verify_code'] = $phone_verify_code;
		$this->phone_verify_code = $phone_verify_code;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getPhoneVerifyCode()
	{
		return $this->phone_verify_code;
	}

	/**
	 * @param null $promo_key
	 */
	public function setPromoKey($promo_key)
	{
		$this->profileToArray['promo_key'] = $promo_key;
		$this->promo_key = $promo_key;
	}

	/**
	 * @return null
	 */
	public function getPromoKey()
	{
		return $this->promo_key;
	}

	/**
	 * @param null $promo_key_friend
	 */
	public function setPromoKeyFriend($promo_key_friend)
	{
		$this->profileToArray['promo_key_friend'] = $promo_key_friend;
		$this->promo_key_friend = $promo_key_friend;
	}

	/**
	 * @return null
	 */
	public function getPromoKeyFriend()
	{
		return $this->promo_key_friend;
	}

	/**
	 * @param null $promocode
	 */
	public function setPromocode($promocode)
	{
		$this->profileToArray['promocode'] = $promocode;
		$this->promocode = $promocode;
	}

	/**
	 * @return null
	 */
	public function getPromocode()
	{
		return $this->promocode;
	}

	/**
	 * Установка нового пароля
	 * @param $password
	 * @return $this
	 */
	public function setPasswordNew($password)
	{
		$password = $this->generatePswNew($password);
		$this->profileToArray['psw'] = $password;
		$this->password = $password;

		return $this;
	}

	/**
	 * @param null $password
	 */
	private function setPsw($password)
	{
		$this->password = $password;
	}

	/**
	 * @return null
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @param null $msg_admin_email
	 */
	public function setMsgAdminEmail($msg_admin_email)
	{
		$this->profileToArray['msg_admin_email'] =$msg_admin_email;
		$this->msg_admin_email = $msg_admin_email;
	}

	/**
	 * @return null
	 */
	public function getMsgAdminEmail()
	{
		return $this->msg_admin_email;
	}

	/**
	 * @param null $msg_admin_sms
	 */
	public function setMsgAdminSms($msg_admin_sms)
	{
		$this->profileToArray['msg_admin_sms'] = $msg_admin_sms;
		$this->msg_admin_sms = $msg_admin_sms;
	}

	/**
	 * @return null
	 */
	public function getMsgAdminSms()
	{
		return $this->msg_admin_sms;
	}

	/**
	 * @param null $msg_communication_email
	 */
	public function setMsgCommunicationEmail($msg_communication_email)
	{
		$this->profileToArray['msg_communication_email'] = $msg_communication_email;
		$this->msg_communication_email = $msg_communication_email;
	}

	/**
	 * @return null
	 */
	public function getMsgCommunicationEmail()
	{
		return $this->msg_communication_email;
	}

	/**
	 * @param null $msg_communication_sms
	 */
	public function setMsgCommunicationSms($msg_communication_sms)
	{
		$this->profileToArray['msg_communication_sms'] = $msg_communication_sms;
		$this->msg_communication_sms = $msg_communication_sms;
	}

	/**
	 * @return null
	 */
	public function getMsgCommunicationSms()
	{
		return $this->msg_communication_sms;
	}

	/**
	 * @param null $msg_favorite_email
	 */
	public function setMsgFavoriteEmail($msg_favorite_email)
	{
		$this->profileToArray['msg_favorite_email'] = $msg_favorite_email;
		$this->msg_favorite_email = $msg_favorite_email;
	}

	/**
	 * @return null
	 */
	public function getMsgFavoriteEmail()
	{
		return $this->msg_favorite_email;
	}

	/**
	 * @param null $msg_favorite_sms
	 */
	public function setMsgFavoriteSms($msg_favorite_sms)
	{
		$this->profileToArray['msg_favorite_sms'] = $msg_favorite_sms;
		$this->msg_favorite_sms = $msg_favorite_sms;
	}

	/**
	 * @return null
	 */
	public function getMsgFavoriteSms()
	{
		return $this->msg_favorite_sms;
	}

	/**
	 * @param null $msg_invite_email
	 */
	public function setMsgInviteEmail($msg_invite_email)
	{
		$this->profileToArray['msg_invite_email'] = $msg_invite_email;
		$this->msg_invite_email = $msg_invite_email;
	}

	/**
	 * @return null
	 */
	public function getMsgInviteEmail()
	{
		return $this->msg_invite_email;
	}

	/**
	 * @param null $msg_invite_sms
	 */
	public function setMsgInviteSms($msg_invite_sms)
	{
		$this->profileToArray['msg_invite_sms'] = $msg_invite_sms;
		$this->msg_invite_sms = $msg_invite_sms;
	}

	/**
	 * @return null
	 */
	public function getMsgInviteSms()
	{
		return $this->msg_invite_sms;
	}

	/**
	 * @param null $msg_news_email
	 */
	public function setMsgNewsEmail($msg_news_email)
	{
		$this->profileToArray['msg_news_email'] = $msg_news_email;
		$this->msg_news_email = $msg_news_email;
	}

	/**
	 * @return null
	 */
	public function getMsgNewsEmail()
	{
		return $this->msg_news_email;
	}

	/**
	 * @param null $msg_news_sms
	 */
	public function setMsgNewsSms($msg_news_sms)
	{
		$this->profileToArray['msg_news_sms'] = $msg_news_sms;
		$this->msg_news_sms = $msg_news_sms;
	}

	/**
	 * @return null
	 */
	public function getMsgNewsSms()
	{
		return $this->msg_news_sms;
	}

	/**
	 * @param $about
	 * @return $this
	 */
	public function setAbout($about)
	{
		$this->about = Sas_Filter_Text::get($about);
		$this->profileToArray['about'] = $this->about;
		return $this;
	}

	/**
	 * @return null
	 */
	public function getAbout()
	{
		return $this->about;
	}

	/**
	 * Задает текущим временем дату активации аккаунта
	 */
	public function setActivationDt()
	{
		$this->activation_dt = CURRENT_DATETIME;
		$this->profileToArray['activation_dt'] = $this->activation_dt;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getActivationDt()
	{
		return $this->activation_dt;
	}

	/**
	 * @param null $activation_key
	 */
	public function setActivationKey($activation_key)
	{
		$this->activation_key = $activation_key;
	}

	/**
	 * @return null
	 */
	public function getActivationKey()
	{
		return $this->activation_key;
	}

	/**
	 * @param null $adoption_club_dt
	 */
	public function setAdoptionClubDt($adoption_club_dt)
	{
		$this->adoption_club_dt = $adoption_club_dt;
	}

	/**
	 * @return null
	 */
	public function getAdoptionClubDt()
	{
		return $this->adoption_club_dt;
	}

	/**
	 * Компания
	 * @param $company
	 * @return $this
	 */
	public function setCompany($company)
	{
		if(is_null($company)) return null;

		$this->company = Sas_Filter_Text::get($company);
		$this->profileToArray['company'] = $this->getCompany();
		return $this;
	}

	/**
	 * @return null
	 */
	public function getCompany()
	{
		return $this->company;
	}

	/**
	 * Образование
	 * @param $education
	 * @return $this
	 */
	public function setEducation($education)
	{
		if(is_null($education)) return null;

		$this->education = Sas_Filter_Text::get($education);
		$this->profileToArray['education'] = $this->getEducation();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getEducation()
	{
		return $this->education;
	}

	/**
	 * @param null $error_email
	 */
	public function setErrorEmail($error_email)
	{
		$this->error_email = $error_email;
	}

	/**
	 * @return null
	 */
	public function getErrorEmail()
	{
		return $this->error_email;
	}

	/**
	 * @param $favorite_places
	 * @return $this
	 */
	public function setFavoritePlaces($favorite_places)
	{
		$this->favorite_places = Sas_Filter_Text::get($favorite_places);
		$this->profileToArray['favorite_places'] = $this->getFavoritePlaces();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getFavoritePlaces()
	{
		return $this->favorite_places;
	}

	/**
	 * @param null $first_name_lat
	 */
	public function setFirstNameLat($first_name_lat)
	{
		$this->first_name_lat = $first_name_lat;
	}

	/**
	 * @return null
	 */
	public function getFirstNameLat()
	{
		return $this->first_name_lat;
	}

	/**
	 * @param null $free_day
	 */
	public function setFreeDay($free_day)
	{
		$this->free_day = $free_day;
	}

	/**
	 * @return null
	 */
	public function getFreeDay()
	{
		return $this->free_day;
	}

	/**
	 * @param $height
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setHeight($height)
	{
		if($height >= 150 || $height <= 210) {
			$this->height = (int)$height;
			$this->profileToArray['height'] = $this->getHeight();
		} else {
			throw new Sas_Models_Exception('Error param height. min 150, max 210.');
		}

		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getHeight()
	{
		return (!is_null($this->height)) ? (int) $this->height : null;
	}

	/**
	 * @param $lang_de
	 * @return $this
	 */
	public function setLangDe($lang_de)
	{
		$this->checkLangCorrectValue($lang_de);
		$this->lang_de = ($lang_de == 'yes') ? 'yes' : 'no';
		$this->profileToArray['lang_de'] = $this->getLangDe();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLangDe()
	{
		return $this->lang_de;
	}

	/**
	 * @param $lang_en
	 * @return $this
	 */
	public function setLangEn($lang_en)
	{
		$this->checkLangCorrectValue($lang_en);
		$this->lang_en = ($lang_en == 'yes') ? 'yes' : 'no';
		$this->profileToArray['lang_en'] = $this->getLangEn();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLangEn()
	{
		return $this->lang_en;
	}

	/**
	 * @param $lang_es
	 * @return $this
	 */
	public function setLangEs($lang_es)
	{
		$this->checkLangCorrectValue($lang_es);
		$this->lang_es = ($lang_es == 'yes') ? 'yes' : 'no';
		$this->profileToArray['lang_es'] = $this->getLangEs();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLangEs()
	{
		return $this->lang_es;
	}

	/**
	 * @param $lang_fr
	 * @return $this
	 */
	public function setLangFr($lang_fr)
	{
		$this->checkLangCorrectValue($lang_fr);
		$this->lang_fr = ($lang_fr == 'yes') ? 'yes' : 'no';
		$this->profileToArray['lang_fr'] = $this->getLangFr();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLangFr()
	{
		return $this->lang_fr;
	}

	/**
	 * @param $lang_it
	 * @return $this
	 */
	public function setLangIt($lang_it)
	{
		$this->checkLangCorrectValue($lang_it);
		$this->lang_it = ($lang_it == 'yes') ? 'yes' : 'no';
		$this->profileToArray['lang_it'] = $this->getLangIt();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLangIt()
	{
		return $this->lang_it;
	}

	/**
	 * @param $lang_ru
	 * @return $this
	 */
	public function setLangRu($lang_ru)
	{
		$this->checkLangCorrectValue($lang_ru);
		$this->lang_ru = ($lang_ru == 'yes') ? 'yes' : 'no';
		$this->profileToArray['lang_ru'] = $this->getLangRu();

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLangRu()
	{
		return $this->lang_ru;
	}

	/**
	 * Проверяет корректность значения языкового параметра в
	 * настройках языка на котором говорит пользователь.
	 *
	 * @param $lang
	 * @return bool
	 * @throws Sas_Models_Exception
	 */
	private function checkLangCorrectValue($lang) {
		if($lang != 'yes' && $lang != 'no') throw new Sas_Models_Exception($this->t('Языковой параметр может содержать только значения yes или no.'));
		return true;
	}

	/**
	 * Задать текущую дату и время в качестве последней для доступа к закрытой информации.
	 */
	public function setLastAccessDt($last_access_dt)
	{
		if(is_null($last_access_dt)) return null;

		$this->last_access_dt = $last_access_dt;
		$this->profileToArray['last_access_dt'] = $this->last_access_dt;

		return $this;
	}

	/**
	 * Дата и время последнего доступа к закрытой информации
	 * @return null|string
	 */
	public function getLastAccessDt()
	{
		return $this->last_access_dt;
	}

	/**
	 * @param $last_name
	 * @return $this|null
	 * @throws Sas_Models_Exception
	 */
	public function setLastName($last_name)
	{
		if(is_null($last_name)) return null;

		if($this->getCurrentStatus() < 70) {
			$last_name = Sas_Filter_Text::get($last_name);
			if(strlen($last_name) < 3) throw new Sas_Models_Exception($this->t('Фамилия не может быть менее трёх букв'));
			if(strlen($last_name) > 50) throw new Sas_Models_Exception($this->t('Фамилия не может быть более 50 букв'));
		}

		$this->profileToArray['last_name'] = $last_name;
		$this->last_name = $last_name;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLastName()
	{
		return $this->last_name;
	}

	/**
	 * @param null $last_name_lat
	 */
	public function setLastNameLat($last_name_lat)
	{
		$this->last_name_lat = $last_name_lat;
	}

	/**
	 * @return null
	 */
	public function getLastNameLat()
	{
		return $this->last_name_lat;
	}

	/**
	 * @param $link_fb
	 * @return $this|null
	 * @throws Sas_Models_Exception
	 */
	public function setLinkFb($link_fb)
	{
		if(is_null($link_fb) || $this->getCurrentStatus() >= 51) return null;

		$link_fb = Sas_Filter_Text::get($link_fb);
		if(!strstr($link_fb, 'facebook.com')) throw new Sas_Models_Exception($this->t('Ссылка на facebook не корректна.'));

		$this->profileToArray['link_fb'] = $link_fb;
		$this->link_fb = $link_fb;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLinkFb()
	{
		return $this->link_fb;
	}

	/**
	 * @param $link_ln
	 * @return $this|null
	 * @throws Sas_Models_Exception
	 */
	public function setLinkLn($link_ln)
	{
		if(is_null($link_ln) || $this->getCurrentStatus() >= 51) return null;

		$link_ln = Sas_Filter_Text::get($link_ln);
		if(!strstr($link_ln, 'linkedin.com')) throw new Sas_Models_Exception($this->t('Ссылка на linkedin не корректна.'));

		$this->profileToArray['link_ln'] = $link_ln;
		$this->link_ln = $link_ln;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLinkLn()
	{
		return $this->link_ln;
	}

	/**
	 * @param $link_vk
	 * @return $this|null
	 * @throws Sas_Models_Exception
	 */
	public function setLinkVk($link_vk)
	{
		if(is_null($link_vk) || $this->getCurrentStatus() >= 51) return null;

		$link_vk = Sas_Filter_Text::get($link_vk);
		if(!strstr($link_vk, 'vk.com')) throw new Sas_Models_Exception($this->t('Ссылка на вКонтакте не корректна.'));

		$this->profileToArray['link_vk'] = $link_vk;
		$this->link_vk = $link_vk;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLinkVk()
	{
		return $this->link_vk;
	}

	/**
	 * @param $link_tw
	 * @return $this|null
	 * @throws Sas_Models_Exception
	 */
	public function setLinkTw($link_tw)
	{
		if(is_null($link_tw) || $this->getCurrentStatus() >= 51) return null;

		//$link_tw = Sas_Filter_Text::get($link_tw);
		//if(!strstr($link_tw, 'twitter.com')) throw new Sas_Models_Exception($this->t('Ссылка на twitter не корректна.'));

		$this->profileToArray['link_tw'] = $link_tw;
		$this->link_tw = $link_tw;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLinkTw()
	{
		return $this->link_tw;
	}

	/**
	 * @param $link_gp
	 * @return $this|null
	 * @throws Sas_Models_Exception
	 */
	public function setLinkGp($link_gp)
	{
		if(is_null($link_gp) || $this->getCurrentStatus() >= 51) return null;

		//$link_gp = Sas_Filter_Text::get($link_gp);
		//if(!strstr($link_gp, 'vk.com')) throw new Sas_Models_Exception($this->t('Ссылка на вКонтакте не корректна.'));

		$this->profileToArray['link_gp'] = $link_gp;
		$this->link_gp = $link_gp;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLinkGp()
	{
		return $this->link_gp;
	}

	/**
	 * @param null $marital_status
	 */
	public function setMaritalStatus($marital_status)
	{
		$this->marital_status = $marital_status;
	}

	/**
	 * @return null
	 */
	public function getMaritalStatus()
	{
		return $this->marital_status;
	}

	/**
	 * @param $position_job
	 * @return $this
	 */
	public function setPositionJob($position_job)
	{
		if(is_null($position_job)) return null;

		$this->position_job = Sas_Filter_Text::get($position_job);
		$this->profileToArray['position_job'] = $this->getPositionJob();
		return $this;
	}

	/**
	 * @return null
	 */
	public function getPositionJob()
	{
		return $this->position_job;
	}

	/**
	 * @param null $recovery_psw_dt
	 */
	public function setRecoveryPswDt($recovery_psw_dt)
	{
		$this->recovery_psw_dt = $recovery_psw_dt;
	}

	/**
	 * @return null
	 */
	public function getRecoveryPswDt()
	{
		return $this->recovery_psw_dt;
	}

	/**
	 * @param $recurrent_dt
	 * @return $this|null
	 */
	private function setRecurrentDt($recurrent_dt)
	{
		if(is_null($recurrent_dt)) return null;

		$this->profileToArray['recurrent_dt'] = $recurrent_dt;
		$this->recurrent_dt = $recurrent_dt;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getRecurrentDt()
	{
		return $this->recurrent_dt;
	}

	/**
	 * @param $recurrent_payment
	 * @return $this|null
	 * @throws Sas_Models_Exception
	 */
	private function setRecurrentPayment($recurrent_payment)
	{
		if(is_null($recurrent_payment)) return null;

		if($recurrent_payment != 'yes' && $recurrent_payment != 'no') throw new Sas_Models_Exception($this->t('Допустимое значение для отметки рекуррентных платежей yes|no'));

		$this->profileToArray['recurrent_payment'] = $recurrent_payment;
		$this->recurrent_payment = $recurrent_payment;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getRecurrentPayment()
	{
		return $this->recurrent_payment;
	}

	/**
	 * @param $recurrent_bonus
	 * @return null
	 */
	public function setRecurrentBonus($recurrent_bonus)
	{
		if(is_null($recurrent_bonus)) return null;

		$this->profileToArray['recurrent_bonus'] = $recurrent_bonus;
		$this->recurrent_bonus = $recurrent_bonus;
	}

	/**
	 * @return null
	 */
	public function getRecurrentBonus()
	{
		return $this->recurrent_bonus;
	}

	/**
	 * Выбавался ли бонус за рек. платежи
	 * @return bool
	 */
	public function isRecurrentBonus()
	{
		return ($this->recurrent_bonus == 'yes') ? true : false;
	}

	/**
	 * @param $key
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setRecurrent($key)
	{
		// Проверки
		if($key == 'no') {
			// Смотрим, давали ли пользователю бонус
			if($this->isRecurrentBonus()) {
				// Достаточно ли карат для отключения рек. платежей?
				if($this->getBalanceAll() >= 200) {
					$Balance = new Models_Balance($this);
					$Balance->minusBalanceAll(200, $this->t('Списание ранее выданных карат за активацию опции автоматического продления Клубной карты.'));
					$this->setRecurrentBonus('no');

					// Списаны бонусные караты за невозможность выполнять рек. платежи
					Models_Actions::add(68, $this->getId());
				} else {
					// Мало денег, отключить рек. нельзя
					throw new Sas_Models_Exception(sprintf($this->t('Для отключения опции автоматического продления Клубной карты Ваш баланс должен быть не менее %s карат.'), 200));
				}
			}
		}
		$this->setRecurrentPayment($key);
		$this->setRecurrentDt(CURRENT_DATETIME);

		return $this;
	}

	/**
	 * @param null $register_dt
	 */
	public function setRegisterDt($register_dt)
	{
		$this->register_dt = $register_dt;
	}

	/**
	 * @return null
	 */
	public function getRegisterDt()
	{
		return $this->register_dt;
	}

	/**
	 * @param null $skype
	 */
	public function setSkype($skype)
	{
		$this->skype = $skype;
	}

	/**
	 * @return null
	 */
	public function getSkype()
	{
		return $this->skype;
	}

	/**
	 * @param $romantic
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setRomantic($romantic)
	{
		if($romantic == 'yes' || $romantic == 'no') {
			$this->romantic = $romantic;
			$this->profileToArray['romantic'] = $this->romantic;
		} else {
			throw new Sas_Models_Exception('Param romantic error value.');
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getRomantic()
	{
		return $this->romantic;
	}

	/**
	 * @return null
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * @param $children
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setChildren($children)
	{
		if($children == 'yes' || $children == 'no') {
			$this->children = $children;
			$this->profileToArray['children'] = $this->children;
		} else {
			throw new Sas_Models_Exception('Param children error value.');
		}

		return $this;
	}

	/**
	 * @param $smoking
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setSmoking($smoking)
	{
		if($smoking == 'yes' || $smoking == 'no') {
			$this->smoking = $smoking;
			$this->profileToArray['smoking'] = $this->smoking;
		} else {
			throw new Sas_Models_Exception('Param children error value.');
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getSmoking()
	{
		return $this->smoking;
	}

	/**
	 * Установка значений для уведомлений
	 * @param $name
	 * @param $value
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setNotice($name, $value) {
		if($value != 'yes' && $value != 'no') {
			throw new Sas_Models_Exception('setNotice error value', 404);
		}

		switch ($name) {
			case 'msg_admin_email':         $this->setMsgAdminEmail($value); break;
			case 'msg_admin_sms':           $this->setMsgAdminSms($value); break;
			case 'msg_communication_email': $this->setMsgCommunicationEmail($value); break;
			case 'msg_communication_sms':   $this->setMsgCommunicationSms($value); break;
			case 'msg_invite_email':        $this->setMsgInviteEmail($value); break;
			case 'msg_invite_sms':          $this->setMsgInviteSms($value); break;
			case 'msg_favorite_email':      $this->setMsgFavoriteEmail($value); break;
			case 'msg_favorite_sms':        $this->setMsgFavoriteSms($value); break;
			case 'msg_news_email':          $this->setMsgNewsEmail($value); break;
			case 'msg_news_sms':            $this->setMsgNewsSms($value); break;
			default:
				throw new Sas_Models_Exception('setNotice error name', 404);
		}

		return $this;
	}

	public function isPasswordCorrect($psw)
	{
		return ($this->getPassword() == $this->generatePswNew($psw)) ? true : false;
	}

	/**
	 * Генерация нового пароля.
	 *
	 * @param $psw
	 * @return string
	 */
	private function generatePswNew($psw)
	{
		return md5($psw);
	}

	/**
	 * Удаление профиля.
	 *
	 * @param $psw
	 * @throws Sas_Models_Exception
	 */
	public function deleteMyProfile($psw)
	{
		// Проверяем пароль
		if (!$this->isPasswordCorrect($psw)) {
			throw new Sas_Models_Exception($this->t('Текущий пароль введён не верно.'));
		}

		// Логируем - Профиль удален пользователем
		Models_Actions::add(27, $this->getId());

		// Пользователь удалил свой аккаунт
		return $this->setCurrentStatus(20)->save();
	}

	/**
	 * Создаёт и записывает код подтверждения в профиль пользователя.
	 *
	 * @return int|string
	 * @throws Sas_Models_Exception
	 */
	public function createPhoneVerifyCode()
	{
		// Проверяем наличие телефона
		if(!$this->getPhone()) {
			throw new Sas_Models_Exception('Нельзя сгенерировать код, так как отсутствует номер телефона.');
		}

		// генерим код
		$code = rand(100000, 999999);
		$code = sprintf('%06d', $code);

		//$update['phone'] = $phone;
		$update['phone_verify_code'] = $code;
		$update['phone_check'] = 'no';

		$where = $this->db->quoteInto('id = ?', $this->id);

		try {
			$this->db->update($this->tblProfile, $update, $where);
			$this->setPhoneVerifyCode($code);

		} catch (Zend_Db_Exception $e) {
			new Sas_Models_Exception($e);
		}

		return $code;
	}

	/**
	 * Отправка код подтверждения телефонного номера по СМС.
	 *
	 * @return bool
	 * @throws Sas_Models_Exception
	 */
	public function sendPhoneVerifyCodeSms()
	{
		if ($this->getPhoneVerifyCode())
		{
			$sms = new Zelenin_SmsRu('c1a21b64-3825-7674-d968-bee81a27d285');
			$send = $sms->sms_send($this->getPhone(), $this->t('Код подтверждения: ') . $this->getPhoneVerifyCode(), 'OnTheList'); // Рабочий режим
			if ($send['code'] != 100) {
				throw new Sas_Models_Exception($this->t('Ошибка отправки СМС с кодом подтверждения номера телефона.'));
			}

		} else {
			throw new Sas_Models_Exception($this->t('Код подтверждения телефонного номера отсутствует, так как не был сгенерирован ранее'));
		}

		return true;
	}

	/**
	 * Проверка совпадения кода подтверждения номера телефона.
	 *
	 * @param $code
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function isPhoneVerifyCode($code)
	{
		$code = trim($code);

		if(!is_numeric($code)) throw new Sas_Models_Exception($this->t('В качестве кода подтверждения номера телефона принимаются только цифры.'));

		if($code != $this->getPhoneVerifyCode()) {
			// Если ранее номер был подтвержден, отметим, что сейчас это уже не так
			if($this->getPhoneCheck() == 'yes') {
				$this->setPhoneCheck('no')->save();
			}

			throw new Sas_Models_Exception($this->t('Код подтверждения номера телефона не верен.'));
		}

		// Отмечаем что номер подтвержден.
		$this->setPhoneCheck('yes');
		$this->setPhoneVerifyCode(null);

		return $this;
	}

	/**
	 * Проверка даты на валидность
	 * @param        $date
	 * @param string $format
	 * @return bool
	 */
	private function validateDate($date, $format = 'Y-m-d H:i:s')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}

	/**
	 * @param $img_path
	 */
	private function setImgPath($img_path)
	{
		$this->img_path = $img_path;
		$this->profileToArray['img_path'] = $img_path;
	}

	/**
	 * @return null
	 */
	public function getImgPath()
	{
		return $this->img_path;
	}

	/**
	 * Отправка анкеты пользователя администратору
	 * @throws Sas_Models_Exception
	 */
	public function sendProfileAdmin()
	{
		try {
			$this->setCurrentStatus(51)->save();
		} catch(Sas_Models_Exception $e) {
			throw new Sas_Models_Exception('Системная ошибка при отправке анкеты для вступления в Клуб.', 1);
		}
	}

	/**
	 * Корректировка разговорных языков в случае, если не один не выбран.
	 * В качестве языка по умолчанию выбирается язык интерфейса.
	 */
	private function correctionLanguageTalk() {
		if($this->getLangRu() == 'no'
			&& $this->getLangEn() == 'no'
			&& $this->getLangDe() == 'no'
			&& $this->getLangFr() == 'no'
			&& $this->getLangEs() == 'no'
			&& $this->getLangIt() == 'no'
		) {
			$curLang = 'setLang'.ucfirst($this->langInterface);
			$this->$curLang('yes');
		}
	}
}
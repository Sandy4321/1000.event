<?php

class Models_Register
{
	private $id = null;
	private $uid = null;
	private $sex = null;
	private $email = null;
	private $password = null;
	private $lang = null;
	private $promocode = null;
	private $promo_key_friend = null;
	private $register_dt = null;
	private $activation_key = null;

	private $soc_name = null;
	private $soc_link = null;

	private $birthday = null;

	private $first_name = null;
	private $last_name  = null;

	/** @var Zend_Db_Adapter_Abstract */
	private $db;
	private $langInterface = LANG_DEFAULT;

	/** @var Zend_Translate */
	private $translate;

	private $tblProfile   = array('profile' => 'users');

	public function __construct()
	{
		$this->db = Zend_Registry::get('db');

		$this->langInterface = Zend_Controller_Front::getInstance()->getPlugin('Sas_Controller_Plugin_Language')->getLocale();
		$this->translate     = Zend_Registry::get('Zend_Translate');
	}

	/**
	 * @return null
	 */
	public function getActivationKey()
	{
		if(is_null($this->activation_key)) {
			$this->activation_key = md5(microtime() . $this->getEmail() . $this->getSex() . rand(0, 10000));
		}
		return $this->activation_key;
	}

	/**
	 * @param $email
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setEmail($email)
	{
		$email = Sas_Filter_Text::get($email);

		if(empty($email)) {
			throw new Sas_Models_Exception($this->t('Email адрес не может быть пустым'), 0);
		}

		if(!preg_match('/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]{2,6})$/', $email)) {
			throw new Sas_Models_Exception($this->t('Email адрес не корректен'), 0);
		}

		// Проверка дубликата
		$user = $this->isDuplicationEmail($email);
		if(is_array($user)) {
			Models_Actions::add(11, $user['id']); // Попытка вторичной регистрации по одному email
			if($user['current_status'] == 10) {
				throw new Sas_Models_Exception($this->t('Данный email зарегистрирован, но не подтвержден.'), 0);
			}
			if($user['current_status'] < 50) {
				throw new Sas_Models_Exception($this->t('Регистрация на данный email запрещена.'), 0);
			}
			// TODO: отправить email с предупреждением о попытке регистрации
			throw new Sas_Models_Exception($this->t('Данный email уже использован для регистрации.'), 1);
		}

		$this->email = $email;

		return $this;
	}

	/**
	 * @param $birthday
	 * @return $this
	 */
	public function setBirthday($birthday)
	{
		$this->birthday = $birthday;

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
	 * @param $first_name
	 * @return $this
	 */
	public function setFirstName($first_name)
	{
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
	 * @param $last_name
	 * @return $this
	 */
	public function setLastName($last_name)
	{
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
	 * @param $soc_link
	 * @return $this
	 */
	public function setSocLink($soc_link)
	{
		$this->soc_link = $soc_link;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getSocLink()
	{
		return $this->soc_link;
	}

	/**
	 * @param $soc_name
	 * @return $this
	 */
	public function setSocName($soc_name)
	{
		$this->soc_name = $soc_name;

		return $this;
	}

	/**
	 * @return null
	 */
	public function getSocName()
	{
		return $this->soc_name;
	}

	/**
	 * @return null
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @return null
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return null
	 */
	public function getLang()
	{
		return $this->langInterface;
	}

	/**
	 * @param $password
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setPassword($password)
	{
		$password = trim($password);
		if(strlen($password) < 6) throw new Sas_Models_Exception($this->t('Пароль не может быть менее 6 символов'));

		$this->password = $password;
		return $this;
	}

	/**
	 * @return null
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @param $promo_key_friend
	 * @return $this
	 */
	public function setPromoKeyFriend($promo_key_friend)
	{
		$promoKey = Sas_Filter_Text::get($promo_key_friend);
		// Если промокод не пустой, проверяем его корректность
		if(!empty($promoKey)) {
			// Проверяем кол-во символов в коде, должно быть 32
			// Проверяем наличие "родительского" промо-кода в бд

			$this->promo_key_friend = $promoKey;

			// В случае ошибок генерим исключение
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getPromoKeyFriend()
	{
		return $this->promo_key_friend;
	}

	/**
	 * @param $promocode
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setPromocode($promocode)
	{
		$promocode = Sas_Filter_Text::get($promocode);

		// Если промокод не пустой, проверяем его корректность
		if(!empty($promocode)) {
			// Вырезаем всё кроме букв и цифр
			$promocode = str_replace('-', '', $promocode);

			// должно остаться 8 символов
			if(strlen($promocode) != 8) throw new Sas_Models_Exception($this->t('Промо код не верный.'));

			// Всё приводим в верхний регистр
			$promocode = strtoupper($promocode);

			// Проверяем наличие и кол-во промокодов в бд
			$this->promocode = $promocode;

			// В случае ошибок генерим исключение
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getPromocode()
	{
		return $this->promocode;
	}

	/**
	 * @return null
	 */
	public function getRegisterDt()
	{
		return $this->register_dt = CURRENT_DATETIME;
	}

	/**
	 * @param $sex
	 * @return $this
	 * @throws Sas_Models_Exception
	 */
	public function setSex($sex)
	{
		if($sex != 'male' && $sex != 'female') throw new Sas_Models_Exception($this->t('Выберите Ваш пол.'));
		$this->sex = ($sex == 'male') ? 'male' : 'female';

		return $this;
	}

	/**
	 * @return null
	 */
	public function getSex()
	{
		return $this->sex;
	}

	/**
	 * @return null
	 */
	public function getUid()
	{
		return $this->uid;
	}

	private function getPromoKey() {
		return md5($this->getEmail(). $this->getActivationKey() . microtime());
	}

	public function save()
	{
		$this->generatorUid(8);
		$data['uid']              = $this->getUid();
		$data['sex']              = $this->getSex();
		$data['email']            = $this->getEmail();
		$data['psw']              = md5($this->getPassword());
		$data['promocode']        = $this->getPromocode();
		$data['promo_key']        = $this->getPromoKey();
		$data['promo_key_friend'] = $this->getPromoKeyFriend();
		$data['activation_key']   = $this->getActivationKey();
		$data['register_dt']      = $this->getRegisterDt();
		$data['lang']             = $this->getLang();

		// Добавляем стандартные поля
		// Роматинтические знакомства включены
		$data['romantic'] = 'yes';
		// Новая анкета
		$data['current_status'] = 10;
		// Стандартные свободные дни
		$data['free_day'] = 'a:13:{i:0;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:1;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:2;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:3;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:4;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:5;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:6;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:7;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:8;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:9;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:10;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:11;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:12;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}}';

		$this->db->beginTransaction();
		try {
			$this->db->insert($this->tblProfile, $data);
			$this->id = $this->db->lastInsertId($this->tblProfile, 'id');
		} catch (Zend_Db_Exception $e) {
			throw new Sas_Models_Exception($e->getMessage(), $e->getCode());
		}

		// Отправляем письмо с уведомлением.
		try {
			$this->sendRegisterEmail();
			$this->db->commit();
		} catch (Sas_Exception $e) {
			$this->db->rollBack();
			throw new Sas_Models_Exception($e->getMessage(), $e->getCode());
		}

		Models_Actions::add(42, $this->getId(), null, $this->getId()); // Получена новая заявка на регистрацию
	}

	public function saveSocNetwork() {
		$this->generatorUid(8);
		$data['uid']               = $this->getUid();
		$data['sex']               = $this->getSex();
		$data['email']             = $this->getEmail();
		$data['psw']               = md5($this->getPassword());
		$data['promocode']         = $this->getPromocode();
		$data['promo_key']         = $this->getPromoKey();
		$data['promo_key_friend']  = $this->getPromoKeyFriend();
		$data['activation_key']    = $this->getActivationKey();
		$data['register_dt']       = $this->getRegisterDt();
		$data['lang']              = $this->getLang();
		$data['first_name']        = $this->getFirstName();
		$data['last_name']         = $this->getLastName();
		$data['birthday']          = $this->getBirthday();
		$data['lang']              = $this->getLang();
		$data[$this->getSocName()] = $this->getSocLink();
		$data['activation_dt']     = CURRENT_DATETIME;

		// Добавляем стандартные поля
		// Роматинтические знакомства включены
		$data['romantic'] = 'yes';
		// Новая анкета
		$data['current_status'] = 50;
		// Стандартные свободные дни
		$data['free_day'] = 'a:13:{i:0;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:1;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:2;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:3;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:4;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:5;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:6;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:7;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:8;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:9;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:10;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:11;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:12;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}}';

		$this->db->beginTransaction();
		try {
			$this->db->insert($this->tblProfile, $data);
			$this->id = $this->db->lastInsertId($this->tblProfile, 'id');
			$this->db->commit();
		} catch (Zend_Db_Exception $e) {
			$this->db->rollBack();
			throw new Sas_Models_Exception($e->getMessage(), $e->getCode());
		}

		Models_Actions::add(77, $this->getId(), null, $this->getId()); // Регистрация через соц. сеть

		return true;
	}

	/**
	 * Отправка письма с активацией
	 * @param null|array $profile
	 */
	public function sendRegisterEmail($profile = null)
	{
		if (is_null($profile)) {
			$profile = array(
				'email' => $this->getEmail(),
				'lang'  => $this->getLang()
			);
		}
		$ModelTplMsg = new Models_TemplatesMessage($profile, 'register');
		$ModelTplMsg->addDataReplace('psw', $this->getPassword());
		$ModelTplMsg->addDataReplace('email', $this->getEmail());
		$ModelTplMsg->addDataReplace('activation_key', $this->getActivationKey());
		$ModelTplMsg->send();
	}

	/**
	 * Отправка письма с новым сгенерированным паролем
	 */
	public function sendRegisterEmailSocNetwork()
	{
		$profile = array(
			'first_name' => $this->getFirstName(),
			'last_name'  => $this->getLastName(),
			'email'      => $this->getEmail(),
			'lang'       => $this->getLang()
		);

		try {
			$ModelTplMsg = new Models_TemplatesMessage($profile, 'register_soc_net');
			$ModelTplMsg->addDataReplace('psw', $this->getPassword());
			$ModelTplMsg->addDataReplace('email', $this->getEmail());
			$ModelTplMsg->send();
		} catch (Exception $e) {
			// глушим проблему отправки если возникает,
			// так как это не так важно как новый пользователь
		}
	}

	/**
	 * Генератор уникальных id.
	 *
	 * @param int $numAlpha
	 * @return string
	 */
	private function generatorUid($numAlpha = 10)
	{
		// символы из которых генерируется индентификатор
		$listAlpha = 'abcdefghjkmnpqrstuvwxyz0123456789ABCDEFGHJKMNPQRSTUVWXYZ';

		// генерируем индентификатор и возвращаем
		$uid = str_shuffle(substr(str_shuffle($listAlpha),0,$numAlpha));

		// Проверяем уникальность uid
		$select = $this->db->select()
			->from($this->tblProfile, 'uid')
			->where('uid = ?', $uid)
			->limit(1);
		$res = $this->db->fetchOne($select);
		if($res) {
			$this->generatorUid($numAlpha);
		} else {
			$this->uid = $uid;
			return $this->uid;
		}
	}

	/**
	 * Генератор новых 7-ми значных паролей
	 * @return string
	 */
	public function generatorNewPsw()
	{
		// генерим
		return sprintf('%07d', rand(1000000, 9999999));
	}

	/**
	 * Перевод текстов через Zend_Translate
	 * @param $msg
	 * @return mixed
	 */
	private function t($msg) {
		return $this->translate->translate($msg);
	}

	/**
	 * Проверка дубликата email
	 *
	 * @param $email
	 * @return true|array false - нет дубликата | array - есть дубликат
	 */
	private function isDuplicationEmail($email)
	{
		$select = $this->db->select()
			->from($this->tblProfile, array('id', 'current_status'))
			->where('email = ?', $email)
			->limit(1);

		$row = $this->db->fetchRow($select);
		if ($row == false) {
			return false;
		}
		return $row;
	}

	/**
	 * Проверка ключа активации нового пользоввтеля.
	 *
	 * @param $key
	 * @return bool|Models_Users
	 */
	public function checkActivationKey($key) {
		// Проверяем уникальность uid
		$select = $this->db->select()
			->from($this->tblProfile, 'id')
			->where('activation_key = ?', $key)
			->where('current_status = 10') // 10 - это первичный статус пользователя в системе
			->limit(1);

		$userId = $this->db->fetchOne($select);

		if(is_numeric($userId)) {
			return new Models_Users($userId);
		} else {
			return false;
		}
	}

	/**
	 * Активация нового пользователя (подтверждение через email по ссылке с ключем).
	 *
	 * @param Models_Users $User
	 */
	public function activationNewUser(Models_Users $User)
	{
		$User->setCurrentStatus(50)
			->setActivationDt()
			->save();
	}
}
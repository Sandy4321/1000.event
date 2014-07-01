<?php

/**
 * Модель регистрации пользователя.
 *
 * Устаревшая модель!!!
 *
 * Обеспечивает взаимодействие при регистрации и обеспечивает проверку (валидацию) полей рег формы.
 */
class Models_User_Register
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	//private $tableProfile = 'users_data';
	private $tableProfile = 'users';

	private $errorMsg = null;
	private $validForm = true;

	private $dataNewUser = null;

	public function __construct() {
		$this->db = Zend_Registry::get('db');
		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();
	}

	/**
	 * Созраняем данные регистрации пользователя
	 *
	 * @param $data
	 * @return bool
	 */
	public function save($data)
	{
		$email = $data['email'];
		if ($this->checkDuplicationEmail($email) == false) {

			return false;
		}

		// Создаём UID
		$uid = $this->generatorUid(8);

		// Перепаковываем день рождения
		#$birthday = explode('-', $data['birthday']);

		#$data['skype'] = htmlspecialchars(strip_tags(trim($data['skype'])));
		$data['promocode'] = htmlspecialchars(strip_tags(trim(str_replace('-', '', $data['promocode']))));

		// Далее реальная регистрация реального пользователя
		$insertData = array(
			// UID
			'uid' => $uid,

			// Определяем ввод промо-кода (старая версия)
			'promocode' => (!empty($data['promocode']) && strlen($data['promocode']) <= 10) ? $data['promocode'] : null,

			// Присваиваем пользователю персональный промо-код
			'promo_key' => md5($data['first_name'] . microtime()),

			// Проверяем был прищел ли пользователь по промо-коду
			'promo_key_friend' => (!empty($data['promo-key']) && strlen($data['promo-key']) == 32) ? $data['promo-key'] : null,

			// Пол пользователя
			'sex' => $data['sex'],

			// Email
			'email' => $data['email'],

			// Пароль
			//'password' => md5($data['psw']),
			'psw' => md5($data['psw']),

			// Язык на котором пользователь регистрировался
			'lang' => $this->lang->__toString(),

			// Дата регистрации
			'register_dt' => date('Y-m-d H:i:s'),

			// Ключ активации (подтверждения)
			'activation_key' => md5(microtime() . $email . $data['sex'] . rand(0, 10000)),

			// Роматинтические знакомства включены
			'romantic' => 'yes',

			// Новая анкета
			'current_status' => 10,

			// Стандартные свободные дни
			'free_day' => 'a:13:{i:0;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:1;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:2;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:3;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}i:4;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:5;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:6;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:7;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:8;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:9;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:10;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:11;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:12;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"0";}}',

			#'first_name' => $data['first_name'],
			#'last_name'  => $data['last_name'],

			#'birthday'   => $birthday[2].'-'.$birthday[1] .'-'.$birthday[0],
			//'phone'      => preg_replace('!-| |\(|\)!', '', $data['phone']),
			#'city_id'    => (int) $data['city_id'],
			#'skype'      => (!empty($data['skype']))    ? $data['skype']      : null,
			#'education'  => (!empty($data['education'])) ? $data['education'] : null,
			#'company'    => (!empty($data['company']))   ? $data['company']   : null,
			//'position'   => (!empty($data['position']))  ? $data['position']  : null,
			#'source'     => (!empty($data['source']))    ? $data['source']    : null,
			#'vk_url'     => (!empty($data['link_vk']))   ? $data['link_vk']   : null,
			#'fb_url'     => (!empty($data['link_fb']))   ? $data['link_fb']   : null,
			#'ln_url'     => (!empty($data['link_li']))   ? $data['link_li']   : null,
		);

		$this->dataNewUser = $insertData;
#		Sas_Debug::dump($insertData);
#exit;
		$res = $this->db->insert($this->tableProfile, $insertData);

		if ($res != 1) {
			return false;
		}

		$this->dataNewUser['id'] = $this->db->lastInsertId();
		#Sas_Debug::dump($this->dataNewUser);

		Models_Actions::add(42, $this->dataNewUser['id'], null, $this->dataNewUser['id']); // Получена новая заявка на регистрацию

		return true;
	}

	/**
	 * Возвращает данные которые пользователь оставил при регистрации.
	 * @return null | array
	 */
	public function getDataNewUser() {
		return $this->dataNewUser;
	}

	/**
	 * Проверка дубликата email
	 *
	 * @param $email
	 * @return bool true - нет дубликата | false - есть дубликат
	 */
	private function checkDuplicationEmail($email)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableProfile, 'email');
		$select->where('email = ?', $email);
		$select->limit(1);

		$row = $this->db->fetchOne($select);
		#Sas_Debug::dump($row, __METHOD__);
		if ($row == false) {
			return true;
		}
		return false;
	}

	/**
	 * Возвращает ID пользователя по email
	 * @param $email
	 * @return false|array
	 */
	public function getProfileToEmail($email)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableProfile, '*');
		$select->where('email = ?', $email);
		$select->limit(1);

		$row = $this->db->fetchRow($select);
		#Sas_Debug::dump($row, __METHOD__);
		if ($row == false) {
			return false;
		}
		return $row;
	}

	/**
	 * Проверка и валидация регистрационной формы
	 * @param $_data Данные для проверки
	 * @return bool
	 */
	public function isValid($_data) {

		$data = array();
		foreach ($_data as $k => $v) {
			$data[$k] = trim($v);
		}

		#Sas_Debug::dump($data);

		// промокод
		if(!empty($data['promocode'])) {
			//$data['promocode'] = $data['promocode-s'] . $data['promocode-n'];
			$data['promocode'] = htmlspecialchars(strip_tags(trim(str_replace('-', '', $data['promocode']))));
			if(!$this->getPromoCode($data['promocode'])) {
				$this->validForm = false;
				$this->addErrorMsg('promocode', 'Персональный код не корректен.');
			}
		}

		// Пол
		#if(empty($data['sex']) || $data['sex'] != 'female' || $data['sex'] != 'male') {
		if(!preg_match("/^female|male$/u", $data['sex'])) {
			$this->validForm = false;
			$this->addErrorMsg('sex', 'Выберите ваш пол.');
		}

		/*// Имя
		if(!preg_match("/^\p{L}+$/u", $data['first_name'])) {
			$this->validForm = false;
			$this->addErrorMsg('first_name', 'Не корректно заполненно поле.');
		}

		// Фамилия
		if(!preg_match('/^\p{L}+$/u', $data['last_name'])) {
			$this->validForm = false;
			$this->addErrorMsg('last_name', 'Не корректно заполненно поле.');
		}

		// Дата рождения
		if(!preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/', $data['birthday'])) {
			$this->validForm = false;
			$this->addErrorMsg('birthday', 'Дата рождения не корректна.');
		}*/

		// Телефон
		/*$data['phone'] = preg_replace('!-| |\(|\)!', '', $data['phone']);
		if(!preg_match('/^\+[0-9]{11}$/', $data['phone'])) {
			$this->validForm = false;
			$this->addErrorMsg('phone', 'Не корректно заполнен номер телефона.');
		}*/

		// E-Mail
		if(!preg_match('/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]{2,6})$/', $data['email'])) {
			$this->validForm = false;
			$this->addErrorMsg('email', 'Email не корректен.');
		}

		// ID города
		/*if(!is_numeric($data['city_id'])) {
			$this->validForm = false;
			$this->addErrorMsg('city_id', 'Город обязателен для заполнения');
		}*/

		// Пароль
		if(strlen($data['psw']) < 6) {
			$this->validForm = false;
				$this->addErrorMsg('pswSmall', 'Пароль не должен быть менее 6 символов.');
		}
		if($data['psw'] != $data['psw1']) {
			$this->validForm = false;
			$this->addErrorMsg('psw', 'Пароли не совпадают.');
		}

		// Ключ пользовательского соглашения
		if(empty($data['agree'])) {
			$this->validForm = false;
			$this->addErrorMsg('agree', 'Правила клуба не приняты.');
		}

		return $this->validForm;
	}

	public function getErrorMsg() {
		return $this->errorMsg;
	}

	private function addErrorMsg($key, $error) {
		$this->errorMsg[$key] = $error;
	}

	private function getPromoCode($code) {
		$select = new Zend_Db_Select($this->db);
		$select->from('promocode', '*');
		$select->where('active = 1');
		$select->where('pcode = ?', $code);
		$select->limit(1);
		$row = $this->db->fetchRow($select);
#Sas_Debug::dump($select->__toString());
#Sas_Debug::dump($row);
		return $row;
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
			->from($this->tableProfile, 'uid')
			->where('uid = ?', $uid)
			->limit(1);
		$res = $this->db->fetchOne($select);
		if($res) {
			$this->generatorUid($numAlpha);
		} else {
			return $uid;
		}
	}

	/**
	 * Проверка ключа активации нового пользоввтеля
	 *
	 * @param $key
	 * @return array Возвращаем профиль при верном ключе | FALSE ключ не найден
	 */
	public function checkActivationKey($key) {
		// Проверяем уникальность uid
		$select = $this->db->select()
			->from($this->tableProfile, '*')
			->where('activation_key = ?', $key)
			->where('current_status = 10') // 10 - это первичный статус пользователя в системе
			->limit(1);

		$res = $this->db->fetchRow($select);

		return ($res) ? $res : false;
	}

	/**
	 * Активация нового пользователя (подтверждение через email по ссылке с ключем)
	 * @param $profile
	 */
	public function activationNewUser($profile)
	{
		$data = array(
			'current_status' => 50,
			'activation_dt' => date('Y-m-d H:i:s')
		);

		$this->db->update($this->tableProfile, $data, $this->db->quoteInto('id = ?', $profile['id']));
	}
}
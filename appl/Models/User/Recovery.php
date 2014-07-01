<?php

class Models_User_Recovery
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $tblProfile = 'users';

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();
	}

	/**
	 * Проверяет наличие email и при наличии возвращает профиль в виде массива
	 * @param $email
	 * @return array
	 */
	public function isEmail($email) {
		$select = $this->db->select()
			->from(array('u'=>$this->tblProfile), '*')
			->where('email = ?', $email)
			->limit(1);

		$profile = $this->db->fetchRow($select);

		return $profile;
	}

	/**
	 * Возвращает дату и время последнего обращения за восстановлением пароля
	 * @param $email
	 * @return string
	 */
	public function getDtRecoveryPsw($email) {
		$select = $this->db->select()
			->from(array('u'=>$this->tblProfile), 'recovery_psw_dt')
			->where('email = ?', $email)
			->limit(1);

		$dt = $this->db->fetchOne($select);

		return $dt;
	}

	/**
	 * Отмечает отправку письма с инструкциями для восстановления пароля текущей датой и временем
	 * @param $userId
	 * @return int
	 */
	public function sendNewDtSendRecoveryPsw($userId) {
		return $this->db->update($this->tblProfile, array('recovery_psw_dt'=>CURRENT_DATETIME), 'id = ' . (int)$userId);
	}

	/**
	 * Возвраащет профиль по ключу
	 * @param $key
	 * @return string
	 */
	public function getKey($key) {
		$select = $this->db->select()
			->from($this->tblProfile, '*')
			->where('activation_key = ?', $key)
			->limit(1);

		return $this->db->fetchRow($select);
	}

	/**
	 * Проверяет валидность ключа по его дате
	 * @param $dateKey
	 * @return bool
	 */
	public function isValidDateKey($dateKey) {
		$check = false;

		$Date = new DateTime($dateKey);
		$Date->modify('+ 1 Hour');

		// Чтобы пройти проверку, текущая дата должна быть меньше чем дата ключа + 1
		if(CURRENT_UNIXTIME < $Date->getTimestamp()) {
			$check = true;
		}

		return $check;
	}

	/**
	 * Устанавливает новый пароль для пользователя
	 * @param $userId
	 * @param $psw
	 * @return int
	 */
	public function setNewPsw($userId, $psw) {
		$Date = new DateTime();
		$Date->modify('- 1 Hour'); // Скидываем дату на час назад, чтобы с ключём из этого письма было уже нельзя пройти проверке
		$data = array(
			'psw'=>md5($psw),
			'recovery_psw_dt'=> $Date->format('Y-m-d H:i:s')
		);
		return $this->db->update($this->tblProfile, $data, 'id = ' . (int)$userId);
	}
}

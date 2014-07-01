<?php

/**
 * Модель шаблонов сообщений пользователям
 *
 * Обеспечивает правильность формирования текстов сообщений и обеспечивает их фактическую отправку.
 */
class Models_TemplatesMessage
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $tableEmail = 'templates_email';
	private $columnEmail = array();
	private $tableSms   = 'templates_sms';
	private $columnSms = array();

	private $robotEmail     = 'robot@onthelist.ru';//EMAIL_ROBOT;
	private $robotNameEmail = 'OnTheList';
	private $robotNameSms   = 'OnTheList';

	#private $tplId      = null;
	private $tplKey    = null;
	#private $tplName    = null;
	#private $tplSubject = null;
	#private $tplText    = null;

	private $profileTo  = null;
	private $firstName  = null;
	private $lastName   = null;
	private $sex        = null;
	private $email      = null;
	private $phone      = null;
	private $phoneCheck = null;

	private $msgSetting;

	private $dataReplace = array();

	public function __construct($profileToArray, $tplKey, $msgSetting = null) {
		$this->db = Zend_Registry::get('db');

//		$this->lang = Zend_Controller_Front::getInstance()
//			->getPlugin('Sas_Controller_Plugin_Language')
//			->getLocale();
		$this->lang = $profileToArray['lang'];

		$this->columnEmail = array('id', 'tpl_key', 'sex', 'name',
			'subject' => 'subject_' . $this->lang,
			'text' => 'text_' . $this->lang
		);
		$this->columnSms = array('id', 'tpl_key', 'sex', 'name',
			'text' => 'text_' . $this->lang
		);

		$this->msgSetting = $msgSetting;

		// Получатель
		$this->profileTo  = $profileToArray;
		$this->firstName  = $profileToArray['first_name'];
		$this->lastName   = $profileToArray['last_name'];
		$this->sex        = $profileToArray['sex'];
		$this->email      = $profileToArray['email'];
		$this->phoneCheck = $profileToArray['phone_check'];
		$this->phone      = preg_replace("/[^0-9]/", '', $profileToArray['phone']);

		$this->tplKey = $tplKey;
	}

	/**
	 * Добавляет данные для разбора в шаблоне
	 * @param $key Ключ поиска
	 * @param $value Значение для замены
	 */
	public function addDataReplace($key, $value)
	{
		$this->dataReplace[$key] = $value;
	}


	public function send()
	{
		#Sas_Debug::dump($this->profileTo);
		// Проверяем по каким каналам слать сообщение пользователю
		if($this->profileTo[$this->msgSetting.'_email'] == 'yes' || !isset($this->profileTo[$this->msgSetting.'_email']))
		{
			#Sas_Debug::dump('email', __METHOD__);
			$this->sendEmail();
		}

		if($this->profileTo[$this->msgSetting.'_sms'] == 'yes')
		{
			#Sas_Debug::dump('sms', __METHOD__);
			$this->sendSms();
		}
	}

	/**
	 * Возвращает текущий шаблон
	 * @param string $type email|sms
	 * @return array
	 */
	private function getTpl($type)
	{
		$select = new Zend_Db_Select($this->db);

		if($type == 'email') {
			$select->from($this->tableEmail, $this->columnEmail);
		}
		if($type == 'sms') {
			$select->from($this->tableSms, $this->columnSms);
		}

		$select->where('tpl_key = ?', $this->tplKey);

		// Работаем с определением пола для получения верного шаблона
		$mySex = Models_User_Model::getMySex();
		if($mySex == $this->sex) {
			$sexTpl = ($this->sex == 'male') ? 'female' : 'male'; // 2 одинаковых пола = ОБРАТНЫЙ шаблон!
		} else {
			$sexTpl = $this->sex;
		}

		$select->where('sex = ? OR sex = "neutral"', $sexTpl);

		$select->limit(1);

		#Sas_Debug::dump($select->__toString(), 'SELECT ' . __METHOD__);
		$row = $this->db->fetchRow($select);
		#Sas_Debug::dump($row, __METHOD__);

		return $row;
	}

	/**
	 * Возвращает текст сообщения с замещениями в шаблоне
	 * @param $text
	 * @return mixed
	 */
	private function getParseTemplate($text)
	{
		$data = array_merge($this->profileTo, $this->dataReplace);

		foreach($data as $key => $value) {
			$search[]  = '%'.$key.'%';
			$replace[] = $value;
		}
		$text = str_replace($search, $replace, $text);
		return $text;
	}

	/**
	 * Отправляем пользователю email уведомление
	 *
	 * @return bool
	 * @throws Sas_Exception
	 */
	private function sendEmail()
	{
		// Получаем нужный шаблон для отправки письма
		$tpl = $this->getTpl('email');
		$text = $this->getParseTemplate($tpl['text']);
		//Sas_Debug::dump($text);

		try {
			/*$config = array('ssl' => 'tls', 'port' => 587, 'auth' => 'login',
				'username' => 'robot@onthelist.ru',
				'password' => '1mar0b0tMan');
			$transport = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $config);*/

			$mail = new Zend_Mail('UTF-8');
			$mail->setSubject($tpl['subject']);
			$mail->setBodyHtml($text);
			$mail->setFrom($this->robotEmail, $this->robotNameEmail);
			if(empty($this->firstName) && empty($this->lastName)) {
				$mail->addTo($this->email);
			} else {
				$mail->addTo($this->email, $this->firstName . ' ' . $this->lastName);
			}

			#$log = new Sas_Log('mail.txt');
			#$log->write($text);
			#Sas_Debug::dump($mail->getBodyHtml());

			//$mail->send($transport);
			$mail->send();
			#Sas_Debug::dump($mail->getBodyText(), __METHOD__);
		} catch (Zend_Mail_Exception $e) {
			throw new Sas_Exception($e->getMessage());
		}

		return true;
	}

	/**
	 * Отправляет смс
	 */
	private function sendSms()
	{
		// проверяем подтвержденность телефона
		if($this->phoneCheck == 'no') {
			return false;
		}

		// Получаем нужный шаблон для отправки письма
		$tpl = $this->getTpl('sms');
		$text = $this->getParseTemplate($tpl['text']);
		#Sas_Debug::dump($text, 'TEXT SMS');

		try {
			$sms = new Zelenin_SmsRu('c1a21b64-3825-7674-d968-bee81a27d285');
			#Sas_Debug::dump($sms, 'SMS '.__METHOD__);
			#$send = $sms->sms_send($this->phone, $text, 'OnTheList', time(), false, true);

			// Ограничения по времени отправки смс
			$currentHours = date('H'); // Текущий час

			// Это работает с 21:00 до 24:00 и переносит дату отправки на обед следующего дня, на 12 часов рандом минут
			if($currentHours >= 21) {
				$Date = new DateTime(date('Y-m-d 12:'.sprintf('%02d', rand(1, 59)).':00'));
				$Date->modify('+1 day');
				$send = $sms->sms_send($this->phone, $text, 'OnTheList', $Date->getTimestamp());
			}

			// Это работает с 00:00 до 11:00 и переносит время отправки на 12 часов и рандом минут текущего дня
			if($currentHours >= 0 && $currentHours <= 11) {
				$Date = new DateTime(date('Y-m-d 12:'.sprintf('%02d', rand(1, 59)).':00'));
				$send = $sms->sms_send($this->phone, $text, 'OnTheList', $Date->getTimestamp());
			}

			// Отправка реал-тайм работает с 12:00 до 20:59
			if($currentHours > 11 && $currentHours < 21) {
				$send = $sms->sms_send($this->phone, $text, 'OnTheList');
			}

			#Sas_Debug::dump($sms->sms_status($this->phone), 'SEND '.__METHOD__);
			#Sas_Debug::dump($send, 'SEND '.__METHOD__);

			if ($send['code'] != 100) {
				throw new Sas_Models_Exception('Ошибка при отправке смс сообщения.');
				// TODO: записать в лог причину ошибки
			}

		} catch (Sas_Models_Exception $e) {
			return $e->getMessage();
		}

		return true;
	}

}
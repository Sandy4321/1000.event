<?php

/**
 * Класс для работы с сессиями используя БД.
 * 
 * @category Sas
 * @package Sas_Session
 * @subpackage Sas_Session_Db
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2013 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 1.0
 */
class Sas_Session_Db
{
    /**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	
	/**
	 * Singleton instance
	 *
	 * @var Sas_Session_Db
	 */
	protected static $_instance = null;
	
	/**
	 * Таблица в бд для хранения сессий
	 * @var string
	 */
	private $tbl = 'sas_sessions_user';
	
	/**
	 * Название сессии
	 * @var string
	 */
	private $sessionName = 'SAS_SID';
	
	/**
	 * Идентификатор сессии
	 * @var string|null
	 */
	private $sessionId = null;
	
	/**
	 * Данные сессии.
	 * @var array
	 */
	private $sessionData = array();
	
	/**
	 * Время жизни куки
	 * @var int
	 */
	private $cookieExpire = 0;

	/**
	 * Запомнить меня
	 * @var int 0|1
	 */
	private $rememberMe = 0;

	private $userId = 0;
	
	public function __construct(Zend_Db_Adapter_Abstract $db, $cookieExpire = 0)
	{
	    $this->db = $db;
	     
	    // Проверка подключения к бд
	    if (!$this->db->isConnected()) {
	    	throw new Sas_Session_Exception('Session - No db connect.');
	    }

		$this->setCfgCookieExpire($cookieExpire);

		$this->setSessionId($_COOKIE[$this->sessionName]);

		self::$_instance = $this;
	}

	/**
	 * Singleton instance
	 *
	 * @return null|Sas_Session_Db
	 * @throws Sas_Session_Exception
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			throw new Sas_Session_Exception('Session - No init');
		}

		return self::$_instance;
	}

	/**
	 * Старт сессии ТОЛЬКО для пользователя с наличием сессионной куки или прошедшего авторизицию
	 *
	 * @param null $userId
	 * @throws Sas_Session_Exception
	 */
	public function start($userId = null)
	{
		$this->userId = (int)$userId;

		// Если куки нет - создаем новую
		if(!$this->isSessionCookie()) {
			$this->setSessionId($this->generateSessionId());

			// Сохраняем новую сессию в бд
			if($this->saveNewSessionDb()) {
				$this->setCookie();
				#Sas_Debug::dump('New Cookie');
			} else {
				$this->_destroyCookie();
				throw new Sas_Session_Exception('Session - no data', '1404');
			}

		} else {
			// Если есть "Запомнить меня" - продлеваем и её
			if($this->isRememberMe()) {
				$this->setRememberMe();
			}

			// если кука есть - продляем её
			$this->setCookie();

			$sesDd = $this->getSessionDb();
			$this->sessionData = unserialize($sesDd['session_data']);
			$this->userId = $sesDd['user_id'];
			$this->addData('user_id', $this->userId);

			#Sas_Debug::dump('Old Cookie... more... time...');
		}
	}

	/**
	 * Возвращает данные сессии из бд.
	 *
	 * @return array
	 * @throws Sas_Session_Exception
	 */
	private function getSessionDb()
	{
		if(!$this->isSessionId()) {
			throw new Sas_Session_Exception('No Session ID');
		}

		$select = $this->db->select()
			->from($this->tbl, '*')
			->where('id = ?', $this->getSessionId())
			->limit(1);

		$row = $this->db->fetchRow($select);
		if(!$row) {
			throw new Sas_Session_Exception('No Session data');
		}

		return $row;
	}

	/**
	 * Сохраняет новую сессию в бд.
	 *
	 * @return bool
	 */
	private function saveNewSessionDb()
	{
		if($this->getUserId() == 0) {
			return false;
		}

		// Пробуем получить старую сессию по Id пользователя
		if($sid = $this->getSessionDbUserId()) {

			// Обновляем сессию
			$this->setSessionId($sid);
			$this->updateSession();
		} else {

			// Это полностью новая сессия
			$data = array(
				'id' => $this->getSessionId(),
				'user_id' => $this->getUserId(),
				'session_data' => serialize($this->sessionData),
				'remember'  => $this->getRememberMe(),
				'dt_create' => $this->getDtSQL(),
				'dt_last_access' => $this->getDtSQL()
			);
			$this->db->insert($this->tbl, $data);
		}

		return true;
	}

	/**
	 * Обновляет информацию сессии в бд.
	 */
	private function updateSession()
	{
		$data = array(
			'session_data' => serialize($this->sessionData),
			'remember'  => $this->getRememberMe(),
			'dt_last_access' => $this->getDtSQL()
		);
		$this->db->update($this->tbl, $data, $this->db->quoteInto('id = ?', $this->getSessionId()));
		#Sas_Debug::dump($data, 'Sessuon update');
	}

	/**
	 * Возвращает информацию о сессии из бд используя для поиска данных $userId.
	 *
	 * @return array|bool
	 */
	private function getSessionDbUserId()
	{
		if($this->getUserId() == 0) {
			return false;
		}

		$select = $this->db->select()
			->from($this->tbl, '*')
			->where('user_id = ?', $this->getUserId())
			->limit(1);

		$row = $this->db->fetchRow($select);

		if(!$row) {
			return false;
		}

		return $row;
	}

	/**
	 * Запомнить меня
	 */
	public function setRememberMe()
	{
		$this->rememberMe = 1;
		setcookie('rm', 1, $this->getCookieExpire(), '/', $_SERVER['HTTP_HOST']);
	}

	/**
	 * Проверяет выбрал ли пользователь опцию запомнить меня.
	 *
	 * @return bool
	 */
	private function isRememberMe()
	{
		return ($this->rememberMe == 1 || $_COOKIE['rm'] == 1) ? true : false;
	}

	/**
	 * Возвращает настройку "Запомнить меня".
	 *
	 * @return int
	 */
	private function getRememberMe()
	{
		return ($this->rememberMe == 1 || $_COOKIE['rm'] == 1) ? 1 : 0;
	}

	/**
	 * Задаёт ID текущей сессии
	 * @param $sId
	 */
	private function setSessionId($sId) {
		$this->sessionId = $sId;
	}

	/**
	 * Сохраняет сессионные данные в бд.
	 */
	public function saveSessionData()
	{
	    if(!$this->isSessionId()) {
			throw new Sas_Session_Exception('No Session ID');
		}

		$this->updateSession();
	}

	/**
	 * Уничтожает сессию.
	 */
	public function destroy()
	{
	    if($this->isSessionId()) {
	        // Удаляем сессию в бд
	        $this->db->delete($this->tbl, $this->db->quoteInto('id = ?', $this->getSessionId()));
			#Sas_Debug::dump($this->sessionId, 'delete session db');
	    }

		$this->_destroyCookie();
	}

	/**
	 * Уничтожает сессионные куки.
	 */
	private function _destroyCookie()
	{
		$this->sessionId = null;
		$this->rememberMe = null;
		unset($_COOKIE[$this->sessionName]);
		unset($_COOKIE['rm']);
		setcookie($this->sessionName, '', 0, '/', $_SERVER['HTTP_HOST']);
		setcookie('rm', '', 0, '/', $_SERVER['HTTP_HOST']);
	}
	
	/**
	 * Проверяет существование ID текущей сессии.
	 * 
	 *  @return string
	 */

	private function isSessionId()
	{
		return (is_null($this->sessionId)) ? false : true;
	}

	/**
	 * Генерирует новый идентификатор сессии.
	 * @return string
	 */
	private function generateSessionId()
	{
	    return md5(microtime() . 'Sas_Session_Db' . time());
	}

	/**
	 * Возвращает ID  сессии.
	 *
	 * @return string
	 */
	public function getSessionId()
	{
		/*if (!$this->isSessionCookie()) {
			$this->sessionId = $this->generateSessionId();
		} else {
			$this->sessionId = $_COOKIE[$this->sessionName];
		}*/

		return $this->sessionId;
	}
	
	/**
	 * Устанавливает сессионную куку в браузер пользователя.
	 */
	private function setCookie()
	{
		setcookie($this->sessionName, $this->getSessionId(), $this->getCookieExpire(), '/', $_SERVER['HTTP_HOST']);
	}

	/**
	 * Возвраащет дату истечения куки.
	 * @return int
	 */
	private function getCookieExpire()
	{
		if(!$this->isRememberMe()) {
			$this->cookieExpire = 0;
		}

		return $this->cookieExpire;
	}
	
	/**
	 * Проверяет и возвращает ID сессии из пользовательской куки.
	 * 
	 * @return string|boolean
	 */
	public function isSessionCookie()
	{
	    if(isset($_COOKIE[$this->sessionName])) {
			$this->setSessionId($_COOKIE[$this->sessionName]);
	        return $_COOKIE[$this->sessionName];
	    } else {
	        return false;
	    }
	}
	
	/**
	 * Устанавливает название таблицы для хранения сессии.
	 * По умолчанию используется таблица sas_sessions
	 * 
	 * @param string $tblName
	 * @return Sas_Session_Db
	 */
	public function setCfgTbl($tblName)
	{
	    $this->tbl = $tblName;
	    return $this;
	}
	
	/**
	 * Устанавливает время жизни куки в браузере (в минутах).
	 * По умолчанию время жизни куки = 0 (до закрытия окна браузера)
	 *
	 * @param int $minute
	 * @return Sas_Session_Db
	 */
	public function setCfgCookieExpire($minute = 0)
	{
		$this->cookieExpire = $this->getTime() + 60 * $minute;
		return $this;
	}
	
	/**
	 * Добавляет данные в сессию для хранения.
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return Sas_Session_Db
	 */
	public function addData($name, $value)
	{
	    $this->sessionData[$name] = $value;
	    return $this;
	}
	
	/**
	 * Возвращает данные из пользовательской сессии.
	 * 
	 * @param string $name
	 * @return NULL|mixed
	 */
	public function getData($name)
	{
	    if(!isset($this->sessionData[$name])) {
	        return null;
	    }
	    
	    return $this->sessionData[$name];
	}

	/**
	 * Очищает переменную сессии.
	 */
	public function clearData($name)
	{
		unset($this->sessionData[$name]);
	}

	/**
	 * Очищает ВСЕ данные сессии.
	 */
	public function clearDataAll()
	{
		$this->sessionData = array();
	}

	/**
	 * Возвращает все данные из пользовательской сессии.
	 *
	 * @return array
	 */
	public function getDataAll()
	{
		return $this->sessionData;
	}

	/**
	 * Возвращает ID ползователя сессии.
	 * @return int
	 */
	public function getUserId()
	{
		return (int)$this->userId;
	}

	/**
	 * Текущее время UNIX
	 * @var int
	 */
	private $curTime = 0;

	/**
	 * Возвращает текущее время UNIX
	 *
	 * @return int
	 */
	private function getTime() {
		return $this->curTime = ($this->curTime == 0) ? time() : $this->curTime;
	}

	/**
	 * Возвращает текущее время в SQL формате: Y-m-d H:i:s
	 *
	 * @return string
	 */
	private function getDtSQL() {
		return date('Y-m-d H:i:s', $this->getTime());
	}

	/**
	 * Перехватить пользовательскую сессию.
	 *
	 * @param $sessionId
	 * @return bool
	 */
	public function hackOpen($sessionId)
	{
		$this->setSessionId($sessionId);

		try {
			// Проверить наличие сессии
			$this->getSessionDb();

			// Перехватить сессию
			$this->setCookie();
		} catch (Sas_Session_Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * Закрытие (у себя) перехваченной сессии
	 */
	public function hackClose()
	{
		$this->_destroyCookie();
	}

	/**
	 * Деструктор класс, при завершении (при наличии открытой сессии) пробует записать все её данные в бд.
	 */
	public function __destruct() {
		if($this->isSessionId()) {
			$this->updateSession();
			#Sas_Debug::dump(__METHOD__, 'UPDATE!');
		} else {
			#Sas_Debug::dump(__METHOD__, 'no update');
		}
	}
}
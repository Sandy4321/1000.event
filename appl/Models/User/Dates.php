<?php
/**
 * Модель с данными о свиданиях.
 *
 * @author sasha
 */
class Models_User_Dates
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	private $myId = null;

	private $tableDates = 'user_dates';
	private $tableProfile = 'users';
	private $tablePlaces  = 'places';

	private $columnProfileStandart = array(
		'userId'=>'id',
		'uid',
		'firstName'=>'first_name',
		'lastName'=>'last_name',
		'sex',
		'birthday',
		'height',
		'children',
		'smoking',
		'phone',
		'balance',
		'balance_bonus',
		'online_last_dt'
	);

	private $columnProfileImg = array('img' => 'CONCAT("/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : $myId;
		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	/**
	 * Возвращает свидания требующие отчёта
	 * @return array
	 */
	public function getDatesToReport()
	{
		$select = $this->db->select();
		$select->from('user_dates_reports', 'dates_id');
		$select->where('user_id = ?', $this->myId);
		$rows = $this->db->fetchPairs($select);
		$rows = array_keys($rows);
		#Sas_Debug::dump($rows);
		if(empty($rows)) {
			return null;
		}

		$delId = implode(',', $rows);

		#Sas_Debug::dump($delId);

		$select->reset();
		$select->from(array('d'=>$this->tableDates), array('datesId'=>'id', '*'));
		$select->where('d.id NOT IN ('.$delId.')');
		$select->where('d.`invitee_id` = ' . $this->myId . ' OR d.`inviter_id` = ' . $this->myId);
		$select->where('d.status = ?', 'yes');
		$select->where('d.selected_datetime < NOW()');
		$select->order('d.date_create DESC');

		$select->join(array('profile'=>$this->tableProfile), '(invitee_id = profile.id OR inviter_id = profile.id) AND profile.id != '.$this->myId, $this->columnProfileStandart);
		$select->columns($this->columnProfileImg);

		#Sas_Debug::dump($select->__toString());

		$rows = $this->db->fetchAll($select);

		#Sas_Debug::dump($rows);

		return $rows;
	}

	/**
	 * Возвращает отправленные пользоватеЛЕМ приглашения на свидания.
	 *
	 * @return array
	 */
	public function getDatesSend()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableDates, '*');
		$select->join(array('profile'=>$this->tableProfile), 'invitee_id = profile.id', $this->columnProfileStandart);
		$select->columns($this->columnProfileImg);
		$select->where('inviter_id = ?', $this->myId);
		//$select->where($this->tableDates.'.status = ?', 'send');
		$select->where($this->tableDates.'.status = "send" OR ' . $this->tableDates.'.status = "read"');
		$select->where($this->tableDates.'.archive = ?', 'no');
		$select->order('date_create DESC');

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Возвращает отправленные пользоватеЛЮ приглашения на свидания.
	 *
	 * @return array
	 */
	public function getDatesSendMy()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableDates, '*');
		$select->join(array('profile'=>$this->tableProfile), 'inviter_id = profile.id', array('userId'=>'id', 'uid','first_name', 'current_status', 'club_card_dt'));
		$select->columns($this->columnProfileImg);
		$select->where('invitee_id = ?', $this->myId);
		//$select->where($this->tableDates.'.status = ?', 'send');
		$select->where($this->tableDates.'.status = "send" OR ' . $this->tableDates.'.status = "read"');
		$select->order('date_create DESC');

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Возвращает последний статус свидания
	 * @param $myId
	 * @param $partnerId
	 * @return string
	 */
	public function getLastStatus($myId, $partnerId) {
		$select = $this->db->select();
		$select->from($this->tableDates, 'status')
			->where('(invitee_id = '.$myId.' AND inviter_id = '.$partnerId.') OR (invitee_id = '.$partnerId.' AND inviter_id = '.$myId.')')
			->order('date_create DESC')
			->limit(1);
		return $this->db->fetchOne($select);
	}

	/**
	 * Возвращает полную информацию по конкретному свиданию (для ajax).
	 *
	 * @param int $id
	 * @return array
	 */
	public function getDatesId($id)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableDates, '*');
		$select->where('id = ?', $id);
		$select->limit(1);

		$row = $this->db->fetchRow($select);

		// Получаем места свиданий
		$row['places_id'] = unserialize($row['places_id']);
		#Sas_Debug::dump($row['places_id']);
		if (is_array($row['places_id']))
		{
			$select->reset();
			$select->from($this->tablePlaces, array('id', 'name', 'descr_short', 'metro', 'address'));
			$where = '`id` IN (';
			for ($i=0, $max = count($row['places_id']); $i < $max; $i++) {
				if ($i == $max - 1) {
					$where .= $row['places_id'][$i];
				} else {
					$where .= $row['places_id'][$i] . ', ';
				}
			}
			$where .= ')';
			$select->where($where);
			$select->order('category_id ASC');
			#Sas_Debug::dump($select->__toString());
			//exit;
			$row['places'] = $this->db->fetchAll($select);
		}

		$row['dates_day_time'] = unserialize($row['dates_day_time']);
		if (is_array($row['dates_day_time'])) {
			sort($row['dates_day_time']);
		}

		// Меняем статус записи о свидании на ПРОЧИТАНО
		#Sas_Debug::dump($row);
		if($row['invitee_id'] == $this->myId) {
			$this->db->update($this->tableDates, array('status'=>'read'), $this->db->quoteInto('id = ?', $id));
		}

		return $row;
	}

	/**
	 * Возвращает суммы необходимые для возврата за отмену свидания и ID пользователя которому нужно вернуть караты
	 * @param $id ID свидания
	 * @return array
	 */
	public function getMoneyBack($id)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableDates, array('moneyBackUserId'=>'inviter_id', 'real_karat', 'bonus_karat'));
		$select->where('id = ?', $id);
		$select->limit(1);

		return $this->db->fetchRow($select);
	}

	/**
	 * Возвращает принятые пользователем приглашения на свидания.
	 *
	 */
	public function getDatesYes()
	{
		// TODO: отптимизировать запрос! см. get History
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableDates, '*');
		#$select->join(array('t1'=>$this->tableProfile), 'invitee_id = t1.id', array('userId_1'=>'id', 'first_name_1'=>'first_name', 'sex_1'=>'sex', 'birthday_1'=>'birthday', 'phone_1'=>'phone'));
		#$select->join(array('t2'=>$this->tableProfile), 'inviter_id = t2.id', array('userId_2'=>'id', 'first_name_2'=>'first_name', 'sex_2'=>'sex', 'birthday_2'=>'birthday', 'phone_2'=>'phone'));
		$select->join(array('profile'=>$this->tableProfile), '(invitee_id = profile.id OR inviter_id = profile.id) AND profile.id != '.$this->myId, $this->columnProfileStandart);
		$select->columns($this->columnProfileImg);
		$select->join($this->tablePlaces, $this->tablePlaces.'.id = selected_place_id', array('placeId'=>'id', 'placesName'=>'name', 'placesDescrShort'=>'descr_short',
																							  'placesDescr'=>'descr', 'placesImg'=>'img', 'placesMetro'=>'metro',
																							  'placesAddress'=>'address', 'placesPhone'=>'phone',
																							  'placesSite'=>'site'));
		$select->where('`invitee_id` = ' . $this->myId . ' OR `inviter_id` = ' . $this->myId);
		$select->where($this->tableDates.'.status = ?', 'yes');
		$select->where('selected_datetime > ?', date('Y-m-d H:i:s'));
		$select->order('date_create DESC');

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Проверяет наличие уже назначенного свидания с конкретным пользователем
	 * @param $user_id
	 * @return int
	 */
	public function checkDatesYesPeople($user_id)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableDates, 'COUNT(*)');
		#$select->from($this->tableDates, '*');
		$select->where(
			'(`invitee_id` = ' . $this->myId . ' AND `inviter_id` = ' . $user_id.') OR '.
				'(`invitee_id` = ' . $user_id . ' AND `inviter_id` = ' . $this->myId.')'
		);
		/*$select->where(
			$this->tableDates.'.status = "yes" OR '.$this->tableDates.'.status = "send"'
		);*/
		$select->where($this->tableDates.'.status = ?', 'send');
		#$select->where('selected_datetime < ?', date('Y-m-d H:i:s'));
		#Sas_Debug::dump($select->__toString());
		#$row = (int) $this->db->fetchAll($select);
		$row = (int) $this->db->fetchOne($select);
		#Sas_Debug::dump($row);
		return $row;
	}

	/**
	 * Обмен контактами (ajax)
	 * @param int $id ID свидания
	 * @param int $userId ID пользователя с которым меняемся контактами
	 * @return array
	 */
	public function exchangeContact($id, $userId)
	{
		// Меняем статус свидания
		$this->db->update($this->tableDates, array('status' => 'contact'), 'id = ' . (int) $id);

		// Возвращаем профиль пользователя с которым меняемся
		$ModelProfile = new Models_User_Profile();
		return $ModelProfile->getProfile($userId);
	}

	/**
	 * Возвращает пользователей с которыми я менялся контактами
	 * @return array
	 */
	public function getContactExchange()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableDates, '*');
		$select->join(array('profile'=>$this->tableProfile), '(invitee_id = profile.id OR inviter_id = profile.id) AND profile.id != '.$this->myId, $this->columnProfileStandart);
		$select->where('`invitee_id` = ' . $this->myId . ' OR `inviter_id` = ' . $this->myId);
		$select->where($this->tableDates.'.status = ?', 'contact');
		$select->where($this->tableDates.'.archive = ?', 'no');
		$select->order('date_create DESC');

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Возвращает историю свиданий
	 *
	 * @return array
	 */
	public function getHistory($status = null)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableDates, '*');
		$select->columns($this->columnProfileImg);
		$select->join(array('profile'=>$this->tableProfile), '(invitee_id = profile.id OR inviter_id = profile.id) AND profile.id != '.$this->myId, $this->columnProfileStandart);
		$select->where('`invitee_id` = ' . $this->myId . ' OR `inviter_id` = ' . $this->myId);
		$select->where('archive = ? OR '.$this->tableDates.'.date_create < ' . $this->db->quote(date('Y-m-d')), 'yes');

		if($status == 'yes') {
			$select->where($this->tableDates.'.`status` = ?', $status);
		}

		//$select->orWhere('date_create < ?', date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 7, date('Y'))));
		$select->order($this->tableDates.'.date_create DESC');
		//Sas_Debug::dump($select->__toString());
		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Возвращает планы свиданий
	 *
	 * @param int $userId
	 * @param string $status Возможные варианты: send|read|yes|no
	 * @return array
	 */
	public function getDatesPlan($userId = null, $status = 'yes')
	{
		$userId = (is_null($userId)) ? $this->myId : $userId;

		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableDates);
		$select->where('`invitee_id` = ' . (int) $userId . ' OR `inviter_id` = ' . (int) $userId);
		$select->where('selected_datetime > ?', date('Y-m-d H:i:s'));
		$select->where('status = ?', $status);

		#Sas_Debug::dump($select->__toString());

		$rows = $this->db->fetchAll($select);

		#Sas_Debug::dump($rows);

		return $rows;
	}

	/**
	 * Возвращает планы свиданий для Dashboard
	 *
	 * @return array
	 */
	public function getDatesPlan2Dashboard()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from(array('dates'=>$this->tableDates), array('id', 'selected_datetime', 'selected_place_id'));
		$select->join(array('profile'=>$this->tableProfile), '(dates.invitee_id = profile.id OR dates.inviter_id = profile.id) AND profile.id != '.$this->myId, $this->columnProfileStandart);
		$select->columns($this->columnProfileImg);
		$select->join(array('p'=>$this->tablePlaces), 'p.id = dates.selected_place_id', array('placeName'=>'name', 'placeAddress'=>'address', 'placeMetro'=>'metro', 'placePhone'=>'phone'));
		$select->where('dates.`invitee_id` = ' . $this->myId . ' OR dates.`inviter_id` = ' . $this->myId);
		$select->where('YEAR(dates.selected_datetime) = ?', date('Y'));
		$select->where('MONTH(dates.selected_datetime) = ?', date('m'));
		$select->where('DAY(dates.selected_datetime) = ?', date('d'));
		$select->where('dates.status = ?', 'yes');
		$select->order('dates.selected_datetime ASC');

		#Sas_Debug::dump($select->__toString());

		$rows = $this->db->fetchAll($select);

		#Sas_Debug::dump($rows);

		return $rows;
	}

	/**
	 * Возвращает чистый массив с датами свиданий
	 * @param array $planData массив данных с выборкой по свиданиям (например результат от getDatesPlan() ).
	 * @return array
	 */
	public function getDatePlan($planData)
	{
		$res = array();
		for ($i = 0; $i < count($planData); $i++) {
			$res[] = substr($planData[$i]['selected_datetime'], 0, -9);
		}
		#Sas_Debug::dump($res);
		return $res;
	}

	/**
	 * Сохраняет отправленное приглашение на свидание.
	 *
	 * @param $data
	 * @return int lastInsertId
	 */
	public function saveInvite($data)
	{
		$insertData = array(
			'invitee_id'     => $data['user_id'],
			'inviter_id'     => $this->myId,
			'places_id'      => serialize($data['places_id']),
			'dates_day_time' => serialize($data['dates']),
			'status'         => 'send',
			'date_create'    => date('Y-m-d H:i:s'),
			'real_karat'     => $data['real_karat'],
			'bonus_karat'    => $data['bonus_karat'],
		);

		#Sas_Debug::dump($insertData, __METHOD__);
		$this->db->insert($this->tableDates, $insertData);
		$insertId = (int) $this->db->lastInsertId();

		Models_Actions::add(1, $this->myId, $data['user_id'], $insertId); // Отправлено приглашение на свидание

		return $insertId;
	}

	/**
	 * Сохраняет информацию о подтверждённом свидании.
	 *
	 * @param $data
	 */
	public function saveInviteYes($data)
	{
		$updateData = array(
			'status'            => 'yes',
			'selected_place_id' => $data['place_id'],
			'selected_datetime' => $data['date_time']
		);
		$where = 'id = ' . (int) $data['id'];
		$this->db->update($this->tableDates, $updateData, $where);
		Models_Actions::add(2, $this->myId, null, $data['id']); // Принято приглашение на свидание
	}

	/**
	 * Удаляет (отклоняет предложение о свидании)
	 *
	 * @param $id
	 */
	public function rejectInvite($id)
	{
		$updateData = array(
			'status' => 'no',
			'archive' => 'yes'
		);
		$where = 'id = ' . (int) $id;
		$this->db->update($this->tableDates, $updateData, $where);
		Models_Actions::add(3, $this->myId, null, $id); // Отклонено приглашение на свидание
	}

	/**
	 * Меняет стату свидания на архивный
	 *
	 * @param int $id ID свидания
	 */
	public function sendArchive($id)
	{
		$updateData = array(
			'archive' => 'yes',
			'status'  => 'no'
		);
		$where = 'id = ' . (int) $id;
		$this->db->update($this->tableDates, $updateData, $where);
	}

	// ====== HELPER =====
	/**
	 * Перепаковывает данные массива смещая его на кол-дней недели относительно понедельника.
	 *
	 * @param array $data
	 * @return mixed
	 */
	static public function _getCorrectDayCallendar($data) {
		$correct = date('N') - 1;
		$i=0;
		while ($i < $correct) {
			$data[] = array_shift($data);
			$i++;
		}

		return $data;
	}

	/**
	 * Возвращает массив с сокращёнными названиями дней недели.
	 *
	 * @return array
	 */
	static public function _getDayWeek() {
		return array('Пн.', 'Вт.', 'Ср.', 'Чт.', 'Пт.', 'Сб.', 'Вс.');
	}
	// ====== HELPER =====

	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'dates'),
			'name'  => $tr->translate('Встречи'),
			'check' => 'user/dates',
			'style' => ' active',
			'icon'  => 'Dates',
			'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'dates', 'action' => 'free-day'),
					'name'  => $tr->translate('Дни'),
					'check' => 'user/dates/free-day',
					'style' => 'active',
					'icon'  => 'FreeDays',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'dates', 'action' => 'favorite-places'),
					'name'  => $tr->translate('Места'),
					'check' => 'user/dates/favorite-places',
					'style' => ' active',
					'icon'  => 'FavoritePlace',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'dates', 'action' => 'history'),
					'name'  => $tr->translate('История'),
					'check' => 'user/dates/history',
					'style' => ' active',
					'icon'  => 'History',
				),
			)
		);

		return $menu;
	}
	//============ /MENU ===========
}
<?php

class Models_User_Dashboard
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $myId = null;

	private $tableDash    = 'dashboard';
	private $tableDashMsg = 'dashboard_msg';
	#private $tableProfile = 'users_data';
	private $tableProfile = 'users';
	private $columnProfileStandard = array(
		'userId'=>'id',
		'uid',
		'current_status',
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
		'online_last_dt',
		'club_card_dt'
	);

	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');

	public function __construct($my = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		if(is_null($my)) {
			$this->myId = Models_User_Model::getMyId();
		} else {
			if ($my instanceof Models_Users) {
				$this->myId = $my->getId();
			}

			if(is_numeric($my)) {
				$this->myId = $my;
			}
		}

		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	public function hideBlock($actionName) {
		$where = $this->db->quoteInto('user_id = ?', $this->myId);

		if($actionName == 'MyGuests') {
			$where .= ' AND (msg_id = 27 OR msg_id = 28)';
		}

		if($actionName == 'LoveMyPhoto') {
			$where .= ' AND msg_id = 29';
		}

		if($actionName == 'AddFavorites') {
			$where .= ' AND (msg_id = 23 OR msg_id = 24)';
		}

		$this->db->update($this->tableDash, array('archive'=>'yes'), $where);
	}

	/**
	 * Мои гости (заходили на мой профиль)
	 * @return array
	 */
	public function getMyGuests() {
		$Date = new DateTime('now');
		$Date->modify('-72 Hour');
		$select = $this->db->select();
		$select->from(array('d' => $this->tableDash), '*')
			->where('user_id = ?', $this->myId)
			->where('archive = ?', 'no')
			->where('msg_id = 27 OR msg_id = 28')
			->where('date_create > ?', $Date->format('Y-m-d H:i:s'))
			->order('date_create DESC');

		// + tbl профиль
		$select->join(array('profile' => $this->tableProfile), 'd.user_id_from = profile.id', array('uid', 'first_name', 'sex'));
		$select->where('profile.current_status = 70'); // Показываем только ЧК
		$select->columns($this->columnProfileImg);

		//Sas_Debug::sql($select);

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Новые ЧК
	 */
	public function getNewUsers () {
		$Date = new DateTime('now');
		$Date->modify('-72 Hour');

		$select = $this->db->select()
			->from(array('profile' => $this->tableProfile), array('uid', 'first_name', 'sex'))
			->where('current_status = 70')
			->where('adoption_club_dt > ?', $Date->format('Y-m-d H:i:s'))
			->order('adoption_club_dt DESC');

		$select->columns($this->columnProfileImg);

		//Sas_Debug::sql($select);

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Полная информация о мероприятиях куда я иду
	 * @return array|null
	 */
	public function getEventsUsers() {
		$ModelEvent = new Models_User_Event($this->myId);
		$myEvents = $ModelEvent->getIGoEvent($this->myId, true);

		$events = null;
		foreach ($myEvents as $eventId => $tmp) {
			$events[$eventId] = $ModelEvent->getEvent($eventId);
			$events[$eventId]['usersGo'] = $ModelEvent->getUserGo($eventId, null);
		}

		return $events;
	}

	/**
	 * Нравятся ваши фото
	 */
	public function getLoveMyPhoto() {
		$Date = new DateTime('now');
		$Date->modify('-7 Day');

		$select = $this->db->select();
		$select->from(array('d' => $this->tableDash), '*')
			->where('user_id = ?', $this->myId)
			->where('archive = ?', 'no')
			->where('msg_id = 29')
			->where('date_create > ?', $Date->format('Y-m-d H:i:s'))
			->order('date_create DESC');

		// + tbl профиль
		$select->join(array('profile' => $this->tableProfile), 'd.user_id_from = profile.id', array('uid', 'first_name', 'sex'));
		$select->where('profile.current_status = 70'); // Показываем только ЧК
		$select->columns($this->columnProfileImg);

		//Sas_Debug::sql($select);

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Добавили в фавориты
	 */
	public function getAddFavorites() {
		$Date = new DateTime('now');
		$Date->modify('-7 Day');

		$select = $this->db->select();
		$select->from(array('d' => $this->tableDash), '*')
			->where('user_id = ?', $this->myId)
			->where('user_id_from != ?', $this->myId)
			->where('archive = ?', 'no')
			->where('msg_id = 23 OR msg_id = 24')
			->where('date_create > ?', $Date->format('Y-m-d H:i:s'))
			->order('date_create DESC');

		// + tbl профиль
		$select->join(array('profile' => $this->tableProfile), 'd.user_id_from = profile.id', array('uid', 'first_name', 'sex'))
			->group('profile.uid');
		$select->where('profile.current_status = 70'); // Показываем только ЧК
		$select->columns($this->columnProfileImg);

		#Sas_Debug::sql($select);

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Фотографии дня
	 * @return array
	 */
	public function getPhotoDay() {
		$Date = new DateTime('now');
		$Date->modify('-1 Day');
		$select = $this->db->select()
			->from(array('l'=>'user_pictures_like'), null)
			->join(array('pic'=>'user_pictures'), 'pic.id = l.photo_id', array('user_id', 'comment'))
			->join(array('profile'=>'users'), 'pic.user_id = profile.id', array('uid','first_name'))
			->columns(array('cntPhoto'=>'COUNT(photo_id)'))
			->columns(array('img'=>'CONCAT( "/img/people/", profile.sex, "/", YEAR(`birthday`), "/", `profile`.`id`, "/", pic.`picture`)'))
			->where('(date_create >= "'.$Date->format('Y-m-d').'" AND date_create < "'.CURRENT_DATE.'")')
			->where('profile.sex = "male"')
			->group('photo_id')
			->order(array('cntPhoto DESC', 'like_cnt DESC'))
			->limit(1);

		#Sas_Debug::sql($select);
		$male = $this->db->fetchRow($select);

		$select->reset('where')
			->where('profile.sex = "female"')
			->where('(date_create >= "'.$Date->format('Y-m-d').'" AND date_create < "'.CURRENT_DATE.'")');

		#Sas_Debug::sql($select);
		$female = $this->db->fetchRow($select);

		return array('male'=>$male, 'female'=>$female);
	}

	/**
	 * Дни рождения на сегодня у ЧК
	 * @return array
	 */
	public function getBirthday() {
		$select = $this->db->select()
			->from(array('profile'=>$this->tableProfile), array('uid', 'first_name', 'sex'))
			->columns($this->columnProfileImg)
			->where('current_status = 70')
			->where('MONTH(`birthday`) = "'.date('m').'" AND DAY(`birthday`) = "'.date('d').'"')
			->order('online_last_dt DESC');

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает сообщения с dashboard
	 * @return array
	 */
	public function getMsg()
	{
		$select = $this->db->select();
		$select->from(array('d' => $this->tableDash), '*');
		$select->where('user_id = ?', $this->myId);
		$select->where('archive = ?', 'no');
		$select->order('date_create DESC');

		// + tbl сообщения
		$columnMsg = array(
			'module', 'controller', 'action', 'action_name', 'params',
			'msg' => 'msg_' . $this->lang,
		);
		$select->join(array('m' => $this->tableDashMsg), 'd.msg_id = m.id', $columnMsg);

		// + tbl профиль
		$select->join(array('profile' => $this->tableProfile), 'd.user_id_from = profile.id', $this->columnProfileStandard);
		$select->where('profile.current_status >= 70'); // Показываем только заходы ЧК
		$select->columns($this->columnProfileImg);
		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Возвращает сообщения с dashboard определенного типа
	 * @param array $typesId
	 * @return array
	 */
	public function getMsgType(array $typesId)
	{
		$select = $this->db->select();
		$select->from(array('d' => $this->tableDash), '*');
		$select->where('user_id = ?', $this->myId);
		$select->where('msg_id IN ('.implode(',', $typesId).')');
		$select->where('archive = ?', 'no');
		$select->order('date_create DESC');

		// + tbl сообщения
		$columnMsg = array(
			'module', 'controller', 'action', 'action_name', 'params',
			'msg' => 'msg_' . $this->lang,
		);
		$select->join(array('m' => $this->tableDashMsg), 'd.msg_id = m.id', $columnMsg);

		// + tbl профиль
		$select->join(array('profile' => $this->tableProfile), 'd.user_id_from = profile.id', $this->columnProfileStandard);
		$select->where('profile.current_status = 70'); // Показываем только заходы ЧК
		$select->columns($this->columnProfileImg);
		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Отправляет сообщение на dashboard пользователю указанному в $user_id_to
	 * @param int $user_id получатель сообщения
	 * @param int $msg_id ID текста сообщения
	 * @param string $anchor_name название якоря
	 * @param int $anchor_id ID якоря
	 * @return int
	 */
	public function sendToDash($user_id, $msg_id, $anchor_name, $anchor_id)
	{
		$data = array(
			'user_id'      => (int)$user_id, // получатель сообщения
			'user_id_from' => $this->myId, // отправитель
			'msg_id'       => $msg_id, // ID текста сообщения
			'archive'      => 'no',
			'anchor_name'  => $anchor_name,
			'anchor_id'    => $anchor_id,
			'date_create'  => date('Y-m-d H:i:s')
		);

		return $this->db->insert($this->tableDash, $data);
	}

	/**
	 * Производит запись на даш при открытии стороннего профиля
	 * @param $user_id
	 */
	public function openProfileToDash($user_id)
	{
		$msgId = (Models_User_Model::getMySex() == 'male') ? 27 : 28;

		// Проверяем существование НЕ скрытой записи на даше получателя
		$select = $this->db->select()
			->from($this->tableDash, 'id')
			->where('user_id = ?', (int)$user_id) // получатель сообщения
			->where('user_id_from = ?', $this->myId)
			->where('msg_id = ?', $msgId)
			->where('archive = ?', 'no');
		$dashId = $this->db->fetchOne($select);
		//Sas_Debug::dump($dashId);
		// Если сообщения нет - пишем его туда
		if ($dashId == false) {
			$this->sendToDash($user_id, $msgId, null, null);
		} else {
			// Меняем дату существующего сообщения
			$this->db->update($this->tableDash, array('date_create' => date('Y-m-d H:i:s')), $this->db->quoteInto('id = ?', $dashId));
		}
	}

	/**
	 * Пишем на даш при лайке на фото
	 * @param $userId
	 */
	public function likePhoto($userId)
	{
		$msgId = 29;

		$select = $this->db->select()
			->from($this->tableDash, 'id')
			->where('user_id = ?', (int)$userId) // получатель сообщения
			->where('user_id_from = ?', $this->myId)
			->where('msg_id = ?', $msgId)
			->where('archive = ?', 'no');
		$dashId = $this->db->fetchOne($select);
		if ($dashId == false) {
			$this->sendToDash($userId, $msgId, null, null);
		} else {
			// Меняем дату существующего сообщения
			$this->db->update($this->tableDash, array('date_create' => date('Y-m-d H:i:s')), $this->db->quoteInto('id = ?', $dashId));
		}
	}

	/**
	 * Закрывает сообщение (убирает его в архив)
	 * Нужно передавать $dashId ИЛИ $anchor_name и $anchor_id вместе
	 * $dashId передаётся при закрытии окна с dash
	 * $anchor_name и $anchor_id при отслеживании действий
	 *
	 * @param null $dashId
	 * @param null $anchor_name
	 * @param null $anchor_id
	 * @return int
	 */
	public function close($dashId=null, $anchor_name=null, $anchor_id = null)
	{
		if(is_null($dashId))
		{
			$where = $this->db->quoteInto('user_id = ?', $this->myId);
			$where .= ' AND ';
			$where .= $this->db->quoteInto('anchor_name = ?', $anchor_name);
			$where .= ' AND ';
			$where .= $this->db->quoteInto('anchor_id = ?', $anchor_id);
		} else {
			$where = $this->db->quoteInto('id = ?', $dashId);
		}

		return $this->db->update($this->tableDash, array('archive' => 'yes'), $where);
	}

	//============ MENU ===========
	static public function getMenu() {
		// Определение необходимости вывода маркера о наличии симпатий в игре Флирт
		if (Sas_Controller_Plugin_Start::$CONTROLLER == 'dashboard') {
			$ModelFlirt = new Models_Games_Flirt();
			$checkBadgeFlirt = $ModelFlirt->getSympathyLastVisit(Models_User_Model::getMyId());
		}
		
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'dashboard'),
			'name'  => $tr->translate('Главная'),
			'check' => 'user/dashboard',
			'style' => ' active',
			'icon'  => 'Dashboard',
			'children' => array(
				/*array(
					'url'   => array('module' => 'user', 'controller' => 'dashboard', 'action' => 'status-top'),
					'name'  => $tr->translate('Топ-20'),
					'check' => 'user/dashboard/status-top',
					'style' => ' active',
					'icon'  => 'Top'
				),*/
				/*array(
					'url'   => array('module' => 'user', 'controller' => 'dashboard', 'action' => 'post-top'),
					'name'  => $tr->translate('Топ-20'),
					'check' => 'user/dashboard/post-top',
					'style' => ' active',
					'icon'  => 'Top'
				),*/
				array(
					'url'   => array('module' => 'user', 'controller' => 'dashboard', 'action' => 'contacts'),
					'name'  => $tr->translate('Контакты'),
					'check' => 'user/dashboard/contacts',
					'style' => ' active',
					'icon'  => 'Meet'
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'dashboard', 'action' => 'posts'),
					'name'  => $tr->translate('Лента'),
					'check' => 'user/dashboard/posts',
					'style' => ' active',
					'icon'  => 'Notification'
				),
				/*array(
					'url'   => array('module' => 'user', 'controller' => 'dashboard', 'action' => 'notification'),
					'name'  => $tr->translate('Оповещения'),
					'check' => 'user/dashboard/notification',
					'style' => ' active',
					'icon'  => 'Notification'
				),*/
				/*array(
					'url'   => array('module' => 'user', 'controller' => 'dashboard', 'action' => 'fortune'),
					'name'  => $tr->translate('Фортуна'),
					'check' => 'user/dashboard/fortune',
					'style' => ' active',
					'icon'  => 'Fortune'
				),*/
				array(
					'url'   => array('module' => 'user', 'controller' => 'dashboard', 'action' => 'flirt'),
					'name'  => $tr->translate('Флирт'),
					'check' => 'user/dashboard/flirt',
					'style' => ' active',
					'icon'  => 'Flirt',
					'badge' => $checkBadgeFlirt // Маркер о наличии симпатий в игре Флирт
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'dashboard', 'action' => 'news'),
					'name'  => $tr->translate('Новости'),
					'check' => 'user/dashboard/news',
					'style' => ' active',
					'icon'  => 'News'
				),
			/*array(
				'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'blacklist'),
				'name'  => $tr->translate('Заблокированные'),
				'check' => 'user/people/blacklist',
				'style' => ' active',
				'icon'  => 'BlackList',
			),*/
		)
		);

		return $menu;
	}
	//============ /MENU ===========
}

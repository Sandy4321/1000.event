<?php
/**
 * Модель с данными о мероприятиях.
 *
 * @author sasha
 */
class Models_User_Event
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	private $myId = null;

	private $tblEvents        = array('event' =>'user_events'); // Основная со списком мероприятий
	private $tblEventsCat     = 'user_events_cat'; // Категории мероприятий
	private $tblEventsLike = array('like' => 'user_events_like'); // Лайки мероприятий
	private $tblEventsComment = array('comment' => 'user_events_comment'); // Комментарии мероприятий
	private $tblEventsCommentLike = array('like_com' => 'user_events_comment_like'); // Лайки комментариев мероприятий
	private $tblEventsReport  = array('report' => 'user_events_report'); // Отчёты о мероприятиях
	private $tblEventsReportImg  = array('report_img' => 'user_events_report_images'); // Отчёты о мероприятиях
	private $tblEventsUsers   = array('users' => 'user_events_users'); // Присоединившиеся к мероприятию пользователи
	private $tblEventsCheckin = array('checkin' => 'user_events_checkin'); // Зачекинившиеся пользователи
	private $tblEventsInvite = array('invite' => 'user_events_invite'); // Приглашения пользователей на мероприятия

	private $tblProfile   = array('profile'=>'users'); // Пользователи
	private $columnProfileAvatar = array('avatar' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');
	private $columnEventsIntro = array('intro' => 'CONCAT( "/img/user_event/", `event`.`user_id`, "/", `event`.`id`, "/intro.jpg" )');

	private $eventId = null;
	private $event = null;

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : (int)$myId;
		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	/**
	 * @param $eventId
	 * @return $this
	 */
	public function setEventId($eventId)
	{
		$this->eventId = $eventId;
		return $this;
	}

	/**
	 * @return null
	 */
	public function getEventId()
	{
		return $this->eventId;
	}

	public function buyTicket(Models_Users $Profile)
	{
		if(is_null($this->event)) throw new Sas_Models_Exception($Profile->t('Нет ID мероприятия.'), 1);
		if($Profile->getBalance() < $this->event['price']) throw new Sas_Models_Exception($Profile->t('Для покупки билета недостаточно карат'), 1);


	}

	/**
	 * Возвращает не начавшиеся мероприятия
	 * @param bool $myOnly - true = только те, куда я пойду
	 * @param bool $categoryId
	 * @param bool $searchText
	 * @param int $limit
	 * @return array
	 */
	public function getEventsNoStart($myOnly = false, $categoryId = null, $searchText = null, $limit = null)
	{
		$select = $this->db->select();
		$select->from($this->tblEvents, '*')
			//->where('event.date_start >= ?', CURRENT_DATE)
			->where('event.date_close >= ?', CURRENT_DATETIME) // Показывем все которые еще не закончились
			->where('event.del = "no"')
			->order(array('event.date_start ASC','event.otl DESC'))
			->columns($this->columnEventsIntro)
			->group('event.id');

		// Если запрошена категория
		if(is_numeric($categoryId)) {
			$select->where('event.cat_id = ?', $categoryId);
		}
		if($categoryId == 'otl') {
			$select->where('event.otl = ?', 'yes');
		}

		// Если есть поисковый текст
		if(!is_null($searchText)) {
			$select->where('event.title LIKE "%'.$searchText.'%"');
		}

		if($myOnly != false) {
			// Только те, куда я иду!
			#$select->joinLeft($this->tblEventsUsers, 'users.event_id = event.id', null)
			$select->where('users.user_id = ?', $this->myId);
		}

		// Название категории
		$select->joinLeft(array('cat'=>$this->tblEventsCat), 'cat.id=event.cat_id', array('cat_name'=>'cat_name_'.$this->lang));

		// Организатор
		$select->joinLeft($this->tblProfile, 'profile.id=event.user_id', array('first_name', 'uid'))
			->columns($this->columnProfileAvatar);

		// Кол-во комментариев
		$select->joinLeft($this->tblEventsComment, 'comment.event_id=event.id', array('cnt_comment'=>'COUNT(DISTINCT comment.id)'));
		#$select->group('comment.event_id');

		// Кол-во лайков к мероприятию
		$select->joinLeft($this->tblEventsLike, 'like.event_id=event.id', array('cnt_like'=>'COUNT(DISTINCT like.id)'));

		$select->joinLeft(array('myLike'=>'user_events_like'), 'myLike.event_id=event.id AND myLike.user_id='.$this->myId, array('myLikeYes'=>'myLike.id'));

		// Кол-во присоединившихся к мероприятию пользователей
		$select->joinLeft($this->tblEventsUsers, 'users.event_id=event.id', array('cnt_users'=>'COUNT(DISTINCT users.user_id)'));
		#$select->group('users.event_id');

		// Кол-во пользователей присутствующих на мероприятии
		$select->joinLeft($this->tblEventsCheckin, 'checkin.event_id=event.id', array('cnt_checkin'=>'COUNT(DISTINCT checkin.user_id)'));
		#$select->group('checkin.event_id');

		if(!is_null($limit)) {
			$select->limit($limit);
		}

		//Sas_Debug::sql($select);
		$rows = $this->db->fetchAll($select);
		if(is_array($rows)) {
			$i=0;
			foreach($rows as $event) {
				// Получить тех, кто планирует пойти на мероприятие
				if($event['cnt_users'] > 0) {
					$rows[$i]['events_users'] = $this->getUserGo($event['id']);
				}

				// Получить тех, кто уже на мероприятие
				if($event['cnt_checkin'] > 0) {
					$rows[$i]['checkin_users'] = $this->getUserCheckIn($event['id']);
				}
				$i++;
			}
		}
		#Sas_Debug::dump($rows);
		return $rows;
	}

	/**
	 * Возвращает основные данные пользователей которые идут на мероприятие.
	 * @param     $eventId
	 * @param int $limit
	 * @return array
	 */
	public function getUserGo($eventId, $limit = 5) {
		$select = $this->db->select();
		$select->from($this->tblEventsUsers, null)
			->where('event_id = ?', (int) $eventId)
			->order('joined_dt DESC');

		if(!is_null($limit)) {
			$select->limit($limit);
		}

		$select->joinLeft($this->tblProfile, 'profile.id=users.user_id', array('id','uid', 'first_name'))
			->columns($this->columnProfileAvatar);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает основные данные пользователей которые присутствуют мероприятие.
	 * @param     $eventId
	 * @param int $limit
	 * @return array
	 */
	public function getUserCheckIn($eventId, $limit = 5) {
		$select = $this->db->select();
		$select->from($this->tblEventsCheckin, null)
			->where('event_id = ?', (int) $eventId)
			->order('checkin_dt DESC');

		if(!is_null($limit)) {
			$select->limit($limit);
		}

		$select->joinLeft($this->tblProfile, 'profile.id=checkin.user_id', array('id','uid', 'first_name'))
			->columns($this->columnProfileAvatar);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает все комментарии к мероприятию.
	 *
	 * @param $eventId
	 * @return array
	 */
	public function getCommentAll($eventId) {
		$select = $this->db->select();
		$select->from($this->tblEventsComment, '*')
			->where('event_id = ?', $eventId)
			->order('date_create ASC');

		// Пользователи
		$select->joinLeft($this->tblProfile, 'profile.id=comment.user_id', array('first_name', 'uid'))
			->columns($this->columnProfileAvatar);

		// Лайки
		$select->joinLeft($this->tblEventsCommentLike, 'like_com.comment_id=comment.id', array('cnt_like'=>'COUNT(DISTINCT like_com.id)'))
			->group('comment.id');

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает массив с ключами ID комментариев к мероприятию которые я лайкнул.
	 * Массив вида [comment_id]=yes
	 * @param $event_id
	 * @return string
	 */
	public function getILikeComment($event_id)
	{
		$select = $this->db->select();
		$select->from($this->tblEventsComment, null)
			->where('comment.event_id = ?', $event_id);

		$select->joinLeft($this->tblEventsCommentLike, 'like_com.comment_id = comment.id', array('comment_id', 'CONCAT("yes")'))
			->where('like_com.user_id = ?', $this->myId);

		return $this->db->fetchPairs($select);
	}

	/**
	 * Возвращает массив с ключами ID мероприятий которые я лайкнул.
	 * Массив вида [comment_id]=yes
	 * @param $event_id
	 * @return string
	 */
	public function getILikeEvent($event_id)
	{
		$select = $this->db->select();
		$select->from($this->tblEventsLike, array('event_id', 'CONCAT("yes")'))
			->where('like.event_id = ?', $event_id)
			->where('like.user_id = ?', $this->myId);

		return $this->db->fetchPairs($select);
	}

	/**
	 * Лайк на коммент к мероприятию
	 * @param $commentId
	 */
	public function likeComment($commentId)
	{
		// Проверяем лайкал ли я уже этот коммент?
		if(!$this->isLikeComment($commentId)) {
			$this->db->insert($this->tblEventsCommentLike, array('comment_id'=>$commentId, 'user_id'=>$this->myId));
		}
	}

	/**
	 * Проверяет лайкал ли я это коммент
	 * @param $commentId
	 * @return bool
	 */
	public function isLikeComment($commentId) {
		$select = $this->db->select()
			->from($this->tblEventsCommentLike, 'user_id')
			->where('user_id = ?', $this->myId)
			->where('comment_id = ?', $commentId);

		$userId = $this->db->fetchOne($select);

		return ($userId == $this->myId) ? true : false;
	}

	/**
	 * Лайк на мероприятие
	 * @param $eventId
	 */
	public function likeEvent($eventId)
	{
		// Проверяем лайкал ли я уже этот коммент?
		if(!$this->isLikeEvent($eventId)) {
			$this->db->insert($this->tblEventsLike, array('event_id'=>$eventId, 'user_id'=>$this->myId));
		}
	}

	/**
	 * Проверяет лайкал ли я это мероприятие
	 * @param $eventId
	 * @return bool
	 */
	public function isLikeEvent($eventId) {
		$select = $this->db->select()
			->from($this->tblEventsLike, 'user_id')
			->where('user_id = ?', $this->myId)
			->where('event_id = ?', $eventId);

		$userId = $this->db->fetchOne($select);

		return ($userId == $this->myId) ? true : false;
	}

	/**
	 * Добавляет комментарий к мероприятию
	 * @param $myID
	 * @param $eventId
	 * @param $commentText
	 */
	public function addCommentText($myID, $eventId, $commentText) {
		$data['user_id']  = $myID;
		$data['event_id'] = $eventId;
		$data['comment_text'] = Sas_Filter_TextReplaceLinks::get(str_replace("\n", "<br>", $commentText));
		$data['date_create'] = CURRENT_DATETIME;
		$this->db->insert($this->tblEventsComment, $data);
	}

	/**
	 * Отмечает или снимает отметку с мероприятия на которое я иду.
	 * @param        $myID
	 * @param        $eventId
	 * @param string $action
	 */
	public function iGoEvent($myID, $eventId, $action = 'yes') {
		if($action == 'yes') {
			$data['event_id'] = $eventId;
			$data['user_id'] = $myID;
			$data['joined_dt'] = CURRENT_DATETIME;
			$this->db->insert($this->tblEventsUsers, $data);
		} else {
			$where = $this->db->quoteInto('event_id = ?', $eventId);
			$where .= ' AND ';
			$where .= $this->db->quoteInto('user_id = ?', $myID);
			$this->db->delete($this->tblEventsUsers, $where);
		}
	}

	/**
	 * Отмечает или снимает отметку с мероприятия на котором я присутствую.
	 * @param        $myID
	 * @param        $eventId
	 * @param string $action
	 */
	public function iCheckInEvent($myID, $eventId, $action = 'yes') {
		if($action == 'yes') {
			$data['event_id'] = $eventId;
			$data['user_id'] = $myID;
			$data['checkin_dt'] = CURRENT_DATETIME;
			$this->db->insert($this->tblEventsCheckin, $data);
		} else {
			$where = $this->db->quoteInto('event_id = ?', $eventId);
			$where .= ' AND ';
			$where .= $this->db->quoteInto('user_id = ?', $myID);
			$this->db->delete($this->tblEventsCheckin, $where);
		}
	}

	/**
	 * Проверяет и возвращает кол-во мероприятий созданных в текущих сутках (удаленные мероприятия вычитаются)
	 * @param $userId
	 * @return string
	 */
	public function isEventSaveToDay($userId) {
		$select = $this->db->select();
		$select->from($this->tblEvents, 'COUNT(*)')
			->where('user_id = ?', $userId)
			->where('YEAR(date_create) = ?', date('Y'))
			->where('MONTH(date_create) = ?', date('m'))
			->where('DAY(date_create) = ?', date('d'))
			->where('del = ?', 'no')
			->limit(1);

		return $this->db->fetchOne($select);
	}

	/**
	 * Возвращает список категорий мероприятий (массив вида key=>value)
	 * @return array
	 */
	public function getCat() {
		$select = $this->db->select();
		$select->from($this->tblEventsCat, array('cat_id' => 'id','cat_name' => 'cat_name_' . $this->lang))
			->order('sort ASC');
		//Sas_Debug::sql($select);
		$rows = $this->db->fetchPairs($select);

		return $rows;
	}

	/**
	 * Возвращает название категории мероприятия по её ID на языке пользователя
	 * @param $catId
	 * @return string
	 */
	public function getCatName($catId) {
		$select = $this->db->select();
		$select->from($this->tblEventsCat, array('cat_name' => 'cat_name_' . $this->lang))
			->where('id = ?', (int)$catId);

		return $this->db->fetchOne($select);
	}

	/**
	 * Возвращает все мои мероприятия
	 * @param        $myId
	 * @param string $order
	 * @return array
	 */
	public function getMyEvents($myId, $order = 'date_close DESC') {
		$select = $this->db->select();
		$select->from($this->tblEvents, '*')
			->where('user_id = ?', (int)$myId)
			->where('del = "no"')
			->order($order);
		$select->columns($this->columnEventsIntro);

		return $this->db->fetchAll($select);
	}

	public function getPartnerCreate($partnerId, $limit=1)
	{
		$select = $this->db->select();
		$select->from($this->tblEvents, '*')
			->where('user_id = ?', (int)$partnerId)
			->where('del = "no"')
			->where('date_start >= ?', CURRENT_DATE)
			->order('date_start ASC')
			->limit($limit);
		$select->columns(array('event_id'=>'id', 'authorId' => 'user_id'));
		$select->columns($this->columnEventsIntro);

		return $this->db->fetchAll($select);
	}

	/**
	 * Информация о мероприятии
	 * @param $eventId
	 * @return array
	 */
	public function getEvent($eventId) {
		$select = $this->db->select();
		$select->from($this->tblEvents, '*')
			->where('id = ?', (int)$eventId)
			->limit(1);

		$this->event = $this->db->fetchRow($select);
		return $this->event;
	}

	/**
	 * Полная информация о мероприятии
	 * @param $eventId
	 * @return array
	 */
	public function getEventFullInfo($eventId) {
		$select = $this->db->select();
		$select->from($this->tblEvents, '*')
			->where('event.id = ?', $eventId)
			->columns($this->columnEventsIntro)
			->limit(1);

		// Название категории
		$select->joinLeft(array('cat'=>$this->tblEventsCat), 'cat.id=event.cat_id', array('cat_name'=>'cat_name_'.$this->lang));

		// Организатор
		$select->joinLeft($this->tblProfile, 'profile.id=event.user_id', array('first_name', 'uid'))
			->columns($this->columnProfileAvatar);

		// Кол-во комментариев
		$select->joinLeft($this->tblEventsComment, 'comment.event_id=event.id', array('cnt_comment'=>'COUNT(DISTINCT comment.id)'))
			->group('comment.event_id');

		// Кол-во лайков к мероприятию
		$select->joinLeft($this->tblEventsLike, 'like.event_id=event.id', array('cnt_like'=>'COUNT(DISTINCT like.id)'));

		// Кол-во присоединившихся к мероприятию пользователей
		$select->joinLeft($this->tblEventsUsers, 'users.event_id=event.id', array('cnt_users'=>'COUNT(DISTINCT users.user_id)'))
			->group('users.event_id');

		// Кол-во пользователей присутствующих на мероприятии
		$select->joinLeft($this->tblEventsCheckin, 'checkin.event_id=event.id', array('cnt_checkin'=>'COUNT(DISTINCT checkin.user_id)'))
			->group('checkin.event_id');

		$row = $this->db->fetchRow($select);
		if(is_array($row)) {
			// Кто пойдет?
			if($row['cnt_users'] > 0) {
				$row['events_users'] = $this->getUserGo($row['id']);
			}

			// Кто Присутствует?
			if($row['cnt_checkin'] > 0) {
				$row['checkin_users'] = $this->getUserCheckIn($row['id']);
			}
		}

		return $row;
	}

	/**
	 * Сохраняет мероприятие
	 * @param $myId
	 * @param $post
	 * @return bool|string
	 */
	public function saveEvent($myId, $post)
	{
		$data['cat_id'] = (int)$post['cat_id'];
		$data['title'] = $post['title'];
		$data['anons'] = Sas_Filter_TextReplaceLinks::get($post['anons']);
		$data['full_text'] = Sas_Filter_TextReplaceLinks::get($post['full_text']);
		$data['point_name'] = $post['point_name'];
		$data['date_start'] = $post['date_start'];
		$data['date_close'] = $post['date_close'];

		$data['price'] = $post['price'];
		$data['money_type'] = $post['money_type'];

		if($post['intro_img'] == 1) $data['intro_img'] = 1;
		if($post['intro_img'] === 0) $data['intro_img'] = null;

		if(!$post['event_id']) {
			$data['user_id'] = (int)$myId;
			$data['date_create'] = CURRENT_DATETIME;
			$this->db->insert($this->tblEvents, $data);

			return $this->db->lastInsertId($this->tblEvents);
		} else {
			$this->db->update($this->tblEvents, $data, 'id = '.(int)$post['event_id']);
			return true;
		}
	}

	public function getReports($limit = 20, $page = 0)
	{
		$select = $this->db->select()
			->from($this->tblEventsReport, 'report_text')
			->limitPage($page, $limit);

		$select->joinLeft($this->tblEvents, 'event.id = report.event_id', '*')
			->order('event.date_close DESC');

		$select->joinLeft($this->tblEventsReportImg, 'event.id = report_img.event_id', array('cnt_img'=>'COUNT(DISTINCT report_img.id)'));

		$row = $this->db->fetchAll($select);

		return $row;
	}

	public function getReportText($eventId, $noBr = false)
	{
		$select = $this->db->select()
			->from($this->tblEventsReport)
			->where('event_id = ?', $eventId)
			->limit(1);

		$row = $this->db->fetchRow($select);
		if(!empty($row['report_text']) && $noBr == false) {
			$row['report_text'] = str_replace("<br>", "", $row['report_text']);
		}
		return $row;
	}

	/**
	 * Возвращает фотографии для отчёта
	 * @param $eventId
	 * @return array
	 */
	public function getReportPhoto($eventId)
	{
		$select = $this->db->select()
			->from($this->tblEventsReportImg)
			->where('event_id = ?', $eventId)
			->limit(30);

		return $this->db->fetchAll($select);
	}

	public function isReport($eventId)
	{
		$select = $this->db->select()
			->from($this->tblEventsReport, 'id')
			->where('event_id = ?', $eventId)
			->limit(1);

		return $this->db->fetchOne($select);
	}

	/**
	 * Сохраняет отчёт о мероприятии
	 * @param $eventId
	 * @param $reportText
	 * @return int
	 */
	public function saveReport($eventId, $reportText)
	{
		// Проверяем есть ли такой отчёт
		$repId = $this->isReport($eventId);

		if(is_numeric($repId)) {
			$data['report_text'] = Sas_Filter_TextReplaceLinks::get($reportText);
			$this->db->update($this->tblEventsReport, $data);
		} else {
			$data['event_id'] = $eventId;
			$data['report_text'] = Sas_Filter_TextReplaceLinks::get($reportText);
			$data['date_create'] = CURRENT_DATETIME;

			$this->db->insert($this->tblEventsReport, $data);
			$repId = $this->db->lastInsertId($this->tblEventsReport);
		}

		return $repId;
	}

	/**
	 * Сохраняет фото к репорту
	 * @param $eventId
	 * @param $imagePath
	 * @param $imageName
	 * @return int
	 */
	public function saveReportPhoto($eventId, $imagePath, $imageName)
	{
		$data['event_id'] = $eventId;
		$data['img_path'] = $imagePath;
		$data['img_name'] = $imageName;

		return $this->db->insert($this->tblEventsReportImg, $data);
	}

	// Получить НЕ начавшиеся мероприятия куда я иду
	// Получить Начавшиеся мероприятия куда я иду
	// Получить Закончившиеся мероприятия куда я иду
	/**
	 * Возвращает массив мероприятий на которые я иду.
	 *
	 * Массив вида array(eventId => myID)
	 * Этот упрощённый массив возвращается с целью выстрого поиска по ключу массива,
	 * для понимания в дальнейшем коде на какие (eventId) я иду.
	 *
	 * @param $myId
	 * @param $onlyActive - только активные мероприятия
	 * @return string
	 */
	public function getIGoEvent($myId, $onlyActive = null)
	{
		$select = $this->db->select();
		$select->from($this->tblEventsUsers, array('event_id', 'user_id'))
			->where('users.user_id = ?', $myId);

		if(!is_null($onlyActive)) {
			$select->join($this->tblEvents, 'event.id = users.event_id', null)
				->where('event.date_start > ?', CURRENT_DATE);
		}

		return $this->db->fetchPairs($select);
	}

	/**
	 * Проверка, идет ли пользователь на мероприятие
	 *
	 * @param $userId
	 * @return bool
	 * @throws Sas_Models_Exception
	 */
	public function isGoEvent($userId)
	{
		if(is_null($this->getEventId())) throw new Sas_Models_Exception('ID мероприятия не был задан заранее.', 404);

		$select = $this->db->select()
			->from($this->tblEventsUsers, 'COUNT(*)')
			->where('users.event_id = ?', $this->getEventId())
			->where('users.user_id = ?', $userId);

		return ($this->db->fetchOne($select) > 0) ? true : false;
	}

	public function getGoUser($userId, $onlyActive = null, $limit = null)
	{
		$select = $this->db->select();
		$select->from($this->tblEventsUsers, array('event_id', 'user_id'))
			->where('users.user_id = ?', $userId)
			->order('date_start ASC');

		if(!is_null($onlyActive)) {
			$select->join($this->tblEvents, 'event.id = users.event_id', array('title', 'point_name', 'date_start', 'otl', 'intro_img', 'authorId'=>'user_id'))
				->where('event.date_start > ?', CURRENT_DATE);
		}

		if(is_int($limit)) {
			$select->limit($limit);
		}

		return $this->db->fetchAll($select);
	}

	/**
	 * Ближайщие мероприятия OTL
	 * @param int $limit
	 * @return array
	 */
	public function getOtl($limit = 2)
	{
		$select = $this->db->select()
			->from($this->tblEvents, '*')
			->where('otl = ?', 'yes')
			->where('del = "no"')
			->where('date_start >= ?', CURRENT_DATE)
			->limit($limit);
		$select->columns(array('event_id'=>'id', 'authorId' => 'user_id'));
		$select->columns($this->columnEventsIntro);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает массив мероприятий на которые я присутствую.
	 *
	 * Массив вида array(eventId => myID)
	 * Этот упрощённый массив возвращается с целью выстрого поиска по ключу массива,
	 * для понимания в дальнейшем коде на какие (eventId) я иду.
	 *
	 * @param $myId
	 * @return string
	 */
	public function getICheckIn($myId)
	{
		$select = $this->db->select();
		$select->from($this->tblEventsCheckin, array('event_id', 'user_id'))
			->where('user_id = ?', $myId);

		return $this->db->fetchPairs($select);
	}

	/**
	 * Проверяет моё ли это мероприятие
	 * @param $myId
	 * @param $eventId
	 * @return bool
	 */
	public function isMyEvent($myId, $eventId)
	{
		$select = $this->db->select()
			->from($this->tblEvents, 'user_id')
			->where('user_id = ?', $myId)
			->where('id = ?', $eventId)
			->limit(1);

		$user_id = $this->db->fetchOne($select);

		return ($user_id == $myId) ? true : false;
	}

	/**
	 * Удаляет мероприятие (вообще то просто скрывает его отмечая как удалённое)
	 * @param $eventId
	 */
	public function deleteEvent($eventId)
	{
		$this->db->update($this->tblEvents, array('del'=>'yes'), 'id = ' . (int) $eventId);
	}

	/**
	 * Возвращается полная информация по мероприятиям на которые я иду, с ограничением по дате начала выборки.
	 *
	 * @param $myId
	 * @param $dateStartLimit
	 * @return array
	 */
	/*public function getIGoEventFullInfo($myId, $dateStartLimit = null)
	{
		$dateStartLimit = (is_null($dateStartLimit)) ? CURRENT_DATETIME : $dateStartLimit;

		$select = $this->db->select();

		// Инфа по мероприятию
		$select->from($this->tblEvents, '*')
			->where('event.date_start >= ?', $dateStartLimit);

		// Только те, куда я иду!
		$select->joinLeft($this->tblEventsUsers, 'users.event_id = event.id', null)
			->where('users.user_id = ?', $myId);

		return $this->db->fetchAll($select);
	}*/

	/**
	 * Удаляет картинки intro.jpg и intro_original.jpg
	 * @param $myID
	 * @param $eventId
	 */
	public function deletePhotoIntro($myID, $eventId)
	{
		$path = $_SERVER['DOCUMENT_ROOT'].'/img/user_event/'.$myID.'/'.$eventId;

		if(file_exists($path.'/intro.jpg')) {
			unlink($path.'/intro.jpg');
		}

		if(file_exists($path.'/intro_original.jpg')) {
			unlink($path.'/intro_original.jpg');
		}
	}

	/**
	 * Делает запись о приглашении партнера на мероприятие.
	 *
	 * @param $user_id
	 * @param $partner_id
	 * @param $event_id
	 */
	public function setInvite($user_id, $partner_id, $event_id)
	{
		$data['event_id']    = $event_id;
		$data['user_id']     = $user_id;
		$data['partner_id']  = $partner_id;
		$data['date_create'] = CURRENT_DATETIME;
		$this->db->insert($this->tblEventsInvite, $data);
	}

	/**
	 * Возвращает ID мероприятий на которые я приглашал партнера.
	 *
	 * @param $user_id
	 * @param $partner_id
	 * @return array
	 */
	public function getInvite($user_id, $partner_id)
	{
		$select = $this->db->select()
			->from($this->tblEventsInvite, array('event_id', 'partner_id'))
			->where('user_id = ?', $user_id)
			->where('partner_id = ?', $partner_id);

		return $this->db->fetchPairs($select);
	}

	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'event', 'action'=>'index'),
			'name'  => $tr->translate('Афиша'),
			'check' => 'user/event',
			'style' => ' active',
			'icon' => 'Event',
			'children' => array(
				/*array(
					'url'   => array('module' => 'user', 'controller' => 'event', 'action' => 'search'),
					'name'  => $tr->translate('Поиск'),
					'check' => 'user/event/search',
					'style' => ' active',
					'icon'  => 'Settings',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'event', 'action' => 'invite'),
					'name'  => $tr->translate('Приглашения'),
					'check' => 'user/event/invite',
					'style' => ' active',
					'icon'  => 'Album',
				),*/
				array(
					'url'   => array('module' => 'user', 'controller' => 'event', 'action'=>'igo'),
					'name'  => $tr->translate('Я иду'),
					'check' => 'user/event/igo',
					'style' => ' active',
					'icon' => 'EventIgo',
				),
				/*array(
					'url'   => array('module' => 'user', 'controller' => 'event', 'action'=>'report'),
					'name'  => $tr->translate('Отчёты'),
					'check' => 'user/event/report',
					'style' => ' active',
					'icon'  => 'Album'
				),*/
				array(
					'url'   => array('module' => 'user', 'controller' => 'event', 'action'=>'my'),
					'name'  => $tr->translate('Мои'),
					'check' => 'user/event/my',
					'style' => ' active',
					'icon'  => 'EventMy'
				),
				/*array(
					'url'   => array('module' => 'user', 'controller' => 'event', 'action'=>'create'),
					'name'  => $tr->translate('Создать'),
					'check' => 'user/event/create',
					'style' => ' active',
					'icon'  => 'Invite'
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'event', 'action'=>'edit'),
					'name'  => $tr->translate('Редактировать'),
					'check' => 'user/event/edit',
					'style' => ' active',
					'icon'  => 'Album'
				),*/
			)
		);

		return $menu;
	}
	//============ /MENU ===========
}
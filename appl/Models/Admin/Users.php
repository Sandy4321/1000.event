<?php

class Models_Admin_Users
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $tblUserProfile = array('profile'=>'users');
	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');

	private $tableCity = 'cities';
	private $tblUserStatus = 'user_statuses';
	private $tblUserDates = 'user_dates'; // таблица свиданий
	private $tblPlaces = 'places'; // таблица мест для свиданий

	public function __construct() {
		$this->db = Zend_Registry::get('db');
	}

	/**
	 * Возвращает пользователей с определённым статусом
	 * @param      $statusId 1-Новая заявка, 2-Ожидающая дополнительной информации, 3-Активная анкета, 4-Отклоненная админами анкета, 5-Удаленная админами анкета, 6-Скрытая пользователем (не используется)
	 * @param null $whereArray
	 * @param null $orderBy
	 * @param null $limit
	 * @return array
	 */
	public function getUserStatus($statusId, $whereArray = null, $orderBy = null, $limit = null)
	{
		$select = $this->db->select()
			->from($this->tblUserProfile, '*')
			->columns($this->columnProfileImg)
			->where('current_status = ?', $statusId);

		if(is_array($whereArray)) {
			for($i = 0; $i <count($whereArray); $i++) {
				$whereArray[$i]['operator'] = (empty($whereArray[$i]['operator'])) ? '=' : $whereArray[$i]['operator'];
				if(is_null($whereArray[$i]['logic']) || $whereArray[$i]['logic'] == 'and' || $whereArray[$i]['logic'] == 'AND') {
					$select->where('`' . $whereArray[$i]['name'] .'` '.$whereArray[$i]['operator'].' ?', $whereArray[$i]['val']);
				} else {
					$select->orWhere('`' . $whereArray[$i]['name'] .'` '.$whereArray[$i]['operator'].' ?', $whereArray[$i]['val']);
				}
			}
		}

		if (is_null($orderBy)) {
			$select->order('register_dt ASC');
		} else {
			$select->order($orderBy);
		}
		if (!is_null($limit)) {
			$select->limit($limit);
		}

		$select->joinLeft(array('city'=>$this->tableCity), 'profile.city_id = city.id', array('cityId'=>'id', 'countryId'=>'country_id', 'cityName'=>'name_ru'));

		#Sas_Debug::dump($select->__toString());
		$rows = $this->db->fetchAll($select);

		// ID приглашающего человека
		for($i=0;$i<count($rows); $i++) {
			if(!is_null($rows[$i]['promo_key_friend'])) {
				$select = $this->db->select()
					->from($this->tblUserProfile, array('id', 'first_name', 'current_status'))
					->where('promo_key = ?', $rows[$i]['promo_key_friend'])
					->limit(1);
				$res = $this->db->fetchRow($select);
				$rows[$i]['friendId'] = $res['id'];
				$rows[$i]['friendName'] = $res['first_name'];
				$rows[$i]['friendStatus'] = $res['current_status'];
			}
		}

		return $rows;
	}

	/**
	 * Возвращает профиль друга (пригласившего)
	 * @param $promo_key_friend
	 * @return string
	 */
	public function getFriend($promo_key_friend) {
		$select = $this->db->select()
			->from($this->tblUserProfile, '*')
			->where('promo_key = ?', $promo_key_friend)
			->limit(1);
		$friend = $this->db->fetchRow($select);
		return $friend;
	}

	/**
	 * Устанавливает новую дату для Клубной карты пользователя
	 * @param $userId
	 * @param $newCardDate
	 * @return int
	 */
	public function setCardDateNew($userId, $newCardDate)
	{
		// Обновляем Клубную карту
		return $this->db->update($this->tblUserProfile, array('club_card_dt' => $newCardDate), $this->db->quoteInto('id = ?', $userId));
	}

	/**
	 * Записывает новую запись в лог фин. операций о доп. начислении дней в клубную карту
	 * @param $userId
	 */
	public function addRecordLogNewFriend($userId) {
		// Записываем в историю счета
		$data = array(
			'user_id' => $userId,
			'transaction_name' => 'Добавлены дни в Клубную Карту за приглашение.',
			'amount' => PRICE_FRIEND,
			'date_create' => CURRENT_DATETIME
		);
		$this->db->insert('user_balance_log', $data);
	}

	public function getCntUsers($status = 3)
	{
		$select = $this->db->select()
			->from($this->tblUserProfile, 'COUNT(id)')
			->where('current_status = ?', $status);

		return $this->db->fetchOne($select);
	}

	/**
	 * Возвращет профиль пользователя
	 * @param $userId
	 * @return array
	 */
	public function getUserProfile($userId)
	{
		$select = $this->db->select()
			->from($this->tblUserProfile, '*')
			->where('id = ?', $userId)
			->limit(1);
		$row = $this->db->fetchRow($select);

		return $row;
	}

	/**
	 * Возвращет ПОЛНЫЙ профиль пользователя
	 * @param $userId
	 * @return array
	 */
	public function getUserProfileFull($userId)
	{
		$select = $this->db->select();
		$select->from($this->tblUserProfile, '*');
		$select->columns($this->columnProfileImg);

		$select->joinLeft(array('city'=>$this->tableCity), 'profile.city_id = city.id', array('cityId'=>'id', 'countryId'=>'country_id', 'cityName'=>'name_ru'));

		$select->join(array('st'=>$this->tblUserStatus), 'profile.current_status = st.id', array('statusId'=>'id', 'statusName'=>'status'));

		$select->where('profile.id = ?', $userId);
		$select->limit(1);
		#Sas_Debug::sql($select);
		$row = $this->db->fetchRow($select);
		#Sas_Debug::dump($row);
		return $row;
	}

	/**
	 * Принять пользователя в клуб
	 *
	 * @param $userId
	 * @return array Профиль пользователя
	 */
	public function addUser($userId)
	{
		$update = array(
			'current_status' => 70,
			'adoption_club_dt' => CURRENT_DATETIME
		);

		$this->db->update($this->tblUserProfile, $update, $this->db->quoteInto('id = ?', (int)$userId));

		return $this->getUserProfile($userId);
	}

	/**
	 * Сделать пользователя в призраком
	 *
	 * @param $userId
	 * @return array Профиль пользователя
	 */
	public function setUserGhost($userId)
	{
		$update = array(
			'current_status' => 50,
		);

		$this->db->update($this->tblUserProfile, $update, $this->db->quoteInto('id = ?', (int)$userId));

		return $this->getUserProfile($userId);
	}

	public function avatarDelete(array $userProfile)
	{
		$imgPath = $_SERVER['DOCUMENT_ROOT'].'/img/people/'.$userProfile['sex'].'/'.Models_User_Model::getMyYear($userProfile['birthday']).'/'.$userProfile['id'].'/';
		$thumbnail = $imgPath.'thumbnail.jpg';
		$optimal = $imgPath.'optimal.jpg';
		$original = $imgPath.'original.jpg';
		@unlink($thumbnail);
		@unlink($optimal);
		@unlink($original);
	}

	/**
	 * Восстановление пользователя в клубе
	 *
	 * @param $userId
	 * @return array Профиль пользователя
	 */
	public function recoverUser($userId)
	{
		$update = array(
			'current_status' => 70,
			#'hide_profile' => 'no',
			#'deleted' => 0,
			'adoption_club_dt' => CURRENT_DATETIME
		);

		$this->db->update($this->tblUserProfile, $update, $this->db->quoteInto('id = ?', (int)$userId));

		return $this->getUserProfile($userId);
	}

	/**
	 * Запросить доп. инфо при регистрации пользователя
	 *
	 * @param $userId
	 * @return array
	 */
	public function requestMoreInfo($userId)
	{
		$update = array(
			'current_status' => 52
		);

		$this->db->update($this->tblUserProfile, $update, $this->db->quoteInto('id = ?', (int)$userId));
		return $this->getUserProfile($userId);
	}

	/**
	 * Удалить пользователя (отклоняем заявку)
	 *
	 * @param $userId
	 * @return array
	 */
	public function deleteNewUser($userId)
	{
		$update = array(
			'current_status' => 30,
			#'denied_dt' => date('Y-m-d H:i:s')
		);

		$this->db->update($this->tblUserProfile, $update, $this->db->quoteInto('id = ?', (int)$userId));
		return $this->getUserProfile($userId);
	}

	/**
	 * Возвращает бесплатные не прочитанные админом тексты сообщений пользователей
	 */
	public function getUsersMsgFree()
	{
		$select = $this->db->select()
			->from('user_questions', '*')
			->where('record_type = ?', 'free')
			->where('answer IS NOT NULL')
			->where('date_read_admin IS NULL')
			->order('date_create ASC');

		$rows = $this->db->fetchAll($select);
		return $rows;
	}

	/**
	 * Возвращает тексты статусов пользователей
	 */
	public function getUsersStatus()
	{
		$select = $this->db->select()
			->from('users_status', '*')
			->where('status_hide = ?', 'no')
			->where('dt_create >= NOW() - INTERVAL 1 MONTH')
			->order('status_hide ASC')
			->order('dt_create DESC');

		$rows = $this->db->fetchAll($select);
		return $rows;
	}

	/**
	 * Удаление статуса пользователя.
	 *
	 * @param $statusId
	 */
	public function deleteUsersStatus($statusId)
	{
		$this->db->delete('users_status', $this->db->quoteInto('id = ?', (int)$statusId));
	}

	/**
	 * Возвращает статусы пользователя
	 */
	public function getStatus($userId)
	{
		$select = $this->db->select()
			->from('users_status', '*')
			->where('user_id = ?', $userId)
			->order('status_hide ASC')
			->order('dt_create DESC');

		$rows = $this->db->fetchAll($select);
		return $rows;
	}

	/**
	 * Возвращает платные не прочитанные админом тексты сообщений пользователей
	 */
	public function getUsersMsgMoney()
	{
		$select = $this->db->select()
			->from('user_questions', '*')
			->where('record_type = ?', 'money')
			->where('date_read_admin IS NULL')
			->order('date_create ASC');

		$rows = $this->db->fetchAll($select);
		return $rows;
	}

	/**
	 * Сохраняет пользовательские сообщения и отмечает их как прочитанные админом
	 *
	 * @param $id
	 * @param $question
	 * @param $answer
	 */
	public function saveUsersMsg($id, $question, $answer)
	{
		$update['question'] = $question;
		$update['answer']   = $answer;
		$update['date_read_admin'] = date('Y-m-d H:i:s');
		$this->db->update('user_questions', $update, 'id = ' . (int) $id);
	}

	/**
	 * Возвращает ближайшие назначенные свидания
	 */
	public function getUserInvite()
	{
		$date = new DateTime(date('Y-m-d'));
		$date->modify('-2 day');

		// Свидания
		$select = $this->db->select()
			->from(array('d' => $this->tblUserDates), '*')
			->where('d.selected_datetime > ?', $date->format('Y-m-d 00:00:00'))
			->order('d.selected_datetime DESC');

		// Места
		$select->join(array('pl'=>$this->tblPlaces), 'pl.id = d.selected_place_id',
			array(
				'placeName'=>'name',
				'placeAddress'=>'address',
				'placeMetro'=>'metro',
				'placePhone'=>'phone',
				'placeSite'=>'site',
				'placeEmail'=>'email'
			)
		);

		// Пользователь (1)
		$select->join(array('pr1'=>'users_data'), 'pr1.id = d.invitee_id',
			array(
				'id1'=>'id',
				'firstName1'=>'first_name',
				'lastName1'=>'last_name',
				'userPhone1'=>'phone',
				'userEmail1'=>'email'
			)
		);

		// Пользователь (2)
		$select->join(array('pr2'=>'users_data'), 'pr2.id = d.inviter_id',
			array(
				'id2'=>'id',
				'firstName2'=>'first_name',
				'lastName2'=>'last_name',
				'userPhone2'=>'phone',
				'userEmail2'=>'email'
			)
		);

		#Sas_Debug::dump($select->__toString());
		#exit;
		$rows = $this->db->fetchAll($select);
		return $rows;
	}

	/**
	 * Блокировка пользователя
	 * @param $userId
	 */
	public function lockUser($userId)
	{
		$update['current_status'] = 40;
		$where = $this->db->quoteInto('id = ?', $userId);

		$this->db->update($this->tblUserProfile, $update, $where);
	}

	/**
	 * Сохранияет профиль пользователя
	 *
	 * @param $userId
	 * @param $data
	 */
	public function saveProfile($userId, $data)
	{
		$data['club_card_dt'] = (empty($data['club_card_dt'])) ? null : $data['club_card_dt'];

		/**
		 * Подписки
		 */
		$data['msg_news_email'] = (empty($data['msg_news_email'])) ? 'no' : 'yes';
		$data['msg_news_sms'] = (empty($data['msg_news_sms'])) ? 'no' : 'yes';

		$data['msg_admin_email'] = (empty($data['msg_admin_email'])) ? 'no' : 'yes';
		$data['msg_admin_sms'] = (empty($data['msg_admin_sms'])) ? 'no' : 'yes';

		$data['msg_invite_email'] = (empty($data['msg_invite_email'])) ? 'no' : 'yes';
		$data['msg_invite_sms'] = (empty($data['msg_invite_sms'])) ? 'no' : 'yes';

		$data['msg_favorite_email'] = (empty($data['msg_favorite_email'])) ? 'no' : 'yes';
		$data['msg_favorite_sms'] = (empty($data['msg_favorite_sms'])) ? 'no' : 'yes';

		// Удаление номера телефона
		if (empty($data['phone'])) {
			$data['phone'] = null;
			$data['msg_admin_sms'] = 'no';
			$data['phone_check'] = 'no';
			$data['phone_verify_code'] = null;
		};

		// Обработка роста
		$data['height'] = (empty($data['height']) || $data['height'] == 0 || !is_numeric($data['height']) || $data['height'] < 100 || $data['height'] > 230) ? null : (int) $data['height'];

		// Обработка ссылок на соц. сети
		$data['link_vk'] = (empty($data['link_vk'])) ? null : $data['link_vk'];
		$data['link_fb'] = (empty($data['link_fb'])) ? null : $data['link_fb'];
		$data['link_ln'] = (empty($data['link_ln'])) ? null : $data['link_ln'];

		foreach($data as $k => $v) {
			if ($k != 'id') {
				$update[$k] = $v;
			}
		}

		$where = $this->db->quoteInto('id = ?', $userId);
		$this->db->update($this->tblUserProfile, $update, $where);
	}

	public function getReportDates($dateMin = null, $dateMax = null)
	{
		$select = $this->db->select();
		$select->from('user_dates_reports', '*');
		$select->where('date_time_create > ?', '2013-06-30 00:00:00');

		if(!is_null($dateMin)) {
			$select->where('date_time_create >= ?', $dateMin);
		}

		if(!is_null($dateMax)) {
			$select->where('date_time_create <= ?', $dateMax);
		}

		$select->order('date_time_create DESC');

		#Sas_Debug::dump($select->__toString());

		return $this->db->fetchAll($select);
	}

	/**
	 * Кол-во свиданий пользователя.
	 *
	 * @param int    $userId
	 * @param string $status
	 * @return string
	 */
	public function getCntDates($userId, $status = 'yes')
	{
		$select = $this->db->select();
		$select->from('user_dates', 'COUNT(*)')
			->where('status = ?', $status)
			->where('invitee_id = ' . $userId .' OR inviter_id = ' . $userId);

		return $this->db->fetchOne($select);
	}

	/**
	 * Кол-во обменов контактами пользователя.
	 *
	 * @param int    $userId
	 * @param string $status
	 * @return string
	 */
	public function getCntContactExchange($userId, $status = 'yes')
	{
		$select = $this->db->select();
		$select->from('contact_exchange', 'COUNT(*)')
			->where('status = ?', $status)
			->where('user_id = ' . $userId .' OR user_id_from = ' . $userId);

		return $this->db->fetchOne($select);
	}

	/**
	 * Полная история действий пользователя.
	 *
	 * @param $userId
	 * @return array
	 */
	public function getUserHistory($userId)
	{
		$select = $this->db->select()
			->from(array('l'=>'v2_actions_log'), array('to_user_ud', 'service_id', 'date_create'))
			->where('primary_user_id = ?', $userId)
			->order('date_create DESC');
		$select->join(array('c'=>'v2_actions_log_category'), 'c.id = l.category_id', array('catName'=>'name_ru'));

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает историю баланса пользователя.
	 *
	 * @param $userId
	 * @return array
	 */
	public function getBalanceHistory($userId)
	{
		$select = $this->db->select()
			->from(array('b'=>'v3_orders'), '*')
			->where('user_id = ?', $userId)
			->order('date_create DESC');
		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает фотоальбом пользователя.
	 *
	 * @param $userId
	 * @return array
	 */
	public function getPhotoAlbum($userId)
	{
		$select = $this->db->select()
			->from(array('p'=>'user_pictures'), '*')
			->where('user_id = ?', $userId)
			->order('datetime_create DESC');
		$select->join($this->tblUserProfile, 'profile.id=p.user_id', null)
			->columns($this->columnProfileImg);
		return $this->db->fetchAll($select);
	}
}
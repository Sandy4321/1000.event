<?php

class Models_User_Status
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	private $myId = null;

	private $tblStatus     = 'users_status';
	private $tblStatusLike = 'users_status_like';
	#private $tblProfile = 'users_data';
	private $tblProfile = 'users';
	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');

	private $tblFavorites = 'user_favorites';

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : (int) $myId;

		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	/**
	 * Возвращает мой текущий статус
	 * @return array
	 */
	public function getMyStatus()
	{
		$select = $this->db->select()
			->from($this->tblStatus, array('id', 'status_text', 'status_vip', 'cnt_like'))
			->where('user_id = ?', $this->myId)
			->where('status_hide = ?', 'no')
			->limit(1);

		return $this->db->fetchRow($select);
	}

	/**
	 * Возвращает все мои статусы
	 * @return array
	 */
	public function getMyStatusAll()
	{
		$select = $this->db->select()
			->from($this->tblStatus, array('id', 'status_text', 'status_vip', 'cnt_like', 'dt_create'))
			->where('user_id = ?', $this->myId)
			->order('dt_create DESC');

		return $this->db->fetchAll($select);
	}

	/**
	 * Добавляет новый статус
	 * @param $text
	 * @param $vip
	 * @return string ok|no-karat
	 */
	public function saveNewStatus($text, $vip = 'no')
	{
		// Получаем старый и "убиваем его"
		$st = $this->getMyStatus();
		$this->db->update($this->tblStatus, array('status_hide'=>'yes'), $this->db->quoteInto('id = ?', $st['id']));

		if ($vip == 'yes') {
			// Списываем караты за VIP статус
			$ModelBalance = new Models_User_Balance();
			$ModelBalance->init($this->myId);
			if ($ModelBalance->checkDebitOnVipStatus()) {
				$ModelBalance->debitOnVipStatus();
			} else {
				$vip = 'no';
				return 'no-karat';
			}
		}

		$insert = array(
			'user_id'     => $this->myId,
			'status_text' => $text,
			'status_vip'  => $vip,
			'status_hide' => 'no',
			'cnt_like'    => 0,
			'dt_create'   => CURRENT_DATETIME
		);
		$this->db->insert($this->tblStatus, $insert);
		Models_Actions::add(60, $this->myId); // Опубликован новый статус

		return 'ok';
	}

	/**
	 * Возвращает статусы всех моих фаворитов.
	 *
	 * @return array
	 */
	public function getStatusesMyFavorites()
	{
		$select = $this->db->select()
			->from(array('s'=>$this->tblStatus), '*')
			->columns(array('favorite'=>'CONCAT("yes")'))
			->where('s.user_id != ?', $this->myId)
			->where('s.status_hide = ?', 'no')
			->order('s.dt_create DESC');

		$select->joinLeft(array('profile'=>$this->tblProfile), 'profile.id = s.user_id', array('first_name', 'uid'))
			->columns($this->columnProfileImg)
			->where('profile.sex != ?', Models_User_Model::getMySex());

		$select->joinLeft(array('f'=>$this->tblFavorites), 's.user_id = f.favorite_user_id', null)
		//$select->joinLeft(array('f'=>$this->tblFavorites), 's.user_id = f.user_id', null)
			->where('f.user_id = ?', $this->myId);
			//->where('f.favorite_user_id = ?', $this->myId);

		#Sas_Debug::sql($select);
		$rows = $this->db->fetchAll($select);
		#Sas_Debug::dump($rows);
		return $rows;
	}

	/**
	 * Возвращает статусы VIP.
	 * @param null $limit
	 * @return array
	 */
	public function getStatusesVip($limit = null)
	{
		$select = $this->db->select()
			->from(array('s'=>$this->tblStatus), '*')
			#->where('s.user_id != ?', $this->myId)
			->where('s.status_vip = ?', 'yes')
			->where('s.status_hide = ?', 'no')
			->order('s.dt_create DESC');

		$select->joinLeft(array('profile'=>$this->tblProfile), 'profile.id = s.user_id', array('first_name', 'uid'))
			->columns($this->columnProfileImg);
			#->where('profile.sex != ?', Models_User_Model::getMySex());

		if(is_int($limit)) {
			$select->limit($limit);
		}
		#Sas_Debug::sql($select);
		$rows = $this->db->fetchAll($select);
		#Sas_Debug::dump($rows);
		return $rows;
	}

	/**
	 * Проверяет лайкал ли текущий пользователь статус
	 *
	 * @param $stId
	 * @return string
	 */
	public function isLikeUser($stId)
	{
		$select = $this->db->select()
			->from($this->tblStatusLike, 'id')
			->where('user_id = ?', $this->myId)
			->where('status_id = ?', $stId)
			->limit(1);
		return $this->db->fetchOne($select);
	}

	public function getAllLike()
	{
		$select = $this->db->select()
			->from($this->tblStatusLike, array('id', 'status_id'))
			->where('user_id = ?', $this->myId);
		return $this->db->fetchPairs($select);
	}

	/**
	 * Возвращает массив людей лайкнувших заданный статус.
	 *
	 * @param $statusId
	 * @return array
	 */
	public function getAllPeopleLike($statusId)
	{
		$select = $this->db->select()
			->from(array('l'=>$this->tblStatusLike), array('user_id'))
			->where('l.status_id = ?', $statusId)
			->order('l.dt_create');
		$select->joinLeft(array('profile'=>$this->tblProfile), 'l.user_id=profile.id', array('first_name', 'uid'))
			->columns($this->columnProfileImg);

		return $this->db->fetchAll($select);
	}

	/**
	 * Добавляет лайк к статусу
	 * @param $stId
	 * @return bool
	 */
	public function likePlus($stId)
	{
		// Проверяем кликал ли пользователь уже на данную фото
		if($this->isLikeUser($stId) >= 1) {
			return false;
		}

		// Нет, не кликал. увеличиваем счётчик кликов
		// Получаем текущее значение
		$select = $this->db->select()
			->from($this->tblStatus, array('user_id', 'cnt_like'))
			->where('id = ?', $stId)
			->limit(1);
		$row = $this->db->fetchRow($select);
		$newCnt = $row['cnt_like'] + 1;
		$this->db->update($this->tblStatus, array('cnt_like'=>$newCnt), $this->db->quoteInto('id = ?', $stId));

		// Делаем запись о том, что этот пользователь кликнул по лайку
		$insert['user_id'] = $this->myId;
		$insert['status_id'] = $stId;
		$insert['dt_create'] = CURRENT_DATETIME;
		$this->db->insert($this->tblStatusLike, $insert);

		$ret['user_id'] = $row['user_id'];
		$ret['newCnt'] = $newCnt;
		return $ret;
	}

	/**
	 * Удаление (по факту просто скрытие) статуса.
	 * @param $statusId
	 */
	public function delete($statusId)
	{
		$where = $this->db->quoteInto('id = ?', $statusId) .' AND ' . $this->db->quoteInto('user_id = ?', $this->myId);
		$this->db->update($this->tblStatus, array('status_hide'=>'yes'), $where);
	}
}

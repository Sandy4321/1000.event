<?php

class Models_User_BlackList
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $lang = LANG_DEFAULT;

	private $myId = null;

	private $tblBlackList = array('bl' => 'user_blacklist');

	private $columnUserAvatar = array('avatar' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `users`.`id`, "/thumbnail.jpg" )');

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : (int) $myId;
	}

	/**
	 * Возвращает идентификаторы пользователей из чёрного списока пользователя.
	 *
	 * @return mixed
	 */
	public function getBlackListId() {
		$select = new Zend_Db_Select($this->db);
		$select->from('user_blacklist', 'bl_user_id');
		$select->where('user_id = ?', $this->myId);
		$select->order('date_create DESC');
		#Sas_Debug::dump($select->__toString());
		$rows = $this->db->fetchAll($select);

		return $rows;
	}


	public function getBlackListForSearch() {
		$select = new Zend_Db_Select($this->db);
		$select->from('user_blacklist', array('my_list'=>'bl_user_id', 'pipl_list'=>'user_id'));
		$select->where(
			'user_id = ' . $this->myId .' OR bl_user_id = ' . $this->myId
		);
		#Sas_Debug::dump($select->__toString());
		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Возвращает полную информацию о пользователях чёрного списка.
	 *
	 * @return mixed
	 */
	public function getBlackListAllInfo() {
		$select = $this->db->select()
			->from($this->tblBlackList, null)
			->where('user_id = ?', $this->myId);

		$select->join('users', 'users.id = bl.bl_user_id', array('user_id'=>'id', 'uid', 'first_name', 'online'))
			->columns($this->columnUserAvatar)
			->where('current_status = ?', 70) // только НЕ удалённые пользователи
			->order('bl.date_create DESC');

		$rows = $this->db->fetchAll($select);
		return $rows;
	}

	/**
	 * Возвраащет мой лист относительно партнера
	 * @param $myId
	 * @param $partnerId
	 * @return array
	 */
	public function isBlackList($myId, $partnerId) {
		$select = $this->db->select();
		$select->from('user_blacklist', 'bl_user_id')
			->where('user_id  = ?', $myId)
			->where('bl_user_id = ?', $partnerId)
			->limit(1);
		return $this->db->fetchRow($select);
	}

	/**
	 * Добавляет пользователя в чёрный список
	 *
	 * @param $addUserId
	 * @return bool
	 */
	public function addUser($addUserId) {
		$data = array(
			'bl_user_id'  => (int) $addUserId,
			'user_id'     => $this->myId,
			'date_create' => date('Y-m-d H:i:s')
		);
		try {
			$this->db->insert('user_blacklist', $data);
			Models_Actions::add(39, $this->myId, (int) $addUserId); // Добавлена запись в Чёрный список
		} catch (Zend_Db_Exception $e) {
			return false;
		}

		// и удаляем из фаворитов если пользователь там есть
		$ModelFavorites = new Models_User_Favorites($this->myId);
		$ModelFavorites->delUser($addUserId);
		#$del = $this->db->delete('user_favorites', 'user_id = ' . $this->myId .' AND favorite_user_id = ' . $addUserId);
		#if($del == 1) {
		#	Models_Actions::add(38, $this->myId, (int) $addUserId); // Удалена запись из Избранного
		#}

	}

	/**
	 * Удаляет пользователя из чёрного списка (моего)
	 *
	 * @param $delUserId ID удаляемого пользователя (не мой!)
	 */
	public function delUser($delUserId) {

		$where = $this->db->quoteInto('`bl_user_id` = ?', (int) $delUserId);
		$where .= ' AND ' . $this->db->quoteInto('`user_id` = ?', $this->myId);
		$this->db->delete('user_blacklist', $where);
		Models_Actions::add(40, $this->myId, (int) $delUserId); // Удалена запись из Чёрного списка
	}
}
<?php

class Models_User_Favorites
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $lang = LANG_DEFAULT;

	private $myId = null;

	private $tableFavorites = 'user_favorites';

	private $columnProfileAvatar = array('avatar' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : (int) $myId;
	}

	/**
	 * Возвращает список моих фаворитов
	 * @return array
	 */
	public function getFavoritesAllInfo() {
		$select = $this->db->select()
			->from($this->tableFavorites, array('favorite_user_id', 'favoriteStatus'=>'status'))
			->where('user_id = ?', $this->myId)
			->from(array('profile'=>'users'))
			->where('profile.id = favorite_user_id')
			->columns($this->columnProfileAvatar)
			->order('date_create DESC');

		#Sas_Debug::sql($select);
		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Возвращает ID всех моих фаворитов
	 * @return array
	 */
	public function getMyFavoritesId()
	{
		$select = $this->db->select()
			->from($this->tableFavorites, array('favorite_user_id'))
			->where('user_id = ?', $this->myId)
			->order('date_create DESC');

		$rows = $this->db->fetchPairs($select);

		return $rows;
	}

	/**
	 * Возвращает список ID моих фаворитов
	 * @return array
	 */
	public function getFavoritesID() {
		$select = $this->db->select()
			->from($this->tableFavorites, 'favorite_user_id')
			->where('user_id = ?', $this->myId);

		$rows = $this->db->fetchPairs($select);

		return $rows;
	}

	public function isFavorites($userId)
	{
		$select = $this->db->select()
			->from($this->tableFavorites, array('user_id', 'favorite_user_id', 'status'))
			->where('user_id = ' . $this->myId .' AND favorite_user_id = ' . $userId);

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Добавляет пользователя в список фаворитов
	 * @param $partnerId
	 * @return string status send|ok send = запрос отправлен (только я добавил), ok = взаимное добавление
	 */
	public function addUser($partnerId) {
		$data = array(
			'favorite_user_id' => $partnerId,
			'user_id'          => $this->myId,
			'date_create'      => date('Y-m-d H:i:s')
		);

		// Проверяем являюсь ли я УЖЕ фаворитом пользователя
		if($this->checkFavoriteMy($partnerId) == 1) {
			$data['status'] = 'ok';

			// Меняем статус для противоположной стороны
			$this->db->update($this->tableFavorites, array('status'=>'ok'), 'user_id = '.$partnerId.' AND favorite_user_id = '.$this->myId);
		} else {
			$data['status'] = 'send';
		}

		$this->db->insert($this->tableFavorites, $data);

		Models_Actions::add(37, $this->myId, $partnerId); // Добавлена запись в Фавориты

		// При добавлении в избранное удаляем человека из своего списка заблокированных
		$ModelBlackList = new Models_User_BlackList($this->myId);
		$ModelBlackList->delUser($partnerId);

		return $data['status'];
	}

	public function lastInsertId() {
		return $this->db->lastInsertId();
	}

	private function checkFavoriteMy($userId)
	{
		$select = $this->db->select()
			->from($this->tableFavorites, 'COUNT(*)')
			->where('favorite_user_id = ?', $this->myId)
			->where('user_id = ?', $userId)
			->limit(1);

		$row = $this->db->fetchOne($select);

		return $row;
	}

	/**
	 * Удаляет пользователя из (моего) списка фаворитов
	 *
	 * @param int $delUserId ID удаляемого пользователя (не мой!)
	 */
	public function delUser($delUserId)
	{
		$delUserId = (int) $delUserId;
		// Удаляем у меня
		$where = $this->db->quoteInto('`favorite_user_id` = ?', $delUserId);
		$where .= ' AND ' . $this->db->quoteInto('`user_id` = ?', $this->myId);
		$this->db->delete($this->tableFavorites, $where);
		Models_Actions::add(38, $this->myId, $delUserId); // Удалена запись из Фаворитов

		// Меняем у партнера статус, если я у него есть в избранном
		$where = $this->db->quoteInto('`user_id` = ?', (int) $delUserId);
		$where .= ' AND ' . $this->db->quoteInto('`favorite_user_id` = ?', $this->myId);
		$this->db->update($this->tableFavorites, array('status'=>'send'), $where);
	}
}
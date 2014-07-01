<?php

class Models_User_Hobby
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;


	private $tblHobby = 'hobby_list';

	private $tblHobbyUser = 'hobby_user';

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();
	}

	/**
	 * Возвращает список хобби
	 *
	 * @param array $minusHobbyId Массив ID которые надо исключить
	 * @return array
	 */
	public function getHobbyList($minusHobbyId = null)
	{
		$select = $this->db->select()
			->from($this->tblHobby, array('id', 'name'=>'name_'.$this->lang))
			->order('name_'.$this->lang);

		if(is_array($minusHobbyId)) {
			foreach($minusHobbyId as $minusId) {
				$select->where('id != ?', $minusId);
			}
		}

		return $this->db->fetchPairs($select);
	}

	/**
	 * Возвращает список интересов для массива пользователей
	 * @param array $usersId
	 * @return array
	 */
	public function getHobbyUsers(array $usersId) {
		$inId = implode(',', $usersId);
		$select = $this->db->select()
			->from($this->tblHobbyUser, '*')
			->where('user_id IN('.$inId.')')
			->order('user_id');
		$res = $this->db->fetchAll($select);
		$userId = 0;
		$out = array();
		foreach($res as $item) {
			#if($item['user_id'] != $usersId) {
			#	$usersId = $item['user_id'];
			#}
			$out[$item['user_id']][] = $item['hobby_id'];
		}

		return $out;
	}

	/**
	 * Возвращает хобби заданного пользователя
	 *
	 * @param $userId
	 * @return array
	 */
	public function getHobbyUser($userId) {
		$select = $this->db->select()
			->from(array('u'=>$this->tblHobbyUser), null)
			->joinLeft(array('h'=>$this->tblHobby), 'h.id=u.hobby_id',  array('id', 'name'=>'name_'.$this->lang))
			->where('u.user_id = ?', $userId)
			->order('h.name_'.$this->lang);

		return $this->db->fetchPairs($select);
	}

	/**
	 * Модифицирует записи
	 * @param $newHobbyArray
	 * @param $userId
	 */
	public function modify($newHobbyArray, $userId) {
		// Очищаем старые записи
		$this->db->delete($this->tblHobbyUser, $this->db->quoteInto('user_id = ?', $userId));

		// Записываем новые
		if(is_array($newHobbyArray)) {
			$data['user_id'] = $userId;
			foreach($newHobbyArray as $hobbyId) {
				$data['hobby_id'] = $hobbyId;
				#Sas_Debug::dump($hobbyId);
				$this->db->insert($this->tblHobbyUser, $data);
			}
		}
	}

	/**
	 * Добавляет новый список интересов пользователю.
	 *
	 * @param $hobbyArray
	 * @param $userId
	 * @return bool true
	 * @throws Sas_Models_Exception
	 */
	public function addList($hobbyArray, $userId) {
		// Записываем новые
		if(is_array($hobbyArray)) {
			$data['user_id'] = $userId;
			foreach($hobbyArray as $hobbyId) {
				$data['hobby_id'] = $hobbyId;
			}
			$this->db->insert($this->tblHobbyUser, $data);
		} else {
			throw new Sas_Models_Exception('Список интересов не является массивом');
		}

		return true;
	}

	/**
	 * Добавляет интерес пользователю.
	 *
	 * @param $hobbyId
	 * @param $userId
	 * @return bool
	 * @throws Sas_Models_Exception
	 */
	public function add($hobbyId, $userId) {
		if(!is_numeric($hobbyId) && !is_numeric($userId)) {
			throw new Sas_Models_Exception('ID интереса или пользователя отсутствуют.');
		}
		$data['user_id'] = (int)$userId;
		$data['hobby_id'] = (int)$hobbyId;
		$this->db->insert($this->tblHobbyUser, $data);

		return true;
	}

	/**
	 * Удаляет интерес у пользователя.
	 *
	 * @param $hobbyId
	 * @param $userId
	 * @return bool
	 * @throws Sas_Models_Exception
	 */
	public function delete($hobbyId, $userId) {
		if(!is_numeric($hobbyId) && !is_numeric($userId)) {
			throw new Sas_Models_Exception('ID интереса или пользователя отсутствуют.');
		}

		$where = $this->db->quoteInto('hobby_id = ?', $hobbyId);
		$where .= ' AND ';
		$where .= $this->db->quoteInto('user_id = ?', $userId);
		$this->db->delete($this->tblHobbyUser, $where);

		return true;
	}
}
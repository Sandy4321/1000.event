<?php

class Models_User_Target
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;


	private $tblTarget = 'target_list';

	private $tblTargetUser = 'target_user';

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();
	}

	/**
	 * Возвращает список жизненных целей
	 *
	 * @param array $minusTargetsId Массив ID которые надо исключить
	 * @return array
	 */
	public function getTargetList($minusTargetsId = null)
	{
		$select = $this->db->select()
			->from($this->tblTarget, array('id', 'name'=>'name_'.$this->lang))
			->order('name_'.$this->lang);

		if(is_array($minusTargetsId)) {
			foreach($minusTargetsId as $minusId) {
				$select->where('id != ?', $minusId);
			}
		}

		return $this->db->fetchPairs($select);
	}

	/**
	 * Возвращает список целей для массива пользователей
	 * @param array $usersId
	 * @return array
	 */
	public function getTargetUsers(array $usersId) {
		$inId = implode(',', $usersId);
		$select = $this->db->select()
			->from($this->tblTargetUser, '*')
			->where('user_id IN('.$inId.')')
			->order('user_id');
		$res = $this->db->fetchAll($select);
		$userId = 0;
		$out = array();
		foreach($res as $item) {
			$out[$item['user_id']][] = $item['target_id'];
		}

		return $out;
	}

	/**
	 * Возвращает цели заданного пользователя
	 *
	 * @param $userId
	 * @return array
	 */
	public function getTargetUser($userId) {
		$select = $this->db->select()
			->from(array('u'=>$this->tblTargetUser), null)
			->joinLeft(array('t'=>$this->tblTarget), 't.id=u.target_id',  array('id', 'name'=>'name_'.$this->lang))
			->where('u.user_id = ?', $userId)
			->order('t.name_'.$this->lang);

		return $this->db->fetchPairs($select);
	}

	/**
	 * Модифицирует записи
	 * @param $newTargetsArray
	 * @param $userId
	 */
	public function modify($newTargetsArray, $userId) {
		// Очищаем старые записи
		$this->db->delete($this->tblTargetUser, $this->db->quoteInto('user_id = ?', $userId));

		// Записываем новые
		if(is_array($newTargetsArray)) {
			$data['user_id'] = $userId;
			foreach($newTargetsArray as $targetId) {
				$data['target_id'] = $targetId;
				$this->db->insert($this->tblTargetUser, $data);
			}
		}
	}

	/**
	 * Добавляет новый список жизненных целей пользователю.
	 *
	 * @param $targetArray
	 * @param $userId
	 * @return bool true
	 * @throws Sas_Models_Exception
	 */
	public function addList($targetArray, $userId) {
		// Записываем новые
		if(is_array($targetArray)) {
			$data['user_id'] = $userId;
			foreach($targetArray as $targetId) {
				$data['target_id'] = $targetId;
			}
			$this->db->insert($this->tblTargetUser, $data);
		} else {
			throw new Sas_Models_Exception('Список жизненных целей не является массивом');
		}

		return true;
	}

	/**
	 * Добавляет жизненную цель пользователю.
	 *
	 * @param $targetId
	 * @param $userId
	 * @return bool
	 * @throws Sas_Models_Exception
	 */
	public function add($targetId, $userId) {
		if(!is_numeric($targetId) && !is_numeric($userId)) {
			throw new Sas_Models_Exception('ID цели или пользователя отсутствуют.');
		}
		$data['user_id'] = (int)$userId;
		$data['target_id'] = (int)$targetId;
		$this->db->insert($this->tblTargetUser, $data);

		return true;
	}

	/**
	 * Удаляет жизненную цель у пользователя.
	 *
	 * @param $targetId
	 * @param $userId
	 * @return bool
	 * @throws Sas_Models_Exception
	 */
	public function delete($targetId, $userId) {
		if(!is_numeric($targetId) && !is_numeric($userId)) {
			throw new Sas_Models_Exception('ID цели или пользователя отсутствуют.');
		}

		$where = $this->db->quoteInto('target_id = ?', $targetId);
		$where .= ' AND ';
		$where .= $this->db->quoteInto('user_id = ?', $userId);
		$this->db->delete($this->tblTargetUser, $where);

		return true;
	}
}
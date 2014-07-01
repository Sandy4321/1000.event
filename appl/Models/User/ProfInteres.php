<?php

class Models_User_ProfInteres
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;


	private $tblProf = 'prof_interes_list';

	private $tblProfUser = 'prof_interes_user';

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();
	}

	/**
	 * Возвращает список профессиональных интересов
	 *
	 * @param array $minusProfId Массив ID которые надо исключить
	 * @return array
	 */
	public function getProfList($minusProfId = null)
	{
		$select = $this->db->select()
			->from($this->tblProf, array('id', 'name'=>'name_'.$this->lang))
			->order('name_'.$this->lang);

		if(is_array($minusProfId)) {
			foreach($minusProfId as $minusId) {
				$select->where('id != ?', $minusId);
			}
		}

		return $this->db->fetchPairs($select);
	}

	/**
	 * Возвращает профессиональные интересы заданного пользователя
	 *
	 * @param $userId
	 * @return array
	 */
	public function getProfUser($userId) {
		$select = $this->db->select()
			->from(array('u'=>$this->tblProfUser), null)
			->joinLeft(array('p'=>$this->tblProf), 'p.id=u.prof_interes_id',  array('id', 'name'=>'name_'.$this->lang))
			->where('u.user_id = ?', $userId)
			->order('p.name_'.$this->lang);

		return $this->db->fetchPairs($select);
	}

	/**
	 * Модифицирует записи
	 * @param $newProfArray
	 * @param $userId
	 */
	public function modify($newProfArray, $userId) {
		// Очищаем старые записи
		$this->db->delete($this->tblProfUser, $this->db->quoteInto('user_id = ?', $userId));

		// Записываем новые
		if(is_array($newProfArray)) {
			$data['user_id'] = $userId;
			foreach($newProfArray as $profId) {
				$data['prof_interes_id'] = $profId;
				$this->db->insert($this->tblProfUser, $data);
			}
		}
	}

	/**
	 * Добавляет новый список профессиональных интересов пользователю.
	 *
	 * @param $profArray
	 * @param $userId
	 * @return bool true
	 * @throws Sas_Models_Exception
	 */
	public function addList($profArray, $userId) {
		// Записываем новые
		if(is_array($profArray)) {
			$data['user_id'] = $userId;
			foreach($profArray as $profId) {
				$data['prof_interes_id'] = $profId;
			}
			$this->db->insert($this->tblProfUser, $data);
		} else {
			throw new Sas_Models_Exception('Список профессиональных интересов не является массивом');
		}

		return true;
	}

	/**
	 * Добавляет профессиональный интерес пользователю.
	 *
	 * @param $profId
	 * @param $userId
	 * @return bool
	 * @throws Sas_Models_Exception
	 */
	public function add($profId, $userId) {
		if(!is_numeric($profId) && !is_numeric($userId)) {
			throw new Sas_Models_Exception('ID профессионального интереса или пользователя отсутствуют.');
		}
		$data['user_id'] = (int)$userId;
		$data['prof_interes_id'] = (int)$profId;
		$this->db->insert($this->tblProfUser, $data);

		return true;
	}

	/**
	 * Удаляет профессиональный интерес у пользователя.
	 *
	 * @param $profId
	 * @param $userId
	 * @return bool
	 * @throws Sas_Models_Exception
	 */
	public function delete($profId, $userId) {
		if(!is_numeric($profId) && !is_numeric($userId)) {
			throw new Sas_Models_Exception('ID профессионального интереса или пользователя отсутствуют.');
		}

		$where = $this->db->quoteInto('prof_interes_id = ?', $profId);
		$where .= ' AND ';
		$where .= $this->db->quoteInto('user_id = ?', $userId);
		$this->db->delete($this->tblProfUser, $where);

		return true;
	}
}
<?php

class Models_User_Places
{
	private $lang = LANG_DEFAULT;
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $table = 'places';
	private $tableUserPlace = 'user_places';

	private $tableCategory = 'places_categories';
	private $tableCity = 'cities';

	// Локализованные названия столбцов в таблице категорий мест
	private $columnsCategory = array();

	private $myId = null;

	public function __construct() {
		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->db = Zend_Registry::get('db');
		
		$this->myId = Models_User_Model::getMyId();
		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}

		$this->columnsCategory = array(
			'cat_id'=>'id',
			'cat_name' => 'name_' . $this->lang,
			'cat_sorting' => 'sorting'
		);
	}

	/**
	 * Возвращает все категории мест для свиданий
	 *
	 * @param null $categoryId
	 * @return mixed
	 */
	public function getCategory($categoryId = null)
	{
		$select = $this->db->select();
		$select->from($this->tableCategory, $this->columnsCategory);
		if(!is_null($categoryId)) {
			$select->where('`id` = ?', $categoryId);
			$result = $this->db->fetchRow($select);
		} else {
			$select->order('sorting ASC');
			$result = $this->db->fetchAll($select);
		}

		return $result;
	}

	/**
	 * Места свиданий по id категории
	 * @param     $category_id
	 * @param null $noId
	 * @return array
	 */
	public function getPlacesList($category_id, $noId = null) {

		$select = $this->db->select();
		$select->from($this->table);
		$select->where('`category_id` = ?', $category_id);
		if(!is_null($noId)) {
			$select->where('id NOT IN ('.$noId.')');
		}
		$select->order('name ASC');

		$result = $this->db->fetchAll($select);

		return $result;
	}

	/**
	 * Все места свиданий
	 * @param null $limit
	 * @return array
	 */
	public function getPlacesAll($limit = null) {

		$select = $this->db->select();

		$select->from(array('t1'=>$this->table),
					array('t1'=>'*'));

		$select->join(array('t2'=>$this->tableCategory),
				't1.category_id = t2.id',
				$this->columnsCategory);

		$select->join(array('c'=>$this->tableCity), 't1.city_id = c.id', array('cityName'=>'name_'.$this->lang));

		$select->order('cat_id ASC');

		if(!is_null($limit) && is_int($limit)) {
			$select->limit($limit);
		}

		$result = $this->db->fetchAll($select);

		return $result;
	}

	/**
	 * Возвращает id любимых мест пользователя
	 *
	 * @param null $userId
	 * @return mixed
	 */
	public function getFavorite($userId = null) {
		if (is_null($userId)) $userId = $this->myId;

		$select = $this->db->select();
		$select->from(array('f'=>$this->tableUserPlace), 'place_id')
			->where('user_id = ?', $userId);

		$rows = $this->db->fetchCol($select);

		#Sas_Debug::dump($rows);
		return $rows;
	}

	/**
	 * Возвращает id любимых мест пользователя
	 *
	 * @param null $userId
	 * @return mixed
	 */
	public function getFavoriteForSelect($userId = null) {
		if (is_null($userId)) $userId = $this->myId;

		$select = $this->db->select();
		/*$select->from(array('f'=>$this->tableUserPlace), 'place_id')
			->where('user_id = ?', $userId);*/

		$select->from(array('f'=>$this->tableUserPlace), 'place_id')
			->join(array('p'=>$this->table), 'f.place_id = p.id', array('name', 'category_id', 'descr_short', 'metro', 'address', 'site'))
			->where('user_id = ?', $userId)
			->order('p.sorting ASC');

		//$rows = $this->db->fetchCol($select);
		$rows = $this->db->fetchAssoc($select);
		#Sas_Debug::dump($rows);
		return $rows;
	}

	/**
	 * Возвращает места для свиданий (используется для дополнения списка мест при назначении свиданий)
	 * @param null $limit
	 * @param null $noId
	 * @return string
	 */
	public function getPlaces($limit = null, $noId = null) {

		$select = $this->db->select();

		$select->from(array('p'=>$this->table), array('place_id'=>'id', 'name', 'category_id', 'descr_short', 'metro', 'address', 'site'))
			->order('p.sorting ASC');

		if(!is_null($limit) && is_int($limit)) {
			$select->limit($limit);
		}

		if ($noId) {
			$select->where('p.`id` NOT IN ('.$noId.')');
		}

		$rows = $this->db->fetchAssoc($select);
		return $rows;
	}

	public function getPlacesMoreId($Ids) {

		$select = $this->db->select();

		$select->from(array('p'=>$this->table), array('place_id'=>'id', 'name', 'category_id', 'descr_short'))
			->order('p.sorting ASC');

		$select->where('p.`id` IN ('.$Ids.')');

		$rows = $this->db->fetchAssoc($select);
		return $rows;
	}

	/**
	 * Добавляет или удаляет место из списка любимых мест пользователя
	 *
	 * @param $placeId
	 * @return string save|delete
	 */
	public function addDelFavorite($placeId)
	{
		// Проверяем наличие записи
		$select = $this->db->select();
		$select->from($this->tableUserPlace, 'COUNT(*)')
			->where('place_id = ?', $placeId)
			->where('user_id = ?', Models_User_Model::getMyId());
		#Sas_Debug::dump($select->__toString());
		$row = $this->db->fetchOne($select);

		if($row == 0) // данных нет - записываем
		{
			$data = array(
				'user_id'  => Models_User_Model::getMyId(),
				'place_id' => $placeId
			);
			$this->db->insert($this->tableUserPlace, $data);
			$insertId = $this->db->lastInsertId();
			$check = 'save';
			Models_Actions::add(51, $this->myId, null, $insertId); // Место для свиданий отмечено как любимое
		}
		else // есть данные - удаляем
		{
			$where = 'user_id = ' .Models_User_Model::getMyId() . ' AND place_id = ' . $placeId;
			$this->db->delete($this->tableUserPlace, $where);
			$check = 'delete';
			Models_Actions::add(52, $this->myId, null, $placeId); // Место для свиданий удалено Любимых
		}

		return $check;
	}

	//============ MENU ===========
	/*static public function getMenu() {
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'places'),
			'name'  => 'Места свиданий',
			'check' => 'user/places',
			'style' => ' class="active"',
			'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'places', 'action' => 'restaurant'),
					'name'  => 'Рестораны и кафе',
					'check' => 'user/places/restaurant',
					'style' => ' class="active"',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'places', 'action' => 'coffee'),
					'name'  => 'Кофейни',
					'check' => 'user/places/coffee',
					'style' => ' class="active"',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'places', 'action' => 'museums-exhibitions'),
					'name'  => 'Выставки и музеи',
					'check' => 'user/places/museums-exhibitions',
					'style' => ' class="active"',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'places', 'action' => 'activities'),
					'name'  => 'Активный отдых',
					'check' => 'user/places/activities',
					'style' => ' class="active"',
				),
			)
		);

		return $menu;
	}*/
	//============ /MENU ===========
}

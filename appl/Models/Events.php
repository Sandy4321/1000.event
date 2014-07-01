<?php

class Models_Events
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $table = 'events';
	private $column = array();

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->column = array(
			'id',
			'keywords'    => 'keywords_' . $this->lang,
			'description' => 'description_' . $this->lang,
			'title'       => 'title_' . $this->lang,
			'anons'       => 'anons_' . $this->lang,
			'text'        => 'text_' . $this->lang,
			'date_create'
		);
	}

	/**
	 * Возвращает последние мероприятие для dashboard
	 * @return array
	 */
	public function getToDashboard()
	{
		$select = $this->db->select()
			->from($this->table, $this->column)
			->where('date_close >= ?', date('Y-m-d'))
			->order('date_create DESC')
			->limit(1);

		return $this->db->fetchRow($select);
	}

	/**
	 * Возвращает меропритие по его ID
	 * @param $id
	 * @return array
	 */
	public function getEvent($id)
	{
		$select = $this->db->select()
			->from($this->table, $this->column)
			->where('id = ?', (int)$id)
			->limit(1);

		$row = $this->db->fetchRow($select);

		return $row;
	}

	/**
	 * Возвращает последние мероприятия
	 * @param $limit
	 * @return array
	 */
	public function getLast($limit = 10)
	{
		$select = $this->db->select()
			->from($this->table, $this->column)
			->order('date_create DESC')
			->limit($limit);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает полный текст мероприятия.
	 * @param $id
	 * @return array
	 */
	public function getFullText($id)
	{
		$select = $this->db->select()
			->from($this->table, $this->column)
			->where('id = ?', $id);

		return $this->db->fetchRow($select);
	}

	/**
	 * Постраничный вывод мероприятий
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	public function getList($page = 1, $limit = 5)
	{
		unset($this->column['keywords'], $this->column['description'], $this->column['text']);

		$select = $this->db->select()
			->from($this->table, $this->column)
			->where('`title_' . $this->lang .'` != ""')
			->limitPage($page, $limit)
			->order('date_create DESC');

		return $this->db->fetchAll($select);
	}

	public function getPageMax() {
		$select = $this->db->select()
			->from($this->name, 'COUNT(*)')
			->where('`title_' . $this->lang .'` != ""');

		return $this->db->fetchOne($select);
	}
}
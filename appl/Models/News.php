<?php

class Models_News
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $table = 'news';
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
	 * Возвращает новость по её ID
	 * @param $id
	 * @return array
	 */
	public function getNews($id)
	{
		$cache = new Sas_Cache_Select(__METHOD__.(int)$id.$this->lang);
		if (!$row = $cache->load()) {
			$select = $this->db->select()
				->from($this->table, $this->column)
				->where('id = ?', (int)$id)
				->limit(1);

			$row = $this->db->fetchRow($select);
			$cache->save($row, 60); // Кеш на 1 мин.
		}

		return $row;
	}

	/**
	 * Возвращает последние новости (по умолчанию 10 шт.)
	 * @param int $limit
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
	 * Возвращает полный текст новости.
	 * @param $id
	 * @return array
	 */
	public function getFullText($id)
	{
		$cache = new Sas_Cache_Select(__METHOD__.(int)$id.$this->lang);
		if (!$row = $cache->load()) {
			$select = $this->db->select()
				->from($this->table, $this->column)
				->where('id = ?', $id);

			$row = $this->db->fetchRow($select);
			$cache->save($row, 60); // Кеш на 1 мин.
		}

		return $row;
	}

	/**
	 * Постраничный вывод новостей
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	public function getList($page = 1, $limit = 5)
	{
		$cache = new Sas_Cache_Select(__METHOD__.(int)$limit.$this->lang);
		if (!$rows = $cache->load()) {
			unset($this->column['keywords'], $this->column['description'], $this->column['text']);

			$select = $this->db->select()
				->from($this->table, $this->column)
				->where('`title_' . $this->lang .'` != ""')
				->limitPage($page, $limit)
				->order('date_create DESC');
			$rows = $this->db->fetchAll($select);
			$cache->save($rows, 180); // Кеш 3 мин.
		}
		return $rows;
	}

	public function getPageMax() {
		$select = $this->db->select()
			->from($this->name, 'COUNT(*)')
			->where('`title_' . $this->lang .'` != ""');

		return $this->db->fetchOne($select);
	}

	/**
	 * Известные люди о нас (для подвала 1 запись)
	 * @return array
	 */
	public function getOneQuote()
	{
		$cache = new Sas_Cache_Select(__METHOD__.$this->lang);
		if (!$row = $cache->load()) {
			$col = array(
				'text' => 'text_' . $this->lang,
				'author' => 'author_' . $this->lang,
				'authorJob' => 'author_job_' . $this->lang
			);
			$select = $this->db->select()
				->from('news_one_quote', $col)
				->order('RAND()')
				->limit(1);
			$row = $this->db->fetchRow($select);
			$cache->save($row);
		}

		return $row;
	}

	/**
	 * Известные люди о нас
	 * @return array
	 */
	public function getQuoteAll()
	{
		$cache = new Sas_Cache_Select(__METHOD__.$this->lang);
		if (!$rows = $cache->load()) {
			$col = array(
				'text' => 'text_' . $this->lang,
				'author' => 'author_' . $this->lang,
				'authorJob' => 'author_job_' . $this->lang
			);
			$select = $this->db->select()
				->from('news_one_quote', $col);

			$rows = $this->db->fetchAll($select);
			$cache->save($rows, 3600); // кеш на 1 час
		}

		return $rows;
	}

	/**
	 * О нас пишут
	 * @return array
	 */
	public function getMassMediaAll()
	{
		$col = array(
			'id',
			'title' => 'title_' . $this->lang,
			'anons' => 'anons_' . $this->lang,
			'url'
		);
		$select = $this->db->select()
			->from('news-mass-media', $col)
			->order('date_create DESC');

		return $this->db->fetchAll($select);
	}
}
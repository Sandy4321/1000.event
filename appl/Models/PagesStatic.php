<?php

/**
 * Модель статических страниц сайта
 */
class Models_PagesStatic
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $table = 'pages_static';
	private $column = array();

	private $module;
	private $controller;
	private $action;

	public function __construct(Sas_Controller_Action $controllerAction) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->column = array(
			'id', 'module', 'controller', 'action', 'keywords', 'descr',
			'content'      => 'content_' . $this->lang
		);

		$this->module     = $controllerAction->getModuleStart();
		$this->controller = $controllerAction->getControllerStart();
		$this->action     = $controllerAction->getActionStart();
	}

	// Возвращает все данные по странице (на основании START module/controller/action)
	public function getPage()
	{
		$param = str_replace('-', '', $this->module.$this->controller.$this->action.$this->lang);
		$cache = new Sas_Cache_Select(__METHOD__.$param);
		if (!$row = $cache->load()) {
			$select = $this->db->select();
			$select->from($this->table, $this->column)
				->where('module = ?',     $this->module)
				->where('controller = ?', $this->controller)
				->where('action = ?',     $this->action)
				->limit(1);
			$row = $this->db->fetchRow($select);
			$cache->save($row, 3600); // Кеш на 1 час
		}

		return $row;
	}
}

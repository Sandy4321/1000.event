<?php

class Models_Privilege
{
	private $id = null;
	private $cat_id = null;
	private $cat_name = null;
	private $title = null;
	private $anons = null;
	private $text = null;
	private $access_level = null;
	private $create_dt = null;

	/** @var Zend_Db_Adapter_Abstract */
	private $db;
	private $langInterface = LANG_DEFAULT;

	/** @var Zend_Translate */
	private $translate;

	private $tblPrivilege = array('priv' => 'privilege');
	private $colPrivilege = array('id', 'access_level', 'create_dt');

	private $tblPrivilegeCat = array('priv_cat' => 'privilege_cat');
	private $colPrivilegeImg = array('img' => 'CONCAT("/img/privilege/", `priv`.`id`, ".jpg")');

	public function __construct()
	{
		$this->db = Zend_Registry::get('db');

		$this->langInterface = Zend_Controller_Front::getInstance()->getPlugin('Sas_Controller_Plugin_Language')->getLocale();
		$this->translate     = Zend_Registry::get('Zend_Translate');

		$this->colPrivilege['title'] = 'title_' . $this->langInterface;
		$this->colPrivilege['anons'] = 'anons_' . $this->langInterface;
		$this->colPrivilege['text']  = 'text_'  . $this->langInterface;
	}

	public function getList($limit = 3)
	{
		$select = $this->db->select()
			->from($this->tblPrivilege, $this->colPrivilege)
			->columns($this->colPrivilegeImg)
			->order('priv.create_dt DESC')
			->limit($limit);

		$select->join($this->tblPrivilegeCat, 'priv.cat_id = priv_cat.id', array('cat_id'=>'id', 'cat_name'=>'name_'.$this->langInterface));

		return $this->db->fetchAll($select);
	}

	public function getPrivilege($id)
	{
		$select = $this->db->select()
			->from($this->tblPrivilege, $this->colPrivilege)
			->columns($this->colPrivilegeImg)
			->where('priv.id = ?', (int) $id)
			->limit(1);

		$select->join($this->tblPrivilegeCat, 'priv.cat_id = priv_cat.id', array('cat_id'=>'id', 'cat_name'=>'name_'.$this->langInterface));

		return $this->db->fetchRow($select);
	}

	public function getPrivilegeCat($cat_id)
	{
		$select = $this->db->select()
			->from($this->tblPrivilege, $this->colPrivilege)
			->columns($this->colPrivilegeImg)
			->where('priv_cat.id = ?', (int) $cat_id)
			->limit(1);

		$select->join($this->tblPrivilegeCat, 'priv.cat_id = priv_cat.id', array('cat_id'=>'id', 'cat_name'=>'name_'.$this->langInterface));

		return $this->db->fetchRow($select);
	}

	public function getCatAll()
	{
		$select = $this->db->select()
			->from($this->tblPrivilegeCat, array('cat_id'=>'id', 'cat_name'=>'name_'.$this->langInterface));

		return $this->db->fetchRow($select);
	}

	/**
	 * @return null
	 */
	public function getAccessLevel()
	{
		return $this->access_level;
	}

	/**
	 * @return null
	 */
	public function getAnons()
	{
		return $this->anons;
	}

	/**
	 * @return null
	 */
	public function getCatId()
	{
		return $this->cat_id;
	}

	/**
	 * @return null
	 */
	public function getCatName()
	{
		return $this->cat_name;
	}

	/**
	 * @return null
	 */
	public function getCreateDt()
	{
		return $this->create_dt;
	}

	/**
	 * @return null
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * @return null
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return null
	 */
	public function getId()
	{
		return $this->id;
	}


}
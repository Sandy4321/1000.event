<?php

class Models_Admin_Search
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $tblUserProfile = array('profile'=>'users');
	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');

	private $tableCity = 'cities';

	public function __construct() {
		$this->db = Zend_Registry::get('db');
	}

	public function quick($like)
	{
		$select = $this->db->select();
		$select->from($this->tblUserProfile, array('id', 'name' => 'CONCAT("ID: ", id, " ", last_name, " ", first_name, " ", email)'));

		if(is_numeric($like)) {
			$select->where('profile.id LIKE ?', $like.'%');
		} elseif(strpos($like, '@')) {
			$select->where('profile.email LIKE ?', $like.'%');
		} else {
			$select->where('profile.first_name LIKE ?', $like.'%');
			$select->orWhere('profile.last_name LIKE ?', $like.'%');
		}

		$select->limit(10);

		//Sas_Debug::dump($select->__toString());
//exit;
		return $this->db->fetchAll($select);
	}
}
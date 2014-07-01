<?php

class Models_User_Friend
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $lang = LANG_DEFAULT;

	private $myId = null;

	#private $tableFriend = 'users_data';
	private $tableFriend = 'users';
	private $columnPromoKey = 'promo_key';
	private $myPromoKey = null;

	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = Models_User_Model::getMyId();

		$this->setMyPromoKey();
	}

	public function getMyPromoKey()
	{
		if (is_null($this->myPromoKey)) {
			$this->setMyPromoKey();
		}

		return $this->myPromoKey;
	}

	private function setMyPromoKey()
	{
		$select = $this->db->select()
			->from($this->tableFriend, $this->columnPromoKey)
			->where('id = ?', $this->myId)
			->limit(1);

		$this->myPromoKey = $this->db->fetchOne($select);
		return $this->myPromoKey;
	}

	/**
	 * Возвращает профили моих друзей
	 * @return array
	 */
	public function getMyFriend()
	{
		$select = $this->db->select();
		$select->from(array('profile'=>$this->tableFriend), '*')
			->columns($this->columnProfileImg)
			->where($this->columnPromoKey . '_friend = ?', $this->getMyPromoKey())
			->where('current_status >= ?', 70)
			->order('register_dt DESC');

		#Sas_Debug::dump($select->__toString());
		return $this->db->fetchAll($select);
	}
}
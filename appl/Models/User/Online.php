<?php

class Models_User_Online
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $myId = null;

	private $tblOnline = 'users';
	private $colOnlineKey = 'online';
	private $colOnlineDate = 'online_last_dt';

	public function __construct($myId) {
		$this->db = Zend_Registry::get('db');

		if (!is_numeric($myId)) {
			throw new Sas_Exception('ERROR NO MY ID - online');
		}

		$this->myId = $myId;
	}

	/**
	 * Устанавливает пользователя online.
	 */
	public function setOnline()
	{
		$data = array(
			$this->colOnlineKey => 'yes',
			$this->colOnlineDate => date('Y-m-d H:i:s')
		);

		$this->db->update($this->tblOnline, $data, $this->db->quoteInto('id = ?', $this->myId));
	}

	/**
	 * Устанавливает пользователей в offline.
	 */
	public function setOffline($minute = 3)
	{
		$date = new DateTime('now');
		$date->sub(new DateInterval('PT' . $minute . 'M'));
		$sql = 'UPDATE `users` AS tbl_1 '.
			'INNER JOIN(SELECT `id` AS nid FROM `users` WHERE "'. $date->format('Y-m-d H:i:s') .'" > `online_last_dt` AND `online` = "yes") AS tbl_2 '.
			'SET tbl_1.`online` = "no" WHERE tbl_1.`id` = nid';

		$this->db->query($sql);
	}
}
<?php

class Models_Orders
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $tbl = 'news';

	public function __construct() {
		$this->db = Zend_Registry::get('db');
	}

	/**
	 * На основании ID пользователя создаёт номер счёта
	 *
	 * @param $userId
	 * @return string
	 */
	public function createNumberInvoice ($userId)
	{
		return time() . $userId;
	}


	public function setNewOrder($userId, $money, $karat)
	{
		$numberInvoice = $this->createNumberInvoice($userId);

		$insert['user_id'] = (int) $userId;
		$insert['number_invoice'] = $numberInvoice;
		$insert['money'] = (int) $money;
		$insert['karat'] = (int) $karat;
		$insert['status'] = 'created';
		$insert['date_create'] = date('Y-m-d H:i:s');

		$res = $this->db->insert($this->tbl, $insert);

		if($res != 1) {
			return false;
		}

		return $numberInvoice;
	}
}
<?php

class Models_Actions
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	static private $tblLog = 'v2_actions_log';
	static private $tblCat = 'v2_actions_log_category';
	static private $tblSearch = 'v2_search_log';

	/*public function __construct() {
		$this->db = Zend_Registry::get('db');
	}*/

	/**
	 * Добавление новой записи в лог действий
	 *
	 * @param int  $catId ID категории
	 * @param null|int $primaryUserId ID пользователя сгеренировавшего событие
	 * @param null|int $toUserId ID пользователя в отношении которого было сгенерировано событие
	 * @param null|int  $serviceId вспомогательный сервисный ID для указания какой то конкретной записи в db (используется при подстановке в таблицу категорий действий)
	 */
	static public function add($catId, $primaryUserId = null, $toUserId = null, $serviceId = null)
	{
		$db = Zend_Registry::get('db');
		$insert['category_id']     = (int) $catId;
		$insert['primary_user_id'] = (!is_null($primaryUserId)) ? (int) $primaryUserId : null;
		$insert['to_user_ud']      = (!is_null($toUserId)) ? (int) $toUserId : null;
		$insert['service_id']      = (!is_null($serviceId)) ? (int) $serviceId: null;
		$insert['date_create']     = date('Y-m-d H:i:s');

		$db->insert(self::$tblLog, $insert);

		// Активность пользователя на сайте (в профиль)
		if (!is_null($primaryUserId)) {
			$db->update('users', array('online_last_dt'=>date('Y-m-d H:i:s')), 'id = '. $primaryUserId);
		}
	}

	static public function searchLog($data)
	{
		$db = Zend_Registry::get('db');
		foreach($data as $key => $val) {
			$insert[$key] = $val;
		}
		$insert['date_create'] = date('Y-m-d H:i:s');

		$db->insert(self::$tblSearch, $insert);
	}
}
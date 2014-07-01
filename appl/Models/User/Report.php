<?php

class Models_User_Report
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	/**
	 * @var Zend_Translate
	 */
	private $tr;

	private $tblReport  = 'user_reports';

	private $myId = null;

	public function __construct($userId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->tr = Zend_Registry::get('Zend_Translate');

		$this->myId = (is_null($userId)) ? Models_User_Model::getMyId() : $userId;
	}

	/**
	 * Сохранение отчёта о человеке
	 * @param      $partnerId
	 * @param      $rating
	 * @param null $reportText
	 * @return int
	 */
	public function save($partnerId, $rating, $reportText = null) {
		$data['user_id'] = $this->myId;
		$data['user_id_about'] = $partnerId;
		$data['rating'] = (int)$rating;
		$text = htmlspecialchars(strip_tags(trim($reportText)));
		$data['report_text'] = (empty($text)) ? null : $text;
		$data['dt'] = CURRENT_DATETIME;

		return $this->db->insert($this->tblReport, $data);
	}
}
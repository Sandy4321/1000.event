<?php

/**
 * Модель отчётов о прошедших свиданиях.
 * Class Models_User_DatesReport
 */
class Models_User_DatesReport
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	private $myId = null;

	private $tableReport  = 'user_dates_reports';

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = Models_User_Model::getMyId();
		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	/**
	 * Сохраняет отчёт пользователя о свидании.
	 *
	 * @param int $dates_id ID свидания
	 * @param array $data
	 * @return int
	 */
	public function saveReport($dates_id, $data)
	{
		$insertData = array(
			'user_id' => $this->myId,
			'dates_id' => (int) $dates_id,
			'date_time_create' => date('Y-m-d H:i:s')
		);

		// Свидание не состоялось по причине: было перенесено,не созвонились, я отказался, партнёр не пришел
		if ($data['no_dates'] == 'revers' || $data['no_dates'] == 'cancel' || $data['no_dates'] == 'my_not_come' ||
			$data['no_dates'] == 'partner_not_come') {
			$insertData['no_dates'] = $data['no_dates'];
		}

		// Реальная дата и время встречи
		//$insertData['date_time_real'] = ($ut = strtotime($data['date_time_real']) !== false) ? date('Y-m-d H:i:s', $ut) : null;
		$insertData['date_time_real'] = (!empty($data['date_time_real'])) ?$data['date_time_real'] : null;

		// Комментарий по свиданию в целом
		$data['comment_dates'] = htmlspecialchars(strip_tags(trim($data['comment_dates'])));
		$insertData['comment_dates'] = (!empty($data['comment_dates'])) ? $data['comment_dates'] : null;

		// ID рейтингуемого места свидания
		$insertData['rating_place_id'] = (is_numeric($data['rating_place_id'])) ? (int) $data['rating_place_id'] : null;

		// Рейтинг места свидания
		$insertData['rating_place'] = ($data['rating_place'] >= 1 && $data['rating_place'] <= 10) ? (int) $data['rating_place'] : null;

		// Комментарий о месте свидания
		$data['comment_place'] = htmlspecialchars(strip_tags(trim($data['comment_place'])));
		$insertData['comment_place'] = (!empty($data['comment_place'])) ? $data['comment_place'] : null;

		// Интересность собеседника: скучный, средне, интересный, очень интересный
		if ($data['conversationalist'] == 'boring' || $data['conversationalist'] == 'medium' || $data['conversationalist'] == 'interesting' ||
			$data['conversationalist'] == 'very_interesting') {
			$insertData['conversationalist'] = $data['conversationalist'];
		}

		// ID рейтингуемого партнёра по свиданию
		$insertData['rating_face_id'] = (is_numeric($data['rating_face_id'])) ? (int) $data['rating_face_id'] : null;

		// Рейтинг внешних данных
		$insertData['rating_face'] = ($data['rating_face'] >= 1 && $data['rating_face'] <= 10) ? (int) $data['rating_face'] : null;

		// Хотите встретиться с этим человеком еще раз
		if ($data['new_dates'] == 'no' || $data['new_dates'] == 'yes') {
			$insertData['new_dates'] = $data['new_dates'];
		}

		// Манеры поведения: плохие, средние, хорошие, очень хорошие
		if ($data['demeanor'] == 'poorly' || $data['demeanor'] == 'medium' || $data['demeanor'] == 'good' ||
			$data['demeanor'] == 'very_good') {
			$insertData['demeanor'] = $data['demeanor'];
		}

		// Где реально прошло свидание
		$data['where_place'] = htmlspecialchars(strip_tags(trim($data['where_place'])));
		$insertData['where_place'] = (!empty($data['where_place'])) ? $data['where_place'] : null;

		$insertId = $this->db->insert($this->tableReport, $insertData);

		if(!empty($insertData['no_dates'])) {
			Models_Actions::add(13, $this->myId, null, $insertId); // Оставил отзыв о не состоявшемся свидании
		} else {
			Models_Actions::add(12, $this->myId, null, $insertId); // Оставил отзыв о состоявшемся свидании
		}

		return $insertId;
	}

	/**
	 * Возвращает (при наличии репорта партнёра) выбор партнёра относительно
	 * вопроса "Вы хотели бы встретиться с этим человеком еще раз?"
	 *
	 * @param $datesId
	 * @return string
	 */
	public function getPartnerReport($datesId) {
		$select = $this->db->select()
			->from($this->tableReport, 'new_dates')
			->where('dates_id = ?', $datesId)
			->limit(1);
		$row = $this->db->fetchOne($select);
		if ($row == 'yes') {
			// Получаем ID партнёра из тбл свидания
			$select->reset();
			$select->from(array('d'=>'user_dates'), null)
				->where('d.id = ?', $datesId);

			#$select->join(array('profile'=>'users_data'), '(invitee_id = profile.id OR inviter_id = profile.id) AND profile.id != '.$this->myId, '*');
			$select->join(array('profile'=>'users'), '(invitee_id = profile.id OR inviter_id = profile.id) AND profile.id != '.$this->myId, '*');
			$row = $this->db->fetchOne($select);
			return $row;
		}

		return false;
	}
}
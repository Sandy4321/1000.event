<?php

/**
 * Модель обмена номерами телефонов пользователей
 */
class Models_User_ExchangePhone
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $tblExchange = array('ex' => 'contact_exchange');

	/**
	 * @var Models_Users
	 */
	private $My;

	/**
	 * @var Models_Users
	 */
	private $Partner;

	private $id;
	private $box;
	private $status;
	private $archive;
	private $date_create;

	public function __construct(Models_Users $My, Models_Users $Partner)
	{
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->My = $My;
		$this->Partner = $Partner;

		$this->init();
	}

	/**
	 * Инициализация и получение первичных данных.
	 */
	private function init()
	{
		$select = $this->db->select()
			->from($this->tblExchange, '*')
			->where('user_id = ?',      $this->My->getId())
			->where('user_id_from = ?', $this->Partner->getId())
			->order('date_create DESC')
			->limit(1);

		$row = $this->db->fetchRow($select);

		if($row) {
			$this->id          = (int)$row['id'];
			$this->box         = $row['box'];
			$this->status      = $row['status'];
			$this->archive     = $row['archive'];
			$this->date_create = $row['date_create'];
		}
	}

	/**
	 * Принять предложение об обмене номерами.
	 * @param $msgId
	 * @return bool
	 */
	public function exchangeYes($msgId)
	{
		// Отмечаем у Меня (у получателя предложения по обмену)
		$data = array(
			'status'           => 'yes',
			//'archive'          => 'yes',
			'date_last_action' => CURRENT_DATETIME,
		);
		$where1 = $this->db->quoteInto('user_id = ?', $this->My->getId());
		$where1 .= ' AND ';
		$where1 .= $this->db->quoteInto('user_id_from = ?', $this->Partner->getId());
		$where1 .= ' AND ';
		$where1 .= $this->db->quoteInto('status = ?', 'new');


		// Отмечаем у Партнёра (у отправителя предложения по обмену)
		$where2 = $this->db->quoteInto('user_id = ?', $this->Partner->getId());
		$where2 .= ' AND ';
		$where2 .= $this->db->quoteInto('user_id_from = ?', $this->My->getId());
		$where2 .= ' AND ';
		//$where2 .= 'status IS NULL';
		$where2 .= $this->db->quoteInto('status = ?', 'new');

		// Фактически фиксируем изменения в бд
		$this->db->beginTransaction();
		try {
			$this->db->update($this->tblExchange, $data, $where1);
			$this->db->update($this->tblExchange, $data, $where2);
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			return false;
		}

		// Удаляем скрываем сообщение о согласии на обмен у Меня
		$ModelMsg = new Models_User_Msg($this->My->getId());
		$ModelMsg->hideMsg($msgId);

		// Отправляем участникам новые сообщения о принятии положительного решения по обмену номерами телефонов.
		$ModelMsg->exchangePhoneYes($this->My, $this->Partner);

		// Информируем партнёра (отправителя предложения) о положительном решении по обмену номерами телефонов.
		try {
			$ModelSendMsg = new Models_TemplatesMessage($this->Partner->getProfileToArray(), 'exchange_contact_yes', 'msg_invite');
			$ModelSendMsg->addDataReplace('my_name', $this->My->getFirstName());
			$ModelSendMsg->send();
		} catch (Sas_Exception $e) {
			// TODO: записать в лог
		}

		// Пишем ПАРТНЕРУ на Dashboard о положительном обмене номерами телефонов
		$ModelDash = new Models_User_Dashboard();
		$msgDashId = ($this->My->isFemale()) ? 13 : 14;
		$ModelDash->sendToDash($this->Partner->getId(), $msgDashId, 'ContactExchangeSend', 0);

		// Логируем - Предложение по обмену контактами принято
		Models_Actions::add(6, $this->My->getId(), $this->Partner->getId());

		return true;
	}

	/**
	 * Отклонить предложение об обмене номерами.
	 * @param $msgId
	 * @return bool
	 */
	public function exchangeNo($msgId)
	{
		// Отмечаем у Меня (у получателя предложения по обмену)
		$data = array(
			'status'           => 'reject',
			//'archive'          => 'yes',
			'date_last_action' => CURRENT_DATETIME,
		);
		$where1 = $this->db->quoteInto('user_id = ?', $this->My->getId());
		$where1 .= ' AND ';
		$where1 .= $this->db->quoteInto('user_id_from = ?', $this->Partner->getId());
		$where1 .= ' AND ';
		$where1 .= $this->db->quoteInto('status = ?', 'new');


		// Отмечаем у Партнёра (у отправителя предложения по обмену)
		$where2 = $this->db->quoteInto('user_id = ?', $this->Partner->getId());
		$where2 .= ' AND ';
		$where2 .= $this->db->quoteInto('user_id_from = ?', $this->My->getId());
		$where2 .= ' AND ';
		//$where2 .= 'status IS NULL';
		$where2 .= $this->db->quoteInto('status = ?', 'new');

		// Фактически фиксируем изменения в бд
		$this->db->beginTransaction();
		try {
			$this->db->update($this->tblExchange, $data, $where1);
			$this->db->update($this->tblExchange, $data, $where2);
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			return false;
		}

		// Удаляем скрываем сообщение об обмене у Меня
		$ModelMsg = new Models_User_Msg($this->My->getId());
		$ModelMsg->hideMsg($msgId);

		// Отправляем участникам новые сообщения об отказе
		$ModelMsg->exchangePhoneNo($this->My, $this->Partner);

		// Информируем партнёра (отправителя предложения) об отказе обменяться телефонами
		try {
			// TODO: пофиксить по всему проекту орфографию в exchnage_contact_no на exchange_contact_no в т.ч. в и db
			$ModelSendMsg = new Models_TemplatesMessage($this->Partner->getProfileToArray(), 'exchnage_contact_no', 'msg_invite');
			$ModelSendMsg->addDataReplace('my_name', $this->My->getFirstName());
			$ModelSendMsg->send();
		} catch (Sas_Exception $e) {
			// TODO: записать в лог
		}

		// Пишем ПАРТНЕРУ на Dashboard отказ от обмена номерами телефонов
		$ModelDash = new Models_User_Dashboard();
		$msgDashId = ($this->My->isFemale()) ? 9 : 10;
		$ModelDash->sendToDash($this->Partner->getId(), $msgDashId, 'ContactExchangeSend', 0);

		// Логируем - Предложение по обмену контактами отклонено
		Models_Actions::add(7, $this->My->getId(), $this->Partner->getId());

		return true;
	}

	/**
	 * Отправляет запрос на обмен номерами телефонов.
	 *
	 * @return bool
	 */
	public function sendExchange()
	{
		// Отправитель запроса
		$data1 = array(
			'user_id'      => $this->My->getId(),
			'user_id_from' => $this->Partner->getId(),
			'box'          => 'send',
			'status'       => 'new',
			'date_create'  => CURRENT_DATETIME
		);

		// Получатель запроса
		$data2 = array(
			'user_id'      => $this->Partner->getId(),
			'user_id_from' => $this->My->getId(),
			'box'          => 'inbox',
			'status'       => 'new',
			'date_create'  => CURRENT_DATETIME
		);

		$this->db->beginTransaction();
		try {
			$this->db->insert($this->tblExchange, $data1);
			$this->db->insert($this->tblExchange, $data2);
			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollBack();
			return false;
		}

		// Пишем сообщение о предложении обмена в переписку людей
		$ModelMsg = new Models_User_Msg($this->My->getId());
		$ModelMsg->exchangePhoneSend($this->My, $this->Partner);

		// Пишем на даш
		$ModelDash = new Models_User_Dashboard($this->My);
		$ModelDash->sendToDash($this->Partner->getId(), 8, 'ContactExchangeInbox', 0);

		// Логируем - Предложено обменяться контактами
		Models_Actions::add(5, $this->My->getId(), $this->Partner->getId());

		// Информируем пользователя о желании обменяться телефонами
		try {
			$ModelTplMsg = new Models_TemplatesMessage($this->Partner->getProfileToArray(), 'exchange_contact', 'msg_invite');
			$ModelTplMsg->addDataReplace('my_name', $this->My->getFirstName());
			$ModelTplMsg->send();
		} catch (Sas_Exception $e) {
			// TODO: записать в лог
		}

		return true;
	}

	/**
	 * Проверка был ли обмен контактами между пользователями.
	 *
	 * @return bool
	 */
	public function isExchange()
	{
		return (is_int($this->id)) ? true : false;
	}

	/**
	 * Возвращает текущий статус обмена телефонами между пользователями.
	 *
	 * @return null|string
	 */
	public function getStatus()
	{
		return (is_string($this->status)) ? $this->status : null;
	}

	/**
	 * Возвращает "направление" обмена номерами телефонов.
	 *
	 * @return string null|in|out
	 */
	public function getBox() {
		if(is_null($this->box)) return null;
		return ($this->box == 'inbox') ? 'in' : 'out';
	}

	/**
	 * Возвращает номер телефона ПАРТНЕРА в зависимости от КК пользователя.
	 * @return string
	 */
	public function getPhone()
	{
		return ($this->My->getClubCard() >= CURRENT_DATE) ? $this->Partner->getPhoneFormat() : $this->Partner->getPhoneFormatHide();
	}
}
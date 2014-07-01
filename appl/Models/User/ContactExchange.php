<?php

/**
 * Модель обмена пользовательскими контактами
 */
class Models_User_ContactExchange
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	private $myId = null;

	private $tableContact = 'contact_exchange';
	#private $tableProfile = 'users_data';
	private $tableProfile = 'users';

	private $columnProfileStandart = array(
		'userId'=>'id',
		'uid',
		'firstName'=>'first_name',
		'lastName'=>'last_name',
		'sex',
		'birthday',
		'height',
		'children',
		'smoking',
		'phone',
		'phone_check',
		'balance',
		'balance_bonus',
		'online_last_dt',
		'current_status'
	);

	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');
	private $columnProfileAvatar = array('avatar' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : $myId;
		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	/**
	 * Подтверждает обмен контактами.
	 *
	 * @param $myRecordId
	 * @param $fromRecordId
	 * @param $debitReal
	 * @param $debitBonus
	 * @return int Возвращает ID пользователя с которым был обмен
	 */
	public function yesExchange($myRecordId, $fromRecordId, $debitReal, $debitBonus)
	{
		// Отправитель
		$curDate = date('Y-m-d H:i:s');
		$data = array(
			'status'           => 'yes',
			'real_karat'       => $debitReal,
			'bonus_karat'      => $debitBonus,
			'date_last_action' => $curDate,
		);

		$where = $this->db->quoteInto('id = ?', $myRecordId);
		$this->db->update($this->tableContact, $data, $where);

		$data = array(
			'status'           => 'yes',
			'date_last_action' => $curDate,
		);

		$where = $this->db->quoteInto('id = ?', $fromRecordId);
		$this->db->update($this->tableContact, $data, $where);

		$select = $this->db->select();
		$select->from($this->tableContact, 'user_id_from')
			->where('id = ?', $myRecordId)
			->limit(1);

		$row = $this->db->fetchOne($select);

		Models_Actions::add(6, $this->myId, $row); // Предложение по обмену контактами принято

		return (int) $row;
	}

	/**
	 * Проверяет статус приглашения.
	 * Используется для ajax при подтверждении принятия обмена контактами
	 * @param $recordId
	 * @return string|null
	 */
	public function checkStatusSender($recordId)
	{
		$select = $this->db->select();
		$select->from(array('c1'=>$this->tableContact), 'status');
		$select->where('id = ?', $recordId);
		$select->limit(1);
		return $this->db->fetchOne($select);
	}

	/**
	 * Убирает записть в архив
	 */
	public function sendArchive($myRecordId)
	{
		$this->db->update($this->tableContact, array('archive'=>'yes', 'date_last_action'=>date('Y-m-d H:i:s')), $this->db->quoteInto('id = ?', $myRecordId));
	}

	/**
	 * Возвращает данные из истории
	 */
	public function getHistory()
	{
		$select = $this->db->select();
		$select->from(array('c1'=>$this->tableContact), '*')
			->where('user_id = ?', $this->myId)
			->where('archive = ?', 'yes')
			->order('date_last_action DESC');
		$select->join(array('profile'=>$this->tableProfile), 'c1.user_id_from = profile.id', $this->columnProfileStandart)
			->columns($this->columnProfileImg);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает номера с которыми был успешный обмен.
	 *
	 * @param Models_Users $MyProfile
	 * @return array
	 */
	public function getExchangeYes(Models_Users $MyProfile)
	{
		$myId = $MyProfile->getId();
		$myCard = $MyProfile->getClubCard();
		$langUrl = ($this->lang == 'ru') ? '' : '/'.$this->lang;
		$msg = $MyProfile->t('Только владелец Клубной карты может видеть полный номер телефона или перейти в профиль.');

		$select = $this->db->select()
			->from(array('c'=>$this->tableContact), '*')
			->where('user_id = ?', $myId)
			->where('status = ?', 'yes')
			->group('c.user_id_from')
			->order('date_last_action DESC');

		$select->join(array('profile'=>$this->tableProfile), 'c.user_id_from = profile.id', $this->columnProfileStandart)
			->columns($this->columnProfileAvatar)
			->columns('profile.first_name');

		$rows = $this->db->fetchAll($select);
		$return = array();
		if(!empty($rows)) {
			$i = 0;
			foreach($rows as $user) {
				$return[$i] = $user;
				$return[$i]['url_profile'] = ($myCard >= CURRENT_DATE) ? $langUrl.'/user/people/profile/view/' . $user['uid'] : 'javascript:void(0)" onclick="goBuyCard(\''.$msg.'\')"';

				$phone = $this->getPhoneFormat($user['phone']);
				if($myCard >= CURRENT_DATE) {
					$return[$i]['phone'] = $phone;
				} else {
					$return[$i]['phone'] = substr($phone, 0, -5) .'XX-XX';
				}

				$i++;
			}
		}
		return $return;
	}

	/**
	 * Форматирование номера телефона.
	 *
	 * @param $number
	 * @return string
	 */
	public function getPhoneFormat($number)
	{
		$phone  = '';
		$phone .= substr($number, 0, -10) . ' ';
		$phone .= '(' . substr($number, -10, 3) . ') ';
		$phone .= substr($number, -7, 3) . '-';
		$phone .= substr($number, -4, 2) . '-';
		$phone .= substr($number, -2, 2);

		return $phone;
	}

	/**
	 * Возвращает последний статус обмена
	 * @param $myId
	 * @param $partnerId
	 * @return string
	 */
	public function getLastStatus($myId, $partnerId) {
		$select = $this->db->select();
		$select->from($this->tableContact, 'status')
			->where('(user_id = '.$myId.' AND user_id_from = '.$partnerId.') OR (user_id = '.$partnerId.' AND user_id_from = '.$myId.')')
			->order('date_create DESC')
			->limit(1);
		return $this->db->fetchOne($select);
	}

	/**
	 * Проверка на возможности создать обмена (разрешен обмен при статусе у ПОЛУЧАТЕЛЯ)
	 * @param $userId
	 * @return string
	 */
	public function checkCreateExchange($userId)
	{
		$select = $this->db->select();
		$select->from(array('c1'=>$this->tableContact), array(
			'myRecId'  => 'id',
			'myId'     => 'user_id',
			'myStatus' => 'status'
		));
		$select->join(
			array('c2'=>$this->tableContact),
			'c1.user_id_from = c2.user_id AND c1.date_create=c2.date_create',
			array('fromRecId'  => 'id',
				  'fromId'     => 'user_id',
				  'fromStatus' => 'status')
		);

		$select->where('c1.user_id = ?', $this->myId);
		$select->where('c1.user_id_from = ?', $userId);
		$select->where('(`c1`.`status` IS NULL OR `c2`.`status` IS NULL) AND ((`c1`.`status` = "read" OR `c2`.`status` = "read") OR (`c1`.`status` = "new" OR `c2`.`status` = "new"))');

		//$this->printSelect($select);
		//exit;
		return $this->db->fetchRow($select);
	}

	/**
	 * Возвращает все полученные запросы на обмен контактами которые не находятся в архиве
	 */
	public function getInbox()
	{
		$select = $this->db->select();
		$select->from(array('c1'=>$this->tableContact),
			array('myRecordId'       => 'id',
				  'myDateCreate'     => 'date_create',
				  'myDateLastAction' => 'date_last_action',
				  'myBox'            => 'box',
				  'myStatus'         => 'status',
				  'myArchive'        => 'archive'
			)
		);

		$select->where('c1.user_id = ?', $this->myId); // мои сообщения
		$select->where('c1.box = ?', 'inbox'); // в папке Inbox (входящие)
		$select->where('c1.archive = ?', 'no'); // которые не в архиве
		$select->order('c1.date_create DESC');

		// Цепляем профиль отправителя
		$select->join(array('profile'=>$this->tableProfile), 'c1.user_id_from = profile.id', $this->columnProfileStandart);
		$select->columns($this->columnProfileImg);

		// Цепляем отправителя
		$select->join(array('c2'=>$this->tableContact),
			'c1.user_id_from = c2.user_id AND c2.box = "send" AND c1.date_create=c2.date_create',
			array(
				'fromRecordId'       => 'id',
				'fromDateLastAction' => 'date_last_action',
				'fromDateCreate'     => 'date_create',
				'fromStatus'         => 'status'
			)
		);

		#$this->printSelect($select, 'ВХОДЯЩИЕ');
		$rows = $this->db->fetchAll($select);
		#Sas_Debug::dump($rows, 'ВХОДЯЩИЕ');

		// Меняем статус на прочитано
		$where = 'user_id = "'.$this->myId.'" AND box = "inbox" AND status = "new"';
		$this->db->update($this->tableContact, array('status'=>'read', 'date_last_action' => date('Y-m-d H:i:s')), $where);

		return $rows;
	}

	/**
	 * Возвращает все отправленные запросы на обмен контактами которые не находятся в архиве
	 */
	public function getSend()
	{
		$select = $this->db->select();
		$select->from(array('c1'=>$this->tableContact),
			array('myRecordId'       => 'id',
				  'myDateCreate'     => 'date_create',
				  'myDateLastAction' => 'date_last_action',
				  'myBox'            => 'box',
				  'myStatus'         => 'status',
				  'myArchive'        => 'archive'
			)
		);

		$select->where('c1.user_id = ?', $this->myId); // мои сообщения
		$select->where('c1.box = ?', 'send'); // в папке Send (отправленные)
		$select->where('c1.archive = ?', 'no'); // которые не в архиве
		$select->order('c1.date_create DESC');

		// Цепляем профиль отправителя
		$select->join(array('profile'=>$this->tableProfile), 'c1.user_id_from = profile.id', $this->columnProfileStandart);
		$select->columns($this->columnProfileImg);

		// Цепляем отправителя
		$select->join(array('c2'=>$this->tableContact),
			'c1.user_id_from = c2.user_id AND c2.box = "inbox" AND c1.date_create=c2.date_create',
			array(
				'fromRecordId'       => 'id',
				'fromDateLastAction' => 'date_last_action',
				'fromDateCreate'     => 'date_create',
				'fromStatus'         => 'status'
			)
		);

		#$this->printSelect($select, 'ОТПРАВЛЕННЫЕ');
		$rows = $this->db->fetchAll($select);
		#Sas_Debug::dump($rows, 'ОТПРАВЛЕННЫЕ');

		return $rows;
	}

	/**
	 * Отправка запроса на обмен контактами
	 * @param $userId
	 * @param $realKarat
	 * @param $bonusKarat
	 */
	public function sendExchange($userId, $realKarat, $bonusKarat)
	{
		$dateCreate = date('Y-m-d H:i:s');

		// Отправитель запроса
		$data = array(
			'user_id'      => $this->myId,
			'user_id_from' => (int)$userId,
			'box'          => 'send',
			'status'       => null,
			'real_karat'   => $realKarat,
			'bonus_karat'  => $bonusKarat,
			'date_create'  => $dateCreate
		);
		$this->db->insert($this->tableContact, $data);

		// Получатель запроса
		$data = array(
			'user_id'      => (int)$userId,
			'user_id_from' => $this->myId,
			'box'          => 'inbox',
			'status'       => 'new',
			'date_create'  => $dateCreate,
		);
		$this->db->insert($this->tableContact, $data);
		Models_Actions::add(5, $this->myId, $userId); // Предложено обменяться контактами
		return $this->db->lastInsertId();
	}

	/**
	 * Отзываем приглашение по обмену контактами
	 * @param $myRecordId
	 * @param $fromRecordId
	 */
	public function revoke($myRecordId, $fromRecordId)
	{
		// Отправитель
		$curDate = date('Y-m-d H:i:s');
		$data = array(
			'status'           => 'revoke',
			'real_karat'       => 0,
			'bonus_karat'      => 0,
			'archive'          => 'yes',
			'date_last_action' => $curDate,
		);

		$where = $this->db->quoteInto('id = ?', $myRecordId);
		$this->db->update($this->tableContact, $data, $where);

		Models_Actions::add(50, $this->myId); // Предложение по обмену контактами отозвано

		/*$data = array(
			'status'           => 'revoke',
			'date_last_action' => $curDate,
		);

		$where = $this->db->quoteInto('id = ?', $fromRecordId);
		$this->db->update($this->tableContact, $data, $where);*/
	}

	/**
	 * Отклоняет предложение об обмене контактами
	 * @param $myRecordId
	 * @param $fromRecordId
	 */
	public function reject($myRecordId, $fromRecordId)
	{
		$curDate = date('Y-m-d H:i:s');

		// Получатель
		$data = array(
			'status'           => 'reject',
			'archive'          => 'yes',
			'date_last_action' => $curDate,
		);
		$where = $this->db->quoteInto('id = ?', $myRecordId);
		$this->db->update($this->tableContact, $data, $where);

		// Отправитель
		$data = array(
			'real_karat'       => 0,
			'bonus_karat'      => 0
		);
		$where = $this->db->quoteInto('id = ?', $fromRecordId);
		$this->db->update($this->tableContact, $data, $where);

		Models_Actions::add(7, $this->myId); // Предложение по обмену контактами отклонено
	}

	/**
	 * Возвращает суммы необходимые для возврата за отзыв или отмету предложения об обмене контактами
	 * @param $recordId
	 * @return array
	 */
	public function getMoneyBack($recordId)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableContact, array('real_karat', 'bonus_karat', 'user_id', 'user_id_from'));
		$select->where('id = ?', $recordId);
		$select->limit(1);

		return $this->db->fetchRow($select);
	}

	#=== HELPERS ===


}
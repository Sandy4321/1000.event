<?php

class Models_Admin_Analytics
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $tblLog = array('log'=>'v2_actions_log');
	private $tblLogCat = array('logCat'=>'v2_actions_log_category');
	#private $tblUser = array('u'=>'users_data');
	private $tblUser = array('u'=>'users');

	public function __construct() {
		$this->db = Zend_Registry::get('db');
	}

	/**
	 * Сокращённый список категорий лога
	 * @return array
	 */
	public function getCatLogMini()
	{
		$select = $this->db->select()
			->from($this->tblLogCat, '*')
			->where('id NOT IN (19,20,21,22,23,24,28,41,42,43,44,45,46,49,49)')
			->order('name_ru ASC');

		return $this->db->fetchPairs($select);
	}

	/**
	 * Возвраащет записи лога из заданной категории
	 * @param      $catId
	 * @param null $dateMin
	 * @param null $dateMax
	 * @return array
	 */
	public function getLogCatId($catId, $dateMin = null, $dateMax = null)
	{
		$select = $this->db->select()
			->from($this->tblLog, '*')
			->where('category_id = ?', $catId);

		if(!is_null($dateMin)) {
			$select->where('date_create >= ?', $dateMin);
		}

		if(!is_null($dateMax)) {
			$select->where('date_create <= ?', $dateMax);
		}

		$select->order('date_create DESC');

		#Sas_Debug::dump($select->__toString());

		return $this->db->fetchAll($select);
	}

	/**
	 * Глобальный баланс системы
	 *
	 * @return array
	 */
	public function getGlobalBalance()
	{
		#$sql = 'SELECT SUM(`balance`) AS `real`, SUM(`balance_bonus`) AS `bonus`, SUM(`balance`) + SUM(`balance_bonus`) AS `summa` FROM `users_data`';
		$sql = 'SELECT SUM(`balance`) AS `real`, SUM(`balance_bonus`) AS `bonus`, SUM(`balance`) + SUM(`balance_bonus`) AS `summa` FROM `users`';
		return $this->db->fetchRow($sql);
	}

	/**
	 * Платежные операции
	 * @param string $status
	 * @param null   $dateCreateStart
	 * @return array
	 */
	public function getHistoryPayment($status = 'success', $dateCreateStart = null)
	{
		$select = $this->db->select()
			->from('v3_orders', '*')
			->order('date_create DESC');
		if(!is_null($status)) {
			$select->where('status = ?', $status);
		}
		if(!is_null($dateCreateStart)) {
			$select->where('date_create >= ?', $dateCreateStart);
		}

		$select->join('users', 'users.id = v3_orders.user_id', array('recurrent_payment'));

		return $this->db->fetchAll($select);
	}

	/**
	 * Успешные платежные операции (для графика)
	 * @param null $dateCreateStart
	 * @return array
	 */
	public function getPaymentSuccess($dateCreateStart = null)
	{
		$select = $this->db->select()
			//->from('v2_orders', array('money'=>'SUM(money)', 'dc'=>'DATE_FORMAT(date_create, "%Y-%m-%d")'))
			->from('v3_orders', array('money'=>'SUM(money)', 'dc'=>'DATE_FORMAT(date_create, "%Y-%m-%d")'))
			->where('`status` = ?', 'success')
			->group('dc')
			->order('date_create ASC');

		if(!is_null($dateCreateStart)) {
			$select->where('date_create >= ?', $dateCreateStart);
		}

		return $this->db->fetchAll($select);
	}

	public function getExpectedPayments() {
		$Date = new DateTime(date('Y-m-01'));
		$PrevMonth = clone $Date;
		$PrevMonth = $PrevMonth->modify('-1 Month');

		echo $curMonthStart = $Date->format('Y-m-d');

		echo $prevMonthStart = $PrevMonth->format('Y-m-d');
		//$oldMondthStart =

		$select = $this->db->select()
			->from('v3_orders', array('money'=>'SUM(money)', 'dc'=>'DATE_FORMAT(date_create, "%Y-%m-%d")'))
			->where('`status` = ?', 'success')
			->where('`response_code` = ?', 'AS000')
			->where('date_create >= "'.$prevMonthStart.'" AND date_create < "'.$curMonthStart.'"')
			->group('dc')
			->order('date_create ASC');

		$select->joinLeft('users', 'users.id=`v3_orders`.`user_id`', null)
			->where('users.`recurrent_payment` = ?', 'yes')
			->where('YEAR(users.`club_card_dt`) = ?', date('Y'))
			->where('MONTH(users.`club_card_dt`) = ?', date('m'));


		#Sas_Debug::sql($select);

		return $this->db->fetchAll($select);
	}

	/**
	 * Активность пользователей
	 * @param null $dateStart
	 * @return array
	 */
	public function getUsersActive($dateStart = null)
	{
		$select = $this->db->select()
			->from('v2_actions_log', array('cnt'=>'COUNT(*)', 'dt'=>'DATE_FORMAT(date_create, "%Y-%m-%d")'))
			->group('dt')
			->order('dt');

		if(!is_null($dateStart)) {
			$select->where('date_create >= ?', $dateStart);
		}

		return $this->db->fetchAll($select);
	}

	/**
	 * Активность пользователей по часам
	 * @param null $dateStart
	 * @return array
	 */
	public function getUsersActiveHour($dateStart = null)
	{
		$select = $this->db->select()
			->from('v2_actions_log', array('cnt'=>'COUNT(*)', 'dt'=>'DATE_FORMAT(date_create, "%H")'))
			->group('dt')
			->order('dt');

		if(!is_null($dateStart)) {
			$select->where('date_create >= ?', $dateStart);
		}

		return $this->db->fetchAll($select);
	}

	/**
	 * Действия внутри часа часам
	 * @param $dtStart
	 * @param $dtStop
	 * @return array
	 */
	public function getUsersActiveAction($dtStart, $dtStop)
	{
		$select = $this->db->select()
			->from('v2_actions_log', array('cnt'=>'COUNT(*)'))
			->where('date_create >= ?', $dtStart)
			->where('date_create <= ?', $dtStop)
			->group('category_id')
			->order('cnt DESC');
		$select->joinLeft('v2_actions_log_category', 'v2_actions_log_category.id = v2_actions_log.category_id', array('name'=>'name_ru'));

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает кол-во пользователей по часам активности
	 * @return array
	 */
	public function getUsersDay()
	{
		$curDate = date('Y-m-d');
		#$sql = 'SELECT FROM_UNIXTIME(`activity_time`, "%Y-%m-%d %H") AS dt, COUNT(*) AS cnt '.
		#	'FROM `users_data` WHERE FROM_UNIXTIME(`activity_time`, "%Y-%m-%d") >= "'.$curDate.'" '.
		#	'GROUP BY dt ORDER BY `activity_time` ASC';
		$sql = 'SELECT `online_last_dt` AS dt, COUNT(*) AS cnt '.
			'FROM `users` WHERE `online_last_dt >= "'.$curDate.'" '.
			'GROUP BY dt ORDER BY `online_last_dt` ASC';

		return $this->db->fetchAll($sql);
	}

	/**
	 * Движение баланса карат пользователей
	 */
	public function getUsersMovementBalance($dateStart = '2013-06-28')
	{
		/*$select = $this->db->select()
			->from('user_balance_log', array('moneyMinus'=>'SUM(amount)', 'dt'=>'DATE_FORMAT(date_create, "%Y-%m-%d")'))
			->where('amount < 0')
			->where('date_create >= ?', '2013-06-28')
			->group('dt')
			->order('dt');*/

		$select = $this->db->select()
			->from(array('n'=>'user_balance_log'), array('dt'=>'DATE_FORMAT(n.date_create, "%Y-%m-%d")'))
			->where('n.date_create >= ?', $dateStart)
			->group('dt')
			->order('dt');

		$select->joinLeft(array('m'=>'user_balance_log'), 'm.id=n.id AND m.amount < 0', array('moneyMinus'=>'SUM(m.amount)'));
		$select->joinLeft(array('p'=>'user_balance_log'), 'p.id=n.id AND p.amount > 0', array('moneyPlus'=>'SUM(p.amount)'));
		$select->joinLeft(array('s'=>'user_balance_log'), 's.id=n.id', array('moneySum'=>'SUM(n.amount)'));

		return $this->db->fetchAll($select);
	}

	public function getStatGiftsSale()
	{
		$select = $this->db->select()
			->from(array('ug'=>'users_gifts'), array('cnt'=>'COUNT(`gifts_id`)'))
			->where('user_id_from != ?', 16)
			->where('user_id_from != ?', 18)
			->where('user_id_from != ?', 3040)
			->where('user_id_from != ?', 5125)
			->order('cnt DESC')
			->group('gifts_id');

		$select->join(array('g'=>'gifts'), 'g.`id` = ug.`gifts_id`', array('name'=>'CONCAT(name_ru, " (", price, "k)")'))
			->group('price');

	 	return $this->db->fetchAssoc($select);
	}
}
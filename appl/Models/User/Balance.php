<?php

class Models_User_Balance
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

	#private $tableBalance  = 'users_data';
	private $tableBalance  = 'users';
	private $columnBalance = 'balance';
	private $columnBonus   = 'balance_bonus';

	private $tableLog  = 'user_balance_log';

	private $myId = null;

	private $real  = null;
	private $bonus = null;
	//private $credit = -300;
	private $credit = 0;

	/**
	 * Кол-во списанных реальных каратов
	 * @var int
	 */
	private $debitReal = 0;

	/**
	 * Кол-во списанных бонусных каратов
	 * @var int
	 */
	private $debitBonus = 0;

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->tr = Zend_Registry::get('Zend_Translate');

		$this->myId = Models_User_Model::getMyId();
	}

	/**
	 * ОБЯЗАТЕЛЬНАЯ первоначальная инициализация при работе с начислениями и списаниями!!!
	 */
	public function init($userId = null) {
		if (is_numeric($userId)) {
			$this->myId = (int) $userId;
		}

		$select = new Zend_Db_Select($this->db);
		$columns = array(
			$this->columnBalance,
			$this->columnBonus
		);
		$select->from($this->tableBalance, $columns)
			->where('id = ?', $this->myId)
			->limit(1);
		$row = $this->db->fetchRow($select);
		$this->real  = $row['balance'];
		$this->bonus = $row['balance_bonus'];
	}

	/**
	 * Инициализаци баланса через профиль.
	 * @param array $profile
	 */
	public function initProfile(array $profile)
	{//TODO: переделать на вход Модели а не массива!!!
		$this->real  = $profile['balance'];
		$this->bonus = $profile['balance_bonus'];
	}

	/**
	 * Возвращает максимальную возможную сумму кредита.
	 *
	 * @return int
	 */
	public function getCreditMax() {
		return $this->credit;
	}

	/**
	 * Возвращает стоимость назначения свидания
	 *
	 * @return int
	 */
	public function getPriceTryst() {
		return PRICE_DATES;
	}

	/**
	 * Возвращает стоимость размещения VIP поста
	 *
	 * @return int
	 */
	public function getPricePost() {
		return PRICE_VIP_POST;
	}

	/**
	 * Возвращает стоимость обмена телефонами
	 *
	 * @return int
	 */
	public function getPriceContact() {
		return PRICE_CONTACT;
	}

	/**
	 * Возвращает стоимость участия в игре Фортуна
	 *
	 * @return int
	 */
	public function getPriceFortune() {
		return PRICE_FORTUNE;
	}

	/**
	 * Возвращает стоимость отправки платного вопроса
	 *
	 * @return int
	 */
	public function getPriceMessage() {
		return PRICE_MESSAGE;
	}

	/**
	 * Возвращает стоимость дней к КК за приглашение друга
	 *
	 * @return int
	 */
	public function getPriceFriend() {
		return PRICE_FRIEND;
	}

	/**
	 * Возвращает стоимость VIP статуса
	 *
	 * @return int
	 */
	public function getPriceVipStatus() {
		return PRICE_VIP_STATUS;
	}

	/**
	 * Добавляет бонусные караты к счёту
	 *
	 * @param $karat Кол-во карат для начисления бонуса
	 * @param $msg Сообщение в лог истории операций
	 * @return bool
	 */
	public function addBonus($karat, $msg)
	{
		$this->bonus = $this->getBonus() + $karat;
		$data = array($this->columnBonus => $this->getBonus());
		$res = $this->db->update($this->tableBalance, $data, 'id = ' . $this->myId);
		if (!$res) {
			return false;
		}

		$this->logBalance($karat, $msg);
		Models_Actions::add(15, $this->myId); // Зачислены бонусные караты
		return true;
	}

	/**
	 * Добавляет бонусные дни к Клубной карте
	 *
	 * @param $day Кол-во дней для добавления к КК
	 * @param $msg Сообщение в лог истории операций
	 * @return bool
	 */
	public function addDayCard($day, $msg)
	{
		// TODO: доделать!
		throw new Exception("Доделать начисление бонусных дней. " . __FILE__);
		#$this->bonus = $this->getBonus() + $karat;
		#$data = array($this->columnBonus => $this->getBonus());
		#$res = $this->db->update($this->tableBalance, $data, 'id = ' . $this->myId);
		#if (!$res) {
		#	return false;
		#}

		#$this->logBalance($karat, $msg);
		#Models_Actions::add(15, $this->myId); // Зачислены бонусные караты
		return true;
	}

	/**
	 * Возвращает кол-во бонусных каратов
	 *
	 * @return null|int
	 */
	public function getBonus()
	{
		if (!is_null($this->bonus)) {
			return $this->bonus;
		}

		$select = $this->db->select();
		$select->from($this->tableBalance, $this->columnBonus)
			->where('id = ?', $this->myId)
			->limit(1);
		$row = $this->db->fetchOne($select);
		$this->bonus = $row;
		return $this->bonus;
	}

	/**
	 * Добавляет реальные караты к счёту
	 *
	 * @param $karat
	 * @param $msg
	 * @return bool
	 */
	public function addReal($karat, $msg)
	{
		$this->real = $this->getReal() + $karat;
		$data = array(
			$this->columnBalance => $this->getReal()
		);
		$res = $this->db->update($this->tableBalance, $data, 'id = ' . $this->myId);
		if (!$res) {
			return false;
		}

		$this->logBalance($karat, $msg);
		Models_Actions::add(14, $this->myId); // Зачислены купленные караты
		return true;
	}

	/**
	 * Возвращает кол-во реальных каратов
	 *
	 * @return null|int
	 */
	public function getReal()
	{
		if (!is_null($this->real)) {
			return $this->real;
		}

		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableBalance, $this->columnBalance)
			->where('id = ?', $this->myId)
			->limit(1);
		$row = $this->db->fetchOne($select);
		$this->real = $row;
		return $this->real;
	}

	/**
	 * Возвращает общее кол-во каратов (реальные + бонусные)
	 *
	 * @return int|null
	 */
	public function getBalanceAll()
	{
		return $this->getBonus() + $this->getReal();
	}

	/**
	 * Уменьшение общего баланса (используя общий баланс = реал + бонусы)
	 *
	 * @param $karat
	 * @return bool
	 */
	private function minusBalanceAll($karat)
	{
		// смотрим кол-во бонусных карат
		if ($this->getBonus() > 0) {

			// Узнаем сколько бонусов мы списываем
			if ($this->getBonus() - $karat >= 0) { // бонустов полность хватаем
				$this->debitBonus = $karat;
				$this->bonus = $this->getBonus() - $karat;
			} else { // Полность бонусов не хватаем, списываем все доступные бонусы
				$this->debitBonus = $this->getBonus();
				$this->bonus = 0; // обнуляем бонусы
			}

			// Меняем кол-во карат для дальнейшего списания после списания бонустов
			$karat = $karat - $this->debitBonus;

			// Выполняем списание бонусов
			$data = array($this->columnBonus => $this->getBonus());
			$this->db->update($this->tableBalance, $data, 'id = ' . $this->myId);

		}

		// Смотрим есть ли у нас еще караты для списания после списания бонусных карат
		if ($karat > 0) {
			$this->debitReal = $karat;
			$this->real = $this->getReal() - $karat;

			// Выполняем списание карат
			$data = array($this->columnBonus => $this->getBonus(), $this->columnBalance => $this->getReal());
			$this->db->update($this->tableBalance, $data, 'id = ' . $this->myId);
		}

		return true;
	}

	/**
	 * Уменьшение баланса реальных карат
	 *
	 * @param $karat
	 * @param $msg
	 * @return bool
	 */
	private function minusBalanceReal($karat, $msg)
	{
		// Карат достаточно смотрим
		$this->real = $this->getReal() - $karat;

		$data = array($this->columnBalance => $this->getReal());
		$this->db->update($this->tableBalance, $data, 'id = ' . $this->myId);

		$this->logBalance(-$karat, $msg);

		return true;
	}

	/**
	 * Уменьшение баланса бонусных карат
	 *
	 * @param $karat
	 * @param $msg
	 * @return bool
	 */
	public function minusBalanceBonus($karat, $msg)
	{
		// Карат достаточно смотрим
		$this->bonus = $this->getBonus() - $karat;

		$data = array($this->columnBonus => $this->getBonus());
		$this->db->update($this->tableBalance, $data, 'id = ' . $this->myId);

		$this->logBalance(-$karat, $msg);

		return true;
	}

	/**
	 * Списывает караты за назначение свидания
	 */
	public function debitOnTryst()
	{
		// Проверяем возможность списать средства
		#if(!$this->checkDebitOnTryst()) {
		#	return false;
		#}

		// Списываем караты
		#$this->minusBalanceAll($this->getPriceTryst());

		// Пишем лог
		#$this->logBalance(-$this->getPriceTryst(), $this->t('Назначение свидания'));
		Models_Actions::add(16, $this->myId); // Списаны караты за свидание
		return true;
	}

	/**
	 * Списывает караты за обмен контактами
	 */
	public function debitOnExchangeContact()
	{
		// Проверяем возможность списать средства
		#if(!$this->checkDebitOnExchangeContact()) {
		#	return false;
		#}

		// Списываем караты
		#$this->minusBalanceAll($this->getPriceContact());

		// Пишем лог
		#$this->logBalance(-$this->getPriceContact(), $this->t('Обмен телефонами'));
		#$this->logBalance(0, $this->t('Обмен телефонами'));
		Models_Actions::add(17, $this->myId); // Списаны караты за обмен контактами

		return true;
	}

	/**
	 * Списывает караты VIP статус
	 */
	public function debitOnVipStatus()
	{
		// Проверяем возможность списать средства
		if(!$this->checkDebitOnVipStatus()) {
			return false;
		}

		// Списываем караты
		$this->minusBalanceAll($this->getPriceVipStatus());

		// Пишем лог
		$this->logBalance(-$this->getPriceVipStatus(), $this->t('Ваш статус опубликован в Топ-20'));
		Models_Actions::add(58, $this->myId); // Списаны караты за публикацию VIP статуса

		return true;
	}

	/**
	 * Списывает караты VIP размещение поста
	 *
	 * @param $postId
	 * @return bool
	 */
	public function debitOnVipPost($postId)
	{
		// Проверяем возможность списать средства
		if(!$this->checkDebitOnVipPost()) {
			return false;
		}

		$msg = $this->t('Публикация поста в премиум ленте');
		// Списываем караты и пишем в историю счета
		$this->minusBalanceAll($this->getPricePost());
		$this->logBalance(-$this->getPricePost(), $msg);

		// Пишем лог
		Models_Actions::add(65, $this->myId, null, $postId); // Списаны караты за размещение поста в ленте для избранных контактов

		return true;
	}

	/**
	 * Списывает караты за подарок
	 * @param int $sum Сумма за подарок
	 * @return bool
	 */
	public function debitOnGifts($sum)
	{
		// Проверяем возможность списать средства
		if(!$this->checkDebitGifts($sum)) {
			return false;
		}

		// Списываем караты
		$this->minusBalanceAll($sum);

		// Пишем лог
		$this->logBalance(-$sum, $this->t('Оплата подарка'));
		Models_Actions::add(61, $this->myId); // Списаны караты за оплату подарка

		return true;
	}

	/**
	 * Проверка возможности списать средства за подарок.
	 * @param int $sum Сумма за подарок
	 * @return bool false-Нельзя списать | true-можно списать
	 */
	public function checkDebitGifts($sum)
	{
		$check = false;

		if ($this->getBalanceAll() - $sum >= $this->getCreditMax()) {
			$check = true;
		}

		return $check;
	}

	/**
	 * Списывает караты за игру Фортуна
	 */
	public function debitOnGamesFortune()
	{
		// Проверяем возможность списать средства
		if(!$this->checkDebitOnGamesFortune()) {
			return false;
		}

		// Списываем караты
		$this->minusBalanceAll($this->getPriceFortune());

		// Пишем лог
		$this->logBalance(-$this->getPriceFortune(), $this->t('Игра "Колесо Фортуны"'));
		Models_Actions::add(57, $this->myId); // Списаны караты за игру Фортуна

		return true;
	}

	/**
	 * Проверка возможности списать средства за игру Фортуна.
	 * @return bool false-Нельзя списать | true-можно списать
	 */
	public function checkDebitOnGamesFortune()
	{
		$check = false;

		if ($this->getBalanceAll() - $this->getPriceFortune() >= 0) {
			$check = true;
		}

		return $check;
	}

	/**
	 * Проверка возможности списать средства за обмен контактами.
	 * @return bool false-Нельзя списать | true-можно списать
	 */
	public function checkDebitOnExchangeContact()
	{
		$check = false;

		//if ($this->getBalanceAll() - $this->getPriceContact() >= $this->getCreditMax()) {
		if ($this->getBalanceAll() - $this->getPriceContact() >= 0) {
			$check = true;
		}

		return $check;
	}

	/**
	 * Проверка возможности списать средства за назначение свидания.
	 * @return bool false-Нельзя списать | true-можно списать
	 */
	public function checkDebitOnTryst()
	{
//		$check = false;
//
//		if ($this->getBalanceAll() - $this->getPriceTryst() >= $this->getCreditMax()) {
//			$check = true;
//		}
//
//		return $check;
		return true;
	}

	/**
	 * Проверка возможности списать средства за VIP статус.
	 * @return bool false-Нельзя списать | true-можно списать
	 */
	public function checkDebitOnVipStatus()
	{
		$check = false;

		if ($this->getBalanceAll() - $this->getPriceTryst() >= $this->getCreditMax()) {
			$check = true;
		}

		return $check;
	}

	/**
	 * Проверка возможности списать средства за VIP размещение поста.
	 * @return bool false-Нельзя списать | true-можно списать
	 */
	public function checkDebitOnVipPost()
	{
		$check = false;

		if ($this->getBalanceAll() - $this->getPricePost() >= $this->getCreditMax()) {
			$check = true;
		}

		return $check;
	}

	/**
	 * Возвращает кол-во списанных реальных карат
	 * @return int
	 */
	public function getDebitReal() {
		return $this->debitReal;
	}

	/**
	 * Возвращает кол-во списанных бонусных карат
	 * @return int
	 */
	public function getDebitBonus() {
		return $this->debitBonus;
	}

	/**
	 * Осуществляет возврат ранее списанных средств
	 * @param $moneyBack
	 * @param $msg
	 * @return bool
	 */
	public function moneyBack($moneyBack, $msg)
	{
		/*$moneyBackUserId = $moneyBack['moneyBackUserId'];
		if (is_numeric($moneyBackUserId)) {
			$this->init($moneyBackUserId);
		}*/

		$real_karat  = $moneyBack['real_karat'];
		$bonus_karat = $moneyBack['bonus_karat'];

		// Выполняем возврат карат
		if ($bonus_karat > 0) {
			$this->addBonus($bonus_karat, $this->t('Возврат бонусных карат').' ' . $msg);
			Models_Actions::add(49, $this->myId); // Выполнен бонусных возврат карат
		}

		$this->addReal($real_karat, $this->t('Возврат карат').' ' . $msg);

		Models_Actions::add(48, $this->myId); // Выполнен возврат карат

		return true;
	}

	/**
	 * Списывает караты за отправку сообщения
	 */
	public function debitOnMessage() {
		// Проверяем возможность списать средства
		if(!$this->checkDebitOnMessage()) {
			return false;
		}

		// Списываем караты
		$this->minusBalanceAll($this->getPriceMessage());

		// Пишем лог
		$this->logBalance(-$this->getPriceMessage(), $this->t('Отправка платного вопроса'));
		Models_Actions::add(18, $this->myId); // Списаны караты за платный вопрос
		return true;
	}

	/**
	 * Проверка возможности списать средства за отправку платного вопроса.
	 * @return bool false-Нельзя списать | true-можно списать
	 */
	public function checkDebitOnMessage()
	{
		$check = false;

		if ($this->getBalanceAll() - $this->getPriceMessage() >= 0) {
			$check = true;
		}

		return $check;
	}

	/**
	 * Логирование операций с каратами
	 *
	 * @param $karat
	 * @param $msg
	 */
	private function logBalance($karat, $msg)
	{
		$data = array(
			'user_id' => $this->myId,
			'transaction_name' => $msg,
			'amount' => $karat,
			'date_create' => date('Y-m-d H:i:s')
		);
		$this->db->insert($this->tableLog, $data);
	}

	/**
	 * Возвращает историю счёта
	 *
	 * @return mixed
	 */
	public function getHistory()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableLog, '*')
			->where('user_id = ?', $this->myId)
			->order('date_create ASC');

		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	/**
	 * Зачисляет бонучные караты за приглашение нового пользователя
	 */
	public function addBonusFriend()
	{
		$this->addBonus($this->getPriceFriend(), $this->t('Бонусные караты за приглашение нового пользователя.'));
	}

	private function t($text) {
		return $this->tr->Translate($text);
	}

	public function startPlayment($user_id, $out_summ, $goods_value, $inv_id, $signature_value, $goods_name)
	{
		$insert['user_id'] = $user_id;
		$insert['money'] = $out_summ;

		if($goods_name == 'Karats') {
			$insert['karat'] = $goods_value;
		} else {
			$insert['card_month'] = $goods_value;
		}

		$insert['number_invoice'] = $inv_id;
		$insert['signature'] = $signature_value;
		$insert['status'] = 'created';
		$insert['date_create'] = date('Y-m-d H:i:s');

		return $this->db->insert('v2_orders', $insert);
	}

	public function failPayment($inv_id)
	{
		$update['status'] = 'error';
		$update['date_close'] = date('Y-m-d H:i:s');

		$where = $this->db->quoteInto('number_invoice = ?', $inv_id);

		return $this->db->update('v2_orders', $update, $where);
	}

	public function okPayment($inv_id)
	{
		$select = $this->db->select()
			->from('v2_orders', '*')
			->where('number_invoice = ?', $inv_id)
			->limit(1);
		$row = $this->db->fetchRow($select);

		if(is_array($row)) {
			$update['status'] = 'success';
			$update['date_close'] = date('Y-m-d H:i:s');

			$where = $this->db->quoteInto('number_invoice = ?', $inv_id);

			$this->db->update('v2_orders', $update, $where);
			return $row;
		} else {
			return false;
		}
	}

	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'balance', 'action'=>'index'),
			'name'  => $tr->translate('Клубная карта'),
			'check' => 'user/balance',
			'style' => ' active',
			'icon' => 'Balance',
			'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'balance', 'action' => 'replenishment'),
					//'name'  => $tr->translate('Продление карты'),
					'name'  => $tr->translate('Пополнение счёта'),
					'check' => 'user/balance/replenishment',
					'style' => ' active',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'balance', 'action' => 'history'),
					'name'  => $tr->translate('История'),
					'check' => 'user/balance/history',
					'style' => ' active',
				),
			)
		);

		return $menu;
	}
	//============ /MENU ===========
}
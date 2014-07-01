<?php

class Models_Balance
{
	private $tblLog  = 'user_balance_log';

	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	/**
	 * @var Models_Users
	 */
	private $Profile;

	public function __construct(Models_Users $Profile)
	{
		if($Profile instanceof Models_Users) {
			$this->db = Zend_Registry::get('db');
			$this->Profile = $Profile;
		} else {
			throw new Sas_Models_Exception('Profile no instanceof Models_Users');
		}
	}

	/**
	 * Уменьшение общего баланса (используя общий баланс = реал + бонусы)
	 *
	 * @param $karat
	 * @param $msg
	 * @throws Sas_Models_Exception
	 */
	public function minusBalanceAll($karat, $msg)
	{
		$all = $this->Profile->getBalanceAll();
		$real = $this->Profile->getBalance();
		$bonus = $this->Profile->getBalanceBonus();

		if($karat > $all) throw new Sas_Models_Exception($this->Profile->t('Недостаточно карат для списания.'));

		// смотрим кол-во бонусных карат
		if($bonus > 0) {
			// Списываем сначала бонусы
			if ($bonus - $karat >= 0) {
				$this->Profile->setBalanceBonus($this->Profile->getBalanceBonus() - $karat);
				$this->logBalance(-$karat, $msg);
			} else {
				// Полностью бонусов не хватает, списываем все доступные бонусы
				$this->Profile->setBalanceBonus(0);
				$karat = $karat - $bonus; // Остаток карат для списания

				$this->logBalance(-$bonus, $msg);
			}
		}

		// Смотрим есть ли у нас еще караты для списания после списания бонусных карат
		if ($karat > 0) {
			$this->Profile->setBalance($real - $karat);
			$this->logBalance(-$karat, $msg);
		}

		// Сохраняем все изменения
		$this->Profile->save();
	}

	/**
	 * Добавляет бонусные караты к счёту
	 *
	 * @param $karat Кол-во карат для начисления бонуса
	 * @param $msg Сообщение в лог истории операций
	 * @return Models_Balance
	 */
	public function addBonus($karat, $msg)
	{
		$this->Profile->setBalanceBonus($this->Profile->getBalanceBonus() + $karat)->save();

		$this->logBalance($karat, $msg);
		Models_Actions::add(15, $this->Profile->getId()); // Зачислены бонусные караты

		return $this;
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
		$this->Profile->setBalance($this->Profile->getBalance() + $karat)->save();

		$this->logBalance($karat, $msg);
		Models_Actions::add(14, $this->Profile->getId()); // Зачислены купленные караты
		return true;
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
			'user_id' => $this->Profile->getId(),
			'transaction_name' => $msg,
			'amount' => $karat,
			'date_create' => date('Y-m-d H:i:s')
		);
		$this->db->insert($this->tblLog, $data);
	}
}
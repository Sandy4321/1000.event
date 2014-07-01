<?php

class Admyn_AnalyticsController extends Sas_Controller_Action_Admin
{
	public function userActionBaseAction()
	{
		$catId   = $this->_getParam('cat_log_id');
		$dateOne = $this->_getParam('date_one');

		$ModelA = new Models_Admin_Analytics();
		$this->view->vCatLog = $ModelA->getCatLogMini();

		if(!empty($catId))
		{
			switch($dateOne) {
				case 'date-current':
					$date = new DateTime();
					$dateMin = $date->format('Y-m-d');
					$dateMax = $date->modify('+1 day')->format('Y-m-d');
				break;
				case 'date-yesterday':
					$date = new DateTime();
					$dateMax = $date->format('Y-m-d');
					$dateMin = $date->modify('-1 day')->format('Y-m-d');
				break;
				case 'date-weekly':
					$date = new DateTime();
					$dateMax = $date->modify('+1 day')->format('Y-m-d');
					$dateMin = $date->modify('-7 day -1 day')->format('Y-m-d');
				break;
				case 'date-month':
					$date = new DateTime();
					$dateMax = $date->modify('+1 day')->format('Y-m-d');
					$dateMin = $date->modify('-1 month -1 day')->format('Y-m-d');
				break;
				case 'date-all':
					$dateMax = null;
					$dateMin = null;
				break;
			}

			$this->view->vCatLogId = $catId;
			$this->view->vDateOne  = $dateOne;
			$this->view->vLog = $ModelA->getLogCatId($catId, $dateMin, $dateMax);
		}
	}

	public function balanceSystemsAction()
	{
		$dateStart = date('Y-m').'-01';

		$ModelA = new Models_Admin_Analytics();
		$this->view->vData = $ModelA->getGlobalBalance();
		$this->view->vHistoryPayment = $ModelA->getHistoryPayment(null, $dateStart);
		$this->view->vExpectedPayments = $ModelA->getExpectedPayments();
		$this->view->vPaymentSuccess = $ModelA->getPaymentSuccess($dateStart);
	}

	/**
	 * Дневная статистика активности
	 */
	public function statUsersDayAction()
	{
		$dateStart = date('Y-m').'-01';
		$dateStartHour = date('Y-m-d');
		$ModelA = new Models_Admin_Analytics();
		$this->view->vData = $ModelA->getUsersActive($dateStart);
		$this->view->vDataHour = $ModelA->getUsersActiveHour($dateStartHour);
		$this->view->vDataActionHour = $ModelA->getUsersActiveAction($dateStartHour.' '. date('H').':00:00', $dateStartHour.' '. date('H:i:s'));
		$this->view->vDataActionDay = $ModelA->getUsersActiveAction($dateStartHour.' 00:00:00', $dateStartHour.' '. date('H:i:s'));
		//$this->view->vDay = $ModelA->getUsersDay();
	}

	/**
	 * Динамика движения карат
	 */
	public function statUsersDinkarAction()
	{
		$dateStart = date('Y-m').'-01';
		$ModelA = new Models_Admin_Analytics();
		$this->view->vMove = $ModelA->getUsersMovementBalance($dateStart);
	}

	/**
	 * Статистика подарков
	 */
	public function statGiftsAction()
	{
		$ModelA = new Models_Admin_Analytics();
		$this->view->vData = $ModelA->getStatGiftsSale();
	}
}
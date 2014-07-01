<?

class Models_Uniteller
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $tblOrders = array('order'=>'v3_orders');
	private $tblProfile = array('profile'=>'users');
	private $tblLog = 'user_balance_log';

	/**
	 * ID Точки продажи
	 * @var string
	 */
	private $pointID = '00002887';
	/**
	 * Название Точки продажи
	 * @var string
	 */
	private $pointName = 'OnTheList LLC';

	private $shopID = '500071650000000-1719';
	private $shopName = 'ОнЗэЛист';

	private $login = '1264';
	private $psw = '3A1ARetrg9Gs2Ml4tHkon2qC2HsL3UkXRyofiEKiDmqgPhSwTFwxJ0F5gseCwq0ZFam4MTVhdpKHLN89';

	private $lifeTime = '3600'; // время жизни формы оплаты в секундах

	//private $urlForm = 'http://onthelist.master/sas_tmp/test_uni.php'; // Адрес для проведения оплаты покупателями
	private $urlForm = 'https://wpay.uniteller.ru/pay/'; // Адрес для проведения оплаты покупателями

	// Адреса возврата см. внутри getUrlReturnOk() и getUrlReturnNo() так как они формируются динамически с учётом языка
	#private $urlReturnOk = 'http://onthelist.ru/payuniteller.php'; // Адрес возврата после успешной оплаты покупателями
	#private $urlReturnNo = 'http://onthelist.ru/payuniteller.php'; // Адрес возврата после неуспешной оплаты покупателями


	//private $urlRecurrent = 'http://onthelist.master/sas_tmp/test_uni.php'; // Адрес для проведения рекурентных платежей
	//private $urlRecurrent = 'http://onthelist.ru/sas_tmp/test_uni.php'; // Адрес для проведения рекурентных платежей
	private $urlRecurrent = 'https://wpay.uniteller.ru/recurrent/'; // Адрес для проведения рекурентных платежей

	private $urlResult = 'https://wpay.uniteller.ru/results/'; // Адрес запроса результата оплаты

	private $moneyTotal = 0; // Сумма для оплаты
	private $userId = 0; // ID пользователя

	private $orderId = null; // Номер заказа
	private $signature = null; // Подпись

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		#$this->lang = Zend_Controller_Front::getInstance()
		#	->getPlugin('Sas_Controller_Plugin_Language')
		#	->getLocale();
	}

	/**
	 * Создает ордер для оплаты
	 * @param $userId
	 * @param $itemName
	 * @param $itemCnt
	 * @param $money
	 * @return false|int orderId
	 */
	public function createOrder($userId, $itemName, $itemCnt, $money) {
		$this->userId = $userId;
		$this->setMoneyTotal($money);

		$dataInsert['user_id'] = $this->getUserId();
		//$data['number_invoice'] = $userId;
		$dataInsert['item_name'] = $itemName;
		$dataInsert['item_cnt'] = $itemCnt;
		$dataInsert['money'] = $this->getMoneyTotal();
		$dataInsert['status'] = 'created';
		$dataInsert['date_create'] = CURRENT_DATETIME;

		$this->db->beginTransaction();
		$cntInsert = $this->db->insert($this->tblOrders, $dataInsert);
		if($cntInsert == 1) {
			$orderId = $this->db->lastInsertId($this->tblOrders);

			if($orderId > 0) {
				$this->setOrderID($orderId);
				$dataUpdate['signature'] = $this->getSignature();
				$this->db->update($this->tblOrders, $dataUpdate, 'id = ' . $orderId);
				$this->db->commit();

				return $orderId;
			}
		} else {
			$this->db->rollBack();
			return false;
		}

		return false;
	}

	/*private function createNumberInvoice($userId) {

	}*/

	public function setOrderID($orderId) {
		return $this->orderId = $orderId;
	}

	public function getOrderID() {
		// запрос в БД на получение нового значения платежа в переменную $orderID
		//...
		// возврат полученного нового значения
		return $this->orderId;
	}

	/*public function getFromSession($name) {
		// получение значения переменной по ее имени из сессии в переменную $result
		//...
		return $result;
	}*/

	public function getMoneyTotal() {
		//return $this->getFromSession('Subtotal_P');
		return $this->moneyTotal;
	}

	public function setMoneyTotal($money) {
		$this->moneyTotal = $money;
		return $this;
	}

	public function getUserId() {
		//return $this->getFromSession('Customer_IDP');
		return $this->userId;
	}

	/**
	 * Возвращает время жизни формы оплаты в секундах
	 * @return string
	 */
	public function getLifeTime() {
		return $this->lifeTime;
	}

	/**
	 * Возвращает адрес для проведения оплаты покупателями
	 * @return string
	 */
	public function getUrlForm() {
		return $this->urlForm;
	}

	/**
	 * Возвращает адрес возврата после успешной оплаты покупателями
	 * @param $lang
	 * @return string
	 */
	public function getUrlReturnOk($lang) {
		$host = 'http://onthelist.ru';
		$l = ($lang == 'ru') ? '' : '/'.$lang;
		$link = '/user/profile/pay-result/result/success';
		return $host.$l.$link;
	}

	/**
	 * Возвращает адрес возврата после не успешной оплаты покупателями
	 * @param $lang
	 * @return string
	 */
	public function getUrlReturnNo($lang) {
		$host = 'http://onthelist.ru';
		$l = ($lang == 'ru') ? '' : '/'.$lang;
		$link = '/user/profile/pay-result/result/error';
		return $host.$l.$link;
	}

	/**
	 * Возвраащет подпись ордера
	 * @return string
	 */
	public function getSignature() {
		if(is_null($this->signature)) {
			$this->signature = $this->createSignature();
		}

		return $this->signature;
	}

	/**
	 * Подпись для формы, вместо неиспользуемых параметров передаются пустые строки.
	 *
	 * @param int    $MeanType платежная система кредитной карты (0 - любая)
	 * @param int    $EMoneyType тип электронной валюты (0 - любая)
	 * @param string $Card_IDP
	 * @param string $IData
	 * @param string $PT_Code
	 * @return string
	 */
	private function createSignature($MeanType = 0, $EMoneyType = 0, $Card_IDP = '', $IData = '', $PT_Code = '') {
		$Signature = strtoupper(
			md5(
				md5($this->getShopId()) . "&" .
				md5($this->getOrderID()) . "&" .
				//md5($this->getFromSession('Subtotal_P')) . "&" . // Сумма для оплаты
				md5($this->getMoneyTotal()) . "&" . // Сумма для оплаты
				md5($MeanType) . "&" .
				md5($EMoneyType) . "&" .
				md5($this->getLifeTime()) . "&" .
				//md5($this->getFromSession('Customer_IDP')) . "&" . // Идентификатор зарегистрированного пользователя
				md5($this->getUserId()) . "&" . // Идентификатор зарегистрированного пользователя
				md5($Card_IDP) . "&" .
				md5($IData) . "&" .
				md5($PT_Code) . "&" .
				md5($this->psw)
			)
		);
		return $Signature;
	}

	/**
	 * Возвращет подпись для рекурентного платежа
	 * @param $orderId
	 * @param $moneyTotal
	 * @param $parentOrderId
	 * @return string
	 */
	public function getSignatureRecurrent($orderId, $moneyTotal, $parentOrderId) {
		$signature = strtoupper(
			md5(
				md5($this->getShopId()) .'&'.
				md5($orderId) .'&'.
				md5($moneyTotal) .'&'.
				md5($parentOrderId) .'&'.
				md5($this->psw)
			)
		);

		return $signature;
	}

	/**
	 * Выполняем рекурентный платеж
	 * @param $orderId
	 * @param $moneySum
	 * @param $parentOrderId
	 * @param $signature
	 * @return string
	 */
	public function runRecurrentPay($orderId, $moneySum, $parentOrderId, $signature) {
		$data = array(
			'Shop_IDP' => $this->getShopId(),
			'Order_IDP' => $orderId,
			'Subtotal_P' => $moneySum,
			'Parent_Order_IDP' => $parentOrderId,
			'Signature' => $signature
		);

		$postdata = http_build_query($data);
		//Sas_Debug::dump($postdata, 'postdata');
		$options = array('http' =>
							 array(
								 'method' => 'POST',
								 'header' => 'Content-type: application/x-www-form-urlencoded',
								 'content' => $postdata
							 )
		);
		#Sas_Debug::dump($options, 'options');
		$context = stream_context_create($options);
		#Sas_Debug::dump($context, 'context');
		//Sas_Debug::dump($this->urlRecurrent);
		$result = file_get_contents($this->urlRecurrent, false, $context);

		return $result;
	}

	/**
	 * Преобразует в массив ответ от Юнителлера полученный от него в csv
	 * @param        $csv
	 * @param string $delimiter
	 * @return array
	 */
	public function responseRecurrentCsvToArray($csv, $delimiter = ';') {
		$res = array();
		$str = explode("\n", $csv);
		if(empty($str[0])) {array_shift($str);}
		$d0 = explode($delimiter, rtrim($str[0], $delimiter));
		$d1 = explode($delimiter, rtrim($str[1], $delimiter));
		for($i = 0; $i < count($d0); $i++) {
			$res[$d0[$i]] = $d1[$i];
		}

		return $res;
	}

	/**
	 * Сохраняет код ответа возвращенный Юнителлером для рекурентного платежа
	 * @param $orderId
	 * @param $code
	 * @return int
	 */
	public function saveRecurrentResponseCode($orderId, $code) {
		return $this->db->update($this->tblOrders, array('recurrent_response_code' => $code), 'id = ' . $orderId);
	}

	/**
	 * Сохраняет код ОШИБКИ ответа возвращенный Юнителлером для рекурентного платежа
	 * @param $orderId
	 * @param $code
	 * @return int
	 */
	public function saveRecurrentErrorCode($orderId, $code) {
		return $this->db->update($this->tblOrders, array('recurrent_error_code' => $code), 'id = ' . $orderId);
	}

	/**
	 * Создает ордер для РЕКУРЕНТНОЙ оплаты
	 * @param $userId
	 * @param $parentOrderId
	 * @param $itemCnt
	 * @param $moneyTotal
	 * @return false|int orderId
	 */
	public function createRecurrentOrder($userId, $parentOrderId, $itemCnt, $moneyTotal) {

		$dataInsert['parent_id'] = $parentOrderId;
		$dataInsert['user_id'] = $userId;
		$dataInsert['item_name'] = 'card';
		$dataInsert['item_cnt'] = $itemCnt;
		$dataInsert['money'] = $moneyTotal;
		$dataInsert['status'] = 'created';
		$dataInsert['date_create'] = CURRENT_DATETIME;

		$this->db->beginTransaction();
		$cntInsert = $this->db->insert($this->tblOrders, $dataInsert);
		if($cntInsert == 1) {
			$orderId = $this->db->lastInsertId($this->tblOrders);

			if($orderId > 0) {
				$dataUpdate['signature'] = $this->getSignatureRecurrent($orderId, $moneyTotal, $parentOrderId);
				$this->db->update($this->tblOrders, $dataUpdate, 'id = ' . $orderId);
				$this->db->commit();

				return $orderId;
			}
		} else {
			$this->db->rollBack();
			return false;
		}

		return false;
	}

	/**
	 * ID магазина (точки продаж)
	 * @return string
	 */
	public function getShopId() {
		return $this->pointID;
	}

	/**
	 * Проверка подлинности подписи и данных
	 *
	 * @param $Order_ID
	 * @param $Status
	 * @param $Signature
	 * @return bool
	 */
	public function checkSignature($Order_ID, $Status, $Signature) {
		return ( $Signature == strtoupper(md5($Order_ID . $Status . $this->psw)) );
	}

	/**
	 * Возвращает полную информацию по ордеру, а так же баланс и клубную карту пользователя.
	 * @param $orderId
	 * @return array
	 */
	public function getOrderAndUserInfo($orderId) {
		$select = $this->db->select();
		$select->from($this->tblOrders, '*')
			->where('order.id = ?', $orderId)
			->limit(1);

		$select->joinLeft($this->tblProfile, 'profile.id = order.user_id', array('lang','balance', 'balance_bonus', 'club_card_dt', 'recurrent_payment', 'recurrent_dt'));

		return $this->db->fetchRow($select);
	}

	public function datePlusMonth($date, $month) {
		$d = new DateTime($date);
		$d->modify('+'.$month.' month');
		$dt = $d->format("Y-m-d");
		return $dt;
	}

	/**
	 * Записывает данные в историю баланса
	 *
	 * @param $userId
	 * @param $transactionName
	 * @param $amount
	 */
	private function recordHistoryBalance($userId, $transactionName, $amount) {
		// Записываем в историю счета
		$data = array(
			'user_id' => $userId,
			'transaction_name' => $transactionName,
			'amount' => $amount,
			'date_create' => date('Y-m-d H:i:s')
		);
		$this->db->insert($this->tblLog, $data);
	}

	// ==== Обработка статусов ордера ======

	/**
	 * Обновляет статус заказа
	 * @param $orderId
	 * @param $newStatus
	 */
	private function updateOrderStatus($orderId, $newStatus) {
		$data['status'] = $newStatus;
		$data['date_close'] = CURRENT_DATETIME;

		$this->db->update($this->tblOrders, $data, 'id = '. $orderId);
	}

	/**
	 * Средства успешно заблокированы (выполнена авторизационная транзакция)
	 * @param $orderId
	 */
	public function statusAuthorized($orderId) {
		$this->updateOrderStatus($orderId, 'authorized');
	}

	/**
	 * Платеж проведен успешно
	 * @param $orderId
	 */
	public function statusSuccess($orderId)
	{
		$orderInfo = $this->getOrderAndUserInfo($orderId);

		// Да, есть такой заказ
		if (is_array($orderInfo)) {
			// Не зависимо от того, что покупалось, меняем статус заказа на success
			$this->updateOrderStatus($orderId, 'success');

			// Получаем полную информацию о завершенном платеже
			$resultXml = $this->getResultOrderXml($orderId);
			$code = $this->getResultOrderXmlParse($resultXml, 'response_code');

			// Проверяем инструмент платежа по его код
			if(!is_null($code)) {
				// Сохраняем полученный код
				$this->saveResponseCode($orderId, $code); // Успешный код при оплате картой: AS000
			}

			// Покупалась Клубная карта
			if($orderInfo['item_name'] == 'card')
			{
				// В этот массим складываем то, что в дальнейшем запишем в профиль пользователя
				$data = array();

				// К текущей карте добавляем купленные месяцы
				if($orderInfo['club_card_dt'] < CURRENT_DATE) {
					$data['club_card_dt'] = $this->datePlusMonth(CURRENT_DATE, $orderInfo['item_cnt']);
				} else {
					$data['club_card_dt'] = $this->datePlusMonth($orderInfo['club_card_dt'], $orderInfo['item_cnt']);
				}

				// Делаем проверки на начисление бонусов только в случае, если это идет НЕ автоматически рекурентный платеж
				// Определяем тип платежа рекурентный или нет

				// Это выполнен НЕ рекурентный платеж - человек платил в ручную, так как у ордера нет идентификатора родителя.
				if(is_null($orderInfo['parent_id']))
				{
					// Глобальный лог - Пишем в общий лог действий
					Models_Actions::add(63, $orderInfo['user_id'], null, $orderInfo['id']); // Куплена клубная карта

					// Зачисляем бонусные караты если отмечено что человек согласен на рекуррентные платежи
					// и он еще не получал от нас бонусы
					// и по информации его платежа предыдущего технически можно провести рек. платежи.
					if($orderInfo['recurrent_payment'] == 'yes'
							&& $orderInfo['recurrent_bonus'] == 'no'
							&& $code == 'AS000' // Успешный код при оплате картой: AS000
						)
					{
						$data['recurrent_dt'] = CURRENT_DATETIME; // dt изменения статуса
						$data['recurrent_bonus'] = 'yes'; // Выданы бонусные караты
						$data['balance_bonus'] = $data['balance_bonus'] + 200;

						// История счета
						$log = array(
							'user_id' => $orderInfo['user_id'],
							'transaction_name' => 'Bonus karat',
							'amount' => 200,
							'date_create' => CURRENT_DATETIME
						);
						$this->db->insert($this->tblLog, $log);

						Models_Actions::add(15, $orderInfo['user_id']); // Зачислены бонусные караты
					} else {
						// Платеж идет не по карте, снимаем отметку о рек. платежах
						$data['recurrent_bonus']   = 'no';
						$data['recurrent_payment'] = 'no';
					}

					// СМС информирование об успешном проведении транзакции
					try {
						$profile = $this->getUserProfile($orderInfo['user_id']);
						$profile['msg_pay_sms'] = 'yes';

						// Пользователям с подтвержденными номерами отправляем СМС уведомления о зачислении платежежа (увеличение КК)
						if(!empty($profile['phone']) && $profile['phone_check'] == 'yes') {
							$ModelTmp = new Models_TemplatesMessage($profile, 'pay_card', 'msg_pay');
							$ModelTmp->send();
						}
					} catch (Sas_Models_Exception $e) {
						// TODO: добавить запись ошибки в лог
					}

				} else {
					// Это идет успешный рекурентный платеж
					// Глобальный лог - Пишем в общий лог действий
					Models_Actions::add(67, $orderInfo['user_id'], null, $orderInfo['id']); // Автоматически продлена Клубная карта

					// СМС информирование об успешном проведении рекуррентной транзакции
					try {
						$profile = $this->getUserProfile($orderInfo['user_id']);
						$profile['msg_recurrent_sms'] = 'yes';

						// Пользователям с подтвержденными номерами отправляем СМС уведомления о платеже
						if(!empty($profile['phone']) && $profile['phone_check'] == 'yes') {
							$ModelTmp = new Models_TemplatesMessage($profile, 'recurrent_pay_yes', 'msg_recurrent');
							$ModelTmp->send();
						}
					} catch (Sas_Models_Exception $e) {
						// TODO: добавить запись ошибки в лог
					}
				}

				// Меняем данные в профиле
				$this->db->update($this->tblProfile, $data, 'id = ' . $orderInfo['user_id']);

				// Записываем в историю счета
				$textLang = ($orderInfo['lang'] == 'ru') ? 'Покупка Клубной карты' : 'Purchase of the Membership card';
				$this->recordHistoryBalance($orderInfo['user_id'], $textLang, $orderInfo['item_cnt']);



				// UBER
				// Получаем промокод и текущий счетчик
				$select = $this->db->select()->from('uber', '*')->where('id = 1')->limit(1);
				$uber = $this->db->fetchRow($select);
				if($uber['cnt'] > 0) // Промокоды еще есть
				{
					// Списываем 1 промокод у Ubera
					$this->db->update('uber', array('cnt'=>($uber['cnt'] - 1)), 'id = 1');

					// Смотрим выдавался ли ранее промокод Убера ли и если да, то какой Убер код
					$select = $this->db->select()->from('system_msg', array('cnt'=>'COUNT(id)'))->where('user_id = ?', $orderInfo['user_id'])->where('msg_ru LIKE "%F7W9B8T5%"')->limit(1);
					$cntUberCodeUser = $this->db->fetchOne($select);

					if($cntUberCodeUser == 0) {
						// даем код 1
						// Пишем покупателю на Dashboard сообщение
						$dashboard = array(
							'user_id' => $orderInfo['user_id'],
							'msg_ru' => '<p>Вы приобрели Клубную карту OnTheList стоимостью '.$orderInfo['money'].' рублей и получаете подарок от нашего партнёра, компании Uber, бесплатную поездку на Мерседесе S-класса. Ваш промо-код: <strong>'.$uber['promo_key'].'</strong><br>1) Скачайте приложение Uber для <a href="https://itunes.apple.com/us/app/uber/id368677368?mt=8">iPhone</a> или <a href="https://play.google.com/store/apps/details?id=com.ubercab">приложение для Android</a><br>2) Зарегистрируйтесь в Uber. При регистрации нужно указать данные Вашей банковской карты, чтобы в дальнейшем Вы смогли расплачиваться с Uber автоматически по окончанию поездки.<br>3) Введите промо-код. На Ваш счет зачислится бесплатная поездка с лимитом 1 000 руб. В случае если поездка превысит 1 000 руб. (поездка дольше 30 минут), остаток суммы будет списан с банковской карты.</p>',
							'msg_en' => '<p>You purchased a Membership card for '.$orderInfo['money'].' rubles, and receive a special present from Uber - 1 free trips with Mercedes S-class. Your promo-code: <strong>'.$uber['promo_key'].'</strong></p><p>Please, see <a href="/news/index/show/id/40"> our announcement </a> for instructions how to apply the code.</p>'
						);
						$this->db->insert('system_msg', $dashboard);

						// Интегрируем новую систему уведомлений в Сообщения от имени админа
						$ModelMsg = new Models_User_Msg(4000); // 4000 - это временный хак (id админа в системе)
						$textMsg = ($orderInfo['lang'] == 'ru') ? $dashboard['msg_ru'] : $dashboard['msg_en'];
						$ModelMsg->saveSystemsMsg($orderInfo['user_id'], $textMsg);

						// Пишем лог операции UBER
						#myLog('UBER - Выдан промокод: ' . $uber['promo_key'] .' пользователю с ID: ' . $userId);
					}

					/*
					 * Уберовский код OTLWELCOME отключен
					 * else {
						// Смотрим а давали ли код 2?
						$select = $this->db->select()->from('system_msg', array('cnt'=>'COUNT(id)'))->where('user_id = ?', $orderInfo['user_id'])->where('msg_ru LIKE "%OTLWELCOME%"')->limit(1);
						$cntUberCodeUser = $this->db->fetchOne($select);
						if($cntUberCodeUser == 0) {
							// даем код 2
							$dashboard = array(
								'user_id' => $orderInfo['user_id'],
								'msg_ru' => '<p><p>Вы приобрели Клубную карту OnTheList стоимостью '.$orderInfo['money'].' рублей и получаете подарок от нашего партнёра, компании Uber, одна бесплатная поездка на Мерседесе S-класса. Ваш промо-код: <strong>OTLWELCOME</strong><br>1) Скачайте приложение Uber для <a href="https://itunes.apple.com/us/app/uber/id368677368?mt=8">iPhone</a> или <a href="https://play.google.com/store/apps/details?id=com.ubercab">приложение для Android</a><br>2) Зарегистрируйтесь в Uber. При регистрации нужно указать данные Вашей банковской карты, чтобы в дальнейшем Вы смогли расплачиваться с Uber автоматически по окончанию поездки.<br>3) Введите промо-код. На Ваш счет зачислится одна бесплатная поездка с лимитом 2 000 руб. В случае если поездка превысит 2 000 руб. (поездка дольше 1 часа), остаток суммы будет списан с банковской карты.</p>',
								'msg_en' => '<p>You purchased a Membership card for '.$orderInfo['money'].' rubles, and receive a special present from Uber - 2 free trips with Mercedes S-class. Your promo-code: <strong>OTLWELCOME</strong></p><p>Please, see <a href="/news/index/show/id/40"> our announcement </a> for instructions how to apply the code.</p>'
							);
							$this->db->insert('system_msg', $dashboard);

							// Интегрируем новую систему уведомлений в Сообщения от имени админа
							$ModelMsg = new Models_User_Msg(4000); // 4000 - это временный хак (id админа в системе)
							$textMsg = ($orderInfo['lang'] == 'ru') ? $dashboard['msg_ru'] : $dashboard['msg_en'];
							$ModelMsg->saveSystemsMsg($orderInfo['user_id'], $textMsg);

							// Пишем лог операции UBER
							#myLog('UBER - Выдан промокод: OTLWELCOME пользователю с ID: ' . $userId);
						}
					}*/

				}
			}

			// Покупались караты
			if($orderInfo['item_name'] == 'karat') {
				$data = array();
				$data['balance'] = $orderInfo['balance'] + $orderInfo['item_cnt'];

				// Меняем данные в профиле
				$this->db->update($this->tblProfile, $data, 'id = ' . $orderInfo['user_id']);

				// Записываем в историю счета
				$textLang = ($orderInfo['lang'] == 'ru') ? 'Покупка карат' : 'Purchase of Carats';
				$this->recordHistoryBalance($orderInfo['user_id'], $textLang, $orderInfo['item_cnt']);

				// Глобальный лог - Пишем в общий лог действий
				Models_Actions::add(14, $orderInfo['user_id']); // Зачислены купленные караты

				// СМС информирование об успешном проведении транзакции
				try {
					$profile = $this->getUserProfile($orderInfo['user_id']);
					$profile['msg_pay_sms'] = 'yes';

					// Пользователям с подтвержденными номерами отправляем СМС уведомления о зачислении платежежа (караты)
					if(!empty($profile['phone']) && $profile['phone_check'] == 'yes') {
						$ModelTmp = new Models_TemplatesMessage($profile, 'pay_carat', 'msg_pay');
						$ModelTmp->send();
					}
				} catch (Sas_Models_Exception $e) {
					// TODO: добавить запись ошибки в лог
				}
			}
		}
	}

	/**
	 * оплачен (выполнена финансовая транзакция или заказ оплачен в электронной платёжной системе)
	 * @param $orderId
	 */
	public function statusPaid($orderId) {
		$this->statusSuccess($orderId);
	}

	/**
	 * средства не заблокированы (авторизационная транзакция не выполнена) по ряду причин.
	 * @param $orderId
	 */
	public function statusNotAuthorized($orderId) {
		$orderInfo = $this->getOrderAndUserInfo($orderId);

		// Да, есть такой заказ
		if (is_array($orderInfo)) {
			// Не зависимо от того, что в заказе, меняем статус заказа на error
			$this->updateOrderStatus($orderId, 'error');
		}
	}

	/**
	 * Ожидается оплата выставленного счёта.
	 * Статус используется только для оплат электронными валютами,
	 * при которых процесс оплаты может содержать этап выставления через систему Uniteller счёта на оплату
	 * и этап фактической оплаты этого счёта Покупателем, которые существенно разнесённы во времени.
	 *
	 * @param $orderId
	 */
	public function statusWaiting($orderId) {
		$orderInfo = $this->getOrderAndUserInfo($orderId);

		// Да, есть такой заказ
		if (is_array($orderInfo)) {
			// Не зависимо от того, что в заказе, меняем статус заказа на waiting
			$this->updateOrderStatus($orderId, 'waiting');
		}
	}

	/**
	 * отменён (выполнена транзакция разблокировки средств или выполнена операция по возврату платежа после списания средств)
	 * @param $orderId
	 */
	public function statusCanceled($orderId) {
		$orderInfo = $this->getOrderAndUserInfo($orderId);

		// Да, есть такой заказ
		if (is_array($orderInfo)) {
			// Не зависимо от того, что в заказе, меняем статус заказа на canceled
			$this->updateOrderStatus($orderId, 'canceled');
		}
	}

	/**
	 * Возвращает информацию профиля пользователя.
	 * @param $userId
	 * @return array
	 */
	private function getUserProfile($userId) {
		$select = $this->db->select()
			->from('users', '*')
			->where('id = ?', $userId)
			->limit(1);

		return $this->db->fetchRow($select);
	}

	/**
	 * @param $orderId
	 * @return SimpleXMLElement
	 */
	public function getResultOrderXml($orderId) {
		//$result = file_get_contents($this->urlResult.'?Shop_ID='.$this->getShopId().'&Login=1264&Password=3A1ARetrg9Gs2Ml4tHkon2qC2HsL3UkXRyofiEKiDmqgPhSwTFwxJ0F5gseCwq0ZFam4MTVhdpKHLN89&Format=4&ShopOrderNumber=' . $orderId);
		$result = file_get_contents($this->urlResult.'?Shop_ID='.$this->getShopId().'&Login='.$this->login.'&Password='.$this->psw.'&Format=4&ShopOrderNumber=' . $orderId);

		return new SimpleXMLElement($result);
	}

	/**
	 * @param SimpleXMLElement $xml
	 * @param string           $fieldName
	 * @return string|null
	 */
	public function getResultOrderXmlParse(SimpleXMLElement $xml, $fieldName = 'response_code') {
		return (empty($xml->orders->order->response_code)) ? null : $xml->orders->order->$fieldName;
	}

	/**
	 * При оплате картой сохраняем код ответа (AS000 код успешного платежа)
	 * @param $orderId
	 * @param $code
	 */
	public function saveResponseCode($orderId, $code) {
		$this->db->update($this->tblOrders, array('response_code'=>$code), 'id = '. (int)$orderId);
	}

	/*public function minusRecurrentBonus($orderId) {
		// По ордеру получаем инфо пользователя
		$orderInfo = $this->getOrderAndUserInfo($orderId);
		#Sas_Debug::dump($info);

		$user_id           = $orderInfo['user_id'];
		$balance_bonus     = $orderInfo['balance_bonus'];
		$recurrent_payment = $orderInfo['recurrent_payment'];

		if($recurrent_payment == 'yes') {
			$bonus = ($balance_bonus >= 200) ? $balance_bonus - 200 : 0;

			$saveData['recurrent_payment'] = 'no';
			$saveData['balance_bonus']     = $bonus;
			$saveData['recurrent_dt']      = CURRENT_DATETIME;

			$this->db->update($this->tblProfile, $saveData, 'id = ' . $user_id);

			// Записываем в историю счета
			$this->recordHistoryBalance($orderInfo['user_id'], 'Bonus karat', '-200');

			// Глобальный лог - Пишем в общий лог действий
			Models_Actions::add(68, $orderInfo['user_id']); // Списаны бонусные караты за невозможность выполнять рек. платежи
		}
	}*/
}
<?

class Models_Admin_PromoAction
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	/**
	 * @var Zend_Translate
	 */
	private $tr;

	private $tblUserProfile = array('u'=>'users');

	public function __construct() {
		$this->db = Zend_Registry::get('db');
		$this->tr = Zend_Registry::get('Zend_Translate');
	}

	/**
	 * Акция Код убера + 30 дней за 3-х друзей
	 * @param $profileNewUser Профиль ПРИГЛАШЕННОГО
	 * @param $profileMaster Профиль ПРИГЛАСИВШЕГО
	 * @return int
	 */
	public function uberPlus30Day($profileNewUser, $profileMaster)
	{
		// 1. Текст с кодом 1 на даш для ПРИГАШЕННОГО
		// Получаем промокод 1 и текущий счетчик
		$uber = $this->getUberCodeAndCnt(); // Возвращает ['cnt'] и ['promo_key']

		if($uber['cnt'] > 0) { // Промокоды еще есть
			// Списываем 1 промокод у Ubera
			$this->minusUberCode($uber['cnt'], 1);

			// Текст с кодом 1 на даш для ПРИГАШЕННОГО (код F7W9B8T5)
			$textRu = '<p><p>Добро пожаловать в клуб!</p><p>Вам подарок от нашего партнёра, компании Uber, бесплатная поездка на Мерседесе S-класса. Ваш промо-код:<br><strong>'.$uber['promo_key'].'</strong><br>1) Скачайте приложение Uber для <a href="https://itunes.apple.com/us/app/uber/id368677368?mt=8">iPhone</a> или <a href="https://play.google.com/store/apps/details?id=com.ubercab">приложение для Android</a><br>2) Зарегистрируйтесь в Uber. При регистрации нужно указать данные Вашей банковской карты, чтобы в дальнейшем Вы смогли расплачиваться с Uber автоматически по окончанию поездки.<br>3) Введите промо-код. На Ваш счет зачислится бесплатная поездка с лимитом 1 000 руб. В случае если поездка превысит 1 000 руб. (поездка дольше 40 минут), остаток суммы будет списан с банковской карты.</p>';
			$textEn = '<p>1 free trips with Mercedes S-class. Your promo-code: <strong>'.$uber['promo_key'].'</strong></p><p>Please, see <a href="/news/index/show/id/40"> our announcement </a> for instructions how to apply the code.</p>';
			$this->sendToDashboard($profileNewUser['id'], $textRu, $textEn);

			// Интегрируем новую систему уведомлений в Сообщения от имени админа
			$ModelMsg = new Models_User_Msg(4000); // 4000 - это временный хак (id админа в системе)
			$textMsg = ($profileNewUser['lang'] == 'ru') ? $textRu : $textEn;
			$ModelMsg->saveSystemsMsg($profileNewUser['id'], $textMsg);
		}

		// 2. Начиная с 7.02.14 начинаем отсчет - за каждых 3х приглашенных даем 30 дней (за 6 соответственно 60 дней и т.д.)
		$cntfriend = $this->getCntFriend($profileMaster['promo_key'], '2014-02-06');

		// Кол-во приглашенных больше 3-х и кратно 3 (3 % 3 = 0)
		if($cntfriend >= 3 && ($cntfriend % 3) == 0) {
			$cardFriend = $profileMaster['club_card_dt'];
			if($cardFriend < CURRENT_DATE) {
				$d = new DateTime(CURRENT_DATE);
				$d->modify('+30 day');
				$newCardDate = $d->format("Y-m-d");
			} else {
				$d = new DateTime($cardFriend);
				$d->modify('+30 day');
				$newCardDate = $d->format("Y-m-d");
			}
			// Обновляем Клубную карту ПРИГЛАСИВШЕГО
			$this->db->update($this->tblUserProfile, array('club_card_dt' => $newCardDate), $this->db->quoteInto('id = ?', $profileMaster['id']));

			// Пишем пригласившему в лог о выдаче КК
			$data = array(
				'user_id' => $profileMaster['id'],
				'transaction_name' => $this->tr->translate('Вам добавлены 30 дней Клубной Карты за приглашение 3-х человек.'),
				'amount' => 30,
				'date_create' => CURRENT_DATETIME
			);
			$this->db->insert('user_balance_log', $data);

			// Интегрируем новую систему уведомлений в Сообщения от имени админа
			$ModelMsg = new Models_User_Msg(4000); // 4000 - это временный хак (id админа в системе)
			#$textMsg = ($profileNewUser['lang'] == 'ru') ? $textRu : $textEn;
			$txt = $this->tr->translate('Вам добавлены 30 дней Клубной Карты за приглашение 3-х человек.');
			$ModelMsg->saveSystemsMsg($profileNewUser['id'], $txt);

			Models_Actions::add(64, null, $profileMaster['id'], $profileNewUser['id']); // ЛОГ - Добавленны дни к Клубной Карте за приглашение друга

			// Смотрим выдавать ли и если да, то какой Убер код
			if($this->getCntUberCodeUser($profileMaster['id'], 'F7W9B8T5') == 0) {
				// даем код 1
				// Текст с кодом 1 + 30 дней КК на даш для ПРИГАСИВШЕГО (код F7W9B8T5)
				$txtRu1 = '<p>Вы пригласили трех друзей в Клуб и получаете подарок - 30 дней клубной карты и 2 бесплатные поездки на Мерседесе S-класса. Ваш промо-код: <strong>'.$uber['promo_key'].'</strong><br>1) Скачайте приложение Uber для <a href="https://itunes.apple.com/us/app/uber/id368677368?mt=8">iPhone</a> или <a href="https://play.google.com/store/apps/details?id=com.ubercab">приложение для Android</a><br>2) Зарегистрируйтесь в Uber. При регистрации нужно указать данные Вашей банковской карты, чтобы в дальнейшем Вы смогли расплачиваться с Uber автоматически по окончанию поездки.<br>3) Введите промо-код. На Ваш счет зачислятся две бесплатные поездки с лимитом 1 000 руб. каждая. В случае если поездка превысит 1 000 руб. (поездка дольше 30 минут), остаток суммы будет списан с банковской карты.</p>';
				$this->sendToDashboard($profileMaster['id'], $txtRu1, $txtRu1);

				// Интегрируем новую систему уведомлений в Сообщения от имени админа
				$ModelMsg->saveSystemsMsg($profileNewUser['id'], $txtRu1);
			}

			/*
			 * Уберовский код OTLWELCOME отключен
			 * else {
				// Смотрим а давали ли код 2?
				if($this->getCntUberCodeUser($profileMaster['id'], 'OTLWELCOME') == 0) {
					// даем код 2
					// 3. Другой Текст с кодом 2 + 30 дней КК на даш для ПРИГАСИВШЕГО (код OTLWELCOME)
					$txtRu2 = '<p>Вы пригласили трех друзей в Клуб и получаете подарок - 30 дней клубной карты и бесплатную поездку на Мерседесе S-класса. Ваш промо-код: <strong>OTLWELCOME</strong><br>1) Скачайте приложение Uber для <a href="https://itunes.apple.com/us/app/uber/id368677368?mt=8">iPhone</a> или <a href="https://play.google.com/store/apps/details?id=com.ubercab">приложение для Android</a><br>2) Зарегистрируйтесь в Uber. При регистрации нужно указать данные Вашей банковской карты, чтобы в дальнейшем Вы смогли расплачиваться с Uber автоматически по окончанию поездки.<br>3) Введите промо-код. На Ваш счет зачислится одна бесплатная поездка с лимитом 2 000 руб. В случае если поездка превысит 2 000 руб. (поездка дольше 1 часа), остаток суммы будет списан с банковской карты.</p>';
					$this->sendToDashboard($profileMaster['id'], $txtRu2, $txtRu2);

					// Интегрируем новую систему уведомлений в Сообщения от имени админа
					$ModelMsg->saveSystemsMsg($profileNewUser['id'], $txtRu2);
				} else {
					$txt = $this->tr->translate('Вы пригласили трех друзей в Клуб и получаете подарок - 30 дней Клубной карты');
					// Пишем отмазку на даш
					$this->sendToDashboard($profileMaster['id'], $txt, null);

					// Интегрируем новую систему уведомлений в Сообщения от имени админа
					$ModelMsg->saveSystemsMsg($profileNewUser['id'], $txt);
				}
			}*/
		}
	}

	public function uber1000drive2($userProfile) {
		// 1. Текст с кодом 1 на даш для ПРИГАШЕННОГО
		// Получаем промокод 1 и текущий счетчик
		$uber = $this->getUberCodeAndCnt(); // Возвращает ['cnt'] и ['promo_key']

		if($uber['cnt'] > 0) { // Промокоды еще есть
			// Списываем 3 промокода у Ubera
			$this->minusUberCode($uber['cnt'], 3);

			// Текст с кодом 1 на даш для ПРИГАШЕННОГО (код F7W9B8T5)
			$textRu = '<p><p>Добро пожаловать в клуб!</p><p>Вам подарок от нашего партнёра, компании Uber, бесплатная поездка на Мерседесе S-класса. Ваш промо-код:<br><strong>'.$uber['promo_key'].'</strong><br>1) Скачайте приложение Uber для <a href="https://itunes.apple.com/us/app/uber/id368677368?mt=8">iPhone</a> или <a href="https://play.google.com/store/apps/details?id=com.ubercab">приложение для Android</a><br>2) Зарегистрируйтесь в Uber. При регистрации нужно указать данные Вашей банковской карты, чтобы в дальнейшем Вы смогли расплачиваться с Uber автоматически по окончанию поездки.<br>3) Введите промо-код. На Ваш счет зачислится бесплатная поездка с лимитом 1 000 руб. В случае если поездка превысит 1 000 руб. (поездка дольше 40 минут), остаток суммы будет списан с банковской карты.</p>';
			$textEn = '<p>1 free trips with Mercedes S-class. Your promo-code: <strong>'.$uber['promo_key'].'</strong></p><p>Please, see <a href="/news/index/show/id/40"> our announcement </a> for instructions how to apply the code.</p>';
			$this->sendToDashboard($userProfile['id'], $textRu, $textEn);

			// Интегрируем новую систему уведомлений в Сообщения от имени админа
			$ModelMsg = new Models_User_Msg(4000); // 4000 - это временный хак (id админа в системе)
			$textMsg = ($userProfile['lang'] == 'ru') ? $textRu : $textEn;
			$ModelMsg->saveSystemsMsg($userProfile['id'], $textMsg);
		}
	}

	/**
	 * Возвращает кол-во друзей ставших членами Клуба, приглашенных с заданной даты (или всего если даты нет).
	 * По промокоду приглашающего находим пром-ключ у друзей (promo_key_friend)
	 * @param      $userFriendKey
	 * @param null $dateStart
	 * @return string
	 */
	private function getCntFriend($userFriendKey, $dateStart = null) {
		$select = $this->db->select()
			->from($this->tblUserProfile, array('cnt'=>'COUNT(id)'))
			->where('promo_key_friend = ?', $userFriendKey)
			->where('current_status = 70');

		if(!is_null($dateStart)) {
			$select->where('register_dt >= ?', $dateStart);
		}

		return $this->db->fetchOne($select);
	}

	/**
	 * Возвращает ['cnt'] и ['promo_key']
	 * @return array
	 */
	private function getUberCodeAndCnt()
	{
		$select = $this->db->select()->from('uber', '*')->where('id = 1')->limit(1);
		return $this->db->fetchRow($select);
	}

	/**
	 * Вычитает/уменьшает кол-во уберовских кодов
	 * @param $currentCnt
	 * @param $minus
	 */
	private function minusUberCode($currentCnt, $minus) {
		$this->db->update('uber', array('cnt'=>($currentCnt - $minus)), 'id = 1');
	}

	/**
	 * Пишет сообщения на даш
	 * @param $userId
	 * @param $textRu
	 * @param $textEn
	 */
	private function sendToDashboard($userId, $textRu, $textEn) {
		$dashboard['user_id'] = $userId;
		$dashboard['msg_ru'] = $textRu;
		$dashboard['msg_en'] = $textEn;
		$this->db->insert('system_msg', $dashboard);
	}

	/**
	 * Возвращает кол-во выданных кодов
	 * @param $userId
	 * @param $code
	 * @return string
	 */
	private function getCntUberCodeUser($userId, $code) {
		$select = $this->db->select()
			->from('system_msg', array('cnt'=>'COUNT(id)'))
			->where('user_id = ?', $userId)
			->where('msg_ru LIKE "%'.$code.'%"')
			->limit(1);

		return $this->db->fetchOne($select);
	}
}
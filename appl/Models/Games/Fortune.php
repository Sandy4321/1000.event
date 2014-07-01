<?php

class Models_Games_Fortune
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	//private $tblProfile = 'users_data';
	private $tblProfile = 'users';
	private $tblGamesPlay = 'games_fortune_users_play';
	private $tblGamesUser = 'games_fortune_users';

	private $columnProfileImg = array('img' => 'CONCAT("/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg")');

	private $currentNumberGames = null;

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();
	}

	/**
	 * Создаёт новую игру.
	 * Первый этап подготовки данных.
	 *
	 * @return int Кол-во мужчин записанных на новую игру
	 */
	public function createNewGames()
	{
		// Из тбл подписчиков выбираем всех мужиков согласно критериям
		$usersMale = $this->getMaleForNewGame();
		#Sas_Debug::dump($usersMale);

		// Номер следующей игры
		$numberGamesNext = $this->getCurrentNumberGames() + 1;
		#Sas_Debug::dump($numberGamesNext);

		// Записываем мужиков на новую игру
		$this->recordMaleNewGames($numberGamesNext, $usersMale);

		return count($usersMale);
	}

	/**
	 * Возвращает всех мужиков для создания новой игры.
	 *
	 * @return array ID мужиков для новой игры
	 */
	private function getMaleForNewGame()
	{
		$select = $this->db->select()
			->from(array('p'=>$this->tblProfile), 'id')
			->where('sex = ?', 'male')
			->where('phone_check = ?', 'yes')
			->where('current_status > ?', 70)
			->where('city_id = ?', 2)
			->where('balance + balance_bonus >= ?', 30)
			->where('lang = ?', 'ru')
			->where('height IS NOT NULL')
			//->limit(100)
			->order('activity_time DESC');

		$select->join(array('g'=>$this->tblGamesUser), 'p.id = g.user_id', null)
			->where('dt_delete IS NULL');

		#$this->printSql($select);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает номер текущей игры.
	 * @return int
	 */
	public function getCurrentNumberGames()
	{
		if(is_null($this->currentNumberGames)) {
			$select = $this->db->select()->from($this->tblGamesPlay, array('number'=>'MAX(game_number)'));
			$this->currentNumberGames = (int) $this->db->fetchOne($select);
		}

		return $this->currentNumberGames;
	}

	/**
	 * Записывает мужиков на новую игру.
	 *
	 * @param $gameNumber
	 * @param $males
	 */
	private function recordMaleNewGames($gameNumber, $males)
	{
		$currentDate = $this->getCurrentSqlDt();
		foreach($males as $male)
		{
			$url = $this->getUnicUrl($this->generatorUrl(8));
			try {
				$this->db->insert($this->tblGamesPlay, array('game_number' => $gameNumber, 'male_id' => $male['id'], 'male_url' => $this->generatorUrl(8), 'dt_create' => $currentDate));
			} catch (Zend_Db_Exception $e) {
				// Если ошибка возникла из за НЕ уникальности ссылки, пробуем вставить с новой ссылкой
				//$this->db->insert($this->tblGamesPlay, array('game_number' => $gameNumber, 'male_id' => $male['id'], 'male_url' => $this->generatorUrl(9), 'dt_create' => $currentDate));
				echo $e->getMessage().'<br>';
			}
		}
	}

	/**
	 * Подбирает мужикам пару для новой игры
	 */
	public function addFemaleForNewGames()
	{
		$gameNumber = $this->getCurrentNumberGames();
		$males = $this->getMaleGame($gameNumber);
		#Sas_Debug::dump($males);
		$para = $this->searchFemale($males);
		#Sas_Debug::dump($para);
		$this->recordFemaleCurrentGame($para, $gameNumber);
	}

	/**
	 * Дописывает мужику выбранную девушку в игру (формирует пары).
	 *
	 * @param $para
	 * @param $gameNumber
	 */
	private function recordFemaleCurrentGame($para, $gameNumber)
	{
		foreach($para as $item) {
			$url = $this->getUnicUrl($this->generatorUrl(8));
			try {
				$this->db->update($this->tblGamesPlay, array('female_id' => $item['female_id'], 'female_url' => $url), 'male_id = ' . $item['male_id'] .' AND game_number = ' . $gameNumber);
			} catch (Zend_Db_Exception $e) {
				// Если ошибка возникла из за НЕ уникальности ссылки, пробуем вставить с новой ссылкой
				//$this->db->update($this->tblGamesPlay, array('female_id' => $item['female_id'], 'female_url' => $this->generatorUrl(9)), 'male_id = ' . $item['male_id'] .' AND game_number = ' . $gameNumber);
				echo $e->getMessage().'<br>';
			}
		}
	}

	private function getUnicUrl($url) {
		$select = $this->db->select()
			->from($this->tblGamesPlay, 'id')
			->where('male_url = "'.$url.'" OR female_url = "'.$url.'"')
			->limit(1);
		$id = $this->db->fetchOne($select);
		if ($id > 0) {
			$this->getUnicUrl($this->generatorUrl(9));
		}

		return $url;
	}

	/**
	 * Возвращает кол-во полных пар в заданной игре.
	 *
	 * @param $gameNumber
	 * @return string
	 */
	public function getUserGames($gameNumber)
	{
		$select = $this->db->select()
			->from($this->tblGamesPlay, 'COUNT(id)')
			->where('game_number = ?', $gameNumber)
			->where('female_id IS NOT NULL');

		return $this->db->fetchOne($select);
	}

	/**
	 * Подбирает (ищет) девушек для мужиков (составляет пары).
	 *
	 * @param $males
	 * @return array
	 */
	private function searchFemale($males)
	{
		$para = array();
		$i = 0;
		foreach($males as $male)
		{
			$select = $this->db->select();
			$select->from(array('u'=>$this->tblProfile), 'id')
				->where('`sex` = ?', 'female')
				->where('`phone_check` = ?', 'yes')
				->where('`status` = ?', 3)
				->where('`city_id` = ?', 2)
				->where('`balance` + `balance_bonus` >= ?', 30)
				->where('`lang` = ?', 'ru');
			#$select->order('RAND()');
			#$select->limit(1);

			// Исключаем друзей
			$select->where('`promo_key_friend` != "' . $male['promo_key'] .'" OR `promo_key_friend` IS NULL');

			// Возраст
			$myAge = $this->getAge($male['birthday']);
			$select->where('`filter_age_min` <= ' . $myAge . ' OR `filter_age_min` IS NULL');
			$select->where('`filter_age_max` >= ' . $myAge . ' OR `filter_age_max` IS NULL');

			// Рост
			$select->where('`filter_height_min` <= ?', $male['height']);
			$select->where('`filter_height_max` >= ?', $male['height']);

			// Дети
			$select->where('`filter_children` = "'. $male['children'] .'" OR `filter_children` IS NULL');

			// Отношение к курению
			$select->where('`filter_smoking` = "'. $male['smoking'] .'" OR `filter_smoking` IS NULL');

			$delId = array();

			// исключаем чёрный список
			$blackList = $this->getBlackList($male['id']);

			if(is_array($blackList)) {
				$delId = array_merge($delId, $blackList);
			}

			// Исключаем свидания
			$datesList = $this->getDatesList($male['id']);
			if(is_array($datesList)) {
				$delId = array_merge($delId, $datesList);
			}

			// Исключаем обмен контактами
			$contactList = $this->getContactList($male['id']);
			if(is_array($contactList)) {
				$delId = array_merge($delId, $contactList);
			}

			// Исключаем предыдущие фортуны
			$fortuneList = $this->getFortuneList($male['id']);
			if(is_array($fortuneList)) {
				$delId = array_merge($delId, $fortuneList);
			}

			// Пользовательский поиск
			if(!is_null($male['search_setting'])) {
				$search = unserialize($male['search_setting']);
				$dY = date('Y');
				$dMD = date('m-d');
				$select->where('`birthday` <= ?', $dY - $search['age_from'] .'-'.$dMD);
				$select->where('`birthday` >= ?', $dY - $search['age_to'] .'-'.$dMD);

				$select->where('`height` >= ?', $search['height_from']);
				$select->where('`height` <= ?', $search['height_to']);

				if (!is_null($search['children'])) {
					$select->where('`children` = ?', $search['children']);
				}

				// Курение
				if (!is_null($search['smoking'])) {
					$select->where('`smoking` = ?', $search['smoking']);
				}
			}

			try {
				//$this->printSql($select);
				$usersFemale = $this->db->fetchAll($select);
			} catch (Zend_Db_Exception $e) {
				//echo '<p class="sqlError">'.$e->getMessage().'<br>'.$select->__toString().'</p>';
			}


			#$cntFemale = count($usersFemale);
			#Sas_Debug::dump($cntFemale, 'Кол-во женщин');
			#Sas_Debug::dump(count($delId), 'Кол-во выкинуть');
			#Sas_Debug::dump($usersFemale, 'FEMALE');
			#Sas_Debug::dump($delId, 'DEL');

			$res = null;
			foreach($usersFemale as $female) {
				if(!in_array($female['id'], $delId)) {
					//$res[$cntGlb][] = $female;
					$res[] = $female;
				}
			}

			if(is_array($res)) {
				#Sas_Debug::dump(count($res), 'Остаток для выбора');
				shuffle($res);
				$dama = current($res);
				$para[$i]['male_id']  = (int) $male['id'];
				$para[$i]['female_id'] = (int) $dama['id'];

				#Sas_Debug::dump($dama, 'PARA - ' . $male['id']);
			} //else {
			//echo '<h1>NO PARA</h1>';
			//}

			$i++;
		}

		return $para;
	}

	private function getAge($date) {
		$ar = explode('-', $date);
		$y = $ar[0];
		$m = $ar[1];
		$d = $ar[2];
		if($m > date('m') || $m == date('m') && $d > date('d'))
			return (date('Y') - $y - 1);
		else
			return (date('Y') - $y);
	}

	/**
	 * Возвращает ID записи из чёрного списока пользователя
	 *
	 * @param $userId
	 * @return array
	 */
	private function getBlackList($userId)
	{
		$select = $this->db->select()
			->from('user_blacklist', array('id1'=>'user_id', 'id2'=>'bl_user_id'))
			->where('user_id = ' . $userId .' OR bl_user_id = ' . $userId);
		$rows = $this->db->fetchAll($select);
		foreach($rows as $ids)
		{
			$ret[] = ($ids['id1'] == $userId) ? $ids['id2'] : $ids['id1'];
		}

		return $ret;
	}

	/**
	 * Возвращает ID записи о свиданиях пользователя.
	 *
	 * @param $userId
	 * @return array
	 */
	public function getDatesList($userId)
	{
		$select = $this->db->select()
			->from('user_dates', array('id1'=>'invitee_id', 'id2'=>'inviter_id'))
			->where('invitee_id = ' . $userId .' OR inviter_id = ' . $userId);
		$rows = $this->db->fetchAll($select);
		foreach($rows as $ids)
		{
			$ret[] = ($ids['id1'] == $userId) ? $ids['id2'] : $ids['id1'];
		}

		return $ret;
	}

	/**
	 * Возвращает ID записи о обмене контактами пользователя.
	 *
	 * @param $userId
	 * @return array
	 */
	private function getContactList($userId)
	{
		$select = $this->db->select()
			->from('contact_exchange', array('user_id_from'))
			->where('user_id = ' . $userId);
		$rows = $this->db->fetchAll($select);
		foreach($rows as $ids)
		{
			$ret[] = $ids['user_id_from'];
		}

		return $ret;
	}

	/**
	 * Возвращает женские ID из прошлых игр пользователя.
	 *
	 * @param $userId
	 * @return array
	 */
	private function getFortuneList($userId)
	{
		$select = $this->db->select()
			->from($this->tblGamesPlay, array('female_id'))
			->where('male_id = ' . $userId);
		$rows = $this->db->fetchAll($select);
		foreach($rows as $ids)
		{
			$ret[] = $ids['female_id'];
		}

		return $ret;
	}

	/**
	 * Возвращает всех мужиков из указанной игры, отсортированных в порядке созданного ранее приоритета.
	 *
	 * @param $gameNumber Номер игры
	 * @return array
	 */
	private function getMaleGame($gameNumber)
	{
		$select = $this->db->select()
			->from(array('p'=>$this->tblProfile), array('id', 'first_name', 'sex', 'phone', 'promo_key', 'height', 'children', 'smoking', 'birthday', 'search_setting'))
			->join(array('g' => $this->tblGamesPlay), 'g.male_id = p.id', null)
			->where('g.game_number = ?', $gameNumber)
			->order('g.id ASC');

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает телефоны для отправки смс участникам игры.
	 *
	 * @param $gameNumber
	 * @return array
	 */
	public function getUsersPhones($gameNumber)
	{
		$select = $this->db->select()
			->from(array('g' => $this->tblGamesPlay), array('male_url', 'female_url'))
			->join(array('p1'=>$this->tblProfile), 'g.male_id = p1.id', array('maleId'=>'id', 'maleName' => 'first_name', 'malePhone' => 'phone'))
			->join(array('p2'=>$this->tblProfile), 'g.female_id = p2.id', array('femaleId'=>'id', 'femaleName' => 'first_name', 'femalePhone' => 'phone'))
			->where('g.game_number = ?', $gameNumber)
			->order('g.id ASC');

		return $this->db->fetchAll($select);
	}

	/**
	 * По URL возвращает пару людей
	 * @param $url
	 * @return array
	 */
	public function getUrlProfileUser($url)
	{
		$select = $this->db->select()
			->from(array('g' => $this->tblGamesPlay), array('id', 'male_url', 'female_url', 'male_action', 'female_action', 'dt_create'))
			->join(array('p1'=>$this->tblProfile), 'g.male_id = p1.id', array('maleId'=>'id', 'maleName' => 'first_name', 'malePhone' => 'phone'))
			->join(array('p2'=>$this->tblProfile), 'g.female_id = p2.id', array('femaleId'=>'id', 'femaleName' => 'first_name', 'femalePhone' => 'phone'))
			->where('g.female_url = "'.$url.'" OR g.male_url = "'.$url.'"');

		return $this->db->fetchRow($select);
	}

	/**
	 * Возвращает профиль участника игры.
	 *
	 * @param $userId
	 * @return array
	 */
	public function getProfileId($userId)
	{
		$select = $this->db->select()
			->from(array('profile'=>$this->tblProfile), '*')
			->columns($this->columnProfileImg)
			->where('profile.id = ?', $userId);

		return $this->db->fetchRow($select);
	}

	/**
	 * Регистрируем открытие страницы пользователем.
	 *
	 * @param $recordId
	 * @param $myAction
	 */
	public function registerOpenPage($recordId, $myAction)
	{
		$this->db->update($this->tblGamesPlay, array($myAction => 'open_page', 'dt_' . $myAction => $this->getCurrentSqlDt()), 'id = ' . $recordId);
	}

	/**
	 * Регистрируем решение пользователя.
	 *
	 * @param $recordId
	 * @param $myAction
	 * @param $answer
	 */
	public function registerUserAction($recordId, $myAction, $answer)
	{
		$this->db->update($this->tblGamesPlay, array($myAction => $answer, 'dt_' . $myAction => $this->getCurrentSqlDt()), 'id = ' . $recordId);
	}

	/**
	 * Возвращает фото пользователя
	 * @param $userId
	 * @return array
	 */
	public function getPhoto($userId)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from('user_pictures');
		$select->where('user_id = ?', $userId);
		$select->order('sort');
		$select->limit(9);

		$rows = $this->db->fetchAll($select);
		return $rows;
	}

	/**
	 * Сохранение настроек игры
	 * @param $userId
	 * @param $action
	 * @return bool true - Игрок подписался на игру | false - отписался от игры
	 */
	public function saveSetting($userId, $action)
	{
		$ret = true;

		if($this->isUserSubscrGame($userId) == 'no-data') { // Нет в тбл подписчиков
			if($action == 'no') {
				// Вносим данные и отписываем человека от игры
				$insert['user_id'] = (int) $userId;
				$insert['dt_create'] = $this->getCurrentSqlDt();
				$insert['dt_delete'] = $this->getCurrentSqlDt();
				$this->db->insert($this->tblGamesUser, $insert);

				$ret = false;
			} else {
				// Вносим данные и подписываем человека на игру
				$insert['user_id'] = (int) $userId;
				$insert['dt_create'] = $this->getCurrentSqlDt();
				$insert['dt_delete'] = null;
				$this->db->insert($this->tblGamesUser, $insert);
			}
		} else { // Есть запись в тбл подписчиков
			if($action == 'no') {
				// Отписываем человека от игры
				$update['dt_delete'] = $this->getCurrentSqlDt();
				$this->db->update($this->tblGamesUser, $update, 'user_id = ' . $userId);

				$ret = false;
			} else {
				// Подписываем человека на игру
				$update['dt_create'] = $this->getCurrentSqlDt();
				$update['dt_delete'] = null;
				$this->db->update($this->tblGamesUser, $update, 'user_id = ' . $userId);
			}

		}

		return $ret;
	}

	/**
	 * Проверяет есть ли пользователь в таблице игры для игры.
	 *
	 * @param $userId
	 * @return bool
	 */
	private function isUserGame($userId) {
		$select = $this->db->select()
			->from($this->tblGamesUser, 'dt_delete')
			->where('user_id = ?', $userId)
			->limit(1);

		$col = $this->db->fetchOne($select);

		return (!is_null($col)) ? true : false;
	}

	/**
	 * Проверяет подписку на игру.
	 *
	 * @param $userId
	 * @return string
	 */
	public function isUserSubscrGame($userId) {
		$select = $this->db->select()
			->from($this->tblGamesUser, '*')
			->where('user_id = ?', $userId)
			->limit(1);

		$col = $this->db->fetchRow($select);
		if($col === false) {
			$ret = 'no-data';
		} else {
			if(is_null($col['dt_delete'])) {
				$ret = 'yes';
			}
			if(!is_null($col['dt_delete'])) {
				$ret = 'no';
			}
		}

		return $ret;
	}

	public function getHistory($userId)
	{
		$select = $this->db->select()
			->from(array('g'=>$this->tblGamesPlay), array('game_number', 'dt_create'))
			->order('g.game_number DESC');

		if(Models_User_Model::getMySex() == 'male') {
			$select->columns(array('myAction'=>'male_action', 'partnerAction'=>'female_action'));
			$select->where('g.male_id = ?', $userId);

			$select->joinLeft(array('profile'=>$this->tblProfile), 'profile.id = g.female_id', array('partnerId'=>'id', 'first_name', 'phone'));
			$select->columns($this->columnProfileImg);
		} else {
			#$select->columns(array('myAction'=>'female_action', 'partnerAction'=>'female_action'));
			$select->columns(array('myAction'=>'female_action', 'partnerAction'=>'male_action'));
			$select->where('g.female_id = ?', $userId);

			#$select->joinLeft(array('profile'=>$this->tblProfile), 'profile.id = g.female_id', array('partnerId'=>'id', 'first_name', 'phone'));
			$select->joinLeft(array('profile'=>$this->tblProfile), 'profile.id = g.male_id', array('partnerId'=>'id', 'first_name', 'phone'));
			$select->columns($this->columnProfileImg);
		}

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает путь для вывода фото
	 * @param $userId
	 * @param $sex
	 * @param $birthday
	 * @return string
	 */
	public function getViewPatch($userId, $sex, $birthday)
	{
		return '/img/people/' . $sex . '/' . Models_User_Model::getMyYear($birthday) . '/' . $userId . '/';
	}

	/**
	 * Возвращает текущее дату и время в формате mysql (для вставки данных)
	 * @return string
	 */
	private function getCurrentSqlDt() {
		return date('Y-m-d H:i:s');
	}

	/**
	 * Генератор уникальных ссылок.
	 *
	 * @param int $numAlpha
	 * @return string
	 */
	private function generatorUrl($numAlpha = 8)
	{
		// символы из которых генерируется индентификатор
		$listAlpha = 'abcdefghjkmnpqrstuvwxyz0123456789ABCDEFGHJKMNPQRSTUVWXYZ';

		// генерируем индентификатор и возвращаем
		return str_shuffle(substr(str_shuffle($listAlpha),0,$numAlpha));
	}

	private function printSql($select)
	{
		echo '<p class="sql">'.$select->__toString().'</p>';
	}

	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'fortune'),
			'name'  => $tr->translate('Колесо Фортуны'),
			'check' => 'user/fortune',
			'style' => ' active',
			'icon'  => 'Fortune'
		);

		return $menu;
	}
	//============ /MENU ===========
}
<?php

class Models_User_Search
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $lang = LANG_DEFAULT;

	private $myId = null;

	private $maxCntSearch = 0;

	private $columnProfileAvatar = array('avatar' => 'CONCAT( "/img/people/", `users`.`sex`, "/", YEAR(`users`.`birthday`), "/", `users`.`id`, "/thumbnail.jpg" )');

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = Models_User_Model::getMyId();
	}

	/**
	 * Подготовка данных для поиска
	 *
	 * @param $data array()
	 * @return mixed
	 */
	private function preparationDataPost($data) {

		// Возраст
		if(is_numeric($data['age_from']) && is_numeric($data['age_to'])) {
			$data['age_from'] = (int) $data['age_from'];
			$data['age_to']  = (int) $data['age_to'];

			if($data['age_from'] <= 20 || $data['age_from'] >= 60) {
				$data['age_from'] = 20;
			}
			if($data['age_to'] > 60 || $data['age_to'] <= 20) {
				$data['age_to'] = 60;
			}

			if($data['age_from'] > $data['age_to']) {
				$d1 = $data['age_from'];
				$d2 = $data['age_to'];
				$data['age_from'] = $d2;
				$data['age_to'] = $d1;
			}
		} else {
			$data['age_from'] = 20;
			$data['age_to'] = 60;
		}

		// Рост
		if(is_numeric($data['height_from']) && is_numeric($data['height_to'])) {
			$data['height_from'] = (int) $data['height_from'];
			$data['height_to']  = (int) $data['height_to'];

			if($data['height_from'] > $data['height_to']) {
				$d1 = $data['height_from'];
				$d2 = $data['height_to'];
				$data['height_from'] = $d2;
				$data['height_to'] = $d1;
			}
		} else {
			$data['height_from'] = 160;
			$data['height_to']  = 190;
		}

		// Дети
		switch($data['children']) {
			case '0': $data['children'] = null; break;
			case 'yes': $data['children'] = 'yes'; break;
			case 'no': $data['children'] = 'no'; break;

			default: $data['children'] = null;
		}

		// Курение
		switch($data['smoking']) {
			case '0': $data['smoking'] = null; break;
			case 'yes': $data['smoking'] = 'yes'; break;
			case 'no': $data['smoking'] = 'no'; break;

			default: $data['smoking'] = null;
		}

		// Семейное положение
		switch($data['marital_status']) {
			case '0': $data['marital_status'] = null; break;
			case 'yes': $data['marital_status'] = 'yes'; break;
			case 'no': $data['marital_status'] = 'no'; break;

			default: $data['smoking'] = null;
		}

		// Языки
		/*if (is_array($data['languages'])) {
			foreach($data['languages'] as $lang => $int) {
				if(!is_numeric($int)) {
					$data['languages'] = null;
					return;
				}
				else {
					$data['languages'][$lang] = (int) $int;
				}
			}
		}*/

		// Компания
		if(!empty($data['company'])) {
			$data['company'] = Sas_Filter_Text::get($data['company']);
		}

		// Должность
		if(!empty($data['position_job'])) {
			$data['position_job'] = Sas_Filter_Text::get($data['position_job']);
		}

		// Образование
		if(!empty($data['education'])) {
			$data['education'] = Sas_Filter_Text::get($data['education']);
		}

		return $data;
	}

	/**
	 * Поиск по анкетам
	 *
	 * @param     $post_data
	 * @param int $page
	 * @param int $rowCount
	 * @param     $myProfile
	 * @return array
	 */
	public function search($post_data, $page=0, $rowCount=10, $myProfile)
	{
		$postData = $this->preparationDataPost($post_data);
		#Sas_Debug::dump($postData, 'preparationDataPost POST data');

		// Выполняем сохранение текущих параметров поиска
		//$this->saveSearchSetting($postData);

		#$searchLog = array();
		#$searchLog['user_id'] = $this->myId;
		#$searchLog['user_lang'] = $this->lang;
		#$searchLog['user_sex'] = $myProfile['sex'];

		$select = $this->db->select();
		$select->from('users');
		$select->columns(array('userId'=>'users.id', 'uid'=>'users.uid'));
		$select->columns($this->columnProfileAvatar);

		// Город
		$select->joinLeft('cities', 'cities.id = users.city_id', array('cityName'=>'name_'.$this->lang));

		// Исключаем МЕНЯ
		$select->where('users.id != ?', $this->myId);

		// Исключаем ADMIN
		$select->where('users.id != ?', 4000);

		// Работаем ТОЛЬКО с активными анкетами
		//$select->where('current_status = ?', '70');
		$select->where('current_status = 70');

		// Компания
		if(!empty($postData['company']) && !is_null($postData['company']) && $postData['company'] != 'null') {
			$select->where('company LIKE "%'.$postData['company'].'%"');
		}

		// Должность
		if(!empty($postData['position_job']) && !is_null($postData['position_job']) && $postData['position_job'] != 'null') {
			$select->where('position_job LIKE "%'.$postData['position_job'].'%"');
		}

		// Образование
		if(!empty($postData['education']) && !is_null($postData['education']) && $postData['education'] != 'null') {
			$select->where('education LIKE "%'.$postData['education'].'%"');
		}

		// Проф интересы
		if(is_array($postData['prof'])) {
			$select->joinLeft('prof_interes_user', 'prof_interes_user.user_id = users.id', null);
			$select->where('prof_interes_user.prof_interes_id IN ('.implode(',', $postData['prof']).')');
			$select->group('users.id');
		}


		// ИСКЛЮЧАЕМ Чёрный список
		$minusIdSelect = '`users`.`id` NOT IN (';
		$ModelBlackList = new Models_User_BlackList();
		$blackList = $ModelBlackList->getBlackListForSearch();
		if (!empty($blackList)) {
			for ($i = 0, $maxCnt = count($blackList); $i < $maxCnt; $i++)
			{
				if ($blackList[$i]['my_list'] == $this->myId) {
					$minusIdSelect .= $blackList[$i]['pipl_list'] . ',';
				} else {
					$minusIdSelect .= $blackList[$i]['my_list'] . ',';
				}
			}
		}

		// Исключаем из результатов поиска фаворитов
		/*$ModelFavorites = new Models_User_Favorites();
		$favorites = $ModelFavorites->getFavoritesID();
		if (!empty($favorites)) {
			foreach($favorites as $fId => $v) {
				$minusIdSelect .= $fId . ',';
			}
		}*/

		// Исключаем скрытые профили
		#$select->where('hide_profile != ?', 'yes');

		// Исключаем удалённые пользователем профили
		//$select->where('deleted != ?', 1);



		// ИСКЛЮЧАЕМ людей с которыми уже были свидания (status = yes)
		/*$ModelDates = new Models_User_Dates();
		$dates = $ModelDates->getHistory('yes');
		if (!empty($dates))
		{
			for ($i = 0, $maxCnt = count($dates); $i < $maxCnt; $i++)
			{
				// выкидываем мой id из запроса
				$delId = ($this->myId == $dates['invitee_id']) ? $dates[$i]['inviter_id'] : $dates[$i]['invitee_id'];

				$deleteId[$delId]=null;
			}

			foreach($deleteId as $key=>$val) {
				$minusIdSelect .= $key . ',';
			}
		}*/

		$minusIdSelect = substr($minusIdSelect, 0, -1);
		$minusIdSelect .= ')';
		if(strlen($minusIdSelect) > 26) $select->where($minusIdSelect); // исключили пустые запросы

		// ИСКЛЮЧАЕМ на основании пользовательского фильтра
		/*
		$myAge = Models_User_Model::getAge(Models_User_Model::getMyBirthday());
		$myHeight = Models_User_Model::getMyHeight();
		$myChildren = Models_User_Model::getMyChildren();
		$mySmoking = Models_User_Model::getMySmoking();

		// Возраст
		$select->where('`filter_age_min` <= ' . $myAge . ' OR `filter_age_min` IS NULL');
		$select->where('`filter_age_max` >= ' . $myAge . ' OR `filter_age_max` IS NULL');

		// Рост
		$select->where('`filter_height_min` <= ' . $myHeight . ' OR `filter_height_min` IS NULL');
		$select->where('`filter_height_max` >= ' . $myHeight . ' OR `filter_height_max` IS NULL');

		// Дети
		$select->where('`filter_children` = "'. $myChildren .'" OR `filter_children` IS NULL');

		// Отношение к курению
		$select->where('`filter_smoking` = "'. $mySmoking .'" OR `filter_smoking` IS NULL');

		// Языковой фильтр - НОВЫЙ
		if($postData['lang_select'] == 'and') {

			$searchLog['lang_method'] = 'and';

			if($postData['filter_lang_ru'] == 'yes') $select->where('filter_lang_ru = ?', 'yes');
			if($postData['filter_lang_en'] == 'yes') $select->where('filter_lang_en = ?', 'yes');
			if($postData['filter_lang_fr'] == 'yes') $select->where('filter_lang_fr = ?', 'yes');
			if($postData['filter_lang_de'] == 'yes') $select->where('filter_lang_de = ?', 'yes');
			if($postData['filter_lang_it'] == 'yes') $select->where('filter_lang_it = ?', 'yes');
			if($postData['filter_lang_es'] == 'yes') $select->where('filter_lang_es = ?', 'yes');
		}

		if($postData['lang_select'] == 'or') {

			$searchLog['lang_method'] = 'or';

			if($postData['filter_lang_ru'] == 'yes' ||
				$postData['filter_lang_en'] == 'yes' ||
				$postData['filter_lang_fr'] == 'yes' ||
				$postData['filter_lang_de'] == 'yes' ||
				$postData['filter_lang_it'] == 'yes' ||
				$postData['filter_lang_es'] == 'yes'
			) {
				$langOr = '';
				$langOr .= ($postData['filter_lang_ru'] == 'yes') ? 'filter_lang_ru = "yes" OR ' : '';
				$langOr .= ($postData['filter_lang_en'] == 'yes') ? 'filter_lang_en = "yes" OR ' : '';
				$langOr .= ($postData['filter_lang_fr'] == 'yes') ? 'filter_lang_fr = "yes" OR ' : '';
				$langOr .= ($postData['filter_lang_de'] == 'yes') ? 'filter_lang_de = "yes" OR ' : '';
				$langOr .= ($postData['filter_lang_it'] == 'yes') ? 'filter_lang_it = "yes" OR ' : '';
				$langOr .= ($postData['filter_lang_es'] == 'yes') ? 'filter_lang_es = "yes" OR ' : '';
				$langOr = substr($langOr, 0, -4);
				$select->where($langOr);
			}
		}
		$searchLog['filter_lang_ru'] = ($postData['filter_lang_ru'] == 'yes') ? 'yes' : 'no';
		$searchLog['filter_lang_en'] = ($postData['filter_lang_en'] == 'yes') ? 'yes' : 'no';
		$searchLog['filter_lang_fr'] = ($postData['filter_lang_fr'] == 'yes') ? 'yes' : 'no';
		$searchLog['filter_lang_de'] = ($postData['filter_lang_de'] == 'yes') ? 'yes' : 'no';
		$searchLog['filter_lang_it'] = ($postData['filter_lang_it'] == 'yes') ? 'yes' : 'no';
		$searchLog['filter_lang_es'] = ($postData['filter_lang_es'] == 'yes') ? 'yes' : 'no';
		*/

		// Языковой фильтр - ОТКЛЮЧЕНО
		/*$langFilter = $this->getMyLangSetting(); // Получаем "мои" языковые настройки
		for($i = 0; $i < count($langFilter); $i++) {
			$select->where('`filter_lang_'.$langFilter[$i].'` = "yes" OR `filter_lang_'.$langFilter[$i].'` IS NULL');
		}*/
		// END конец фильтров

		#------------
		// Пол
		if ($postData['search_block'] == 'romantic') {
			if($myProfile['sex'] == 'male') {
				$select->where('`sex` = ?', 'female');
			} else {
				$select->where('`sex` = ?', 'male');
			}
			$select->where('`romantic` = ?', 'yes');

			// Семейное положение
			#if (!is_null($postData['marital_status'])) {
			#	$select->where('`marital_status` = ?', $postData['marital_status']);
			#}

			// Возраст
			if (!is_null($postData['age_from'])) {// разборки с датой (искали Альмира)
				$ageFrom =  (date('Y') - $postData['age_from'] ) . '-'. date('m-d');//. '-12-31';//. date('m-d');
				$select->where('`birthday` <= ?', $ageFrom);

				#$searchLog['age_min'] = $postData['age_from'];
			}
			if (!is_null($postData['age_to'])) {
				$ageTo =  (date('Y') - $postData['age_to'] - 1) . '-'. date('m-d');
				$select->where('`birthday` >= ?', $ageTo);

				#$searchLog['age_max'] = $postData['age_to'];
			}

			// Рост
			if (!is_null($postData['height_from'])) {
				$select->where('`height` >= ?', $postData['height_from']);
				#$searchLog['height_min'] = $postData['height_from'];
			}
			if (!is_null($postData['height_to'])) {
				$select->where('`height` <= ?', $postData['height_to']);
				#$searchLog['height_max'] = $postData['height_to'];
			}

			// Дети
			if (!is_null($postData['children'])) {
				$select->where('`children` = ?', $postData['children']);
				#$searchLog['children'] = $postData['children'];
			}

			// Курение
			if (!is_null($postData['smoking'])) {
				$select->where('`smoking` = ?', $postData['smoking']);
				#$searchLog['smoking'] = $postData['smoking'];
			}
		}

		// Поиск по личным интересам
		if(is_array($postData['hobby'])) {
			$select->joinLeft('hobby_user', 'hobby_user.user_id = users.id', null);
			$select->where('hobby_user.hobby_id IN ('.implode(',', $postData['hobby']).')');
			$select->group('users.id');
		}

		// Поиск по жизненным целям
		if(is_array($postData['target'])) {
			$select->joinLeft('target_user', 'target_user.user_id = users.id', null);
			$select->where('target_user.target_id IN ('.implode(',', $postData['target']).')');
			$select->group('users.id');
		}


		// Выбираем только тех у кого номер телефона подтвержден
		#$select->where('phone_check = ?', 'yes');

		// Языки
		/*if (is_array($postData['languages'])) {
			$select->joinLeft('user_languages', 'user_languages.user_id = users_data.id');
			$select->joinLeft('languages', 'languages.id = user_languages.language_id');
			$orWhere = '';
			foreach($postData['languages'] as $l => $int) {
				$orWhere .= '`language_id` = '.$int.' OR '; // было OR
			}
			$orWhere = substr($orWhere, 0, -4);
			$select->where($orWhere);
			$select->group('users_data.id');
		}*/


		// Вывод тех кто не указал рост
		#if($post_data['height_null'] == 1) {
		#	$select->where('`height` IS NULL');
		#}



		// Подсчёт общего кол-ва найденных записей
		# ЗАБЛОКИРОВАННО ДЛЯ ВЫВОДА БЕЗКОНЕЧНОГО СПИСКА $this->setMaxCntSearch(clone($select));

		$select->order(array('users.online', 'users.online_last_dt DESC'));
		$select->limitPage($page, $rowCount);

		// Статусы
		$select->joinLeft(array('st'=>'users_status'), 'st.user_id=users.id AND st.status_hide ="no"', array('status_text', 'status_vip'));

		#$searchLog['current_pages'] = $page;
		#$searchLog['cnt_max_result'] = $this->maxCntSearch;
		Models_Actions::add(41, $this->myId); // Выполнен поиск
		# ВРЕМЕННО ЗАБЛОКИРОВАНО Models_Actions::searchLog($searchLog);

		//Sas_Debug::sql($select);
		$rows = $this->db->fetchAll($select);
		//Sas_Debug::dump($rows, 'RESULT');

		return $rows;
	}

	/**
	 * Получаем и устанавливаем кол-во найденных записей при поиске
	 *
	 * @param Zend_Db_Select $select
	 */
	public function setMaxCntSearch(Zend_Db_Select $select)
	{
		$select->reset('columns');
		//$select->reset('group');
		#$select->columns('COUNT(`users_data`.`id`)');
		$select->columns('users.id');
		#Sas_Debug::dump($select->__toString(), __METHOD__);
		#$res = $this->db->fetchOne($select);
		$res = $this->db->fetchCol($select);
		$this->maxCntSearch = count($res);
		#Sas_Debug::dump($this->maxCntSearch, 'CNT result search');
	}

	/**
	 * Возвращает кол-во найденных записей при поиске
	 *
	 * @return int
	 */
	public function getMaxCntSearch()
	{
		return $this->maxCntSearch;
	}

	/**
	 * Выполняем сохранение текущих параметров поиска
	 *
	 * @param $params текущие параметры запроса
	 */
	private function saveSearchSetting($params)
	{
		unset($params['page']); // Выкидываем информацию о странице с результатами поиска

		$serializeSession = Models_User_Model::getMySearchSetting();
		$serializeData = serialize($params);
		if($serializeSession != $serializeData) // НЕ РАВНО!
		{
			Models_User_Model::setMySearchSetting($serializeData);// пишем в сессию
			$Model = new Models_User_Profile();// пишем в базу
			$Model->saveSearchSetting($serializeData);
		}

		// в противном случае ничего не делаем :)
	}

	/**
	 * Возвращает массив с буквенным названием языков на которых может говорить пользователь
	 * @return array
	 */
	private function getMyLangSetting()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from('user_languages', null);
		$select->join('languages', 'languages.id=user_languages.language_id', 'small_name');
		$select->where('user_id =?', $this->myId);
		#Sas_Debug::dump($select->__toString());
		$rows = $this->db->fetchCol($select);
		#Sas_Debug::dump($rows);
		return $rows;
	}

	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		#$sex = (Models_User_Model::getMySex() == 'male') ? $tr->translate('Девушки') : $tr->translate('Мужчины');
		$sex = $tr->translate('Поиск');
		#$sexIcon = (Models_User_Model::getMySex() == 'male') ? 'Female' : 'Male';
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'search'),
			'name'  => $sex,
			'check' => 'user/search',
			'style' => ' active',
			'icon'  => 'Search', // $sexIcon
			'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'index'),
					'name'  => $tr->translate('Романтика'),
					'check' => 'user/search/index',
					'style' => ' active',
					'icon'  => 'Romantic',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'business'),
					'name'  => $tr->translate('Бизнес'),
					'check' => 'user/search/business',
					'style' => ' active',
					'icon'  => 'Business',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'interests'),
					'name'  => $tr->translate('Интересы'),
					'check' => 'user/search/interests',
					'style' => ' active',
					'icon'  => 'Interests',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'targets'),
					'name'  => $tr->translate('Цели'),
					'check' => 'user/search/targets',
					'style' => ' active',
					'icon'  => 'Targets',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'favourites'),
					'name'  => $tr->translate('Избранное'),
					'check' => 'user/search/favourites',
					'style' => ' active',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'blacklist'),
					'name'  => $tr->translate('Заблокированные'),
					'check' => 'user/search/blacklist',
					'style' => ' active',
				),
			)
		);

		return $menu;
	}

	static public function getMenuPhone() {
		$tr = Zend_Registry::get('Zend_Translate');
		$sex = $tr->translate('Поиск');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'search'),
			'name'  => $sex,
			'check' => 'user/search',
			'style' => ' active',
			'icon'  => 'Search',
			'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'index'),
					'name'  => $tr->translate('Романтика'),
					'check' => 'user/search/index',
					'style' => ' active',
					'icon'  => 'Romantic',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'business'),
					'name'  => $tr->translate('Бизнес'),
					'check' => 'user/search/business',
					'style' => ' active',
					'icon'  => 'Business',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'interests'),
					'name'  => $tr->translate('Интересы'),
					'check' => 'user/search/interests',
					'style' => ' active',
					'icon'  => 'Interests',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'targets'),
					'name'  => $tr->translate('Цели'),
					'check' => 'user/search/targets',
					'style' => ' active',
					'icon'  => 'Targets',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'favourites'),
					'name'  => $tr->translate('Избранное'),
					'check' => 'user/search/favourites',
					'style' => ' active',
					'icon'  => 'Favorite',
				),
			)
		);

		return $menu;
	}
	//============ /MENU ===========
}
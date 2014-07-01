<?php

/**
 * Поиск
 */
class User_SearchController extends Sas_Controller_Action_User
{
	/**
	 * Обеспечение поиска
	 */
	public function indexAction()
	{
		// Мой профиль
		$ModelProfile = new Models_User_Profile();
		$myProfile = $ModelProfile->getProfile(Models_User_Model::getMyId());

		// Автоматический выброс пользователя из системы, если его скажем заблокировали
		if($myProfile['current_status'] < 50) {$this->_redirect('/user/login/quit');}

		$this->view->myProfile = $myProfile;

		// Профессиональные интересы
		$ModelProfInteres = new Models_User_ProfInteres();
		$this->view->vProfList = $ModelProfInteres->getProfList();

		// Хобби
		$ModelHobby = new Models_User_Hobby();
		$this->view->vHobbyList = $ModelHobby->getHobbyList();

		// Жизненные цели
		$ModelTarget = new Models_User_Target();
		$this->view->vTargetList = $ModelTarget->getTargetList();

		$request = $this->getRequest();
		$params = $request->getParams();
		#Sas_Debug::dump($request->getParams());

		if(!$params['page']) {
			$this->clearCookieSearch('search-page');
		}

		// Преодбазование из строк (куки) 123,14,876 в массив
		if(!empty($params['prof']) && !is_array($params['prof'])) {
			$params['prof'] = explode(',', $params['prof']);
			$this->_setParam('prof', $params['prof']);
		}
		if(!empty($params['hobby']) && !is_array($params['hobby'])) {
			$params['hobby'] = explode(',', $params['hobby']);
			$this->_setParam('hobby', $params['hobby']);
		}
		if(!empty($params['target']) && !is_array($params['target'])) {
			$params['target'] = explode(',', $params['target']);
			$this->_setParam('target', $params['target']);
		}

		// Смотрим куки
		if(!$this->isCookieSearch('search')) { // Нет куки
			// А запрос для поиска есть?
			if(empty($params['search_block'])) { // Запрос для поиска пустой
				// Кидаем параметры для поиска по умолчанию
				$defParams['search_block'] = 'romantic';
				$defParams['age_from'] = 24;
				$defParams['age_to'] = 40;
				$defParams['height_from'] = 160;
				$defParams['height_to'] = 190;
				$defParams['children'] = 'no';
				$defParams['smoking'] = 'no';

				foreach($defParams as $k => $v) {
					$this->_setParam($k, $v);
				}

				$this->setCookieSearch('search', Zend_Json::encode($defParams));
			} else { // Да, запрос есть
				// Искали бизнес
				if($params['search_block'] == 'business') {
					$searchParams['search_block'] = 'business';
					$searchParams['company']      = $params['company'];
					$searchParams['position_job'] = $params['position_job'];
					$searchParams['education']    = $params['education'];

					$this->setCookieSearch('search', Zend_Json::encode($searchParams));
				}
				// Искали цели
				if($params['search_block'] == 'target') {
					$searchParams['search_block'] = 'target';
					$searchParams['target'] = $params['target'];

					$this->setCookieSearch('search', Zend_Json::encode($searchParams));
				}
				// Искали хобби (интересы)
				if($params['search_block'] == 'hobby') {
					$searchParams['search_block'] = 'hobby';
					$searchParams['hobby'] = $params['hobby'];
					$searchParams['prof']  = $params['prof'];

					$this->setCookieSearch('search', Zend_Json::encode($searchParams));
				}
			}
		} else {
			// Уже искали?
			if(empty($params['search_block'])) {
				// Нет не искали еще

				// Восстанавливаем данные из куки
				$cookieJson = $this->getCookieSearch('search');
				$cParams = Zend_Json::decode($cookieJson);

				// Заводим в систему параметры последнего поиска
				foreach($cParams as $k => $v) {
					$this->_setParam($k, $v);
				}
			} else {
				//Sas_Debug::dump('SAS');
				// Да уже искали есть параметры запроса
				$this->clearCookieSearch('search'); // Чистим куки

				// Искали романтику
				if($params['search_block'] == 'romantic') {
					$searchParams['search_block'] = 'romantic';
					$searchParams['age_from']     = $params['age_from'];
					$searchParams['age_to']       = $params['age_to'];
					$searchParams['height_from']  = $params['height_from'];
					$searchParams['height_to']    = $params['height_to'];
					$searchParams['children']     = $params['children'];
					$searchParams['smoking']      = $params['smoking'];

					$this->setCookieSearch('search', Zend_Json::encode($searchParams));
				}

				// Искали бизнес
				if($params['search_block'] == 'business') {
					$searchParams['search_block'] = 'business';
					$searchParams['company']      = $params['company'];
					$searchParams['position_job'] = $params['position_job'];
					$searchParams['education']    = $params['education'];

					$this->setCookieSearch('search', Zend_Json::encode($searchParams));
				}

				// Искали цели
				if($params['search_block'] == 'target') {
					$searchParams['search_block'] = 'target';
					$searchParams['target']       = $params['target'];

					$this->setCookieSearch('search', Zend_Json::encode($searchParams));
				}

				// Искали хобби (интересы)
				if($params['search_block'] == 'hobby') {
					$searchParams['search_block'] = 'hobby';
					$searchParams['hobby']        = $params['hobby'];
					$searchParams['prof']         = $params['prof'];

					$this->setCookieSearch('search', Zend_Json::encode($searchParams));
				}
			}
		}

		#Sas_Debug::dump($request->getParams());

		// Выполняем поиск
		if($this->_getParam('search_block') == 'romantic'
			|| $this->_getParam('search_block') == 'business'
			|| $this->_getParam('search_block') == 'hobby'
			|| $this->_getParam('search_block') == 'target'
		) {
			$page = (int) $request->getParam('page', 1);
			//$this->setCookieSearch('search-page', $page);

			$ModelSearch = new Models_User_Search();

			if($myProfile['romantic'] != 'yes' && $this->_getParam('search_block') == 'romantic') {
				$result = false;
			} else {
				$result = $ModelSearch->search($this->_getAllParams(), $page, 30, $myProfile);
			}
			if(!empty($result)) {
				$usersId = array();
				foreach($result as $users) {
					$usersId[] = $users['id'];
				}

				// Блок совпадений показываем и формируем только для:
				// romantic + hobby + target
				// то есть, исключая business
				if($this->_getParam('search_block') != 'business') {
					// Для romantic и hobby блок одинаковый
					if($this->_getParam('search_block') != 'target') {
						$items = $ModelHobby->getHobbyUsers($usersId);
						$myItems = $ModelHobby->getHobbyUser($myProfile['id']);
					} else { // Персональный блок для целей (target)
						$items = $ModelTarget->getTargetUsers($usersId);
						$myItems = $ModelTarget->getTargetUser($myProfile['id']);
					}

					$out = array();
					$i=0;
					foreach($result as $user) {
						$out[$i] = $user;
						$out[$i]['cntInteres'] = count($items[$user['id']]);
						if(is_array($items[$user['id']])) {
							foreach($items[$user['id']] as $int) {
								if(array_key_exists($int, $myItems)) {
									$out[$i]['cntMatch'] += 1;
								}
							}
						}

						$i++;
					}
				} else {
					$out = $result;
				}

				#Sas_Debug::dump($result);
				// Пишем на вывод результаты поиска
				$this->view->vSearchResult = $out;
			}
		}

		// Пробрасываем параметры поискового запроса на вывод
		#Sas_Debug::dump($this->_getAllParams(), "ALL PARAMS");
		$this->view->paramSearch = $this->_getAllParams();

	}

	public function ajaxAction()
	{
		$this->ajaxInit();

			// Мой профиль
		$ModelProfile = new Models_User_Profile();
		$myProfile = $ModelProfile->getProfile(Models_User_Model::getMyId());

		// Автоматический выброс пользователя из системы, если его скажем заблокировали
		if($myProfile['current_status'] < 50) {$this->_redirect('/user/login/quit');}


		#$json['params1'] = $this->_getAllParams();

		// Выполняем поиск
		if($this->_getParam('search_block') == 'romantic'
			|| $this->_getParam('search_block') == 'business'
			|| $this->_getParam('search_block') == 'hobby'
			|| $this->_getParam('search_block') == 'target'
		) {
			$page = (int) $this->_getParam('page', 1);

			if($this->_getParam('prof') != 'null' && $this->_getParam('prof') && !is_array($this->_getParam('prof'))) {
				$this->_setParam('prof', explode(',', $this->_getParam('prof')));
			}

			if($this->_getParam('hobby') != 'null' && $this->_getParam('hobby') && !is_array($this->_getParam('hobby'))) {
				$this->_setParam('hobby', explode(',', $this->_getParam('hobby')));
			}
			if($this->_getParam('target') != 'null' && $this->_getParam('target') && !is_array($this->_getParam('target'))) {
				$this->_setParam('target', explode(',', $this->_getParam('target')));
			}

			$json['params2'] = $this->_getAllParams();
			$ModelSearch = new Models_User_Search();
			$result = $ModelSearch->search($this->_getAllParams(), $page, 30, $myProfile);

			if(!empty($result)) {
				$usersId = array();
				foreach($result as $users) {
					$usersId[] = $users['id'];
				}

				$urlProfile = ($this->_getParam('lang') != 'ru') ? '/'.$this->_getParam('lang') : '';

				// Блок совпадений показываем и формируем только для:
				// romantic + hobby + target
				// то есть, исключая business
				$json['search_block'] = $this->_getParam('search_block');
				if($this->_getParam('search_block') != 'business') {
					// Для romantic и hobby блок одинаковый
					if($this->_getParam('search_block') != 'target') {
						$ModelHobby = new Models_User_Hobby();
						$items = $ModelHobby->getHobbyUsers($usersId);
						$myItems = $ModelHobby->getHobbyUser($myProfile['id']);
					} else { // Персональный блок для целей (target)
						$ModelTarget = new Models_User_Target();
						$items = $ModelTarget->getTargetUsers($usersId);
						$myItems = $ModelTarget->getTargetUser($myProfile['id']);
					}

					$i=0;
					foreach($result as $user) {
						$json['data'][$i] = array(
							'first_name'   => $user['first_name'],
							'avatar'       => $user['avatar'],
							'profile_link' => $urlProfile . '/user/people/profile/view/'.$user['uid'],
							'online'       => $user['online'],
							'status_text'  => $user['status_text']
						);

						if(is_array($items[$user['id']])) {
							foreach($items[$user['id']] as $int) {
								if(array_key_exists($int, $myItems)) {
									$json['data'][$i]['cntMatch'] += 1;
								}
							}
						}
						$json['data'][$i]['cntMatch'] = (isset($json['data'][$i]['cntMatch'])) ? $json['data'][$i]['cntMatch'] : 0;
						$json['data'][$i]['cntInteres'] = count($items[$user['id']]);

						$i++;
					}
				} else {
					$i=0;
					foreach($result as $user) {
						$json['data'][$i] = array(
							'first_name'   => $user['first_name'],
							'avatar'       => $user['avatar'],
							'profile_link' => $urlProfile . '/user/people/profile/view/'.$user['uid'],
							'online'       => $user['online'],
							'status_text'  => $user['status_text'],
							'company'      => $user['company'],
							'position_job' => $user['position_job']
						);
						$i++;
					}
				}

			} else {
				$json['error'] = 'no result';
			}

		}

		//unset($json['data']);

		$this->getJson($json);
	}


	// КУКИ
	private function getCookieSearch($name) {
		return $_COOKIE[$name];
	}

	private function setCookieSearch($name, $value) {
		if($this->getLang() == 'ru') {
			setcookie($name, $value, 0, '/user');
		} else {
			setcookie($name, $value, 0, '/'.$this->getLang().'/user');
		}

	}

	private function isCookieSearch($name) {
		return (!empty($_COOKIE[$name])) ? true : false;
	}

	private function clearCookieSearch($name) {
		setcookie ($name, '', time() - 3600);
		setcookie ($name, '', time() - 3600, '/user');
		setcookie ($name, '', time() - 3600, '/'.$this->getLang().'/user');
		unset($_COOKIE[$name]);
	}
}

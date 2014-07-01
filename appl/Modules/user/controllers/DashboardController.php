<?php

class User_DashboardController extends Sas_Controller_Action_User
{
	/**
	 * Первая страница открывающаяся пользователю (Dachboard)
	 */
	public function indexAction()
	{
		// Мой профиль
		$myID = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myID);
		$myProfile = $ModelProfile->getProfile();

		// Автоматический выброс пользователя из системы, если его скажем заблокировали
		if($myProfile['current_status'] < 50) {
			//$this->_helper->actionStack('quit', 'login', 'user');
			$this->_redirect('/user/login/quit');
		} else {
			$_SESSION['user']['current_status'] = $myProfile['current_status'];
		}

		$this->view->myProfile = $myProfile;


		// Блоки стандартных сообщений
		if($myProfile['current_status'] == 70) {
			$ModelDash = new Models_User_Dashboard($myID);
			$this->view->vDashMyGuests = $ModelDash->getMyGuests(); // Мои гости
			$this->view->vDashNewUsers = $ModelDash->getNewUsers(); // Новые ЧК
			$this->view->vDashEvents = $ModelDash->getEventsUsers(); // Люди, которые идут на такие же мероприятия что и я
			$this->view->vDashLoveMyPhoto = $ModelDash->getLoveMyPhoto(); // Люди, которым нравятся мои фото
			$this->view->vDashAddFavorites = $ModelDash->getAddFavorites(); // Люди которые добавили меня в фавориты
			$this->view->vDashBirthday = $ModelDash->getBirthday(); // Именинники сегодня
			$this->view->vDashPhotoDay = $ModelDash->getPhotoDay(); // Лучшие фото сегодняшнего дня
		}

		// Новости
		$ModelNews = new Models_News();
		$this->view->vNews = $ModelNews->getLast(3);

		// Афиша
		$ModelEvent = new Models_User_Event($myID);
		$this->view->vEvents = $ModelEvent->getEventsNoStart(false, null, null, 3);

		// Посты
		$ModelPosts = new Models_User_Posts($myID);
		$postsFavorite = $ModelPosts->getPostsAllFavAndMoney(20);
		$this->view->vPostsFavorite = $postsFavorite;
		$this->view->vILikePostFavorite = $ModelPosts->getILikePost($postsFavorite);
	}

	/**
	 * Список новых моих гостей
	 */
	public function popupMyGuestsAction()
	{
		$this->ajaxInit();

		$this->view->vTitle = $this->view->t('Ваши гости');

		$json = array();
		$ModelDash = new Models_User_Dashboard();
		$data = $ModelDash->getMyGuests();
		if(!empty($data)) {
			$urlLang = ($this->getLang() == 'ru') ? '' : '/'.$this->getLang();
			$i = 0;
			foreach ($data as $row) {
				$json[$i]['uid'] = $row['uid'];
				$json[$i]['url'] = $urlLang. '/user/people/profile/view/'.$row['uid'];
				$json[$i]['title'] = $row['first_name'];
				$json[$i]['avatar'] = (empty($row['avatar'])) ? $row['img'].'thumbnail.jpg' : $row['avatar'];
				$i++;
			}

			$this->view->vTitleCnt = count($json);
		}

		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}

	/**
	 * Список новых ЧК
	 */
	public function popupNewUsersAction()
	{
		$this->ajaxInit();

		$this->view->vTitle = $this->view->t('Новые члены Клуба');

		$json = array();
		$ModelDash = new Models_User_Dashboard();
		$data = $ModelDash->getNewUsers();
		if(!empty($data)) {
			$urlLang = ($this->getLang() == 'ru') ? '' : '/'.$this->getLang();
			$i = 0;
			foreach ($data as $row) {
				$json[$i]['uid'] = $row['uid'];
				$json[$i]['url'] = $urlLang. '/user/people/profile/view/'.$row['uid'];
				$json[$i]['title'] = $row['first_name'];
				$json[$i]['avatar'] = (empty($row['avatar'])) ? $row['img'].'thumbnail.jpg' : $row['avatar'];
				$i++;
			}

			$this->view->vTitleCnt = count($json);
		}

		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}

	/**
	 * Список Мои события
	 */
	public function popupIGoEventsAction()
	{
		$this->ajaxInit();
		$eventId = $this->_getParam('id', 0);

		$this->view->vTitle = $this->view->t('Мои события');

		$json = array();
		$ModelEvent = new Models_User_Event(Models_User_Model::getMyId());
		$data = $ModelEvent->getUserGo($eventId, null);
		if(!empty($data)) {
			$urlLang = ($this->getLang() == 'ru') ? '' : '/'.$this->getLang();
			$i = 0;
			foreach ($data as $row) {
				$json[$i]['uid'] = $row['uid'];
				$json[$i]['url'] = $urlLang. '/user/people/profile/view/'.$row['uid'];
				$json[$i]['title'] = $row['first_name'];
				$json[$i]['avatar'] = (empty($row['avatar'])) ? $row['img'].'thumbnail.jpg' : $row['avatar'];
				$i++;
			}

			$this->view->vTitleCnt = count($json);
		}

		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}

	/**
	 * Список Нравятся Ваши фотографии
	 */
	public function popupLoveMyPhotoAction()
	{
		$this->ajaxInit();

		$this->view->vTitle = $this->view->t('Нравятся Ваши фотографии');

		$json = array();
		$ModelDash = new Models_User_Dashboard();
		$data = $ModelDash->getLoveMyPhoto();
		if(!empty($data)) {
			$urlLang = ($this->getLang() == 'ru') ? '' : '/'.$this->getLang();
			$i = 0;
			foreach ($data as $row) {
				$json[$i]['uid'] = $row['uid'];
				$json[$i]['url'] = $urlLang. '/user/people/profile/view/'.$row['uid'];
				$json[$i]['title'] = $row['first_name'];
				$json[$i]['avatar'] = (empty($row['avatar'])) ? $row['img'].'thumbnail.jpg' : $row['avatar'];
				$i++;
			}

			$this->view->vTitleCnt = count($json);
		}

		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}

	/**
	 * Список Добавили Вас в избранное
	 */
	public function popupAddFavoritesAction()
	{
		$this->ajaxInit();

		$this->view->vTitle = $this->view->t('Добавили Вас в избранное');

		$json = array();
		$ModelDash = new Models_User_Dashboard();
		$data = $ModelDash->getAddFavorites();
		if(!empty($data)) {
			$urlLang = ($this->getLang() == 'ru') ? '' : '/'.$this->getLang();
			$i = 0;
			foreach ($data as $row) {
				$json[$i]['uid'] = $row['uid'];
				$json[$i]['url'] = $urlLang. '/user/people/profile/view/'.$row['uid'];
				$json[$i]['title'] = $row['first_name'];
				$json[$i]['avatar'] = (empty($row['avatar'])) ? $row['img'].'thumbnail.jpg' : $row['avatar'];
				$i++;
			}

			$this->view->vTitleCnt = count($json);
		}

		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}

	/**
	 * Список Именинники сегодня
	 */
	public function popupBirthdayAction()
	{
		$this->ajaxInit();

		$this->view->vTitle = $this->view->t('Именинники сегодня');

		$json = array();
		$ModelDash = new Models_User_Dashboard();
		$data = $ModelDash->getBirthday();
		if(!empty($data)) {
			$urlLang = ($this->getLang() == 'ru') ? '' : '/'.$this->getLang();
			$i = 0;
			foreach ($data as $row) {
				$json[$i]['uid'] = $row['uid'];
				$json[$i]['url'] = $urlLang. '/user/people/profile/view/'.$row['uid'];
				$json[$i]['title'] = $row['first_name'];
				$json[$i]['avatar'] = (empty($row['avatar'])) ? $row['img'].'thumbnail.jpg' : $row['avatar'];
				$i++;
			}

			$this->view->vTitleCnt = count($json);
		}

		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}
}
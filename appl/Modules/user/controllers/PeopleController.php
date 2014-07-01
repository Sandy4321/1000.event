<?php

/**
 * Профили пользователей
 */
class User_PeopleController extends Sas_Controller_Action_User
{
	/**
	 * Просмотр стороннего профиля
	 */
	public function profileAction()
	{
		$uId = $this->_getParam('view', 0);

		if (strlen($uId) != 8) {
			$this->_redirect('/user/search');
		}

		$ModelProfile = new Models_User_Profile();

		// запрашиваемый профиль
		$profilePartner  = $ModelProfile->getProfile($uId);
		$partnerId = $profilePartner['id'];

		$myId = Models_User_Model::getMyId();
		$myProfile = $ModelProfile->getProfile($myId);

		// Проверка на свой профиль
		if($uId == $myProfile['uid']) {
			$this->_redirect('/user/profile/');
		}

		################
		$MyProfile = new Models_Users($myId);
		$partnerProfile = new Models_Users($partnerId);
		################

		// Проверка если профиль был удалён
		if($profilePartner['current_status'] < 50) {
			$this->_redirect('/user/people/deleted');
		}

		$this->view->partnerProfile  = $profilePartner;
		$this->view->myProfile = $myProfile; // мой профиль

		### Пишем лог
		Models_Actions::add(19, Models_User_Model::getMyId(), $partnerId); // Открыт сторонний профиль для просмотра

		// Учитываем только посещения членов клуба
		// За исключением админа ID 4000
		if($myProfile['current_status'] >= 70 && $myProfile['id'] != 4000) {
			$ModelDash = new Models_User_Dashboard();
			$ModelDash->openProfileToDash($partnerId);
		}

		$ModelFavorites = new Models_User_Favorites();
		$this->view->vFavoritesStatus = $ModelFavorites->isFavorites($partnerId);

		$ModelBlackList = new Models_User_BlackList();
		$this->view->vBlackList = $ModelBlackList->isBlackList($myId, $partnerId);

		### Фотоальбом
		$ModelPhoto = new Models_User_Photo();
		$photoUser = $ModelPhoto->getPhoto($partnerId);
		$this->view->vImgAlbum = $photoUser;
		$this->view->vImgLike = $ModelPhoto->getIsLike($photoUser);
		$this->view->vImgPatch = $ModelPhoto->getViewPatch($profilePartner['userId'], $profilePartner['sex'], $profilePartner['birthday']);

		### Город
		$ModelCity = new Models_CountriesCities();
		$this->view->vCity = $ModelCity->getCity($profilePartner['city_id']);

		### Статус человека
		$ModelStatus = new Models_User_Status($partnerId);
		$st = $ModelStatus->getMyStatus();
		$this->view->vStatus = $st;
		$ModelStatus = new Models_User_Status($myId);
		if(!is_null($st['id'])) {
			$this->view->vStatusLike = $ModelStatus->isLikeUser($st['id']);
		}

		### Хобби
		$ModelHobby = new Models_User_Hobby();
		$this->view->vHobby = $ModelHobby->getHobbyUser($partnerId);
		$this->view->myHobby = $ModelHobby->getHobbyUser($myId);
		$this->view->vHobbyEqual = array_intersect($this->view->myHobby, $this->view->vHobby);

		### Цели
		$ModelTarget = new Models_User_Target();
		$this->view->vTarget = $ModelTarget->getTargetUser($partnerId);
		$this->view->myTarget = $ModelTarget->getTargetUser($myId);
		$this->view->vTargetEqual = array_intersect($this->view->myTarget, $this->view->vTarget);

		### Проф интересы
		$ModelProf = new Models_User_ProfInteres();
		$this->view->vProf = $ModelProf->getProfUser($partnerId);
		$this->view->myProf = $ModelProf->getProfUser($myId);
		$this->view->vProfEqual = array_intersect($this->view->myProf, $this->view->vProf);

		### Переписка пользователей
		// Модель сообщений
		$ModelMsg = new Models_User_Msg($myId);

		// Вывод всей переписки с партнёром
		$talk = $ModelMsg->getTalk($MyProfile, $partnerProfile);
		$this->view->assign('vTalk', $talk);
		$this->view->assign('vTalkFormScrollNo', true);
		$this->view->assign('isRecordNewMsg', $ModelMsg->isRecordNewMsg($MyProfile,  $partnerProfile, $talk));
		#$msg = $ModelMsg->getTalkFull($myId, $partnerId);
		#$this->view->vMsg = $msg;

		// Мероприятия на которые идет пользователь
		$ModelEvent = new Models_User_Event();
		$events1 = $ModelEvent->getGoUser($partnerId, true, 3); // максимум 3 будущих мероприятия на которые пользователь подписался сам
		$events2 = $ModelEvent->getOtl(2); // максимум 2 ближайших мероприятия OnTheList
		$events3 = $ModelEvent->getPartnerCreate($myId, 1); // 1 ближайшее мероприятие которое создал ПРОСМАТРИВАЮЩИЙ пользователь (если есть)
		$events = array_merge($events1, $events2, $events3);
		$isEventId = array(); // id найденых ранее мероприятия
		$eventsUni = array(); // массив уникальных мероприятий

		// Создаем массив уникальных мероприятий (выкидывая дублирующие записи)
		foreach($events as $item) {
			if(!array_key_exists($item['event_id'], $isEventId)) {
				$eventsUni[] = $item;
				$isEventId[$item['event_id']] = $item['event_id'];
			}
		}
		$this->view->vEvents = $eventsUni;
		$this->view->vEventsInvite = $ModelEvent->getInvite($myId, $partnerId); // Мероприятия на которые я пригласил партнера

		// Посты
		$ModelPosts = new Models_User_Posts($partnerId);
		$posts = $ModelPosts->getPostsAll(3, 0, false, true);
		$this->view->vPosts = $posts;
		$this->view->vILikePost = $ModelPosts->getILikePost($posts, $myId);

		// Обмен телефонами
		$My = new Models_Users($myId);
		$Partner = new Models_Users($uId);
		// Смотрим, есть ли (и каков его статус) предложение от меня у партнера во входящем.
		$ModelExchange = new Models_User_ExchangePhone($Partner, $My);
		$this->view->assign('vExchangePhoneBox',    $ModelExchange->getBox());
		$this->view->assign('vExchangePhoneStatus', $ModelExchange->getStatus());
	}

	/**
	 * Страница удалённых пользователей
	 */
	public function deletedAction() {

	}

	/**
	 * Лайк по фотографии из альбома
	 */
	public function photoAlbumLikeAction() {
		$this->ajaxInit();
		$json = array();

		$myID = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myID);
		$myProfile = $ModelProfile->getProfile($myID);

		$photoId = (int) $this->_getParam('data_id', 0);

		if($photoId > 0 && $myProfile['current_status'] >= 70) {
			$ModelPhoto = new Models_User_Photo($myID);
			$ModelPhoto->plusLike($photoId);

			$json['msg'] = $this->view->t('Вам нравится');
		} else {
			$json['error']['code'] = 5001;
			$json['error']['msg'] = $this->view->t('Ошибка записи лайка фотографии');
		}

		$this->getJson($json);
	}

	/**
	 * Лайк по текущему статусу пользователя
	 */
	public function statusLikeAction() {
		$this->ajaxInit();
		$json = array();

		$myID = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myID);
		$myProfile = $ModelProfile->getProfile($myID);

		$statusId = (int) $this->_getParam('data_id', 0);

		if($statusId > 0 && $myProfile['current_status'] >= 70) {
			$ModelStatus = new Models_User_Status($myID);
			$ModelStatus->likePlus($statusId);

			$json['msg'] = $this->view->t('Вам нравится');
		} else {
			$json['error']['code'] = 5001;
			$json['error']['msg'] = $this->view->t('Ошибка записи лайка статуса');
		}

		$this->getJson($json);
	}

	public function popupReviewAction()
	{
		$this->ajaxInit();
		$partnerId = $this->_getParam('id', 0);

		$this->view->vPartnerId = $partnerId;

		$json = array();
		/*$ModelPosts = new Models_User_Posts();
		$data = $ModelPosts->getLikeComment($commentId, null);

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
		}*/

		$this->view->vData = $json;

		$this->renderScript('/popup/popup-review.phtml');
	}
}
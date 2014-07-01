<?php
class User_EventController extends Sas_Controller_Action_User
{
	/**
	 * Список текущих мероприятий
	 */
	public function indexAction()
	{
		$myID = Models_User_Model::getMyId();

		$ModelProfile = new Models_User_Profile($myID);
		$this->view->vMyProfile = $ModelProfile->getProfile();

		$ModelEvent = new Models_User_Event();

		$categoryId = (is_numeric($this->_getParam('category')) || $this->_getParam('category') == 'otl') ? $this->_getParam('category') : null;

		if(!is_null($this->_getParam('search'))) {
			$searchText = htmlspecialchars(mb_substr(strip_tags(trim($this->_getParam('search'))), 0, 100, 'UTF-8'));
		}
		$searchText = (!empty($searchText) && mb_strlen($searchText) >= 3) ? $searchText : null;

		$this->view->vEventsNoStart = $ModelEvent->getEventsNoStart(false, $categoryId, $searchText);
		$this->view->vEventsCat = $ModelEvent->getCat();
		$this->view->vEventCatSelect = $categoryId;
		$this->view->vIgoEvent = $ModelEvent->getIGoEvent($myID);
		$this->view->vICheckIn = $ModelEvent->getICheckIn($myID);
	}

	/**
	 * Полная информация о мероприятии
	 */
	public function viewAction()
	{
		$myID = Models_User_Model::getMyId();
		$ModelEvent = new Models_User_Event($myID);

		$ModelProfile = new Models_User_Profile($myID);
		$myProfile = $ModelProfile->getProfile();
		$this->view->vMyProfile = $myProfile;
		$this->view->myProfile = $myProfile;

		$event_id = (int) $this->_getParam('id');
		if(is_int($event_id) && $event_id > 0) {

			// Добавление комментария
			if($this->_getParam('comment_text') && $myProfile['current_status'] >= 70) {
				$commentText = htmlspecialchars(mb_substr(strip_tags(trim($this->_getParam('comment_text'))), 0, 200, 'UTF-8'));
				if(!empty($commentText)) {
					// Записывам комментарий
					$ModelEvent->addCommentText($myID, $event_id, $commentText);
				}
			}

			$event = $ModelEvent->getEventFullInfo($event_id);
			// Если мероприятие закончилось - перекидываем на список мероприятий
			if(!empty($event) && $event['date_close'] < CURRENT_DATETIME) {
				$this->_redirect('user/event');
				return;
			}

			if(!empty($event)) {
				$this->view->vEvent = $event;
				$this->view->vEventsCat = $ModelEvent->getCat(); // Категории
				$this->view->vICheckIn = $ModelEvent->getICheckIn($myID);
				$this->view->vIgoEvent = $ModelEvent->getIGoEvent($myID);

				// Получаем комментарии к эвенту
				if($event['cnt_comment'] > 0) {
					$this->view->vEventComment = $ModelEvent->getCommentAll($event_id);
					$this->view->vILikeComment = $ModelEvent->getILikeComment($event_id);
				}

				// Получаем лайки к эвенту
				if($event['cnt_like'] > 0) {
					$this->view->vILikeEvent = $ModelEvent->getILikeEvent($event_id);
				}

			} else {
				// Не найдено
				$this->_redirect('user/event');
			}
		} else {
			$this->_redirect('user/event');
		}
	}

	/**
	 * Добавление мероприятие в афишу = Создать новое мероприятие
	 */
	public function createAction()
	{
		$error = false;
		$myID = Models_User_Model::getMyId();
		$ModelEvent = new Models_User_Event($myID);

		// Категории мероприятий
		$this->view->vCat = $ModelEvent->getCat();

		$cntEventDay = $ModelEvent->isEventSaveToDay($myID);

		if($cntEventDay > 0) {
			$error['cntEventDay'] = $cntEventDay;
		}

		if($this->_request->isPost() && $cntEventDay == 0) {
			$post = $this->_request->getPost();

			// Обработка даты начала
			$post['date_start'] = sprintf("%04d-%02d-%02d %02d:00:00", $post['date_start_year'], $post['date_start_month'], $post['date_start_day'], $post['date_start_hour']);

			// Обработка даты окончания
			$post['date_close'] = sprintf("%04d-%02d-%02d %02d:00:00", $post['date_close_year'], $post['date_close_month'], $post['date_close_day'], $post['date_close_hour']);

			// Обработка цены
			if ((int)$post['price'] != 0) {
				$post['price'] = (int)$post['price'];

				// Валюта, так как есть цена, по умолчанию валюта РУБЛИ
				if($post['money_type'] != 'rub' && $post['money_type'] != 'usd' && $post['money_type'] != 'karat') {
					$post['money_type'] = 'rub';
				}
			} else {
				$post['price'] = null;
				$post['money_type'] = null;
			}

			// Обработка анонса
			$post['anons'] = htmlspecialchars(strip_tags(trim($post['anons'])));
			if(!empty($post['anons'])) {
				$post['anons'] = str_replace("\n", "<br>", $post['anons']);
			}

			// Обработка основного текста
			$post['full_text'] = htmlspecialchars(strip_tags(trim($post['full_text'])));
			if(!empty($post['full_text'])) {
				$post['full_text'] = str_replace("\n", "<br>", $post['full_text']);
			}

			// Обработка места проведения
			$post['point_name'] = htmlspecialchars(strip_tags(trim($post['point_name'])));
			if(empty($post['point_name'])) {
				$post['point_name'] = null;
			}

			$ModelProfile = new Models_User_Profile($myID);
			$this->view->vProfile = $ModelProfile->getProfile($myID);
			$this->view->vMyProfile = $this->view->vProfile;

			// Отмечаем что есть фото
			$post['intro_img'] = ($_FILES['photo_event']['error'] == 0) ? 1 : null;

			$eventId = $ModelEvent->saveEvent($myID, $post);
			$post['id'] = $eventId;

			if($_FILES['photo_event']['error'] == 0 && is_numeric($eventId)) {
				#Sas_Debug::dump($_FILES['photo_event']);

				$photoPathView = '/img/user_event/'.$myID.'/'.$eventId.'/';

				#$photoPathSave = $_SERVER['DOCUMENT_ROOT'].$photoPathView;
				#Sas_Debug::dump($photoPathSave, 'Сохраняем фото по адресу');
				#Sas_Debug::dump($photoPathView, 'Показываем фото с адреса');

				$Image = new Sas_Image();
				$Image->configSaveOriginal('intro_original', 'jpg');
				$Image->configSaveOptimal(640, 480, 'intro', 'jpg');
				$Image->setImgDir($photoPathView, true);
				$checkSavePhoto = $Image->save($_FILES['photo_event']['tmp_name']);
				if($checkSavePhoto != false) {
					#Sas_Debug::dump($Image->getFullPath(), 'IMG getFullPath');
					#Sas_Debug::dump($Image->getPathOptimalName(), 'IMG getPathOptimalName');
					$post['intro_img'] = 1;
					$post['intro'] = $Image->getPathOptimalName();
				} else {
					#Sas_Debug::dump($Image->getError(), 'ERROR SAVE PHOTO');
				}
			}

			$post['cat_name'] = $ModelEvent->getCatName($post['cat_id']);
			$this->view->vEvent = $post;
			$this->renderScript('event/create-preview.phtml');
		}

		// Вывод ошибок
		$this->view->vError = $error;
	}

	/**
	 * Редактировать свои мероприятия
	 */
	public function editAction()
	{
		$myID = Models_User_Model::getMyId();
		$ModelEvent = new Models_User_Event($myID);

		// Сохранение после редактирования
		if(is_numeric($this->_getParam('event_id')) && $this->_getParam('edit') == 'save' && $this->_request->isPost())
		{
			$post = $this->_request->getPost();
			$post['event_id'] = (int)$post['event_id'];

			// Обработка даты начала
			$post['date_start'] = sprintf("%04d-%02d-%02d %02d:00:00", $post['date_start_year'], $post['date_start_month'], $post['date_start_day'], $post['date_start_hour']);

			// Обработка даты окончания
			$post['date_close'] = sprintf("%04d-%02d-%02d %02d:00:00", $post['date_close_year'], $post['date_close_month'], $post['date_close_day'], $post['date_close_hour']);

			// Обработка цены
			if ((int)$post['price'] != 0) {
				$post['price'] = (int)$post['price'];

				// Валюта, так как есть цена, по умолчанию валюта РУБЛИ
				if($post['money_type'] != 'rub' && $post['money_type'] != 'usd' && $post['money_type'] != 'karat') {
					$post['money_type'] = 'rub';
				}
			} else {
				$post['price'] = null;
				$post['money_type'] = null;
			}

			// Обработка анонса
			$post['anons'] = htmlspecialchars(strip_tags(trim($post['anons'])));
			if(!empty($post['anons'])) {
				$post['anons'] = str_replace("\n", "<br>", $post['anons']);
			}

			// Обработка основного текста
			$post['full_text'] = htmlspecialchars(strip_tags(trim($post['full_text'])));
			if(!empty($post['full_text'])) {
				$post['full_text'] = str_replace("\n", "<br>", $post['full_text']);
			}

			// Обработка места проведения
			$post['point_name'] = htmlspecialchars(strip_tags(trim($post['point_name'])));
			if(empty($post['point_name'])) {
				$post['point_name'] = null;
			}

			// Удаляем фото
			if($post['delete_photo'] == 1) {
				// Отмечаем что есть фото
				$ModelEvent->deletePhotoIntro($myID, $post['event_id']);
				$post['intro_img'] = 0;
			}

			// Отмечаем что есть фото
			if ($_FILES['photo_event']['error'] == 0) {
				$post['intro_img'] = 1;
			}

			if($ModelEvent->saveEvent($myID, $post) == true) {
				$this->view->vSaveEdit = $post['event_id'];
			}

			if($_FILES['photo_event']['error'] == 0) {
				#Sas_Debug::dump($_FILES['photo_event']);

				$photoPathView = '/img/user_event/'.$myID.'/'.$post['event_id'].'/';
				//$photoPathSave = $_SERVER['DOCUMENT_ROOT'].$photoPathView;
				//Sas_Debug::dump($photoPathSave, 'Сохраняем фото по адресу');
				#Sas_Debug::dump($photoPathView, 'Показываем фото с адреса');

				$Image = new Sas_Image();
				$Image->configSaveOriginal('intro_original', 'jpg');
				$Image->configSaveOptimal(640, 480, 'intro', 'jpg');
				$Image->setImgDir($photoPathView, true);
				$checkSavePhoto = $Image->save($_FILES['photo_event']['tmp_name']);
				if($checkSavePhoto != false) {
					#Sas_Debug::dump($Image->getFullPath(), 'IMG getFullPath');
					#Sas_Debug::dump($Image->getPathOptimalName(), 'IMG getPathOptimalName');
					$post['intro_img'] = 1;
					$post['intro'] = $Image->getPathOptimalName();
				} else {
					#Sas_Debug::dump($Image->getError(), 'ERROR SAVE PHOTO');
				}
			}

			#Sas_Debug::dump($post);
			$this->_redirect('user/event/my');
		}

		if(is_numeric($this->_getParam('event_id')) && $this->_getParam('edit') == 'go') { // Выводим для редактирования
			$eventId = (int)$this->_getParam('event_id');
			$this->view->vCat = $ModelEvent->getCat();
			$this->view->vEvent = $ModelEvent->getEvent($eventId);
			$this->renderScript('event/edit_form.phtml');
		} else { // Выводи список для выбора
			$this->view->vEventsAll = $ModelEvent->getMyEvents($myID);
		}
	}

	/**
	 * Управление моими мероприятиями
	 */
	public function myAction()
	{
		$ModelEvent = new Models_User_Event();
		$this->view->vEventsMy = $ModelEvent->getMyEvents(Models_User_Model::getMyId(), 'date_start DESC');
		$this->view->vEventsCat = $ModelEvent->getCat();
	}

	/**
	 * Мероприятия на которые я пойду
	 */
	public function igoAction()
	{
		$ModelEvent = new Models_User_Event(Models_User_Model::getMyId());
		$this->view->vEventsCat = $ModelEvent->getCat();
		$this->view->vEvents = $ModelEvent->getEventsNoStart(true);
	}

	/**
	 * Возвращет всех кто уже пришел на мероприятие
	 */
	public function popupCheckInAction()
	{
		$this->ajaxInit();

		$this->view->vTitle = $this->view->t('Участники');

		if(is_numeric($this->_getParam('id'))) {
			$eventId = (int) $this->_getParam('id');
			$myID = Models_User_Model::getMyId();
			$ModelEvent = new Models_User_Event($myID);

			$data = $ModelEvent->getUserCheckIn($eventId, null);
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
		}
		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}

	/**
	 * Возвращет всех кто пойдет на мероприятие
	 */
	public function popupUserGoAction()
	{
		$this->ajaxInit();

		$this->view->vTitle = $this->view->t('Участники');

		if(is_numeric($this->_getParam('id'))) {
			$eventId = (int) $this->_getParam('id');
			$myID = Models_User_Model::getMyId();
			$ModelEvent = new Models_User_Event($myID);

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
		}
		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}

	/**
	 * Лайк мероприяти
	 */
	public function eventLikeAction() {
		$this->ajaxInit();
		$json = array();

		$myID = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myID);
		$myProfile = $ModelProfile->getProfile($myID);

		$eventId = (int) $this->_getParam('data_id', 0);

		if($eventId > 0 && $myProfile['current_status'] >= 70) {
			$ModelEvent = new Models_User_Event($myID);
			$ModelEvent->likeEvent($eventId);

			$json['msg'] = $this->view->t('Вам нравится');
		} else {
			$json['error']['code'] = 5001;
			$json['error']['msg'] = $this->view->t('Когда Вы лайкнули мероприятие произошла ошибка. Пожалуйста обратитесь к администратору.');
		}

		$this->getJson($json);
	}

	/**
	 * Лайк комментария в мероприятии
	 */
	public function commentLikeAction()
	{
		$this->ajaxInit();
		$json = array();

		$myID = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myID);
		$myProfile = $ModelProfile->getProfile($myID);

		$commentId = (int) $this->_getParam('data_id', 0);

		if($commentId > 0 && $myProfile['current_status'] >= 70) {
			$ModelEvent = new Models_User_Event($myID);
			$ModelEvent->likeComment($commentId);

			$json['msg'] = $this->view->t('Вам нравится');
		} else {
			$json['error']['code'] = 5001;
			$json['error']['msg'] = $this->view->t('Когда Вы лайкнули комментарий мероприятия произошла ошибка. Пожалуйста обратитесь к администратору.');
		}

		$this->getJson($json);
	}

	/**
	 * Удаляет мероприятие.
	 *
	 * Ожидет параметры:
	 * int event_id - id мероприятия
	 *
	 * Возвращает:
	 * json.msg Текст
	 *
	 * json.error.code 5001|5002
	 * json.error.msg Описание ошибки
	 */
	public function deleteAction()
	{
		$this->ajaxInit();
		$json = array();

		if(is_numeric($this->_getParam('event_id'))) {
			$eventId = (int) $this->_getParam('event_id');
			$myID = Models_User_Model::getMyId();
			$ModelEvent = new Models_User_Event($myID);

			// Проверяем что это мероприятие моё (я его создал)
			if($ModelEvent->isMyEvent($myID, $eventId)) {
				$ModelEvent->deleteEvent($eventId);
				$json['msg'] = $this->view->t('Мероприятие удалено.');
			} else {
				$json['error']['code'] = 5002;
				$json['error']['msg'] = $this->view->t('Вы не можете удалить чужое мероприятие');
			}
		} else {
			$json['error']['code'] = 5001;
			$json['error']['msg'] = $this->view->t('При удалении мероприятия произошла ошибка.');
		}

		$this->getJson($json);
	}

	/**
	 * Я иду (или нет) на мероприятие.
	 *
	 * Ожидет параметры:
	 * int event_id - id мероприятия
	 * string go - yes|no
	 *
	 * Возвращает:
	 * json.msg yes|no - Успех
	 *
	 * json.error.code 5001
	 * json.error.msg Описание ошибки
	 */
	public function setIGoAction()
	{
		$this->ajaxInit();
		$json = array();

		if(is_numeric($this->_getParam('event_id')) && $this->_getParam('go')) {
			$eventId = (int) $this->_getParam('event_id');
			$myID = Models_User_Model::getMyId();
			$ModelEvent = new Models_User_Event($myID);

			// Я пойду
			if($this->_getParam('go') == 'yes') {
				$ModelEvent->iGoEvent($myID, $eventId, 'yes');
				$json['msg'] = 'yes';
			} else { // я НЕ пойду
				$ModelEvent->iGoEvent($myID, $eventId, 'no');
				$json['msg'] = 'no';
			}
		} else {
			$json['error']['code'] = 5001;
			$json['error']['msg'] = $this->view->t('Ошибка при установке отметки iGo');
		}

		$this->getJson($json);
	}

	/**
	 * Отметиться или снять отметку с мероприятия что я тут.
	 *
	 * Ожидет параметры:
	 * int event_id - id мероприятия
	 * string checkin - yes|no
	 *
	 * Возвращает:
	 * json.msg yes|no - Успех
	 *
	 * json.error.code 5001
	 * json.error.msg Описание ошибки
	 */
	public function setCheckInAction()
	{
		$this->ajaxInit();
		$json = array();

		if(is_numeric($this->_getParam('event_id')) && $this->_getParam('checkin')) {
			$eventId = (int) $this->_getParam('event_id');
			$myID = Models_User_Model::getMyId();
			$ModelEvent = new Models_User_Event($myID);

			// Я пойду
			if($this->_getParam('checkin') == 'yes') {
				$ModelEvent->iCheckInEvent($myID, $eventId, 'yes');
				$json['msg'] = 'yes';
			} else { // я НЕ пойду
				$ModelEvent->iCheckInEvent($myID, $eventId, 'no');
				$json['msg'] = 'no';
			}
		} else {
			$json['error']['code'] = 5001;
			$json['error']['msg'] = $this->view->t('Ошибка при установке отметки CheckIn');
		}

		$this->getJson($json);
	}
}
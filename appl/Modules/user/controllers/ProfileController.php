<?php

/**
 * Профиль
 */
class User_ProfileController extends Sas_Controller_Action_User
{
	/**
	 * Визард для призраков
	 */
	public function wizardAction()
	{
		$myId = Models_User_Model::getMyId();
		try {
			$MyProfile = new Models_Users($myId);

			if($MyProfile->getCurrentStatus() >= 70) {
				$this->_redirect('user/profile');
			}

			$this->view->assign('myProfile', $MyProfile->getProfileToArray());

			// Проверим наличие всех данных для того, чтобы можно было отправить анкету
			if(($MyProfile->getCurrentStatus() == 51) ||
				(
					!is_null($MyProfile->getFirstName()) &&
					!is_null($MyProfile->getLastName()) &&
					!is_null($MyProfile->getBirthday()) &&
					(
						(!is_null($MyProfile->getCompany()) && !is_null($MyProfile->getPositionJob())) ||
						!is_null($MyProfile->getEducation())
					)
					&&
					(
						(!is_null($MyProfile->getLinkFb()) || !is_null($MyProfile->getLinkVk()) || !is_null($MyProfile->getLinkLn()))
						|| file_exists($_SERVER['DOCUMENT_ROOT'].$MyProfile->getImgPath().'fileNameResume.txt')
					) &&
					file_exists($_SERVER['DOCUMENT_ROOT'].$MyProfile->getAvatar())
				)
			) {
				// все данные есть, отмечаем что анкета отправлена
				if($MyProfile->getCurrentStatus() != 51) $MyProfile->setCurrentStatus(51)->save();

				// Врубаем рендер промежуточной страницы ожидания решения
				$this->renderScript('profile/wizard51.phtml');
			}
		} catch (Sas_Models_Exception $e) {
			$this->view->assign('vError', $e->getMessage());
		}
	}

	/**
	 * Мой профиль для редактирования
	 */
	public function indexAction()
	{
		// Редирект для призраков для заполнения анкеты
		if(Models_User_Model::getMyCurrentStatus() < 70) {$this->_redirect('user/profile/wizard');}

		$myId = Models_User_Model::getMyId();
		Models_Actions::add(20, $myId); // Открыт свой профиль

		// Модели данных
		$ModelProfile     = new Models_User_Profile($myId); // Профиль
		$ModelHobby       = new Models_User_Hobby();        // Хобби
		$ModelTarget      = new Models_User_Target();       // Жизненные цели
		$ModelProfInteres = new Models_User_ProfInteres();  // Профессиональные интересы
		$ModelPhoto       = new Models_User_Photo();        // Фотоальбом
		$ModelPosts       = new Models_User_Posts($myId);   // Посты

		$myProfile = $ModelProfile->getProfile();
		$this->view->vProfile = $myProfile;
		$this->view->myProfile = $myProfile;

		### Статус человека
		$ModelStatus = new Models_User_Status($myId);
		$this->view->vStatus = $ModelStatus->getMyStatus();

		### Хобби
		// Хобби
		$this->view->vHobbyList = $ModelHobby->getHobbyList();
		$this->view->vHobbyUser = $ModelHobby->getHobbyUser($myId);

		// Цели (жизни)
		$this->view->vTargetList = $ModelTarget->getTargetList();
		$this->view->vTargetUser = $ModelTarget->getTargetUser($myId);

		// Проф интересы
		$this->view->vProfList = $ModelProfInteres->getProfList();
		$this->view->vProfUser = $ModelProfInteres->getProfUser($myId);

		// Фотоальбом
		$this->view->vImgAlbum = $ModelPhoto->getMyPhoto();
		$this->view->vImgAlbumPatch = $ModelPhoto->getViewPatch($myId, $myProfile['sex'], $myProfile['birthday']);

		// Посты
		$posts = $ModelPosts->getPostsAll(20, 0, false, true); // $limit = 20, $page = 0, $favorite = false, $my = false
		$this->view->vPosts = $posts;
		$this->view->vILikePost = $ModelPosts->getILikePost($posts);
	}

	/**
	 * Настройки моего профиля
	 */
	public function settingsAction()
	{
		// Редирект для призраков для заполнения анкеты
		if(Models_User_Model::getMyCurrentStatus() < 70) {$this->_redirect('user/profile/wizard');}

		$myId = Models_User_Model::getMyId();

		Models_Actions::add(78, $myId); // Открыты настройки своего профиля

		// Модели данных
		$ModelProfile = new Models_User_Profile($myId); // Профиль

		$myProfile = $ModelProfile->getProfile();
		$this->view->myProfile = $myProfile;
	}

	/**
	 * Пригласить друзей
	 */
	public function inviteAction()
	{
		$ModelProfile = new Models_User_Profile();
		$myProfile = $ModelProfile->getProfile();

		// Редирект для призраков для заполнения анкеты
		if($myProfile['current_status'] < 70) {$this->_redirect('user/profile/wizard');}

		Models_Actions::add(79, $myProfile['id']); // Открыта страница Пригласить друзей

		$this->view->myProfile = $myProfile;

		$ModelFriend = new Models_User_Friend();
		$promoKey = $ModelFriend->getMyPromoKey();

		// Приглащенные друзья
		$this->view->vFriend = $ModelFriend->getMyFriend();

		$theme = $this->t('OnTheList.ru - приглашение в Клуб');
		$this->view->vTheme = $theme;

		$text  = $this->t('Здравствуйте!') . "<br/><br/>";
		$text .= sprintf($this->t('%s приглашает Вас в Клуб OnTheList www.onthelist.ru'), $myProfile['first_name'] . ' ' . $myProfile['last_name']) . "<br/><br/>";
		$text .= $this->t('Чтобы зарегистрироваться в Клубе пройдите по ссылке:') . "<br/>";
		$text .= 'http://' . $_SERVER['HTTP_HOST'] . '/user/register/?promo-key=' . $promoKey ."<br/><br/>";
		$text .= $this->t('Добро пожаловать в Клуб!') . "<br/><br/>";
		$text .= $this->t('После регистрации Вы сразу получите подарок от нашего партнёра, компании Uber - 2 бесплатные поездки на Мерседесе S-класса с лимитом 1 000 руб. каждая.') . "<br/><br/>";
		$text .= $this->t('С уважением,') . "<br/>";
		$text .= $this->t('Администрация Клуба OnTheList') . "<br/><br/>";
		$text .= $this->t('Внимание: Это письмо не требует ответа.') . "<br/>";
		$text .= $this->t('Почтовый ящик, с которого вы получили это письмо, не является контактным.');

		$this->view->vTextMail = $text;

		if ($this->_getParam('email') == 1 || $this->_getParam('email') == 2) {
			$validate = new Zend_Validate_EmailAddress();
			if ($validate->isValid($this->_getParam('emailTo'))) {
				try {
					$mail = new Zend_Mail('UTF-8');
					$mail->setSubject($theme);
					$text = str_replace('<br/>', "\n", $text);
					$mail->setBodyText($text);
					if ($this->_getParam('email') == 1) {
						$mail->setFrom($myProfile['email'], $myProfile['first_name'] . ' ' . $myProfile['last_name']);
					} else {
						$mail->setFrom('welcome@onthelist.ru', 'OnTheList Club');
					}

					$mail->addTo($this->_getParam('emailTo'));
					$mail->send();
					$this->view->vSendOk = true;
					Models_Actions::add(80, $myProfile['id']); // Отправлено приглашение другу/подруге
				} catch (Zend_Mail_Exception $e) {
					//echo $e->getMessage();
				}
			}
		}

	}

	/**
	 * Баланс пользователя
	 */
	public function balanceAction()
	{
		$myId = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myId);
		$myProfile = $ModelProfile->getProfile();

		// Редирект для призраков для заполнения анкеты
		if($myProfile['current_status'] < 70) {$this->_redirect('user/profile/wizard');}

		Models_Actions::add(81, $myProfile['id']); // Открыта страница баланса

		$this->view->myProfile = $myProfile;

		// Пополнение баланса
		$data['male'][]   = array(50, 500); // 500
		$data['female'][] = array(50, 500);

		$data['male'][]   = array(100, 1000);
		$data['female'][] = array(100, 1000);

		$data['male'][]   = array(300, 3000); // 3000
		$data['female'][] = array(300, 2400);

		/*$data['male'][]   = array(1000, 9000);
		$data['female'][] = array(1000, 7200);

		$data['male'][]   = array(3000, 24000);
		$data['female'][] = array(3000, 19200);*/

		$this->view->vPriceKarat = ($myProfile['sex'] == 'male') ? $data['male'] : $data['female'];

		$dataCard[] = array(1, 1000);
		$dataCard[] = array(2, 1900);
		$dataCard[] = array(3, 2700);
		$dataCard[] = array(4, 3400);
		$dataCard[] = array(5, 4000);

		$this->view->vPriceCard = $dataCard;

		// История платежей
		$Model = new Models_User_Balance();
		$this->view->vPayHistory = $Model->getHistory();
	}

	/**
	 * Страница отображения состояния оплаты счета
	 */
	public function payResultAction()
	{
		$result = $this->_getParam('result');
		if($result != 'success' && $result != 'error') {
			$this->view->assign('vResult', false);
		} else {
			$this->view->assign('vResult', $result);
		}
	}

	/**
	 * Мои фавориты
	 */
	public function favoritesAction()
	{
		// Редирект для призраков для заполнения анкеты
		if(Models_User_Model::getMyCurrentStatus() < 70) {$this->_redirect('user/profile/wizard');}

		$Model = new Models_User_Favorites();

		// Удаление из списка фаворитов
		/*if($this->_getParam('delete', null) == 'yes' && is_numeric($this->_getParam('id', null))) {
			$Model->delUser($this->_getParam('id'));

			// Получаем профиль получателя уведомления
			$ModelProfile = new Models_User_Profile();
			$profile = $ModelProfile->getProfile($this->_getParam('id'));

			// Проверка если профиль был удалён
			if($profile['current_status'] <= 50) {
				$this->_redirect('/user/people/deleted');
			}

			// Уведомляем о удалении из фаворитов
			$ModelSendMsg = new Models_TemplatesMessage($profile, 'favorite_del', 'msg_favorite');
			$ModelSendMsg->addDataReplace('my_name', Models_User_Model::getMyFirstName());
			try {
				$ModelSendMsg->send();
			} catch (Exception $e) {
				// TODO: записать лог
			}

			$this->view->vYesDelete = 1;
		}*/

		// Добавление в список фаворитов
		/*if($this->_getParam('add', null) == 'yes' && is_numeric($this->_getParam('id', null))) {
			try {
				$status = $Model->addUser($this->_getParam('id'));
				//$insertId = $Model->lastInsertId();

				// Получаем профиль получателя уведомления
				$ModelProfile = new Models_User_Profile();
				$profile = $ModelProfile->getProfile($this->_getParam('id'));

				// Уведомляем о добавлении
				$ModelSendMsg = new Models_TemplatesMessage($profile, 'favorite_add', 'msg_favorite');
				$ModelSendMsg->addDataReplace('my_name', Models_User_Model::getMyFirstName());
				try {
					$ModelSendMsg->send();
				} catch (Exception $e) {
					// TODO: записать лог
				}

				// Пишем запись в Dashboard
				$ModelDash = new Models_User_Dashboard(); // Модель Dashboard
				// Шлем в dash получателю
				if ($status == 'send') {
					$msgId = (Models_User_Model::getMySex() == 'female') ? 23 : 24;
				} else {
					$msgId = (Models_User_Model::getMySex() == 'female') ? 25 : 26;
				}

				$ModelDash->sendToDash($profile['id'], $msgId, null, null);

			} catch (Zend_Db_Exception $e) {
				// Пользователь уже в списке
				null;
			}

			$this->view->vYesAdd = 1;
		}*/

		// Вывод текущего списка фаворитов
		$this->view->vData = $Model->getFavoritesAllInfo();
	}

	/**
	 * Добавление в избранное.
	 *
	 * В качестве возврата доступен json:
	 * json.user_id
	 * json.uid
	 * json.first_name
	 * json.sex
	 * json.msg
	 *
	 * json.error
	 *
	 * json.notice.msg
	 * json.notice.error
	 * json.notice.exception_msg
	 *
	 */
	public function addFavoritesAction() {
		$this->ajaxInit();
		$myId = Models_User_Model::getMyId();

		if($myId > 0 && is_numeric($this->_getParam('user_id', null)))
		{
			try {
				// Профиль получателя уведомления (моего партнера которого я добавил в избранное)
				$ModelProfile = new Models_User_Profile($this->_getParam('user_id'));
				$partnerProfile = $ModelProfile->getProfile($this->_getParam('user_id'));

				$json['user_id']    = $partnerProfile['id'];
				$json['uid']        = $partnerProfile['uid'];
				$json['first_name'] = $partnerProfile['first_name'];
				$json['sex']        = $partnerProfile['sex'];

				$ModelFavorites = new Models_User_Favorites($myId);
				$status = $ModelFavorites->addUser($partnerProfile['id']);

				if($status == 'send' || $status == 'ok') {
					$json['msg'] = $this->t('Пользователь добавлен в избранное.');
				} else {
					$json['error'] = $this->t('Ошибка добавления в избранное.');
				}

				// Уведомляем о добавлении
				if($partnerProfile['current_status'] >= 70) {
					$ModelSendMsg = new Models_TemplatesMessage($partnerProfile, 'favorite_add', 'msg_favorite');
					$ModelSendMsg->addDataReplace('my_name', Models_User_Model::getMyFirstName());
					try {
						$ModelSendMsg->send();

						$json['notice']['msg'] = $this->t('Уведомление о добавлении в избранное отправлено пользователю.');
					} catch (Exception $e) {
						// TODO: записать лог
						$json['notice']['error'] = $this->t('При отправке уведомления пользователю о добавлении в избранное произошла ошибка.');
						$json['notice']['exception_msg'] = $e->getMessage();
					}
				}

				// Пишем запись в Dashboard
				$ModelDash = new Models_User_Dashboard(); // Модель Dashboard

				// Шлем в dash получателю
				if ($status == 'send') {
					$msgId = (Models_User_Model::getMySex() == 'female') ? 23 : 24;
				} else {
					$msgId = (Models_User_Model::getMySex() == 'female') ? 25 : 26;
				}

				$ModelDash->sendToDash($partnerProfile['id'], $msgId, null, null);

			} catch (Zend_Db_Exception $e) {
				// Пользователь уже в списке
				$json['msg'] = $this->t('Пользователь уже был добавлен в Избранные контакты ранее.');
				null;
			}
		} else {
			$json['error'] = $this->t('Ошибка добавления в Избранное.');
		}

		$this->getJson($json);
	}

	/**
	 * Удаление из избранного.
	 *
	 * В качестве возврата доступен json:
	 * json.msg
	 * json.error
	 */
	public function delFavoritesAction() {
		$this->ajaxInit();
		$myId = Models_User_Model::getMyId();

		if($myId > 0 && is_numeric($this->_getParam('user_id', null))) {
			$ModelFavorites = new Models_User_Favorites($myId);
			$ModelFavorites->delUser($this->_getParam('user_id'));

			$json['msg'] = $this->t('Пользователь был удален из Вашего Избранного.');
		} else {
			$json['error'] = $this->t('При удалении пользователя из Избранных контактов произошла ошибка.');
		}

		$this->getJson($json);
	}

	/**
	 * Вывод текущего чёрного списка - пользователи которых я заблокировал.
	 */
	public function blacklistAction()
	{
		// Редирект для призраков для заполнения анкеты
		if(Models_User_Model::getMyCurrentStatus() < 70) {$this->_redirect('user/profile/wizard');}

		$Model = new Models_User_BlackList();
		$this->view->vData = $Model->getBlackListAllInfo();
	}

	/**
	 * Добавление в чёрный список.
	 *
	 * В качестве возврата доступен json:
	 * json.user_id
	 * json.uid
	 * json.first_name
	 * json.sex
	 * json.msg
	 *
	 * json.error
	 */
	public function addBlacklistAction()
	{
		$this->ajaxInit();
		$myId = Models_User_Model::getMyId();

		if($myId > 0 && is_numeric($this->_getParam('user_id', null))) {
			$ModelProfile = new Models_User_Profile($this->_getParam('user_id'));
			$partnerProfile = $ModelProfile->getProfile($this->_getParam('user_id'));

			$json['user_id']    = $partnerProfile['id'];
			$json['uid']        = $partnerProfile['uid'];
			$json['first_name'] = $partnerProfile['first_name'];
			$json['sex']        = $partnerProfile['sex'];

			$ModelBlackList = new Models_User_BlackList($myId);
			$ModelBlackList->addUser($partnerProfile['id']);

			$json['msg'] = $this->t('Пользователь заблокирован.');
		} else {
			$json['error'] = $this->t('Ошибка удаления пользователя из списка заблокированных.');
		}

		$this->getJson($json);
	}

	/**
	 * Удаление из чёрного списока.
	 *
	 * В качестве возврата доступен json:
	 * json.msg
	 * json.error
	 */
	public function delBlacklistAction()
	{
		$this->ajaxInit();
		$myId = Models_User_Model::getMyId();

		if($myId > 0 && is_numeric($this->_getParam('user_id', null))) {
			$ModelBlackList = new Models_User_BlackList($myId);
			$ModelBlackList->delUser($this->_getParam('user_id'));

			$json['msg'] = $this->t('Пользователь удалён из Вашего списка заблокированных.');
		} else {
			$json['error'] = $this->t('Ошибка удаления из списка заблокированных.');
		}

		$this->getJson($json);
	}

	/**
	 * Загрузка-Добавление фотографии в галерею пользователя.
	 *
	 * Ожидает на вход: $_FILES['userPhoto']
	 *
	 * Возвращает:
	 * json.img src снимка
	 * json.img_path отдельно путь без названия файла
	 * json.img_name отдельно название загруженного файла
	 *
	 * json.error текст ошибок
	 */
	public function uploadPhotoAlbumAction()
	{
		$this->ajaxInit();
		$myId = Models_User_Model::getMyId();
		$json = array();

		// Модель для работы с фотографиями пользователя
		$ModelPhoto = new Models_User_Photo();

		$pathHost = PATH_DIR_HOST . DIRECTORY_SEPARATOR . 'public';

		$pathImg = $ModelPhoto->getViewPatch($myId, Models_User_Model::getMySex(), Models_User_Model::getMyBirthday());

		$validFormats = array('image/jpeg');

		// Ограничения на кол-во фотографий в галерее
		$cntMyPhoto = $ModelPhoto->getCntMyPhoto();

		if($cntMyPhoto >= 9) {
			$json['error'] = $this->t('Количество фотографий в галерее не может превышать 9 шт.');
		} else {
			if(isset($_POST) && $_SERVER['REQUEST_METHOD'] == 'POST') {

				// Проверка наличия директории для сохранения
				if (!file_exists($pathHost . $pathImg)) {
					$ModelPhoto->createDirPhotoUser($pathHost . $pathImg);
				}

				$userPhotoSize    = $_FILES['userPhoto']['size'];
				$userPhotoType    = $_FILES['userPhoto']['type'];
				$userPhotoTmpName = $_FILES['userPhoto']['tmp_name'];
				$userPhotoError   = $_FILES['userPhoto']['error'];

				if(in_array($userPhotoType, $validFormats) && $userPhotoError == 0 && $userPhotoSize < $ModelPhoto->getLimitSize())
				{
					$userPhotoNameNew = md5($myId . microtime()) . '.jpg';
					if(move_uploaded_file($userPhotoTmpName, $pathHost . $pathImg . $userPhotoNameNew)) {
						$json['img'] = $pathImg . $userPhotoNameNew;
						$json['img_path'] = $pathImg;
						$json['img_name'] = $userPhotoNameNew;
						$ModelPhoto->savePhoto($userPhotoNameNew);
					}
					else {
						$json['error'] = $this->t('При попытке загрузки фотографии в альбом произошла ошибка.');
					}
				} else {
					$json['error'] = $this->t('Фотография не соответствует формату или превышен максимально допустимый размер снимка (10 Мб.).');
				}
			}
		}

		$this->getJson($json);

	}

	/**
	 * Удаление фотографии из галереи пользователя.
	 *
	 * На входе ожидает параметр picture - название файла для удаления
	 */
	public function delPhotoAlbumAction() {
		$this->ajaxInit();
		$json = array();
		$myId = Models_User_Model::getMyId();

		// Удаление фото
		if($this->_getParam('picture', false)) {
			$ModelPhoto = new Models_User_Photo($myId);

			if($ModelPhoto->deletePhoto($this->_getParam('picture'))) {
				$json['msg'] = $this->t('Фотография удалена.');
			} else {
				$json['error'] = $this->t('При попытке удалить фотографию произошла ошибка.');
			}
		} else {
			$json['error'] = $this->t('Не достаточно данных для удаления фотографии.');
		}

		$this->getJson($json);
	}

	/**
	 * Добавление и удаление комментария к фотографии в галерее пользователя.
	 *
	 * @param string picture Название картинки которая комментируется
	 * @param string comment Текст комментария
	 */
	public function addPhotoAlbumCommentAction() {
		$this->ajaxInit();
		$json = array();
		$myId = Models_User_Model::getMyId();

		// Сохранение подписи к фотографии
		if($this->_getParam('picture', false) && $this->_getParam('comment', false)) {
			$ModelPhoto = new Models_User_Photo($myId);
			if($ModelPhoto->saveComment($this->_getParam('picture'), $this->_getParam('comment'))) {
				$json['data'] = $this->_getParam('comment');
			} else {
				$json['error'] = $this->t('Ошибка сохранения подписи к фотографии.');
			}
		} else {
			$json['error'] = $this->t('Для сохранения подписи к фотографии недостаточно параметров.');
		}

		$this->getJson($json);
	}

	/**
	 * PopUp для редактирования аватарки профиля
	 */
	public function popupAvatarEditAction()
	{
		$this->ajaxInit();
		$myId = Models_User_Model::getMyId();

		$ModelPhoto = new Models_User_Photo($myId);

		$this->view->assign('vTitle', $this->view->t('Фотография профиля'));
		$this->view->assign('vPathImg', $ModelPhoto->getPatchAvatar());

		$this->renderScript('/popup/popup-avatar-edit.phtml');
	}

	/**
	 * Список для popup лайкнувщих фото в фотоальбоме
	 */
	public function popupPhotoAlbumAction()
	{
		$this->ajaxInit();
		$photoId = $this->_getParam('id', 0);

		$this->view->vTitle = $this->view->t('Ваша фотография понравилась');

		$json = array();
		$ModelPhoto = new Models_User_Photo();
		$data = $ModelPhoto->getPeopleLike($photoId);

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
	 * Удаляет профиль пользователя по его собственному желанию.
	 * В случае успеха выкидывает пользователя из закрытой области сайта
	 *
	 * При ошибке вернет json.error
	 */
	public function deleteAction()
	{
		$this->ajaxInit();
		$json = array();
		$myId = Models_User_Model::getMyId();
		$request = $this->getRequest();

		try {
			$ModelUser = new Models_Users($myId);

			// Удаляем профиль пользователя по его желанию
			if($ModelUser->deleteMyProfile($request->getParam('psw'))) {
				$json['msg'] = $this->t('Профиль удален.');
			}
		}
		catch (Sas_Models_Exception $e) {
				$json['error'] = $e->getMessage();
			}

		$this->getJson($json);
	}

	public function phoneBookAction()
	{
		$myId = Models_User_Model::getMyId();
		$MyProfile = new Models_Users($myId);
		$this->view->assign('myProfile', $MyProfile->getProfileToArray());

		$ModelExchange = new Models_User_ContactExchange($myId);
		$this->view->assign('vData', $ModelExchange->getExchangeYes($MyProfile));
	}
}
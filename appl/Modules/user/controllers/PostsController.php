<?php

/**
 * Посты пользователей
 */
class User_PostsController extends Sas_Controller_Action_User
{
	/**
	 * Список бесплатных постов
	 */
	public function indexAction()
	{
		$myID = Models_User_Model::getMyId();

		$ModelProfile = new Models_User_Profile($myID);
		$this->view->myProfile = $ModelProfile->getProfile($myID);

		$ModelPosts = new Models_User_Posts($myID);
		$posts = $ModelPosts->getPostsAll(20, 0);
		$this->view->vPosts = $posts;
		$this->view->vILikePost = $ModelPosts->getILikePost($posts);
	}

	/**
	 * Комментарии к посту
	 */
	public function commentsAction()
	{
		$this->_helper->layout()->disableLayout();

		$postId = (int) $this->_getParam('post_id');
		if($postId > 0) {
			$myID = Models_User_Model::getMyId();
			$ModelProfile = new Models_User_Profile($myID);
			$myProfile = $ModelProfile->getProfile($myID);
			$this->view->myProfile = $myProfile;

			$ModelPosts = new Models_User_Posts($myID);

			$this->view->vComments = $ModelPosts->getComments($postId);
			$this->view->vMyID = $myID;
			$this->view->vPostId = $postId;
			$this->view->vILikeComment = $ModelPosts->getILikeComment($postId);
		}
	}

	/**
	 * Добавление пользоватеьского поста
	 */
	public function addPostAction()
	{
		#Sas_Debug::dump($_POST, 'POST');
		#Sas_Debug::dump($_FILES, 'FILES');
		#exit;

		if(!$this->getRequest()->isPost()) {
			$this->_redirect('user/profile');
		}

		$postText = htmlspecialchars(strip_tags(trim($this->_getParam('post_text'))));
		if(empty($postText)) {
			$this->_redirect('user/profile');
		}
		$postText = str_replace("\n", "<br>", $postText);

		$myID = Models_User_Model::getMyId();
		$ModelPosts = new Models_User_Posts($myID);

		$money = 'no';

		$ModelBalance = new Models_User_Balance();

		if($this->_getParam('money') == 'yes') {
			$ModelBalance->init($myID);
			if($ModelBalance->checkDebitOnVipPost()) {
				$money = 'yes';
			} else {
				$this->_redirect('user/profile/balance');
			}
		}

		$postId = $ModelPosts->savePost(null, $postText, null, $money);
		if($postId != false)
		{
			if($money == 'yes') { // Заказано платное размещение и все проверки на наличие карат пройдены
				$ModelBalance->debitOnVipPost($postId);
			}

			// Начинаем работать с фото если она есть
			if($_FILES['post_img']['error'] == 0) {
				$ModelPosts->savePostImg($postId, $_FILES['post_img']);
			}
		}

		// если нет редиректа = на профиль
		if(is_null($this->_getParam('redirect'))) {
			$this->_redirect('user/profile');
		}

		// Посты написанные из профиля = оставляем в профиль
		if($this->_getParam('redirect') == '/user/profile') {
			$this->_redirect('user/profile#post-members');
		} else {
			// написание из любых других мест
			// при написании платных постов редиректим на даш
			if($money == 'yes') {
				$this->_redirect('user/dashboard#post-members');
			} else {
				// любые другие в ленту
				$this->_redirect('user/posts#post-members');
			}
		}

	}

	/**
	 * Удалить пост
	 */
	public function delPostAction()
	{
		$this->ajaxInit();

		$postId = (int) $this->_getParam('post_id');
		if($postId > 0) {
			$myID = Models_User_Model::getMyId();
			$ModelPosts = new Models_User_Posts($myID);
			$ModelPosts->delPost($postId);
		}
	}

	/**
	 * Скрыть пост
	 */
	public function hidePostAction()
	{
		$this->ajaxInit();

		$postId = (int) $this->_getParam('post_id');
		if($postId > 0) {
			$myID = Models_User_Model::getMyId();
			$ModelPosts = new Models_User_Posts($myID);
			$ModelPosts->hidePost($postId);
		}
	}

	/**
	 * Добавление пользовательского поста
	 */
	public function addCommentAction()
	{
		$this->ajaxInit();

		$postId = (int) $this->_getParam('post_id');
		$commentText = htmlspecialchars(strip_tags(trim($this->_getParam('comment_text'))));
		if($postId > 0 && !empty($commentText)) {
			$myID = Models_User_Model::getMyId();
			$ModelPosts = new Models_User_Posts($myID);
			$ModelPosts->saveComment($postId, str_replace("\n", "<br>", $commentText));

			$this->_redirect('user/posts/comments/post_id/' . $postId);
		}
	}

	/**
	 * Удаление комментария
	 */
	public function delCommentAction()
	{
		$this->ajaxInit();

		$postId = (int) $this->_getParam('post_id');
		$commentId = (int) $this->_getParam('comment_id');
		if($postId > 0 && $commentId > 0) {
			$myID = Models_User_Model::getMyId();
			$ModelPosts = new Models_User_Posts($myID);
			$ModelPosts->delComment($postId, $commentId);
		}
	}

	/**
	 * Лайк поста
	 */
	public function postLikeAction() {
		$this->ajaxInit();
		$json = array();

		$myID = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myID);
		$myProfile = $ModelProfile->getProfile($myID);

		$postId = (int) $this->_getParam('data_id', 0);

		if($postId > 0 && $myProfile['current_status'] >= 70) {
			$ModelPosts = new Models_User_Posts($myID);
			$ModelPosts->likePost($postId);

			$json['msg'] = $this->view->t('Вам нравится');
		} else {
			$json['error']['code'] = 5001;
			$json['error']['msg'] = $this->view->t('Ошибка записи лайка поста');
		}

		$this->getJson($json);
	}

	/**
	 * Лайк комментария поста
	 */
	public function postCommentLikeAction() {
		$this->ajaxInit();
		$json = array();

		$myID = Models_User_Model::getMyId();
		$ModelProfile = new Models_User_Profile($myID);
		$myProfile = $ModelProfile->getProfile($myID);

		$commentId = (int) $this->_getParam('data_id', 0);

		if($commentId > 0 && $myProfile['current_status'] >= 70) {
			$ModelPosts = new Models_User_Posts($myID);
			$ModelPosts->likeComment($commentId);

			$json['msg'] = $this->view->t('Вам нравится');
		} else {
			$json['error']['code'] = 5002;
			$json['error']['msg'] = $this->view->t('Ошибка записи лайка комментария поста');
		}

		$this->getJson($json);
	}

	/**
	 * Список для popup лайкнувщих пост
	 */
	public function popupPostAction()
	{
		$this->ajaxInit();
		$postId = $this->_getParam('id', 0);

		$this->view->vTitle = $this->view->t('Ваш пост понравился');

		$json = array();
		$ModelPosts = new Models_User_Posts();
		$data = $ModelPosts->getLikeUsers($postId, null);

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
	 * Список для popup лайкнувщих комментарий
	 */
	public function popupPostCommentAction()
	{
		$this->ajaxInit();
		$commentId = $this->_getParam('id', 0);

		$this->view->vTitle = $this->view->t('Ваш комментарий понравился');

		$json = array();
		$ModelPosts = new Models_User_Posts();
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
		}

		$this->view->vData = $json;

		$this->renderScript('/popup/popup-people.phtml');
	}
}
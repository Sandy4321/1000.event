<?php

/**
 * Модель пользовательских постов
 * Class Models_User_Posts
 */
class Models_User_Posts
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	private $myId = null;

	private $tblPost            = array('post'=>'users_post');
	private $tblPostComment     = array('comment'=>'users_post_comment');
	private $tblPostLike        = array('like'=>'users_post_like');
	private $tblPostCommentLike = array('like_com'=>'users_post_comment_like');
	private $tblPostHide        = array('hide'=>'users_post_hide');

	private $tblProfile   = array('profile'=>'users'); // Пользователи
	private $columnProfileAvatar = array('avatar' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');

	private $tblFavorite   = array('fav'=>'user_favorites'); // Избранное

	private $error = array();

	private $myProfile = null;

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : (int) $myId;

		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	/*public function setMyProfile(array $myProfile) {
		$this->myProfile = $myProfile;
	}*/

	// Вывод всех постов с лимитом и сортировкой + кол-во лайков и ком. (ком. = комментарии)
	public function getPostsAll($limit = 20, $page = 0, $favorite = false, $my = false)
	{
		$select = $this->db->select()
			->from($this->tblPost, '*')
			->where('post.del = ?', 'no')
			->group('post.id')
			->order('post.date_create DESC')
			->limitPage($page, $limit);

		$select->where('post.user_id NOT IN (SELECT favorite_user_id FROM user_favorites WHERE user_id = ?)', $this->myId);

		// Только мои посты
		if($my != false) {
			$select->where('post.user_id = ?', $this->myId);
		} else {
			$select->where('post.money != "yes"');
		}

		// Только не скрытые посты
		$select->joinLeft($this->tblPostHide, 'hide.`post_id`=post.`id` AND hide.`user_id` = '.$this->myId, null)
			->where('hide.id IS NULL');

		// Пользователь
		$select->joinLeft($this->tblProfile, 'profile.id=post.user_id', array('first_name', 'uid'))
			->columns($this->columnProfileAvatar);

		// Кол-во комментариев
		$select->joinLeft($this->tblPostComment, 'comment.post_id=post.id', array('cnt_comment'=>'COUNT(DISTINCT comment.id)'));

		// Кол-во лайков на пост
		$select->joinLeft($this->tblPostLike, 'like.post_id=post.id', array('cnt_like'=>'COUNT(DISTINCT like.id)'));

		//Sas_Debug::sql($select);
		$rows = $this->db->fetchAll($select);

		return $rows;
	}

	public function getPostsAllFavAndMoney($limit = 20) {
		$sql = 'SELECT * FROM (
SELECT `post`.*, `profile`.`first_name`, `profile`.`uid`, CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" ) AS `avatar`, COUNT(DISTINCT comment.id) AS `cnt_comment`, COUNT(DISTINCT like.id) AS `cnt_like` FROM `users_post` AS `post`
 INNER JOIN `user_favorites` AS `fav` ON (post.user_id = fav.favorite_user_id AND fav.user_id = '.$this->myId.')
 LEFT JOIN `users` AS `profile` ON profile.id=post.user_id
 LEFT JOIN `users_post_comment` AS `comment` ON comment.post_id=post.id
 LEFT JOIN `users_post_like` AS `like` ON like.post_id=post.id
 LEFT JOIN `users_post_hide` AS hide ON (`hide`.`post_id`=`post`.`id` AND `hide`.`user_id` = '.$this->myId.')
 WHERE (post.del = "no")
 AND `hide`.`id` IS NULL
 GROUP BY `post`.`id`
 LIMIT '.$limit.'
 UNION ALL
SELECT `post`.*, `profile`.`first_name`, `profile`.`uid`, CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" ) AS `avatar`, COUNT(DISTINCT comment.id) AS `cnt_comment`, COUNT(DISTINCT like.id) AS `cnt_like` FROM `users_post` AS `post`
 LEFT JOIN `users` AS `profile` ON profile.id=post.user_id
 LEFT JOIN `users_post_comment` AS `comment` ON comment.post_id=post.id
 LEFT JOIN `users_post_like` AS `like` ON like.post_id=post.id
 LEFT JOIN `users_post_hide` AS hide ON (`hide`.`post_id`=`post`.`id` AND `hide`.`user_id` = '.$this->myId.')
 WHERE
 post.money = "yes" AND post.del = "no"
 AND `hide`.`id` IS NULL
 GROUP BY `post`.`id`
 ORDER BY `date_create` DESC LIMIT '.$limit.'
) AS t GROUP BY `id` ORDER BY `date_create` DESC LIMIT ' . $limit;
		//Sas_Debug::dump($sql);
		$select = $this->db->query($sql);

		return $select->fetchAll();
	}

	// Вывод всех постов конкретного пользователя с лимитом и сортировкой  + кол-во лайков и текстом ком. + аватарка + имя + ссылка на профиль
	// Вывод всех пользователей из моего избранного с лимитом и сортировкой  + кол-во лайков и текстом ком. + аватарка + имя + ссылка на профиль

	// Вывод заданного поста со списком текстов комментариев + (аватарка + имя + ссылка на профиль) и кол-во лайков к посту
	public function getComments($postId)
	{
		/*if(is_null($this->myProfile)) {
			throw new Sas_Models_Exception('Нет профиля получателя');
		}
		$mySex = $this->myProfile['sex'];
		$myStatus = $this->myProfile['current_status'];*/

		$select = $this->db->select()
			->from($this->tblPostComment, '*')
			->where('comment.post_id = ?', $postId)
			->order('comment.date_create ASC');

		// Владелец поста
		$select->joinLeft($this->tblPost, 'post.id=comment.post_id', array('master_user_id' => 'user_id'));

		// Пользователь
		$select->joinLeft($this->tblProfile, 'profile.id=comment.user_id', array('first_name', 'uid'))
			->columns($this->columnProfileAvatar);

		// Кол-во лайков на пост
		$select->joinLeft($this->tblPostCommentLike, 'like_com.comment_id=comment.id', array('cnt_like'=>'COUNT(DISTINCT like_com.id)'))
			->group('comment.id');
		//$select->joinLeft($this->tblPostCommentLike, 'com_like.comment_id=comment.id', array('cnt_like_com'=>'COUNT(DISTINCT com_like.id)'));

		$rows = $this->db->fetchAll($select);
		#Sas_Debug::sql($select);

		// Формируем ссылки на профиль
		/*$urlProfile = ($this->lang == 'ru') ? '' : '/'.$this->lang;
		if($rows) {
			Sas_Debug::dump($rows);
			if($myStatus >= 70) {
				$urlProfile =
			}
			foreach($rows as $k=>$v) {

				echo $v;
			}

		}*/

		return $rows;
	}

	/**
	 * Возвращает массив с ключами ID комментариев к посту которые я лайкнул.
	 * Массив вида [comment_id]=yes
	 * @param $postId
	 * @return string
	 */
	public function getILikeComment($postId)
	{
		$select = $this->db->select();
		$select->from($this->tblPostComment, null)
			->where('comment.post_id = ?', $postId);

		$select->joinLeft($this->tblPostCommentLike, 'like_com.comment_id = comment.id', array('comment_id', 'CONCAT("yes")'))
			->where('like_com.user_id = ?', $this->myId);

		return $this->db->fetchPairs($select);
	}

	// Посты которые я лайкал
	public function getILikePost($posts, $userId = null)
	{
		$userId = (is_null($userId)) ? $this->myId : $userId;
		$p = array();
		if(!empty($posts)) {
			foreach($posts as $post) {
				$p[] = $post['id'];
			}
			$select = $this->db->select()
				->from($this->tblPostLike, array('post_id', 'user_id'))
				->where('user_id = ?', $userId)
				->where('post_id IN(?)', $p);
			$p = $this->db->fetchPairs($select);
		}
		return $p;
	}

	/**
	 * Лайкнуть на комментарий к мероприятию
	 * @param $commentId
	 */
	public function likeComment($commentId)
	{
		// Проверяем лайкал ли я уже этот коммент?
		if(!$this->isLikeComment($commentId)) {
			$this->db->insert($this->tblPostCommentLike, array('comment_id'=>$commentId, 'user_id'=>$this->myId, 'date_create'=>CURRENT_DATETIME));
		}
	}

	/**
	 * Проверяет лайкал ли я это комментарий
	 * @param $commentId
	 * @return bool
	 */
	public function isLikeComment($commentId) {
		$select = $this->db->select()
			->from($this->tblPostCommentLike, 'user_id')
			->where('user_id = ?', $this->myId)
			->where('comment_id = ?', $commentId);

		$userId = $this->db->fetchOne($select);

		return ($userId == $this->myId) ? true : false;
	}

	// + Добавить лайк на пост
	public function likePost($postId)
	{
		// Проверяем лайкал ли я уже этот пост?
		if(!$this->isLikePost($postId)) {
			$this->db->insert($this->tblPostLike, array('post_id'=>$postId, 'user_id'=>$this->myId, 'date_create'=>CURRENT_DATETIME));
		}
	}

	public function isLikePost($postId) {
		$select = $this->db->select()
			->from($this->tblPostLike, 'user_id')
			->where('user_id = ?', $this->myId)
			->where('post_id = ?', $postId);

		$userId = $this->db->fetchOne($select);

		return ($userId == $this->myId) ? true : false;
	}

	/**
	 * Возвращает основные данные пользователей которые лайкнули пост.
	 * @param     $postId
	 * @param int $limit
	 * @return array
	 */
	public function getLikeUsers($postId, $limit = 5) {
		$select = $this->db->select();
		$select->from($this->tblPostLike, null)
			->where('like.post_id = ?', (int) $postId)
			->order('date_create DESC');

		if(!is_null($limit)) {
			$select->limit($limit);
		}

		$select->joinLeft($this->tblProfile, 'profile.id=like.user_id', array('id','uid', 'first_name'))
			->columns($this->columnProfileAvatar);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает основные данные пользователей которые лайкнули комментарий.
	 * @param     $commentId
	 * @param int $limit
	 * @return array
	 */
	public function getLikeComment($commentId, $limit = 5) {
		$select = $this->db->select();
		$select->from($this->tblPostCommentLike, null)
			->where('like_com.comment_id = ?', (int) $commentId)
			->order('like_com.date_create DESC');

		if(!is_null($limit)) {
			$select->limit($limit);
		}

		$select->joinLeft($this->tblProfile, 'profile.id=like_com.user_id', array('id','uid', 'first_name'))
			->columns($this->columnProfileAvatar);

		return $this->db->fetchAll($select);
	}

	// + Сохранить пост (в т.ч. и после редактирования)
	public function savePost($post_id = null, $text = null, $img = null, $money = null)
	{
		if(is_null($post_id)) {
			// Добавляем пост
			$data['user_id'] = $this->myId;
			$data['date_create'] = CURRENT_DATETIME;
			if(!is_null($text)) $data['post_text'] = Sas_Filter_TextReplaceLinks::get($text);
			$data['img'] = (is_null($img)) ? 'no' : 'yes';

			if(!is_null($money)) {
				$data['money'] = $money;
			}

			$this->db->insert($this->tblPost, $data);
			return $this->db->lastInsertId($this->tblPost);
		} else {
			// Это мой пост?
			if($this->isMyPost($post_id)) {
				if(!is_null($text)) $data['post_text'] = Sas_Filter_TextReplaceLinks::get($text);
				$data['img'] = (is_null($img)) ? 'no' : 'yes';

				$this->db->update($this->tblPost, $data, 'id = ' . (int)$post_id);
				return $post_id;
			}
		}

		return false;
	}

	// Сохранить картинку к посту
	public function savePostImg($postId, $img)
	{
		$photoPathView = '/img/user_post/'.$this->myId.'/';

		//$fileName = md5($photoPathView . time());

		$Image = new Sas_Image();
		$Image->configSaveOriginal($postId.'_original', 'jpg');
		$Image->configSaveOptimal(640, 480, $postId, 'jpg');
		$Image->setImgDir($photoPathView, true);
		$checkSavePhoto = $Image->save($img['tmp_name']);
		if($checkSavePhoto != false) {
			#Sas_Debug::dump($Image->getFullPath(), 'IMG getFullPath');
			#Sas_Debug::dump($Image->getPathOptimalName(), 'IMG getPathOptimalName');
			return $this->savePost($postId, null, 'yes');//$Image->getPathOptimalName();
		} else {
			#Sas_Debug::dump($Image->getError(), 'ERROR SAVE PHOTO');
			return false;
		}
	}
	// - Удалить пост
	public function delPost($postId)
	{
		// Проверяем мой ли это пост
		if($this->isMyPost($postId)) {
			$this->db->update($this->tblPost, array('del'=>'yes'),'id = ' . $postId);
			return $postId;
		}

		return 0;
	}

	/**
	 * Скрыть пост
	 * @param $postId
	 * @return int
	 */
	public function hidePost($postId)
	{
		$this->db->insert($this->tblPostHide, array('post_id'=>$postId, 'user_id' => $this->myId));
		return 1;
	}

	// - Удалить картинку у поста
	// + Сохранить комментарий к посту
	public function saveComment($postId, $commentText)
	{
		$data['user_id'] = $this->myId;
		$data['post_id'] = $postId;
		$data['comment_text'] = Sas_Filter_TextReplaceLinks::get($commentText);
		$data['date_create'] = CURRENT_DATETIME;

		$this->db->insert($this->tblPostComment, $data);

		return $this->db->lastInsertId($this->tblPostComment);
	}

	// - Удалить комментарий к посту (владелец поста может удалить любой ком., владелец ком. только свой ком.)
	public function delComment($postId, $commentId)
	{
		// Проверяем мой ли это пост или мой ли это комментарий
		$select = $this->db->select()
			->from($this->tblPost, array('master_user_id'=>'user_id'))
			->where('post_id = ?', $postId)
			->joinInner($this->tblPostComment, 'post.id = comment.post_id', array('comment_user_id'=>'user_id'))
			->where('comment.id = ?', $commentId);
		$check = $this->db->fetchRow($select);

		if($check['master_user_id'] == $this->myId || $check['comment_user_id'] == $this->myId || $this->myId == 4000) {
			$this->db->delete($this->tblPostComment, 'id = ' . $commentId);
			$this->db->delete($this->tblPostCommentLike, 'comment_id = ' . $commentId);
		}
	}

	/**
	 * Определяет чей пост
	 * @param $postId
	 * @return bool true - мой / false - не мой
	 */
	public function isMyPost($postId)
	{
		$select = $this->db->select()
			->from($this->tblPost, 'user_id')
			->where('id = ?', $postId)
			->limit(1);
		$userId = $this->db->fetchOne($select);

		return ($userId == $this->myId || $this->myId == 4000) ? true : false;
	}

	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'posts', 'action'=>'index'),
			'name'  => $tr->translate('Посты'),
			'check' => 'user/posts',
			'style' => ' active',
			'icon' => 'Profile',
			/*'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'posts', 'action' => 'add'),
					'name'  => $tr->translate('Добавить'),
					'check' => 'user/posts/add',
					'style' => ' active',
					'icon'  => 'Settings',
				),
			)*/
		);

		return $menu;
	}
	//============ /MENU ===========
}


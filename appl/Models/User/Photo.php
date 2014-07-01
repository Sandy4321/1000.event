<?php

class Models_User_Photo
{
	private $lang = LANG_DEFAULT;
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $table = 'user_pictures';
	private $tblLike = 'user_pictures_like';
	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');

	private $myId = null;

	/**
	 * @var Models_Users
	 */
	private $MyProfile = null;

	private $limit = 9;
	private $cntMyPhoto = null;

	public function __construct($user = null) {
		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->db = Zend_Registry::get('db');

		if($user instanceof Models_Users) {
			$this->MyProfile = $user;
			$this->myId = $user->getId();
		} else {
			if(is_null($user)) {
				$this->myId = Models_User_Model::getMyId();
			} else {
				$this->myId = (int)$user;
			}
		}

		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	/**
	 * Возвращает максимальный лимит в байтах для загрузки фотографий
	 * @return int
	 */
	public function getLimitSize() {
		return 1024 * 1024 * 1024 * 10;
	}

	/**
	 * Возвращает мои фото
	 * @return array
	 */
	public function getMyPhoto()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->table);
		$select->where('user_id = ?', $this->myId);
		$select->order('sort');
		$select->limit($this->getMaxLimitPhoto());

		$rows = $this->db->fetchAll($select);
		$this->cntMyPhoto = count($rows);
		return $rows;
	}

	/**
	 * Возвращает фото пользователя
	 * @param $userId
	 * @return array
	 */
	public function getPhoto($userId)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->table);
		$select->where('user_id = ?', $userId);
		$select->order('sort');
		$select->limit($this->getMaxLimitPhoto());

		$rows = $this->db->fetchAll($select);
		$this->cntMyPhoto = count($rows);
		return $rows;
	}

	/**
	 * Возвращает кол-во моих фото в галерее
	 * @return int
	 */
	public function getCntMyPhoto() {
		if (is_null($this->cntMyPhoto))
		{
			$select = new Zend_Db_Select($this->db);
			$select->from($this->table, 'COUNT(id)');
			$select->where('user_id = ?', $this->myId);
			#Sas_Debug::dump($select->__toString());
			$this->cntMyPhoto = (int) $this->db->fetchOne($select);
		}

		#Sas_Debug::dump($this->cntMyPhoto);
		return $this->cntMyPhoto;
	}

	/**
	 * Возвращает максимальный лимит установленный для одной галереи пользователя.
	 * @return int
	 */
	public function getMaxLimitPhoto() {
		return $this->limit;
	}

	/**
	 * Сохраняет данные фотографии в бд
	 * @param $imgName
	 */
	public function savePhoto($imgName)
	{
		$insertData = array(
			'user_id' => $this->myId,
			'picture' => $imgName,
			'sort' => 100,
			'datetime_create' => date('Y-m-d H:i:s')
		);

		$this->db->insert($this->table, $insertData);
		$insertId = $this->db->lastInsertId();

		Models_Actions::add(34, $this->myId, null, $insertId); // Загружена новыя фотография в галерею пользователя
	}

	/**
	 * Сохраняет комментарий к фотографии
	 * @param $imgName
	 * @param $comment
	 * @return bool
	 */
	public function saveComment($imgName, $comment)
	{
		$comment = htmlspecialchars(strip_tags(trim($comment)));
		$data = array(
			'comment' => (empty($comment)) ? null : $comment,
		);

		$where = $this->db->quoteInto('picture = ?', $imgName);
		$where .= ' AND ';
		$where .= $this->db->quoteInto('user_id = ?', $this->myId);
		$res = $this->db->update($this->table, $data, $where);
		if($res > 0) {
			Models_Actions::add(36, $this->myId); // Добавлен/удалён комментарий для фотографии в галерее
			return true;
		}

		return false;
	}

	/**
	 * Удаление фотографии.
	 *
	 * @param $picture
	 * @return bool
	 */
	public function deletePhoto($picture)
	{
		$patch = $_SERVER['DOCUMENT_ROOT'].$this->getViewPatch(Models_User_Model::getMyId(), Models_User_Model::getMySex(), Models_User_Model::getMyBirthday()).$picture;
		//Sas_Debug::dump($patch);
		@unlink($patch);

		// Получаем id удаляемого фото
		$select = $this->db->select()
			->from($this->table, 'id')
			->where('user_id = ?', $this->myId)
			->where('picture = ?', $picture)
			->limit(1);
		$id = $this->db->fetchOne($select);

		if (is_numeric($id)) {
			// Удаляем из счетчика лайков
			$this->db->delete($this->tblLike, $this->db->quoteInto('photo_id = ?', $id));

			// Удаляем из тбл картинок
			$this->db->delete($this->table, $this->db->quoteInto('id = ?', $id));

			Models_Actions::add(35, $this->myId); // Удалена фотография из галереи пользователя

			return true;
		}

		return false;
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
	 * Возвращает относительный путь для аватара пользователя
	 * @return string
	 */
	public function getPatchAvatar()
	{
		if(is_null($this->MyProfile)) {
			$path = '/img/people/'.Models_User_Model::getMySex() . '/' . Models_User_Model::getMyYear(Models_User_Model::getMyBirthday()).'/' . Models_User_Model::getMyId() . '/';
		} else {
			$path = $this->MyProfile->getImgPath();
		}
		return $path;
	}

	/**
	 * Создаёт директорию для хранения фото пользователя
	 * @param $dirPath
	 */
	public function createDirPhotoUser($dirPath)
	{
		mkdir($dirPath, 0777, true);
	}

	public function plusLike($photoId)
	{
		// Проверяем кликал ли пользователь уже на данную фото
		if($this->isLikeUser($photoId) >= 1) {
			return false;
		}

		// Нет, не кликал. увеличиваем счётчик кликов
		// Получаем текущее значение
		$select = $this->db->select()
			->from($this->table, array('user_id', 'like_cnt'))
			->where('id = ?', $photoId)
			->limit(1);
		$row = $this->db->fetchRow($select);
		$newCnt = $row['like_cnt'] + 1;
		$this->db->update($this->table, array('like_cnt'=>$newCnt), $this->db->quoteInto('id = ?', $photoId));

		// Делаем запись о том, что этот пользователь кликнул по лайку
		$insert['user_id'] = $this->myId;
		$insert['photo_id'] = $photoId;
		$insert['date_create'] = date('Y-m-d H:i:s');
		$this->db->insert($this->tblLike, $insert);

		$ret['user_id'] = $row['user_id'];
		$ret['newCnt'] = $newCnt;
		return $ret;
	}

	/**
	 * Проверяет кликал ли текущий пользователь на фото
	 *
	 * @param $photoId
	 * @return string
	 */
	public function isLikeUser($photoId)
	{
		$select = $this->db->select()
			->from($this->tblLike, 'id')
			->where('user_id = ?', $this->myId)
			->where('photo_id = ?', $photoId)
			->limit(1);
		return $this->db->fetchOne($select);
	}

	/**
	 * Возвращает массив для проверок нажимал ли пользователь на like
	 * @param $photoUser
	 * @return null|string
	 */
	public function getIsLike($photoUser)
	{
		$ar = null;
		for ($i = 0; $i <  count($photoUser); $i++) {
			$ar[] = $photoUser[$i]['id'];
		}

		$idS = null;
		if(is_array($ar)) {
			$idS = implode(',', $ar);
		}

		if (!is_null($idS)) {
			$select = $this->db->select()
				->from($this->tblLike, 'photo_id')
				->where('user_id = ?', $this->myId)
				->where('photo_id IN ('.$idS.')');
			return $this->db->fetchPairs($select);
		}

		return null;
	}

	/**
	 * Возвращает полную информацию по ID фото
	 * @param $photoId
	 * @return array
	 */
	public function getPhotoId($photoId)
	{
		$select = $this->db->select()
			->from($this->table, '*')
			->where('id = ?', $photoId)
			->limit(1);
		$row = $this->db->fetchRow($select);
		if(is_array($row)) {
			$row['img'] = $this->getViewPatch($this->myId, Models_User_Model::getMySex(), Models_User_Model::getMyBirthday()) . $row['picture'];
		} else {
			$row = null;
		}

		return $row;
	}

	/**
	 * Возвращает профили людей лайкнувших фото
	 * @param $photoId
	 * @return array
	 */
	public function getPeopleLike($photoId)
	{
		$select = $this->db->select()
			->from(array('l'=>$this->tblLike), null)
			#->join(array('profile'=>'users_data'), 'l.user_id = profile.id', array('id', 'first_name', 'last_name'))
			->join(array('profile'=>'users'), 'l.user_id = profile.id', array('id', 'uid', 'first_name', 'last_name'))
			->columns($this->columnProfileImg)
			->where('photo_id = ?', $photoId)
			->order('date_create DESC');

		return $this->db->fetchAll($select);
	}
}
<?php

class Models_User_Gifts
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $lang = LANG_DEFAULT;

	private $myId = null;

	private $tblGifts = 'gifts';
	private $columnsGiftsName;

	private $tblGiftsUsers = 'users_gifts';

	#private $tblProfile = 'users_data';
	private $tblProfile = 'users';
	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : (int) $myId;

		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}

		$this->columnsGiftsName = array('giftsName'=>'name_' . $this->lang);
	}

	/**
	 * Возвращает все подарки.
	 *
	 * @param string $giftSex male|female|null
	 * @param string $order
	 * @return array
	 */
	public function getGifts($giftSex = null, $order = 'price DESC')
	{
		$select = $this->db->select()
			->from($this->tblGifts, '*')
			->columns($this->columnsGiftsName)
			->where('hide = ?', 'no');

		if(!is_null($giftSex)) {
			$select->where('gift_sex = "neutral" OR gift_sex = ?', $giftSex);
		}

		if(!is_null($order)) {
			$select->order($order);
		}

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает подарок по его ID.
	 *
	 * @param $giftsId
	 * @return array
	 */
	public function getGiftId($giftsId)
	{
		$select = $this->db->select()
			->from($this->tblGifts, '*')
			->columns($this->columnsGiftsName)
			->where('id = ?', (int) $giftsId)
			->where('hide = ?', 'no');

		return $this->db->fetchRow($select);
	}

	/**
	 * Возвращает все подарки пользователя которые мне подарили.
	 *
	 * @return array
	 */
	public function getGiftsUser()
	{
		$select = $this->db->select()
			->from(array('g'=>$this->tblGifts), array('giftsId'=>'id', 'price', 'gift_sex'))
			->columns($this->columnsGiftsName)
			->where('g.hide = ?', 'no');

		$select->join(array('gu'=>$this->tblGiftsUsers), 'gu.gifts_id = g.id', array('recId'=>'id', 'user_id_from', 'gifts_cnt', 'gifts_comment', 'dt_create'))
			->where('gu.user_id = ?', $this->myId)
			->order('gu.dt_create DESC');

		$select->join(array('profile'=>$this->tblProfile), 'gu.user_id_from = profile.id', array('profileId'=>'id', 'first_name'))
			->columns($this->columnProfileImg);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает все подарки которые ОН(Я) подарил другим пользователям.
	 *
	 * @return array
	 */
	public function getGiftsUserFrom()
	{
		$select = $this->db->select()
			->from(array('g'=>$this->tblGifts), array('giftsId'=>'id', 'price', 'gift_sex'))
			->columns($this->columnsGiftsName)
			->where('g.hide = ?', 'no');

		$select->join(array('gu'=>$this->tblGiftsUsers), 'gu.gifts_id = g.id', array('recId'=>'id', 'user_id', 'gifts_cnt', 'gifts_comment', 'dt_create'))
			->where('gu.user_id_from = ?', $this->myId)
			->order('gu.dt_create DESC');

		$select->join(array('profile'=>$this->tblProfile), 'gu.user_id = profile.id', array('profileId'=>'id', 'first_name'))
			->columns($this->columnProfileImg);

		return $this->db->fetchAll($select);
	}

	/**
	 * Дарим подарок.
	 *
	 * @param      $giftId ID подарка
	 * @param      $userId ID пользователя которому дарим
	 * @param int  $cnt Кол-во подарка
	 * @param null $comment Комментарий к подарку (опционально)
	 * @return bool false-недостаточно карат|true - успешно
	 */
	public function giveGift($giftId, $userId, $cnt = 1, $comment = null)
	{
		// Проверяем наличие карат для покупки
		$gift = $this->getGiftId($giftId);
		$price = $gift['price'] * $cnt; // Общая стоимость подарка

		$ModelBalance = new Models_User_Balance();
		$ModelBalance->init($this->myId);
		if (!$ModelBalance->checkDebitGifts($price)) {
			return false;
		} else {
			$ModelBalance->debitOnGifts($price);
		}

		$data = array(
			'user_id' => (int)$userId,
			'user_id_from' => $this->myId,
			'gifts_id' => $giftId,
			'gifts_cnt' => (int)$cnt,
			'gifts_comment' => htmlspecialchars(trim($comment)),
			'dt_create' => date('Y-m-d H:i:s')
		);
		$this->db->insert($this->tblGiftsUsers, $data);
		$id = $this->db->lastInsertId();
		Models_Actions::add(62, $this->myId, $userId, $id); // Подарен подарок

		// Добавляем в профиль подарок (для вывода в поиске)
		$this->db->update($this->tblProfile, array('gift_id'=>(int)$giftId, 'gift_date'=>date('Y-m-d')), $this->db->quoteInto('id = ?', $userId));

		return true;
	}
}
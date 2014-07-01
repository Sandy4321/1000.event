<?php

class Models_Admin_Advert
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	/**
	 * @var Zend_Translate
	 */
	private $tr;

	private $tblUserProfile = array('u'=>'users');

	public function __construct() {
		$this->db = Zend_Registry::get('db');
		$this->tr = Zend_Registry::get('Zend_Translate');
	}

	public function add(array $data) {
		$insert['uid'] = $this->generatorUid(8);
		$insert['promo_key'] = md5($data['adv-type'].$data['adv-name'].$insert['uid']);
		$insert['current_status'] = $data['adv-type'];
		$insert['first_name'] = $data['adv-name'];
		$insert['email'] = $insert['uid'].'@onthelist.ru-adv';
		$insert['about'] = (!empty($data['adv-descr'])) ? $data['adv-descr'] : null;
		$insert['register_dt'] = CURRENT_DATETIME;
		$insert['sex'] = 'male';
		$insert['lang'] = 'ru';
		$insert['activation_key'] = md5(microtime() . $insert['email']);

		$insert['msg_admin_email']  = 'no';
		$insert['msg_invite_email'] = 'no';
		$insert['msg_news_email']   = 'no';

		$insert['recurrent_payment'] = 'no';

		$res = $this->db->insert($this->tblUserProfile, $insert);
		#$res = 1;
		return ($res > 0) ? $insert : false;
	}

	public function getAdvType($typeId) {
		$select = $this->db->select()
			->from($this->tblUserProfile, array('id', 'name'=>'first_name', 'about', 'create_dt'=>'register_dt', 'promo_key'))
			->where('u.current_status = ?', $typeId)
			->order('u.register_dt DESC')
			->group('u.id');

		$select->joinLeft(array('t1'=>'users'), 't1.promo_key_friend=u.promo_key', array('cntRegAll'=>'COUNT(DISTINCT t1.id)'));

		$select->joinLeft(array('t2'=>'users'), 't2.promo_key_friend=u.promo_key AND t2.current_status = 70', array('cntRegClub'=>'COUNT(DISTINCT t2.id)'));

		return $this->db->fetchAll($select);
	}

	/**
	 * Генератор уникальных id.
	 *
	 * @param int $numAlpha
	 * @return string
	 */
	private function generatorUid($numAlpha = 10)
	{
		// символы из которых генерируется индентификатор
		$listAlpha = 'abcdefghjkmnpqrstuvwxyz0123456789ABCDEFGHJKMNPQRSTUVWXYZ';

		// генерируем индентификатор и возвращаем
		$uid = str_shuffle(substr(str_shuffle($listAlpha),0,$numAlpha));

		// Проверяем уникальность uid
		$select = $this->db->select()
			->from($this->tblUserProfile, 'uid')
			->where('uid = ?', $uid)
			->limit(1);
		$res = $this->db->fetchOne($select);
		if($res) {
			$this->generatorUid($numAlpha);
		} else {
			return $uid;
		}
	}
}

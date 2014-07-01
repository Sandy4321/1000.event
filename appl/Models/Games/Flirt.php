<?php

class Models_Games_Flirt
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $tblProfile = array('profile' => 'users');
	private $tblFlirt = array('flirt' => 'games_flirt');

	private $columnProfileAvatar = array('avatar' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');

	/**
	 * @var Models_User_Profile
	 */
	private $profile = null;
	private $myId = null;
	private $mySex = null;

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();
	}

	/**
	 * Обязательная операция для инициализации профиля игрока
	 * @param Models_User_Profile $profile
	 */
	public function initGames(Models_User_Profile $profile) {
		$this->profile = $profile->getProfile(Models_User_Model::getMyId());
		$this->myId  = $this->profile['id'];
		$this->mySex = $this->profile['sex'];
	}

	/**
	 * Возвращает набор данных для игры
	 * @param int $limit
	 * @return array
	 * @throws Sas_Models_Exception
	 */
	public function getGamesData($limit = 20) {
		if(is_null($this->profile)) {
			throw new Sas_Models_Exception('No select user');
		}

		$select = $this->db->select()
			->from($this->tblProfile, array('id','uid','first_name'))
			->columns($this->columnProfileAvatar)
			->where('current_status = 70')
			->where('phone_check = "yes"')
			->where('profile.id != ?', $this->myId)
			->where('profile.id != ?', 4000) // Профиль админа
			->where('sex != ?', $this->mySex)
			->limit($limit)
			->order('RAND()');
		#$select->where('profile.id NOT IN (SELECT partner_id FROM games_flirt WHERE user_id = ' . $this->myId . ')');

		$select->joinLeft($this->tblFlirt, 'profile.id = flirt.partner_id AND flirt.user_id = ' . $this->myId, null)
			->where('flirt.id IS NULL');

		#$select->where('profile.id = 5125'); // ТОЛЬКО для тестов
		#Sas_Debug::sql($select);
		$rows = $this->db->fetchAll($select);
		#Sas_Debug::dump($rows);
		return $rows;
	}

	/**
	 * Сохраняет выбор игрока
	 * @param $partnerId
	 * @param $choice
	 */
	public function saveChoice($partnerId, $choice)
	{
		$data['user_id']    = Models_User_Model::getMyId();
		$data['partner_id'] = $partnerId;
		$data['choice']     = $choice;
		$data['dt_create']  = CURRENT_DATETIME;

		try {
			$this->db->insert($this->tblFlirt, $data);
		} catch (Zend_Db_Exception $e) {
			// null
		}
	}

	/**
	 * Возвращет кол-во да/нет
	 * @param        $myId
	 * @param string $choice
	 * @return string
	 *
	 */
	public function getResultCnt($myId, $choice = 'yes') {
		$select = $this->db->select()
			->from($this->tblFlirt, array('cnt' => 'COUNT(id)'))
			->where('user_id = ?', $myId)
			->where('choice = ?', $choice);

		return $this->db->fetchOne($select);
	}

	/**
	 * Возвращает кол-во совпадений симпатий
	 * @param $myId
	 * @return string
	 */
	public function getSympathyCnt($myId) {
		$select = $this->db->select()
			->from($this->tblFlirt, array('cnt' => 'COUNT(id)'))
			->where('user_id = ?', $myId)
			->where('sympathy = "yes"');

		return $this->db->fetchOne($select);
	}

	/**
	 * Возвращает кол-во людей которым я понравился
	 * @param $myId
	 * @return string
	 */
	public function getILikedCnt($myId) {
		$select = $this->db->select()
			->from($this->tblFlirt, array('cnt' => 'COUNT(id)'))
			->where('partner_id = ?', $myId)
			->where('choice = "yes"');

		return $this->db->fetchOne($select);
	}

	/**
	 * Проверка обоюдного совпадения
	 * @param $myId
	 * @param $partnerId
	 * @return int|mixed
	 */
	public function isMatch($myId, $partnerId)
	{
		$select = $this->db->select()
			->from($this->tblFlirt, array('cnt' => 'COUNT(id)'))
			->where('user_id = '. $partnerId .' AND partner_id = '. $myId .' AND choice = "yes"')
			->limit(1);
		$cnt = $this->db->fetchOne($select);

		return ($cnt > 0) ? $this->getPartnerInfo($partnerId) : 0;
	}

	private function getPartnerInfo($partnerId) {
		$Model = new Models_User_Profile();
		return $Model->getProfile($partnerId);
	}

	/**
	 * Устанавливает (фиксирует) симпатию
	 * @param $myId
	 * @param $partnerId
	 */
	public function setSympathy($myId, $partnerId) {
		$this->db->update($this->tblFlirt, array('sympathy'=>'yes'), 'user_id = ' . $myId .' AND partner_id = ' . $partnerId);
		$this->db->update($this->tblFlirt, array('sympathy'=>'yes'), 'user_id = ' . $partnerId .' AND partner_id = ' . $myId);
	}

	/**
	 * Возвращает информацию из профилей пользователей с которыми есть взаимная симпатия
	 * @param $myProfile
	 * @return array
	 */
	public function getSympathyAll($myProfile)
	{
		$select = $this->db->select()
			->from($this->tblFlirt, null)
			->where('user_id = ?', $myProfile['id'])
			->where('sympathy = "yes"')
			->order('flirt.dt_create DESC');

		$select->join($this->tblProfile, 'profile.id = flirt.partner_id', array('id', 'uid', 'first_name', 'phone'))
			->columns($this->columnProfileAvatar);

		$res = $this->db->fetchAll($select);
		$out = null;
		if($res) {
			$urlProfile = ($this->lang == 'ru') ? '' : '/'.$this->lang;
			foreach($res as $k => $v) {
				$out[$k] = $v;
				if($myProfile['current_status'] >= 70 && $myProfile['club_card_dt'] >= CURRENT_DATE) {
					$out[$k]['phone'] = $this->phoneFormat($v['phone']);
					$out[$k]['url_profile'] = $urlProfile . '/user/people/profile/view/' . $v['uid'];
				} else {
					$out[$k]['phone'] = substr($this->phoneFormat($v['phone']), 0, -5) . 'XX-XX';
					$out[$k]['url_profile'] = $urlProfile . '/user/profile/balance';
				}

			}
		}

		return $out;
	}

	/**
	 * Возвращает ключ уведомления о наличии симпаний в сторону пользователя с момента его последнего действия в игре
	 * @param $myId
	 * @return bool
	 */
	public function getSympathyLastVisit($myId) {

		$maxDt = $this->getDtLastActionGame($myId);
		if(is_null($maxDt)) {
			return false;
		}

		$select = $this->db->select()
			->from($this->tblFlirt, 'COUNT(id)')
			->where('partner_id = ?', $myId)
			->where('choice = "yes"')
			->where('dt_create >= ?', $maxDt)
			->limit(1);

		$ret = ($this->db->fetchOne($select) > 0) ? true : false;
		return $ret;
	}

	/**
	 * Дата и время последнего действия в игре
	 * @param $myId
	 * @return string
	 */
	private function getDtLastActionGame($myId) {
		$select = $this->db->select()
			->from($this->tblFlirt, array('max_dt' => 'MAX(dt_create)'))
			->where('user_id = ?', $myId)
			->limit(1);

		return $this->db->fetchOne($select);
	}

	public function phoneFormat($number) {
		$phone = '';
		$phone .= substr($number, 0, -10) .' ';
		$phone .= '('.substr($number, -10, 3) .') ';
		$phone .= substr($number, -7, 3) .'-';
		$phone .= substr($number, -4, 2) .'-';
		$phone .= substr($number, -2, 2);
		return $phone;
	}
}
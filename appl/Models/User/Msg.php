<?php

class Models_User_Msg
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	private $myId = null;

	private $tblMsg = array('m'=>'user_msg');
	private $tblMsgText = array('t'=>'user_msg_text');

	private $tblProfile = array('profile'=>'users');
	private $columnProfileAvatar = array('avatar' => 'CONCAT( "/img/people/", `profile`.`sex`, "/", YEAR(`profile`.`birthday`), "/", `profile`.`id`, "/thumbnail.jpg" )');

	/**
	 * @var Models_Users
	 */
	private $ModelsProfile;

	private $textMsg;

	private $msg_text_tr_en = null;
	private $msg_text_tr_ru = null;

	private $translateIs = false;
	private $translateNewText = null;
	private $translate_lang = null;

	#/** @var Zend_Translate */
	#private $translate;

	public function __construct($myId = null) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		#$this->translate = Zend_Registry::get('Zend_Translate');

		$this->myId = (is_null($myId)) ? Models_User_Model::getMyId() : (int) $myId;

		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	public function setProfile(Models_Users $ModelsProfile) {
		$this->ModelsProfile = $ModelsProfile;
	}

	/**
	 * @param null $msg_text_tr_en
	 */
	private function setMsgTextTrEn($msg_text_tr_en)
	{
		$this->msg_text_tr_en = $msg_text_tr_en;
	}

	/**
	 * @return null
	 */
	private function getMsgTextTrEn()
	{
		return $this->msg_text_tr_en;
	}

	/**
	 * @param null $msg_text_tr_ru
	 */
	private function setMsgTextTrRu($msg_text_tr_ru)
	{
		$this->msg_text_tr_ru = $msg_text_tr_ru;
	}

	/**
	 * @return null
	 */
	private function getMsgTextTrRu()
	{
		return $this->msg_text_tr_ru;
	}

	/**
	 * @param $textMsg
	 */
	public function setTextMsg($textMsg)
	{
		$this->textMsg = $textMsg;
	}

	/**
	 * @return mixed
	 */
	public function getTextMsg()
	{
		return $this->textMsg;
	}

	/**
	 * Возвращает все последние сообщения из моей переписки пользователя
	 * @param $myId
	 * @return array
	 */
	public function getLastAll($myId)
	{
		$query = 'SELECT ';
		$query .= 'm.*, t.msg_text, t.send_dt, t.read_dt, t.msg_type, ';
		$query .= 'u.uid, u.first_name, u.sex, u.club_card_dt, ';
		$query .= 'CONCAT( "/img/people/", u.sex, "/", YEAR(u.birthday), "/", u.id, "/thumbnail.jpg") AS avatar ';
		$query .= 'FROM ';
		$query .= '(SELECT u.* FROM `user_msg` AS u WHERE u.`user_id` = '.$myId.' ORDER BY u.`create_dt` DESC) AS m ';
		$query .= 'JOIN `user_msg_text` AS t ON t.id=m.`msg_id` ';
		$query .= 'JOIN `users` AS u ON u.id=m.`partner_id` ';
		$query .= 'WHERE m.`user_id` = '.$myId.' AND m.`del` = "no" GROUP BY m.`partner_id` ORDER BY m.create_dt DESC';

		$res = $this->db->query($query)->fetchAll();

		if($res && $this->ModelsProfile instanceof Models_Users) {
			foreach($res as $key => $msg) {
				// Только для входящей переписки между людьми
				if($msg['msg_type'] == 'msg' && $msg['box'] == 'in') {
					if($msg['access_read'] == 'yes' // Доступ тупо разрешен
						|| $msg['club_card_dt'] >= CURRENT_DATE // У партнера есть КК
						|| $this->ModelsProfile->getClubCard() >= CURRENT_DATE // У меня есть КК
					) {
						$res[$key]['msg_text'] = str_replace(array("\r\n", "\n", "\r"), '<br>', $msg['msg_text']);
					} else {
						$res[$key]['msg_text'] = mb_substr($msg['msg_text'], 0, 5, 'UTF-8').'...';
					}
				}
			}
		}
		//Sas_Debug::sql($select);
		//$res = $this->db->fetchAll($select);
		//Sas_Debug::dump($res);
		return $res;
	}

	/**
	 * Возвращает всю переписку двух пользователей.
	 * @param Models_Users $MyProfile
	 * @param Models_Users $PartnerProfile
	 * @return array
	 */
	public function getTalk(Models_Users $MyProfile, Models_Users $PartnerProfile)
	{
		// Перед тем, как вывести переписку, проверим не нужно ли нам сменить права доступа к ним
		$accessRead = $this->isAccessRead($MyProfile, $PartnerProfile);

		// Если у меня есть право читать переписку,
		if($accessRead == true) {
			// Права на чтение есть,
			// отмечаем все непрочитанные сообщения как прочитанные
			// и за одним выставляем им всем (входящим, исходящие я и так могу читать всегда, потому что они мои)
			// ключ разрешения доступа к чтению каждого сообщения
			$this->setReadDt($MyProfile->getId(), $PartnerProfile->getId());
		}

		$select = $this->db->select()
			->from($this->tblMsg, '*')
			->where('m.user_id = ?', $MyProfile->getId())
			->where('m.partner_id = ?', $PartnerProfile->getId())
			->where('m.del = ?', 'no');

		$select->join($this->tblMsgText, 't.id=m.msg_id', array('msg_text', 'msg_text_tr_ru', 'msg_text_tr_en', 'send_dt', 'read_dt', 'msg_type'))
			->order('t.send_dt ASC');

		#$select->join($this->tblProfile, 'm.partner_id=profile.id', array('first_name'));

		$res = $this->db->fetchAll($select);
		if($res) {
			$isTranslateView = ($MyProfile->getAutomaticTranslation() == 'yes' && $MyProfile->getLang() != $PartnerProfile->getLang()) ? true : false;

			foreach($res as $key => $msg) {
				// Мне написали (входящие сообщения)
				if($msg['box'] == 'in' &&  ($msg['msg_type'] == 'msg' || $msg['msg_type'] == 'event_invite'))
				{
					// замена текста, если есть опция автозамены и есть текст перевода
					if($isTranslateView && (!is_null($msg['msg_text_tr_en']) || !is_null($msg['msg_text_tr_ru'])))
					{
						// да, надо вернуть текст автоперевода
						if (!is_null($msg['msg_text_tr_en'])) {
							$msg['msg_text'] = $msg['msg_text_tr_en'];
							$res[$key]['translate'] = 'en';
						}
						if (!is_null($msg['msg_text_tr_ru'])) {
							$msg['msg_text'] = $msg['msg_text_tr_ru'];
							$res[$key]['translate'] = 'ru';
						}
					}

					// Проверка общих прав доступа и персонально на каждое сообщение
					if($accessRead == true || $msg['access_read'] == 'yes') {
						$res[$key]['msg_text'] = str_replace(array("\r\n", "\n", "\r"), '<br>', $msg['msg_text']);
					} else {
						$textNoReadMsg = ($this->lang == 'ru') ? 'У Вас нет <a href="/user/profile/balance">Клубной карты</a>, поэтому Вы сможете прочитать данное сообщение только через 72 часа.' : 'Without <a href="/'.$this->lang.'/user/profile/balance">Membership card</a> you can read this message only in 72 hours.';
						$res[$key]['msg_text'] = mb_substr($msg['msg_text'], 0, 5, 'UTF-8').'...<br><span style="font-size: 10px;color: #666666;">'.$textNoReadMsg.'</span>';
					}

					$res[$key]['first_name'] = $PartnerProfile->getFirstName();
					$res[$key]['url_profile'] = $PartnerProfile->getUrlProfile();
					$res[$key]['avatar'] = $PartnerProfile->getAvatar();
				}

				// Обработка системных сообщений
				if($msg['box'] == 'in' && $msg['msg_type'] == 'systems') {
					$res[$key]['first_name'] = 'OnTheList';
					$res[$key]['url_profile'] = ($this->lang == 'ru') ? '/user/people/profile/view/udUJEtf1' : '/'.$this->lang.'/user/people/profile/view/udUJEtf1';
					$res[$key]['avatar'] = '/img/people/male/1975/4000/thumbnail.jpg';
				}

				// Текст который написал я
				if($msg['box'] == 'out' && ($msg['msg_type'] == 'msg' || $msg['msg_type'] == 'event_invite')) {
					$res[$key]['first_name'] = $MyProfile->getFirstName();
					$res[$key]['url_profile'] = $MyProfile->getUrlProfile();
					$res[$key]['avatar'] = $MyProfile->getAvatar();
				}

				// Обработка игры Флирт
				if($msg['msg_type'] == 'game_flirt') {
					$res[$key]['first_name'] = ($this->lang == 'ru') ? 'Игра Флирт' : 'Games Flirt';
					$res[$key]['url_profile'] = ($this->lang == 'ru') ? '/user/flirt' : '/'.$this->lang.'/user/flirt';
					$res[$key]['avatar'] = '/img/robots/games_flirt.jpg';
				}

				// Обработка обменом телефонных номеров
				if($msg['msg_type'] == 'exchange_phone') {
					$res[$key]['first_name'] = ($this->lang == 'ru') ? 'Обмен телефонами' : 'Exchange phone';
					$res[$key]['url_profile'] = ($this->lang == 'ru') ? '/user/messages' : '/'.$this->lang.'/user/messages';
					$res[$key]['avatar'] = '/img/robots/exchange_phone.png';
				}
			}
		}

		return $res;
	}

	private function markReadMsg($msg)
	{

	}

	/**
	 * Выполняет глобальную проверку для чтения новых сообщений.
	 *
	 * @param Models_Users $MyProfile
	 * @param Models_Users $PartnerProfile
	 * @return bool
	 */
	public function isAccessRead(Models_Users $MyProfile, Models_Users $PartnerProfile)
	{
		// У меня или партнера есть или появилась КК
		// остальное не важно
		if($MyProfile->getClubCard() >= CURRENT_DATE || $PartnerProfile->getClubCard() >= CURRENT_DATE) {
			return true;
		} else {
			// Был ли обмен контактами
			$ModelContact = new Models_User_ContactExchange();
			$contactStatus = $ModelContact->getLastStatus($MyProfile->getId(), $PartnerProfile->getId()); // yes|no

			// Возможно с момента последней проверки мы обменялись контактами
			if($contactStatus == 'yes') {
				return true;
			}

			// Проверим кол-во сообщений в переписке
			// Кол-во сообщений >= 3 значит читать уже можно!
			if($this->getCntMsg($MyProfile->getId(), $PartnerProfile->getId()) >= 3) {
				return true;
			}

			// Так как все предыдущие проверки провалились, остаётся проверить когда был последний раз
			// доступ к закрытой временем иформации
			// и если время задержки уже истекло, мы предоставим доступ
			// и отметим новое время доступа к закрытому контенту
			$DateAccess = new DateTime($MyProfile->getLastAccessDt());
			$DateAccess->modify(TIME_DELAY);
			if($DateAccess->format('Y-m-d H:i:s') <= CURRENT_DATETIME) {
				// Прошло более 72 (за эти часы отвечает константа TIME_DELAY) часов с
				// момента последнего моего действия в отношении закрытой информации.
				// на основании этого доступ снова разрешается и время снова скидывается
				$MyProfile->setLastAccessDt(CURRENT_DATETIME)->save();
				return true;
			}
		}

		return false;
	}

	/**
	 * Проверка можен ли пользователь писать новые сообщения.
	 *
	 * @param Models_Users $MyProfile
	 * @param Models_Users $PartnerProfile
	 * @param array        $talk
	 * @return bool
	 */
	public function isRecordNewMsg(Models_Users $MyProfile, Models_Users  $PartnerProfile, $talk)
	{
		$cntMsg = count($talk);
		if($MyProfile->getClubCard() >= CURRENT_DATE // у меня есть КК
			|| $PartnerProfile->getClubCard() >= CURRENT_DATE // у партнера есть КК
			|| $PartnerProfile->getId() == 4000 // партнер это админ
			|| $cntMsg >= 3 // кол-во сообщений в переписке больше или равно 3
			|| $cntMsg == 0 // это первое сообщение в переписке
			|| ($talk[$cntMsg-1]['box'] == 'in' && $talk[$cntMsg-1]['access_read'] == 'yes') // Последнее сообщение было от партнера и я его прочитал, так как получил соответствующие права для этого
		) {
			return true;
		}

		return false;
	}

	/**
	 * Возвращает кол-во не прочитанных сообщений и id этих пользователей
	 * @param $myId
	 * @return array
	 */
	public function getNoRead($myId) {
		$select = $this->db->select()
			->from($this->tblMsg, array('partner_id', 'cnt'=>'COUNT(*)'))
			->where('m.user_id = ?', $myId)
			->where('m.box = ?', 'in')
			->group('m.partner_id');

		$select->join($this->tblMsgText, 't.id=m.msg_id', null)
			->where('t.read_dt IS NULL');

		$res = $this->db->fetchAssoc($select);
		#Sas_Debug::dump($res);
		return $res;
	}

	/**
	 * Возвращает общее кол-во не прочитанных сообщений
	 * @return array
	 */
	public function getNoReadAll() {
		$select = $this->db->select()
			->from($this->tblMsg, 'COUNT(*)')
			->where('user_id_to = ?', $this->myId)
			->where('read_dt IS NULL');
		$res = $this->db->fetchOne($select);
		#Sas_Debug::dump($res);
		return $res;
	}

	/**
	 * Возвращает всю переписку двух пользователей.
	 * @param $myId
	 * @param $partnerId
	 * @return array
	 */
	public function getTalkFull($myId, $partnerId)
	{
		$select = $this->db->select()
			->from($this->tblMsg, '*')
			->where('m.user_id = ?', $myId)
			->where('m.partner_id = ?', $partnerId)
			->where('m.del = ?', 'no');

		$select->join($this->tblMsgText, 't.id=m.msg_id', array('msg_text', 'send_dt', 'read_dt', 'msg_type'))
			->order('t.send_dt ASC');

		#$select->join($this->tblProfile, 'm.partner_id=profile.id', array('first_name'));

		$res = $this->db->fetchAll($select);
		return $res;
	}

	/**
	 * Возвращает кол-во сообщений двух пользователей.
	 *
	 * @param $userId
	 * @param $partnerId
	 * @return int
	 */
	public function getCntMsg($userId, $partnerId)
	{
		$select = $this->db->select()
			->from($this->tblMsg, array('cnt' => 'COUNT(id)'))
			->where('user_id = ?', $userId)
			->where('partner_id = ?', $partnerId);

		$res = $this->db->fetchOne($select);
		return $res;
	}

	/**
	 * Возвращает кол-во контактов в переписке с другими людми в сутки
	 * @param $myId
	 * @return string
	 */
	public function getCntMsgDay($myId) {
		$select = $this->db->select()
			->from($this->tblMsg, array('id'))
			//->from($this->tblMsg, array('cnt'=>'COUNT(m.id)'))
			->where('user_id = ?', $myId)
			->where('box = ?', 'out')
			->where('create_dt >= ?', CURRENT_DATE)
			->group('partner_id')
		;
		//Sas_Debug::sql($select);
		$res = $this->db->fetchCol($select);

		return ($res) ? count($res) : 0;
		#$cnt = 0;
		#if($res) { $cnt = count($res); }
		//Sas_Debug::dump($cnt);
		#return $cnt;
		//return $res;
	}

	/**
	 * Устанавливает для сообщения положительный режим доступа к нему
	 * @param $recordId
	 */
	public function setAccessMsgYes($recordId) {
		$this->db->update($this->tblMsg, array('access_read'=>'yes'), $this->db->quoteInto('id = ?', $recordId));
	}

	/**
	 * Устанавливает дату прочтения для всех входящих сообщений между мной и партнером,
	 * а так же, раз я сообщение прочел, значит имею на это право,
	 * соответственно обновляем и ключи доступа к сообщению.
	 * @param $myId
	 * @param $partnerId
	 */
	public function setReadDt($myId, $partnerId)
	{
		$select = $this->db->select()
			->from($this->tblMsg, 'msg_id')
			->where('user_id = ?', $myId)
			->where('partner_id = ?', $partnerId)
			->where('box = ?', 'in');

		$select->join($this->tblMsgText, 't.id=m.msg_id', null)
			->where('t.read_dt IS NULL');

		$rows = $this->db->fetchCol($select);

		if (!empty($rows)) {
			$in = implode(',', $rows);
			$this->db->update($this->tblMsgText, array('read_dt' => CURRENT_DATETIME), 'id IN (' . $in . ')');

			// Скидываем этим сообщениям ключ доступа на yes
			$this->db->update($this->tblMsg, array('access_read' => 'yes'), 'msg_id IN (' . $in . ')');

			// Если партнер сейчас онлайн и он есть на странице с моими сообщениями
			// Кидаем ему в сокет что я прочитал каждое его сообщение
			// TODO: тут функционал отправки в сокет не реализован
		}
	}

	/**
	 * Проверяет нет ли дубликата записи за последние 10 сек.
	 * @param $myId
	 * @param $partnerId
	 * @param $textMsg
	 * @return string
	 */
	public function isTakeMsg($myId, $partnerId, $textMsg) {
		$select = $this->db->select()
			->from($this->tblMsg, 'COUNT(id)')
			->where('user_id_from = ?', $myId)
			->where('user_id_to = ?', $partnerId)
			->where('msg_text = ?', $textMsg)
			->where('send_dt >= ?', date('Y-m-d H:i:s', time() - 10));
		return $this->db->fetchOne($select);
	}

	/**
	 * Сохранение системного сообщения от имени администратора.
	 *
	 * @param $partnerId
	 * @param $textMsg
	 * @return string
	 */
	public function saveSystemsMsg($partnerId, $textMsg)
	{
		// Пишем непосредственно сообщение
		$msgId = $this->saveMsgOnly($textMsg, 'systems');

		// Потом пишем получателю
		$this->saveMsgPartner($msgId, 4000, $partnerId, 'yes');

		return $msgId;
	}

	/**
	 * Проверка нужно ли выполнять автоматический перевод текста.
	 *
	 * @param Models_Users $ModelProfileMy
	 * @param Models_Users $ModelProfilePartner
	 * @return bool  true - переводить | false - не переводить
	 */
	private function isTranslate(Models_Users $ModelProfileMy, Models_Users $ModelProfilePartner)
	{
		$myLang = $ModelProfileMy->getLang();
		$fLang = 'getLang'.ucfirst($myLang);

		// Не переводить если авто перевод отключен
		if($ModelProfilePartner->getAutomaticTranslation() == 'no') return false;

		// Проверяем необходимость перевода
		if($myLang == $ModelProfilePartner->getLang()
			|| $ModelProfilePartner->$fLang() == 'yes'
		) {
			return false;
		}

		return true;
	}

	/**
	 * Возвращает ключ, был ли выполнен автоматический перевод
	 * @return bool
	 */
	public function getTranslateIs()
	{
		return $this->translateIs;
	}

	/**
	 * @return boolean
	 */
	public function getTranslateNewText()
	{
		return $this->translateNewText;
	}

	public function getTranslateLang()
	{
		return $this->translate_lang;
	}

	/**
	 * Сохраняет новое сообщение
	 *
	 * @param Models_Users $ModelProfileMy
	 * @param Models_Users $ModelProfilePartner
	 * @param              $textMsg
	 * @param              $accessRead
	 * @param string       $msg_type string msg|game_flirt
	 *
	 * @return int ID текста сообщения
	 */
	public function saveNewMsg(Models_Users $ModelProfileMy, Models_Users $ModelProfilePartner, $textMsg, $accessRead, $msg_type = 'msg')
	{
		$myId = $ModelProfileMy->getId();
		$partnerId = $ModelProfilePartner->getId();

		$textMsg = Sas_Filter_TextReplaceLinks::get($textMsg);

		// Определяем необходимость автоматического перевода
		if($this->isTranslate($ModelProfileMy, $ModelProfilePartner)) {
			$tr = new Sas_Translate_Yandex();
			$tr->translateText($textMsg, $ModelProfilePartner->getLang());
			if(!$tr->isError()) {
				$lang = $tr->getLangTo();
				$fLang = 'setMsgTextTr'.ucfirst($lang);
				$this->$fLang($tr->getTextTranslate());
				$this->translateIs = true;
				$this->translateNewText = $tr->getTextTranslate();
				$this->translate_lang = $lang;
			}
		}

		$this->setTextMsg($textMsg);

		// Пишем непосредственно сообщение
		$msgId = $this->saveMsgOnly($textMsg, $msg_type);

		// Записанное сообщение пишем пользователям "в ящики"
		// Сначала пишем мне
		$this->saveMsgMy($msgId, $myId, $partnerId);

		// Потом пишем получателю
		$this->saveMsgPartner($msgId, $myId, $partnerId, $accessRead);

		return $msgId;
	}

	/**
	 * Запись МОЕГО сообщения в мой ящик (отправитель)
	 * @param $msgId
	 * @param $myId
	 * @param $partnerId
	 */
	private function saveMsgMy($msgId, $myId, $partnerId) {
		$data['msg_id'] = $msgId;
		$data['user_id'] = $myId;
		$data['partner_id'] = $partnerId;
		$data['box'] = 'out'; // исходящее
		$data['create_dt'] = CURRENT_DATETIME;
		$data['access_read'] = 'yes'; // я всегда могу читать свои сообщения
		$data['del'] = 'no'; // сообщение не удалено естественно
		$this->db->insert($this->tblMsg, $data);
	}

	/**
	 * Запись сообщения в ящик ПАРТНЕРА (получатель)
	 *
	 * @param $msgId
	 * @param $myId
	 * @param $partnerId
	 * @param $accessRead string Права для чтения этого сообщения
	 */
	private function saveMsgPartner($msgId, $myId, $partnerId, $accessRead){
		$data['msg_id'] = $msgId;
		$data['user_id'] = $partnerId;
		$data['partner_id'] = $myId;
		$data['box'] = 'in'; // входящее
		$data['create_dt'] = CURRENT_DATETIME;
		$data['access_read'] = $accessRead;
		$data['del'] = 'no'; // сообщение не удалено естественно
		$this->db->insert($this->tblMsg, $data);
	}

	/**
	 * Сохранение непосредственно текст сообщения, для дальнейшего его распределения по "ящикам".
	 *
	 * @param string $textMsg
	 * @param string $msg_type msg|game_flirt|exchange_phone
	 * @return string lastInsertId
	 */
	private function saveMsgOnly($textMsg, $msg_type)
	{
		// Пишем сообщение
		$data['msg_text'] = $textMsg;
		$data['send_dt'] = CURRENT_DATETIME;
		$data['msg_type'] = $msg_type;

		if(!is_null($this->getMsgTextTrEn())) {
			$data['msg_text_tr_en'] = $this->getMsgTextTrEn();
		}

		if(!is_null($this->getMsgTextTrRu())) {
			$data['msg_text_tr_ru'] = $this->getMsgTextTrRu();
		}

		$this->db->insert($this->tblMsgText, $data);
		return $this->db->lastInsertId($this->tblMsgText, 'id');
	}

	/**
	 * Возвращает последнее сообщение из переписки
	 * @param $myId
	 * @param $partnerId
	 * @return array
	 */
	public function getLastMsg($myId, $partnerId) {
		$select = $this->db->select()
			->from($this->tblMsg, '*')
			->where('(`user_id_from` = '.$myId.' AND `user_id_to` = '.$partnerId.') || ((`user_id_from` = '.$partnerId.' AND `user_id_to` = '.$myId.'))')
			->order('id DESC');

		return $this->db->fetchRow($select);
	}

	/**
	 * Скрывает сообщения (для пользователей выгладит как Удаление)
	 * @param $msgId
	 */
	public function hideMsg($msgId)
	{
		// Скрываем это сообщение для пользователя
		$where = $this->db->quoteInto('msg_id = ?', $msgId);
		$where .= ' AND ';
		$where .= $this->db->quoteInto('user_id = ?', $this->myId);
		$this->db->update($this->tblMsg, array('del'=>'yes'), $where);
	}

	/**
	 * Скрывает все сообщения в переписке пользователя
	 * @param $partnerId
	 */
	public function hideAllMsg($partnerId)
	{
		// Скрываем это сообщение для пользователя
		$where = $this->db->quoteInto('partner_id = ?', $partnerId);
		$where .= ' AND ';
		$where .= $this->db->quoteInto('user_id = ?', $this->myId);
		$this->db->update($this->tblMsg, array('del'=>'yes'), $where);
	}

	/**
	 * Сохраняет в переписку предложение по обмену номерами телефонов.
	 *
	 * @param Models_Users $My
	 * @param Models_Users $Partner
	 */
	public function exchangePhoneSend(Models_Users $My, Models_Users $Partner)
	{
		// Пишем сообщение для Меня
		$textMy = $My->getFirstName().', ' . $My->t('Вы отправили предложение обменяться телефонными номерами.');
		$msgIdMy = $this->saveMsgOnly($textMy, 'exchange_phone');
		$this->saveMsgMy($msgIdMy, $My->getId(), $Partner->getId());

		// Пишем сообщение для Партнера
		$textPartner = $Partner->getFirstName().', '. $My->getFirstName().' '.$My->t('предлагает Вам обменяться телефонными номерами. Вы согласны на обмен?');
		$msgIdPartner = $this->saveMsgOnly($textPartner, 'exchange_phone');
		//$this->saveMsgPartner($msgIdPartner, $My->getId(), $Partner->getId(), ($Partner->getClubCard() >= CURRENT_DATE) ? 'yes' : 'no');
		$this->saveMsgPartner($msgIdPartner, $My->getId(), $Partner->getId(), 'yes');

		// TODO: Отправить сообщения в пуш!
	}

	/**
	 * Отправляет сообщения пользователям об отрицательном решении по обмену номерами телефонов.
	 * @param Models_Users $My
	 * @param Models_Users $Partner
	 */
	public function exchangePhoneNo(Models_Users $My, Models_Users $Partner)
	{
		// Пишем сообщение для Меня
		$textMy = $My->getFirstName().', '.$My->t('Вы отказались обменяться телефонными номерами.');
		$msgIdMy = $this->saveMsgOnly($textMy, 'exchange_phone');
		$this->saveMsgMy($msgIdMy, $My->getId(), $Partner->getId());

		// Пишем сообщение для Партнера
		$textPartner = $Partner->getFirstName().', '.$My->t('Вам отказали в обмене телефонными номерами.');
		$msgIdPartner = $this->saveMsgOnly($textPartner, 'exchange_phone');
		#$this->saveMsgPartner($msgIdPartner, $My->getId(), $Partner->getId(), ($Partner->getClubCard() >= CURRENT_DATE) ? 'yes' : 'no');
		#$this->saveMsgPartner($msgIdPartner, $My->getId(), $Partner->getId(), 'yes');

		// Небольшой хак, для того, чтобы у сообщения не появились кнопки принятия решения
		// Смысл хака отправка сообщения партнёру в out (отправленные), типа он сам себе это отправил
		// соответственно меняем местами пользователей
		$this->saveMsgMy($msgIdPartner, $Partner->getId(), $My->getId());

		// TODO: Отправить сообщения в пуш!
	}

	/**
	 * Отправляет сообщения пользователям о положительном решении по обмену номерами телефонов.
	 * @param Models_Users $My
	 * @param Models_Users $Partner
	 */
	public function exchangePhoneYes(Models_Users $My, Models_Users $Partner)
	{
		// Пишем сообщение для Меня
		$text = $My->getFirstName().' '.$My->t('и').' '. $Partner->getFirstName() .', '.$My->t('Вы обменялись телефонными номерами.');
		$text .= '<br>'.$My->t('Номер телефона пользователя Вы всегда можете посмотреть в его/ее профиле.');
		$msgIdMy = $this->saveMsgOnly($text, 'exchange_phone');
		$this->saveMsgMy($msgIdMy, $My->getId(), $Partner->getId());

		// Пишем сообщение для Партнера - оно точно такое как и для Меня
		$msgIdPartner = $this->saveMsgOnly($text, 'exchange_phone');

		// Небольшой хак, для того, чтобы у сообщения не появились кнопки принятия решения
		// Смысл хака отправка сообщения партнёру в out (отправленные), типа он сам себе это отправил
		// соответственно меняем местами пользователей
		$this->saveMsgMy($msgIdPartner, $Partner->getId(), $My->getId());

		// TODO: Отправить сообщения в пуш!
	}

	public function eventInvite(Models_Users $My, Models_Users $Partner, Models_User_Event $Event)
	{
		// Отмечаем что я сам иду на мероприятие
		if(!$Event->isGoEvent($My->getId())) {
			$Event->iGoEvent($My->getId(), $Event->getEventId(), 'yes');
		}

		// Отмечаем что я пригласил на это мероприятие партнера
		$Event->setInvite($My->getId(), $Partner->getId(), $Event->getEventId());

		$event = $Event->getEvent($Event->getEventId());
		$eventDateStart = new DateTime($event['date_start']);
		$eventLinkMy = ($My->getLang() == 'ru') ? '' : '/'.$My->getLang();
		$eventLinkMy .= '/user/event/view/id/'.$event['id'];

		// Оправляем тексты приглашения

		// Пишем сообщение для Меня
		$text = $Partner->getFirstName().', '.$My->t('я приглашаю Вас посетить вместе со мной мероприятие:');
		$text .= '<br>'.$event['title'].', '.$My->t('которое состоится').' '.date_format($eventDateStart, 'd.m.Y').' '.$My->t('в').' '.date_format($eventDateStart, 'H:i').', ' . $event['point_name'].'. <a href="'.$eventLinkMy.'">'.$My->t('Подробнее о мероприятии').'</a>';
		#Sas_Debug::dump($event);
		#Sas_Debug::dump($text);
		$msgId = $this->saveNewMsg($My, $Partner, $text, 'yes', 'event_invite');
		#$msgIdMy = $this->saveMsgOnly($text, 'event_invite');

		#$this->saveMsgMy($msgIdMy, $My->getId(), $Partner->getId());

		return $msgId;
	}
}

<?php

/**
 * Модель активации пользовательского профиля
 */
class Models_User_Activation
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	//private $tableProfile = 'users_data';
	private $tableProfile = 'users';
	private $key = null;

	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');

	public function __construct($key) {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->key = $key;
	}

	/**
	 * Запрос информации по активизируемому аккаунту
	 * @return array
	 */
	public function activation()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from(array('profile'=>$this->tableProfile), '*');
		$select->columns($this->columnProfileImg);
		$select->where('activation_key = ?', $this->key);
		$select->where('`psw` IS NULL');
		$select->limit(1);
		#Sas_Debug::dump($select->__toString());
		$row = $this->db->fetchRow($select);

		$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
		if(file_exists($docRoot . $row['img'] . 'thumbnail.jpg')) {
			$row['img'] .= 'thumbnail.jpg';
		} else {
			unset($row['img']);
		}

		Models_Actions::add(47, $row['id']); // Попытка активации аккаунта

		return $row;
	}

	/**
	 * Проверка введенных данных при активации аккаунта
	 * @param $data
	 * @return bool
	 */
	public function isValid($data)
	{
		$dataOk['id'] = (int)$data['id'];

		// Компания
		$company = htmlspecialchars(strip_tags(trim($data['company'])));
		if(empty($company)) {
			return false;
		}
		$dataOk['company'] = $company;

		// ВУЗ
		$education = htmlspecialchars(strip_tags(trim($data['education'])));
		if(empty($education)) {
			return false;
		}
		$dataOk['education'] = $education;

		// Увлечения (хобби)
		$hobby = htmlspecialchars(strip_tags(trim($data['hobby'])));
		if(empty($hobby)) {
			return false;
		}
		$dataOk['hobby'] = $hobby;

		// Любимые места
		$favPlaces = htmlspecialchars(strip_tags(trim($data['fav_places'])));
		if(empty($favPlaces)) {
			return false;
		}
		$dataOk['fav_places'] = $favPlaces;

		// Рост
		$height = (int)$data['height'];
		if($height < 150 || $height > 220) {
			return false;
		}
		$dataOk['height'] = $height;

		// Дети
		$dataOk['children'] = ($data['children'] == 'yes') ? 'yes' : 'no';

		// Сигареты
		$dataOk['smoking'] = ($data['smoking'] == 'yes') ? 'yes' : 'no';

		// Языки
		/*$dataOk['filter_lang_ru'] = ($data['filter_lang_ru'] == 'yes') ? 'yes' : 'no';
		$dataOk['filter_lang_en'] = ($data['filter_lang_en'] == 'yes') ? 'yes' : 'no';
		$dataOk['filter_lang_fr'] = ($data['filter_lang_fr'] == 'yes') ? 'yes' : 'no';
		$dataOk['filter_lang_de'] = ($data['filter_lang_de'] == 'yes') ? 'yes' : 'no';
		$dataOk['filter_lang_it'] = ($data['filter_lang_it'] == 'yes') ? 'yes' : 'no';
		$dataOk['filter_lang_es'] = ($data['filter_lang_es'] == 'yes') ? 'yes' : 'no';*/

		/*$dataOk['filter_children'] = null;
		$dataOk['filter_smoking'] = null;

		$dataOk['filter_age_min'] =  20;
		$dataOk['filter_age_max'] =  60;

		$dataOk['filter_height_min'] = 150;
		$dataOk['filter_height_max'] = 220;*/

		// Пароли
		$password = $data['password'];
		$password2 = $data['password2'];
		if ($password != $password2) {
			return false;
		}
		$dataOk['psw'] = $password;

		// Фото
		$validFormats = array('image/jpeg');
		$limitMb = 1024 * 1024 * 1024 * 5; // 5Mb

		// Нет текущего фото и нет данных с новым фото
		$docRoot = ltrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
		if(empty($_FILES['photoFile']) && !file_exists($docRoot . $data['img'])) {
			return false;
		}

		// Наличие новых данных с фото
		if($_FILES['photoFile']['error'] == 0 && $_FILES['photoFile']['size'] < $limitMb && in_array($_FILES['photoFile']['type'], $validFormats))
		{
			$pathImg  = '/img/people/'. $data['sex'] . '/' . Models_User_Model::getMyYear($data['birthday']). '/' . $data['id'] . '/';

			$img = new Sas_Image();

			$img->configSaveOriginal('original', 'jpg');
			$img->configSaveOptimal(1000, 750, 'optimal', 'jpg');
			$img->configSaveCrop(500, 'thumbnail', 'jpg');

			$img->setImgDir($pathImg, true);

			$saveImg = $img->save($_FILES['photoFile']['tmp_name']);

			if(!$saveImg) {
				return false;
			}
		}

		return $dataOk;
	}

	/**
	 * Сохранение нового пользователя
	 * @param $data
	 * @return bool
	 */
	public function save($data)
	{
		$update['psw'] =  new Zend_Db_Expr('MD5("'.$data['password'].'")');
		$update['company'] = $data['company'];
		$update['education'] = $data['education'];
		$update['hobby'] = $data['hobby'];
		$update['fav_places'] = $data['fav_places'];
		$update['height'] = $data['height'];
		$update['children'] = $data['children'];
		$update['smoking'] = $data['smoking'];
		$update['status'] = 3;
		$update['activate_dt'] = date('Y-m-d H:i:s');

		$update['balance_bonus'] = $data['startBonus'];

		$update['filter_lang_ru'] = $data['filter_lang_ru'];
		$update['filter_lang_en'] = $data['filter_lang_en'];
		$update['filter_lang_fr'] = $data['filter_lang_fr'];
		$update['filter_lang_de'] = $data['filter_lang_de'];
		$update['filter_lang_it'] = $data['filter_lang_it'];
		$update['filter_lang_es'] = $data['filter_lang_es'];

		/*$update['filter_children'] = $data['filter_children'];
		$update['filter_smoking'] = $data['filter_smoking'];

		$update['filter_age_min'] =  $data['filter_age_min'];
		$update['filter_age_max'] =  $data['filter_age_max'];

		$update['filter_height_min'] = 150;
		$update['filter_height_max'] = 220;*/

		$update['filter_children'] = null;
		$update['filter_smoking'] = null;

		$update['filter_age_min'] =  20;
		$update['filter_age_max'] =  60;

		$update['filter_height_min'] = 150;
		$update['filter_height_max'] = 220;

		$update['msg_invite_email'] = 'yes';
		$update['msg_invite_sms']   = 'yes';
		$update['msg_communication_email'] = 'yes';
		$update['msg_admin_email'] = 'yes';
		$update['msg_favorite_email'] = 'yes';

		$update['free_day'] = 'a:13:{i:0;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"0";i:6;s:1:"1";}i:1;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:2;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:3;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:4;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:5;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:6;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:7;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:8;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"0";i:5;s:1:"1";i:6;s:1:"1";}i:9;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:10;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:11;a:7:{i:0;s:1:"1";i:1;s:1:"1";i:2;s:1:"1";i:3;s:1:"1";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"1";}i:12;a:7:{i:0;s:1:"0";i:1;s:1:"0";i:2;s:1:"0";i:3;s:1:"0";i:4;s:1:"1";i:5;s:1:"1";i:6;s:1:"0";}}';

		$where = $this->db->quoteInto('id = ?', (int) $data['id']);
		#Sas_Debug::dump($update, 'UPDATE');
		#Sas_Debug::dump($where, 'WHERE');

		$res = $this->db->update($this->tableProfile, $update, $where);
		if ($res == 1) {

			// Запись в лог, если при регистрации начисленны бонусные караты
			if($data['startBonus'] > 0) {
				$data = array(
					'user_id' => (int) $data['id'],
					'transaction_name' => 'Start bonus', // TODO: Вывести сообщение на языке пользователя
					'amount' => $update['balance_bonus'],
					'date_create' => date('Y-m-d H:i:s')
				);
				$this->db->insert('user_balance_log', $data);
			}

			/*$ModelProfile = new Models_User_Profile();
			$profile = $ModelProfile->getProfile($data['id']);

			$ModelBalance = new Models_User_Balance();
			$ModelBalance->initProfile($profile);
			$ModelBalance->addBonus(200, 'Start bonus');*/
			Models_Actions::add(46, $data['id']); // Полностью зарегистрирован новый пользователь
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Добавляет бонусные караты другу
	 * @param $friendPromoKey
	 */
	public function addFriendKarat($friendPromoKey)
	{
		$select = $this->db->select()
			->from($this->tableProfile, 'id')
			->where('promo_key = ?', $friendPromoKey)
			->limit(1);
		$friendId = $this->db->fetchOne($select);

		if(is_numeric($friendId))
		{
			$ModelBalance = new Models_User_Balance();
			$ModelBalance->init($friendId);
			$ModelBalance->addBonusFriend();
		}
	}
}
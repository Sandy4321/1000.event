<?

class Models_Users
{
	/** @var Zend_Db_Adapter_Abstract */
	private $db;

	/** @var Zend_Translate */
	private $translate;

	private $id;

	/** @var string Текущий язык интерфейса */
	private $langInterface = LANG_DEFAULT;

	private $tblProfile = array('u' => 'users');

	public function __construct($userId = null)
	{
		$this->db = Zend_Registry::get('db');
		$this->langInterface = Zend_Controller_Front::getInstance()->getPlugin('Sas_Controller_Plugin_Language')->getLocale();
		if (empty($this->langInterface)) $this->langInterface = LANG_DEFAULT;
		$this->translate     = Zend_Registry::get('Zend_Translate');

		$this->setMyId();

		if(!is_null($userId) && is_numeric($userId) && $userId > 0) {
			$this->setProfile($userId);
		}
	}

	public function setProfile($userId)
	{
		$select = $this->db->select()
			->from($this->tblProfile, '*')
			->where('id = ?', $userId)
			->limit(1);

		if(1==1) {
			$select->join('city', 'city.id = u.city_id', array('name'=>'name_'.$this->langInterface));
		}

		//Sas_Debug::sql($select);
		//Sas_Debug::dump('любая переменная', 'Название');
		//echo $select->__toString();

		$row = $this->db->fetchRow($select);
		if($row) {
			foreach ($row as $item) {
				//
			}
		}
		return $row;
	}

	/**
	 * Перевод текстов через Zend_Translate
	 * @param $msg
	 * @return mixed
	 */
	public function t($msg) {
		return $this->translate->translate($msg);
	}

	/**
	 * @param $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}
}
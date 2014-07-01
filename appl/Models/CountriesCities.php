<?php

/**
 * Модель стран и городв
 *
 * Обеспечивает взаимодействие при работе со странами и городами
 */
class Models_CountriesCities
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $tableCountry = 'countries';
	private $columnCountry = array();

	private $tableCity   = 'cities';
	private $columnCity = array();

	public function __construct($lang = null) {
		$this->db = Zend_Registry::get('db');

		if(is_null($lang)) {
			$this->lang = Zend_Controller_Front::getInstance()
				->getPlugin('Sas_Controller_Plugin_Language')
				->getLocale();
		} else {
			$this->lang = $lang;
		}

		$this->columnCity = array(
			'city_id'    => 'id',
			'country_id' => 'country_id',
			'city_name'  => 'name_' . $this->lang,
			'time_zone'
		);
		$this->columnCountry = array(
			'id',
			'name' => 'name_' . $this->lang,
			'phone_code'
		);
	}

	/**
	 * Возвращает все города
	 *
	 * @return array
	 */
	public function getCityAll()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableCity, $this->columnCity);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает все города
	 *
	 * @return array
	 */
	public function getCityAllToSelectForm()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableCity, array('id', 'name'=>'name_'.$this->lang));

		return $this->db->fetchPairs($select);
	}

	/**
	 * Возвращает все страны
	 *
	 * @return array
	 */
	public function getCountryAll()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableCountry, $this->columnCountry);

		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает информацию по стране по её ID
	 * @param $countryId
	 * @return array
	 */
	public function getCountry($countryId)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableCountry, $this->columnCountry);
		$select->where('id = ?', (int) $countryId);
		$select->limit(1);

		return $this->db->fetchRow($select);
	}

	/**
	 * Поиск страны по начальным буквам (для ajax)
	 * @param $like
	 * @return array
	 */
	public function searchCountry($like)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableCountry, array('id', 'country_name'=>'name_'.$this->lang));
		$select->where('name_'.$this->lang.' LIKE ?', $like.'%');

		#$select->from($this->tableCountry, array('id', 'name'=>'name_en'));
		#$select->where('name_en LIKE ?', $like.'%');

		return $this->db->fetchAll($select);
	}

	/**
	 * Поиск города по начальным буквам
	 * @param $like
	 * @return array
	 */
	public function searchCity($like)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableCity, array('city_id'=>'id', 'city_name'=>'name_'.$this->lang));

		$select->where($this->tableCity.'.name_ru LIKE "'.$like.'%" OR '.$this->tableCity.'.name_en LIKE "'.$like.'%"');


		return $this->db->fetchAll($select);
	}

	/**
	 * Поиск города по начальным буквам (для ajax), возврат вместе с названием страны
	 * @param $like
	 * @return array
	 */
	public function searchCityCountry($like)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableCity, array('city_id'=>'id', 'city_name'=>'name_'.$this->lang));
		$select->join($this->tableCountry, $this->tableCountry.'.id = ' .$this->tableCity.'.country_id', array('country_id'=>'id', 'country_name'=>'name_'.$this->lang));

		$select->where($this->tableCity.'.name_ru LIKE "'.$like.'%" OR '.$this->tableCity.'.name_en LIKE "'.$like.'%"');


		return $this->db->fetchAll($select);
	}

	/**
	 * Возвращает город по его ID
	 * @param $cityId
	 * @return array
	 */
	public function getCity($cityId)
	{
		$select = $this->db->select()
			->from($this->tableCity, $this->columnCity)
			->where('id = ?', (int) $cityId)
			->limit(1);

		return $this->db->fetchRow($select);
	}

	/**
	 * По ID страны возвращает все города в стране
	 * @param $countryId
	 * @return array
	 */
	public function getCitiesInCountry($countryId)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableCountry, $this->columnCountry);
		$select->join($this->tableCity, $this->tableCountry.'.id = ' .$this->tableCity.'.country_id', $this->columnCity);
		$select->where($this->tableCountry.'.id = ?', (int) $countryId);

		return $this->db->fetchAll($select);
	}

	/**
	 * По ID города возвращает страну в которой это город
	 * @param $cityId
	 * @return array
	 */
	public function getCountryWhichCity($cityId)
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableCity, $this->columnCity);
		$select->join($this->tableCity, $this->tableCountry.'.id = ' .$this->tableCity.'.country_id', $this->columnCountry);
		$select->where('id = ?', (int) $cityId);

		return $this->db->fetchRow($select);
	}

	/**
	 * Добавляет (сохраняет) в бд новый город.
	 * @param $countryId
	 * @param $cityNameRu
	 * @param $cityNameEn
	 * @param $timeZone
	 * @return int
	 */
	public function addNewCity($countryId, $cityNameRu, $cityNameEn, $timeZone)
	{
		$insert = array(
			'country_id' => (int) $countryId,
			'name_ru' => $cityNameRu,
			'name_en' => $cityNameEn,
			'time_zone' => $timeZone
		);
		return $this->db->insert($this->tableCity, $insert);
	}
}

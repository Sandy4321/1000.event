<?php

class Sas_Cache_Select
{
	static private $frontendOptions = array('lifetime'  => 600, 'automatic_serialization' => true);
	static private $backendOptions  = array();
	private $cacheName = null;

	/**
	 * ZendCache
	 *
	 * @var Zend_Cache_Frontend
	 */
	static private $cache = null;

	private $cacheNo = false;

	public function __construct($cache_name, $cache_no = false)
	{
		$this->cacheNo = $cache_no;
		#$this->cacheNo = true;
		if ($this->cacheNo === false) {
			self::$backendOptions = array(
				'cache_dir' => PATH_DIR_HOST .
					DIRECTORY_SEPARATOR .
					'cache' .
					DIRECTORY_SEPARATOR .
					'db' .
					DIRECTORY_SEPARATOR .
					'select'
			);

			if (is_null(self::$cache)) {
				self::initFactory();
			}

			$name = explode('::', $cache_name);
			$dirName = $name[0];
			$this->setDir($dirName);
			$this->cacheName = $name[1];
		}
	}

	public function load()
	{
		if ($this->cacheNo === false) {
			return self::$cache->load($this->cacheName);
		} else {
			return false;
		}
	}

	/**
	 * Сохранение кеша
	 * @param     $result
	 * @param int $time время в секундах (600 сек = 10 мин.)
	 */
	public function save($result, $time = 600)
	{
		if ($this->cacheNo === false) {
			self::$cache->save($result, $this->cacheName, array(), $time);
		}
	}

	static private function initFactory()
	{
		self::$cache = Zend_Cache::factory('Core', 'File', self::$frontendOptions, self::$backendOptions);
	}

	static public function setFrontendOptions($name, $value)
	{
		self::$frontendOptions[$name] = $value;
		self::initFactory();
	}

	static public function setBackendOptions($name, $value)
	{
		self::$backendOptions[$name] = $value;
		self::initFactory();
	}

	private function setDir($dirName)
	{
		$newDir = self::$backendOptions['cache_dir'].DIRECTORY_SEPARATOR.$dirName;
		if (!is_dir($newDir)) {
			if (!mkdir($newDir, 0777)) {
				throw new Sas_Cache_Exception('Не могу создать директорию: ' . $dirName);
			}
		}
		$this->setBackendOptions('cache_dir', $newDir);
	}

}
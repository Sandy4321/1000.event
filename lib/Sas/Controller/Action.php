<?php
session_start();
/**
 * SiteActionSystems (SAS)
 * 
 * Контроллер действий.
 * 
 * @category Sas
 * @package Sas_Controller
 * @subpackage Sas_Controller_Action
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2008 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 2.1
 */

class Sas_Controller_Action extends Zend_Controller_Action
{
	private static $site_charset = SITE_CHARSET;
	private static $lang = LANG_DEFAULT;
	private static $cntRun = 0;

	public $isRender = true;
	
	public function init()
	{
		$this->setEncoding(self::$site_charset);
		$this->setViewSeparator(' :: ');
		$this->setMyHelper();

		// Есть еще опции см. в мануале
        $optionsMvc = array(
        	'contentKey' => 'content',
        	'layoutPath' => PATH_DIR_LAYOUT_SITE
        );
		$this->_startLayout($optionsMvc);
		$this->_setStartPluginInfo();
		self::$lang = $this->getRequest()->getParam(LANG_KEY);

		// Единственный запуск!
		if (self::$cntRun == 0) {
			// Установка промо куки
			if(strlen($this->getRequest()->getParam('promo-key')) == 32) {
				$this->setCookiePromoKey($this->getRequest()->getParam('promo-key'));
				#Sas_Debug::dump($this->getRequest()->getParam('promo-key'), 'СТАВИМ КУКУ');
			}
		}

		$this->initSas();

		self::$cntRun++;
	}

	public function initSas()
	{
	}

	public function getLang() {
		return self::$lang;
	}

	public function _redirect($url, $option = array()) {

		if(self::$lang != LANG_DEFAULT) {
			$url = ltrim($url, '/');
			$url = '/' . self::$lang . '/' . $url;
		}
		parent::_redirect($url, $option);
	}
	
	/**
	 * Устанавливает новый разделитель для заголовков страниц в TITLE.
	 * 
	 * @param string $separator Новый символ(ы) для разделителя.
	 */
	public function setViewSeparator($separator) {
		$this->view->headTitle()->setSeparator($separator);
	}
	
	public function setMyHelper() {
		$this->view->addHelperPath(PATH_DIR_LIB . DIRECTORY_SEPARATOR . 'Sas'
		. DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR
		. 'Helper', 'Sas_View_Helper');
	}

	protected function _startLayout($options)
	{
		Zend_Layout::startMvc($options);
		$this->view->layout()->setLayoutPath($options['layoutPath']);
	}

	/**
	 * Устанавливает кодировку для страниц вида.
	 * 
	 * При инициализации объекта устанавливается UTF-8.
	 *
	 * @param string $newEncoding
	 */
	public function setEncoding($newEncoding)
	{
		self::$site_charset = $newEncoding;
		$this->view->setEncoding(self::$site_charset);
	}
	
	/**
	 * Возвращает кодировку для страниц вида.
	 * 
	 * @return string
	 */
	public function getEncoding()
	{
		return self::$site_charset;
	}
	
	protected function _setStartPluginInfo() {
		$this->view->startModule = $this->getModuleStart();
		$this->view->startController = $this->getControllerStart();
		$this->view->startAction = $this->getActionStart();
	}
	
	public function getModuleStart()
	{
		return Sas_Controller_Plugin_Start::$MODULE;
	}
	
	public function getControllerStart()
	{
		return Sas_Controller_Plugin_Start::$CONTROLLER;
	}
	
	public function getActionStart()
	{
		return Sas_Controller_Plugin_Start::$ACTION;
	}
	
	
	/**
	 * Отключает рендеринг текущего действия.
	 */
	public function noRender() {
		$this->_helper->viewRenderer->setNoRender();
		$this->isRender = false;
	}

	/**
	 * Отключает layout и рендеринг текущего действия.
	 */
	protected function ajaxInit() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		$this->isRender = false;
	}

	protected function getJson($json) {
		$sendJson = $this->_helper->json($json);
		$this->getResponse()->appendBody($sendJson);

		$this->isRender = false;

		return $sendJson;
	}

	/**
	 * Переводчик.
	 *
	 * @param $string
	 * @return mixed
	 */
	final function t($string) {
		return $this->view->t($string);
	}

	public function setCookiePromoKey($keyValue) {
		setcookie('promo-key', $keyValue, time() + 31536000, '/', $_SERVER['HTTP_HOST']);
	}

	public function getCookiePromoKey() {
		return (!empty($_COOKIE['promo-key'])) ? $_COOKIE['promo-key'] : false;
	}

	public function deleteCookiePromoKey() {
		setcookie('promo-key', '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
		unset($_COOKIE['promo-key']);
	}
}
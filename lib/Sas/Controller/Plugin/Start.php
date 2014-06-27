<?php

class Sas_Controller_Plugin_Start extends Zend_Controller_Plugin_Abstract
{
	static public $MODULE;
	static public $CONTROLLER;
	static public $ACTION;
	
	public function routeShutdown(Zend_Controller_Request_Abstract $request)
	{
		self::$MODULE     = $request->getModuleName();
		self::$CONTROLLER = $request->getControllerName();
		self::$ACTION     = $request->getActionName();
	}
	
	/**
	 * Возвращает название стартового модуля
	 * @return string
	 */
	public function getStartModule() {
		return self::$MODULE;
	}
	
	/**
	 * Возвращает название стартового контроллера
	 * @return string
	 */
	public function getStartController() {
		return self::$CONTROLLER;
	}
	
	/**
	 * Возвращает название стартового действия
	 * @return string
	 */
	public function getStartAction() {
		return self::$ACTION;
	}
}
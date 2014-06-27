<?php

/**
 * SiteActionSystems (SAS)
 * 
 * Плагин проверки url на соответствие переданному.
 * 
 * @category Sas
 * @package Sas_View
 * @subpackage Sas_View_Helper
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2008 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 2.1
 */
class Sas_View_Helper_CheckActive extends Zend_View_Helper_Abstract
{
    public function CheckActive($checkUrl, $printString=null)
    {
    	// Инициализация переменных
    	$seg = array();
    	$check = false;
    	
    	// Получаем стартовые настройки
    	$sm = Sas_Controller_Plugin_Start::$MODULE;
    	$sc = Sas_Controller_Plugin_Start::$CONTROLLER;
    	$sa = Sas_Controller_Plugin_Start::$ACTION;
    	
    	// Разбираем на составляющие переданный для проверки URL
    	$checkUrl = trim($checkUrl, '/');
    	$seg = explode('/', $checkUrl);
    	
    	// Считаем количество параметров в разобранном URL
    	$cntSeg = count($seg);
    	
    	// Проверяем только по модулю
    	if ($cntSeg == 1) {
    		if ($this->getUrl($seg) == $sm) {
    			$check = true;
    		}
    	}
    	
	    // Проверяем по модулю и контроллеру
    	if ($cntSeg == 2) {
    		$seg[1] = (empty($seg[1])) ? 'index' : $seg[1];
    		if ($this->getUrl($seg) == $sm.'/'.$sc) {
    			$check = true;
    		}
    	}
    	
	    // Проверяем по модулю, контроллеру и действию
    	if ($cntSeg == 3) {
    		$seg[2] = (empty($seg[2])) ? 'index' : $seg[2];
    		if ($this->getUrl($seg) == $sm.'/'.$sc.'/'.$sa) {
    			$check = true;
    		}
    	}
    	
    	// Проверка по всем параметрам
    	if ($cntSeg >= 4) {
    		$realUrl = trim(Zend_View_Helper_Url::url(), '/');
    		
    		// Убираем информацию о языках
    		$request = Zend_Controller_Front::getInstance()->getRequest();
    		$realUrl = trim($realUrl, $request->getParam(LANG_KEY).'/');
    		
    		if ($this->getUrl($seg) == $realUrl) {
    			$check = true;
    		}
    	}
    	
    	
    	// Определяем необходимость вывода строки
    	if (!is_null($printString) && $check === true) {
    		return $printString;
    	}
    	
        return $check;
    }
    
    /**
     * Возвращает собранный из сегментов URL
     * @param $segment Массив с сегментами URL
     * @return string
     */
    private function getUrl($segment) {
    	return implode('/', $segment);
    }
}

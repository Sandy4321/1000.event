<?php

/**
 * SiteActionSystems (SAS)
 * 
 * Плагин переводчик.
 * 
 * @category Sas
 * @package Sas_View
 * @subpackage Sas_View_Helper
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2008 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 1.0 2009-05-01 22:32:00
 */
class Sas_View_Helper_T extends Zend_View_Helper_Translate
{
	public function __construct($translate = null) {
		parent::__construct($translate);
	}
	
    public function T($messageid = null)
    {
    	return parent::translate($messageid);
    }
}

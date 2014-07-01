<?php

/**
 * SiteActionSystems (SAS)
 * 
 * @category Sas_Modules
 * @copyright Copyright (c) 2009 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 1.0 2009-02-07 09:33:21
 */

/**
 * Контроллер по умолчанию (первая страница сайта).
 * 
 * @category Sas_Modules
 * @package Modules_Default
 * @subpackage Default_IndexController
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2009 Alexander Klabukov. (http://www.klabukov.ru)
 */
class IndexController extends Sas_Controller_Action
{
	public function initSas() {
		if($_SESSION['user']['auth'] == true) {
			Models_User_Model::rememberMe();
			$this->_redirect('/user');
		}
	}

	/**
	 * Первая страница сайта.
	 */
	public function indexAction()
	{
		// Мероприятия
		$ModelEvent = new Models_User_Event(0);

		// Ближайщие два мероприятия
		$event = $ModelEvent->getEventsNoStart(false, null, null, 2);
		$this->view->assign('vEvents', $event);

		// модель статических страниц
		#$ModelPages = new Models_PagesStatic($this);
		#$this->view->assign('vPage', $ModelPages->getPage());
		
		#$this->_helper->actionStack('forhome', 'index', 'partners', array('SEGMENT' => 'PartnerIndexForhome'));
		#$this->_helper->actionStack('one-quote', 'people', 'news', array('SEGMENT' => 'NewsPeopleOneQuote'));
	}

}
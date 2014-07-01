<?php

/**
 * Люди о клубе
 */
class News_PeopleController extends Sas_Controller_Action
{
	public function initSas() {
		// Подменяем макеты
		//$this->_startLayout(array('layoutPath' => PATH_DIR_LAYOUT_SITE_OLD));
	}

	/**
	 * index
	 */
	public function indexAction()
	{
		$Model = new Models_News();

		$this->view->assign('vData', $Model->getQuoteAll());
	}
}

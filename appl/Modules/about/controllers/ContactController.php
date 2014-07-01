<?php

class About_ContactController extends Sas_Controller_Action
{
	public function initSas() {
		// Подменяем макеты
		//$this->_startLayout(array('layoutPath' => PATH_DIR_LAYOUT_SITE_OLD));
	}

	public function indexAction()
	{
		// модель статических страниц
		$ModelPages = new Models_PagesStatic($this);
		$this->view->assign('vPage', $ModelPages->getPage());
	}
}

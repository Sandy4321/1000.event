<?php

class About_IndexController extends Sas_Controller_Action
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

	/**
	 * Как вступить
	 */
	public function howToJoinAction()
	{
		// модель статических страниц
		$ModelPages = new Models_PagesStatic($this);
		$this->view->assign('vPage', $ModelPages->getPage());
	}

	/**
	 * Члены клуба
	 */
	public function membersAction()
	{
		// модель статических страниц
		$ModelPages = new Models_PagesStatic($this);
		$this->view->assign('vPage', $ModelPages->getPage());
	}

	/**
	 * Правила
	 */
	public function rulesAction()
	{
		// модель статических страниц
		$ModelPages = new Models_PagesStatic($this);
		$this->view->assign('vPage', $ModelPages->getPage());
	}

	/**
	 * Конфиденциальность
	 */
	public function privacyAction()
	{
		// модель статических страниц
		$ModelPages = new Models_PagesStatic($this);
		$this->view->assign('vPage', $ModelPages->getPage());
	}

	/**
	 * FAQ вопросы/ответы
	 */
	public function faqAction()
	{
		// модель статических страниц
		$ModelPages = new Models_PagesStatic($this);
		$this->view->assign('vPage', $ModelPages->getPage());
	}
}
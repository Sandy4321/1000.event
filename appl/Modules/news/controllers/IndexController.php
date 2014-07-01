<?php

class News_IndexController extends Sas_Controller_Action
{
	public function initSas() {
		// Подменяем макеты
		//$this->_startLayout(array('layoutPath' => PATH_DIR_LAYOUT_SITE_OLD));
	}

	/**
	 * Вывод последних новостей
	 */
	public function indexAction()
	{
		$page = 1;
		$limit = 5;

		$ModelNews = new Models_News();

		$this->view->vData = $ModelNews->getList($page, $limit);
	}

	/**
	 * Вывод полного текста новости
	 */
	public function showAction()
	{
		$id = (int)$this->_getParam('id', 1);

		// Получаем полный текст
		$News = new Models_News();
		$this->view->vData =  $News->getFullText($id);
	}
}

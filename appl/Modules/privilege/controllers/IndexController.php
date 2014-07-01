<?php

class Privilege_IndexController extends Sas_Controller_Action
{
	/**
	 * Главная страница списка всех привилегий
	 */
	public function indexAction()
	{
		$ModelPrivilege = new Models_Privilege();
		$this->view->assign('vItems', $ModelPrivilege->getList(10));
	}
}

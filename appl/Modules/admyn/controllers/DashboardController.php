<?php

class Admyn_DashboardController extends Sas_Controller_Action_Admin
{
	public function indexAction()
	{
		$Model = new Models_Admin_Users();
		$this->view->vData = $Model->getUserStatus(51); // 1-Новая заявка
	}
}
<?php

class Admyn_SearchController extends Sas_Controller_Action_Admin
{
	public function ajaxQuickUserAction()
	{
		$this->ajax();

		$ModelSearch = new Models_Admin_Search();
		$data = $ModelSearch->quick($this->_getParam('query'));
		$this->setJson($data);
		//$this->getResponse()->appendBody($this->json2($data))->setHeader('Content-Type', 'application/json');
	}
}
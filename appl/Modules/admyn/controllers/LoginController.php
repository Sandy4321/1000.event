<?php

class Admyn_LoginController extends Sas_Controller_Action
{
	public function indexAction()
	{
		$data = $this->getRequest()->getPost();
		if (!$data) return false;

		$json['login'] = 'Sas';
		$this->getResultJson($json);
	}

	private function getResultJson($data)
	{
		return $this->getResponse()->appendBody($this->_helper->json($data, false));
	}

	public function preDispatch()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
	}
}
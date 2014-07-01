<?php

class Promo_PressController extends Sas_Controller_Action
{
	/**
	 * Грушим всё в этом контроллере
	 */
	public function preDispatch()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		$this->_redirect('/');
	}
}
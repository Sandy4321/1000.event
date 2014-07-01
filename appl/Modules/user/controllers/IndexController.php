<?php

class User_IndexController extends Sas_Controller_Action_User
{
	public function indexAction()
	{
		$ModelProfile = new Models_User_Profile();
		$profile      = $ModelProfile->getProfile();

		if ($profile['current_status'] < 70) {
			$this->_redirect('/user/profile/settings');
		} else {
			$this->_redirect('/user/dashboard');
		}

	}
}
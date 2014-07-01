<?php

/**
 * Привилегии
 */
class User_PrivilegeController extends Sas_Controller_Action_User
{
	/**
	 * Главная страница списка всех привилегий
	 */
	public function indexAction()
	{
		$myId         = Models_User_Model::getMyId();
		$ModelProfile = new Models_Users($myId);
		$this->view->assign('myProfile', $ModelProfile->getProfileToArray());

		$ModelPrivilege = new Models_Privilege();
		$this->view->assign('vItems', $ModelPrivilege->getList(10));
	}

	/**
	 * Полная информация о привилегии
	 */
	public function viewAction()
	{
		$myId         = Models_User_Model::getMyId();
		$ModelProfile = new Models_Users($myId);
		$this->view->assign('myProfile', $ModelProfile->getProfileToArray());

		$ModelPrivilege = new Models_Privilege();
		$data           = $ModelPrivilege->getPrivilege($this->_getParam('id', 0));
		if (empty($data)) {
			$this->_redirect('user/privilege');

			return;
		}

		$this->view->assign('vItem', $data);
	}

	public function megafonAction()
	{
		$this->ajaxInit();

		$Model = new Models_User_Profile(Models_User_Model::getMyId());
		$profile = $Model->getProfile();
		if ($profile['current_status'] < 70) {
			$this->_redirect('/user/profile/settings');
		} else {
			//$this->_redirect('downloads/Certificate-Megafon.pdf');
			$Pdf = new Models_Pdf();
			$Pdf->createMegafonCertificate($profile['first_name'], $profile['last_name'], $profile['id'], $profile['uid']);
		}
	}
}
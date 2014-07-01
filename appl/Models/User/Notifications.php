<?php

class Models_User_Notifications
{
	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'notifications'),
			'name'  => $tr->translate('Уведомления'),
			'check' => 'user/notifications',
			'style' => ' active',
			'icon'  => 'Notifications'
		);

		return $menu;
	}
	//============ /MENU ===========
}
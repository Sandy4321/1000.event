<?php
exit;
class Models_User_Invite
{
	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'invite', 'action'=>'friend'),
			'name'  => $tr->translate('Пригласить друзей'),
			'check' => 'user/invite/friend',
			'style' => ' active',
			'icon'  => 'Invite'
			/*'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'balance', 'action' => 'replenishment'),
					'name'  => 'Пополнение баланса',
					'check' => 'user/balance/replenishment',
					'style' => ' class="active"',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'balance', 'action' => 'history'),
					'name'  => 'История платежей',
					'check' => 'user/balance/history',
					'style' => ' class="active"',
				),
			)*/
		);

		return $menu;
	}
	//============ /MENU ===========
}
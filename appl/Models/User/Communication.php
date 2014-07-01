<?php

class Models_User_Communication
{
	//============ MENU ===========
	static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'communication'),
			'name'  => $tr->translate('Общение'),
			'check' => 'user/communication',
			'style' => ' active',
			'icon'  => 'Communication'
			/*'children' => array(#
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'favorit'),
					'name'  => 'Фавориты',
					'check' => 'user/search/favorit',
					'style' => ' class="active"',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'search', 'action' => 'black-list'),
					'name'  => 'Черный список',
					'check' => 'user/search/black-list',
					'style' => ' class="active"',
				),
			)*/
		);

		return $menu;
	}
	//============ /MENU ===========
}
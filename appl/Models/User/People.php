<?php

class Models_User_People
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();
	}

	//============ MENU ===========
	/*static public function getMenu() {
		$tr = Zend_Registry::get('Zend_Translate');
		#$sex = (Models_User_Model::getMySex() == 'male') ? $tr->translate('Девушки') : $tr->translate('Мужчины');
		$sex = $tr->translate('Поиск');
		$sexIcon = (Models_User_Model::getMySex() == 'male') ? 'Female' : 'Male';
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'people'),
			'name'  => $sex,
			'check' => 'user/people',
			'style' => ' active',
			'icon'  => $sexIcon,
			'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'index'),
					'name'  => $tr->translate('Романтика'),
					'check' => 'user/people/index',
					'style' => ' active',
					'icon'  => 'Romantic',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'business'),
					'name'  => $tr->translate('Бизнес'),
					'check' => 'user/people/business',
					'style' => ' active',
					'icon'  => 'Business',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'interests'),
					'name'  => $tr->translate('Интересы'),
					'check' => 'user/people/interests',
					'style' => ' active',
					'icon'  => 'Interests',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'targets'),
					'name'  => $tr->translate('Цели'),
					'check' => 'user/people/targets',
					'style' => ' active',
					'icon'  => 'Targets',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'favourites'),
					'name'  => $tr->translate('Избранное'),
					'check' => 'user/people/favourites',
					'style' => ' active',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'blacklist'),
					'name'  => $tr->translate('Заблокированные'),
					'check' => 'user/people/blacklist',
					'style' => ' active',
				),
			)
		);

		return $menu;
	}*/

	/*static public function getMenuPhone() {
		$tr = Zend_Registry::get('Zend_Translate');
		$sex = $tr->translate('Поиск');
		$menu = array(
			'url'   => array('module' => 'user', 'controller' => 'people'),
			'name'  => $sex,
			'check' => 'user/people',
			'style' => ' active',
			'icon'  => 'Search',
			'children' => array(
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'index'),
					'name'  => $tr->translate('Романтика'),
					'check' => 'user/people/index',
					'style' => ' active',
					'icon'  => 'Romantic',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'business'),
					'name'  => $tr->translate('Бизнес'),
					'check' => 'user/people/business',
					'style' => ' active',
					'icon'  => 'Business',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'interests'),
					'name'  => $tr->translate('Интересы'),
					'check' => 'user/people/interests',
					'style' => ' active',
					'icon'  => 'Interests',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'targets'),
					'name'  => $tr->translate('Цели'),
					'check' => 'user/people/targets',
					'style' => ' active',
					'icon'  => 'Targets',
				),
				array(
					'url'   => array('module' => 'user', 'controller' => 'people', 'action' => 'favourites'),
					'name'  => $tr->translate('Избранное'),
					'check' => 'user/people/favourites',
					'style' => ' active',
					'icon'  => 'Favorite',
				),
			)
		);

		return $menu;
	}*/
	//============ /MENU ===========
}
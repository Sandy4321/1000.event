<?php

class Admyn_AdvertController extends Sas_Controller_Action_Admin
{
	/**
	 * Добавить рекламную кампанию
	 */
	public function addAction()
	{
		$request = $this->getRequest();
		if($request->isPost() && $request->getParam('adv-name')) {
			$data = $request->getPost();

			$ModelAdv = new Models_Admin_Advert();
			$result = $ModelAdv->add($data);

			if($result) {
				$this->view->vData = $result;
			}
		}
	}

	/**
	 * Просмотр рекламных кампаний
	 */
	public function viewAction()
	{
		$ModelAdv = new Models_Admin_Advert();

		$this->view->vWeb = $ModelAdv->getAdvType(1);
		$this->view->vMob = $ModelAdv->getAdvType(2);
	}
}

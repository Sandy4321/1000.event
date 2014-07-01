<?php

class Admyn_TemplatesController extends Sas_Controller_Action_Admin
{
	// ============================== AJAX ===============================
	public function ajaxSaveTplEmailAction()
	{
		$this->ajax();

		$ModelTpl = new Models_Admin_Templates();

		$ModelTpl->saveTplEmail($this->_getAllParams());

		$json['type'] = 'ok';
		$json['msg'] = 'Email шаблон сохранён';

		$this->setJson($json);
	}

	public function ajaxSaveTplSmsAction()
	{
		$this->ajax();

		$ModelTpl = new Models_Admin_Templates();

		$ModelTpl->saveTplSms($this->_getAllParams());

		$json['type'] = 'ok';
		$json['msg'] = 'SMS шаблон сохранён';

		$this->setJson($json);
	}

	public function ajaxSaveTplDashAction()
	{
		$this->ajax();

		$ModelTpl = new Models_Admin_Templates();

		$ModelTpl->saveTplDash($this->_getAllParams());

		$json['type'] = 'ok';
		$json['msg'] = 'Шаблон для Dashboard сохранён';

		$this->setJson($json);
	}
	// ============================== /AJAX ==============================

	// ============================== EMAIL ===============================

	public function emailAction()
	{
		$ModelTpl = new Models_Admin_Templates();
		$this->view->vData = $ModelTpl->getTplEmail();
	}

	// ============================== /EMAIL ==============================

	// ============================== SMS ===============================

	public function smsAction()
	{
		$ModelTpl = new Models_Admin_Templates();
		$this->view->vData = $ModelTpl->getTplSms();
	}

	// ============================== /SMS ==============================

	// ============================== DASH ===============================

	public function dashAction()
	{
		$ModelTpl = new Models_Admin_Templates();
		$this->view->vData = $ModelTpl->getTplDash();
	}

	// ============================== /DASH ==============================
}
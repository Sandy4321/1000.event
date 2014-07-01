<?php

class Models_Admin_Templates
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;

	private $tblEmail = 'templates_email';
	private $tblSms = 'templates_sms';
	private $tblDash = 'dashboard_msg';

	public function __construct() {
		$this->db = Zend_Registry::get('db');
	}

	public function getTplEmail() {
		$select = $this->db->select()
			->from($this->tblEmail, '*')
			->order('tpl_key ASC')
			->order('id ASC');

		return $this->db->fetchAll($select);
	}

	public function getTplSms() {
		$select = $this->db->select()
			->from($this->tblSms, '*')
			->order('id');

		return $this->db->fetchAll($select);
	}

	public function getTplDash() {
		$select = $this->db->select()
			->from($this->tblDash, '*')
			->order('id');

		return $this->db->fetchAll($select);
	}

	public function saveTplEmail($data) {
		$update['sex'] = $data['sex'];
		$update['subject_ru'] = $data['subject_ru'];
		$update['text_ru'] = $data['text_ru'];
		$update['subject_en'] = $data['subject_en'];
		$update['text_en'] = $data['text_en'];
		$where = $this->db->quoteInto('id = ?', (int) $data['id']);
		return $this->db->update($this->tblEmail, $update, $where);
	}

	public function saveTplSms($data) {
		$update['sex'] = $data['sex'];
		$update['text_ru'] = $data['text_ru'];
		$update['text_en'] = $data['text_en'];
		$where = $this->db->quoteInto('id = ?', (int) $data['id']);
		return $this->db->update($this->tblSms, $update, $where);
	}

	public function saveTplDash($data) {
		$update['msg_ru'] = $data['msg_ru'];
		$update['msg_en'] = $data['msg_en'];
		$where = $this->db->quoteInto('id = ?', (int) $data['id']);
		return $this->db->update($this->tblDash, $update, $where);
	}
}
<?php

class Models_User_SystemMsg
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;

	private $myId = null;

	private $table  = 'system_msg';
	private $column = array(
		'id', 'user_id', 'hide_dash', 'hide_user', 'date_create'
	);

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->myId = Models_User_Model::getMyId();
		#if (!is_int($this->myId)) {
		#	throw new Sas_Exception('ERROR no myId');
		#}

		$this->column['msg'] = 'msg_' . $this->lang;
	}

	/**
	 * Возвращет для Dash все не скрытые системные сообщения для пользователя.
	 * $userId - Можно не передавать, в этом случае вернутся сообщения
	 * пользователя от имени которого делается выборка.
	 *
	 * @param null $userId
	 * @param bool $all
	 * @return array
	 */
	public function getMsgDash($userId = null, $all = false)
	{
		$userId = (is_null($userId)) ? $this->myId : $userId;

		$select = $this->db->select()
			->from($this->table, $this->column)
			->where('user_id = ?', $userId);

		if($all == false) {
			$select->where('hide_dash = ?', 'no');
		}

		$select->order('date_create DESC');
		#Sas_Debug::dump($select->__toString());
		$rows = $this->db->fetchAll($select);
		#Sas_Debug::dump($rows);
		return $rows;
	}

	/**
	 * Возвращет все не скрытые (удалённые) системные сообщения для пользователя.
	 * $userId - Можно не передавать, в этом случае вернутся сообщения
	 * пользователя от имени которого делается выборка.
	 *
	 * @param null $userId
	 * @return array
	 */
	public function getMsg($userId = null)
	{
		$userId = (is_null($userId)) ? $this->myId : $userId;

		$select = $this->db->select()
			->from($this->table, $this->column)
			->where('user_id = ?', $userId)
			->where('hide_user = ?', 'no')
			->order('date_create DESC');

		return $this->db->fetchAll($select);
	}

	public function close($msgId, $hide_dash = false, $hide_user = false)
	{
		$where = $this->db->quoteInto('id = ?', (int) $msgId);
		($hide_dash !== false) ? $data['hide_dash'] = 'yes' : null;
		($hide_user !== false) ? $data['hide_user'] = 'yes' : null;
		$this->db->update($this->table, $data, $where);
	}

	public function setMsgUser($userId, $userLang, $textMsg)
	{
		$text = htmlspecialchars(trim($textMsg));
		$text_ = explode("\n", $text);
		$text = implode("<br/>\n", $text_);

		$insert['user_id'] = (int) $userId;
		$insert['msg_' . $userLang] = $text;
		$insert['date_create'] = date('Y-m-d H:i:s');

		$this->db->insert($this->table, $insert);
	}
}
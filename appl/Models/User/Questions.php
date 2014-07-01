<?php

class Models_User_Questions
{
	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db;
	private $lang = LANG_DEFAULT;
	private $myId = null;

	private $tableQuestionsCategory = 'questions_category';
	private $columnsCategory = array();

	private $tableQuestions = 'questions';
	private $columnsQuestions = array();

	private $tableUserQuestions = 'user_questions';

	//private $tableUserQuestionsMoney = 'user_questions_money';

	#private $tableProfile = 'users_data';
	private $tableProfile = 'users';
	private $columnProfileStandard = array(
		'userId'=>'id',
		'uid',
		'firstName'=>'first_name',
		'lastName'=>'last_name',
		'sex',
		'birthday',
		'height',
		'children',
		'smoking',
		'phone',
		'current_status',
		'balance',
		'balance_bonus',
		'online_last_dt',
		'club_card_dt'
	);
	private $columnProfileImg = array('img' => 'CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" )');

	public function __construct() {
		$this->db = Zend_Registry::get('db');

		$this->lang = Zend_Controller_Front::getInstance()
			->getPlugin('Sas_Controller_Plugin_Language')
			->getLocale();

		$this->columnsCategory = array(
			'categoryId'   => 'id',
			'categoryName' => 'category_name_' . $this->lang
		);

		$this->columnsQuestions = array(
			'questionsId' => 'id',
			'questionText' => 'question_' . $this->lang
		);

		$this->myId = Models_User_Model::getMyId();
		if (!is_int($this->myId)) {
			throw new Sas_Exception('ERROR no myId');
		}
	}

	/**
	 * Возвращает весь диалог с пользователем
	 * @param $userId
	 * @param $noId
	 * @return array
	 */
	public function getDialog($userId, $noId)
	{
		$colFree = array('id', 'user_id_from', 'user_id_to',
			'record_type',
			'question_id',
			'question',
			'answer',
			'question_is_read',
			'answer_is_read',
			'date_create',
			'date_last_action',
			'date_answer',
			'date_read_question',
			'date_read_answer',
		);

		$select = $this->db->select();
		$select->from(array('uq'=>$this->tableUserQuestions), $colFree);
		$select->where('(`user_id_from` = ' . $this->myId . ' AND `user_id_to` = ' . $userId . ') OR (`user_id_from` = ' . $userId . ' AND `user_id_to` = ' . $this->myId . ')');
		$select->where('uq.id != ?', $noId);
		#$select->where('archive_from = ?', 'no');
		$select->order('uq.date_last_action DESC');

		#Sas_Debug::dump($select->__toString());
		$rows = $this->db->fetchAll($select);
		//Sas_Debug::dump($rows);

		// Устанавливаем всем НЕ прочитанным НЕ моим вопросам и НЕ моим ответам статус - прочитано
		$noAnswer = null; // ID не отвеченных стандартных вопросов.
		foreach($rows as $item)
		{
			if ($item['user_id_to'] == $this->myId && $item['question_is_read'] == 'no') {
				$question_id[] = (int)$item['id'];
			}
			if ($item['user_id_to'] == $this->myId && $item['answer_is_read'] == 'no') {
				$answer[] = (int)$item['id'];
			}

			// собираем ID стандартных вопросов которые еще не отвечены
			if($item['record_type'] == 'free' && is_null($item['answer'])) {
				$noAnswer[] = $item['question_id'];
			}
		}

		// Пробуем получить ответы
		if (is_array($noAnswer)) {
			$str = '';
			for($i=0; $i < count($noAnswer); $i++)
			{
				$str .= 'question_id = ' . (int) $noAnswer[$i];
				if(!empty($noAnswer[$i+1])) {
					$str .= ' OR ';
				}
			}

			$select->reset();
			$select->from($this->tableUserQuestions, array('question_id', 'answer'))
				->where('user_id_to = ?', $this->myId)
				->where('record_type = ?', 'free')
				->where('answer IS NOT NULL')
				->where($str);
			#Sas_Debug::dump($select->__toString());
			$answerOld = $this->db->fetchAll($select); // Ответы данные на такие же вопросы ранее
			#Sas_Debug::dump($answerOld);
		}

		// Подставляем старые ответы в результат
		if(is_array($answerOld)) {
			for($i=0; $i < count($rows); $i++) {
				$c_q_id = $rows[$i]['question_id'];
				foreach($answerOld as $old) {
					if($c_q_id == $old['question_id']) {
						$rows[$i]['old_answer'] = $old['answer'];
					}
				}
			}
		}

		if(!empty($question_id)) {
			$id = implode(',', $question_id);
			$data['question_is_read']   = 'yes';
			$data['date_read_question'] = date('Y-m-d H:i:s');
			$where = '`id` IN ('.$id.')';
			$this->db->update($this->tableUserQuestions, $data, $where);
		}
		if(!empty($answer)) {
			$id = implode(',', $answer);
			$data['answer_is_read']   = 'yes';
			$data['date_read_answer'] = date('Y-m-d H:i:s');
			$where = '`id` IN ('.$id.')';
			$this->db->update($this->tableUserQuestions, $data, $where);
		}

		//$this->setReadYes($rows, 'free'); // Меняем всем НЕ моим вопросам статус на прочитано
		#Sas_Debug::dump($rows);
		return $rows;
	}

	/**
	 * Возвращает все сообщения сгруппированные по контактному пользователю
	 * @return array
	 */
	public function getGroupMsg()
	{
		$select = 'SELECT ';
		$select .= '`uq`.`id`, `uq`.`user_id_from`, `uq`.`user_id_to`, `uq`.`record_type`, `uq`.`question`, `uq`.`question_id`, `uq`.`answer`, `uq`.`question_is_read`, `uq`.`answer_is_read`, `uq`.`date_create`, `uq`.`date_last_action`, `uq`.`date_answer`, `uq`.`date_read_question`, `uq`.`date_read_answer`, ';
		$select .= '`profile`.`id` AS `userId`, `uid`, `profile`.`first_name` AS `firstName`, `profile`.`last_name` AS `lastName`, `profile`.`sex`, `profile`.`birthday`, `profile`.`height`, `profile`.`children`, `profile`.`smoking`, `profile`.`phone`, `profile`.`current_status`, `profile`.`balance`, `profile`.`balance_bonus`, `profile`.`club_card_dt`, `profile`.`online_last_dt`, CONCAT( "/img/people/", `sex`, "/", YEAR(`birthday`), "/", `profile`.`id`, "/" ) AS `img` ';
		$select .= 'FROM (SELECT `tbl`.`id` FROM `user_questions` AS `tbl` WHERE (`user_id_from` = '.$this->myId.' OR `user_id_to` = '.$this->myId.') ';
		//$select .= 'AND (archive = "no") ';
		$select .= 'ORDER BY `date_last_action` DESC) AS `t` ';
		$select .= 'JOIN `user_questions` AS `uq` ';
		$select .= 'USING (`id`) ';
		$select .= 'INNER JOIN `'.$this->tableProfile.'` AS `profile` ON (user_id_from = profile.id OR user_id_to = profile.id) AND profile.id != '.$this->myId.' ';
		$select .= 'GROUP BY `profile`.`id` ';
		$select .= 'ORDER BY `uq`.`date_last_action` DESC';
		#Sas_Debug::dump($select);
		$rows = $this->db->fetchAll($select);
		#Sas_Debug::dump($rows);

		// Устанавливаем всем НЕ прочитанным НЕ моим вопросам и НЕ моим ответам статус - прочитано
		$noAnswer = null; // ID не отвеченных стандартных вопросов.
		foreach($rows as $item)
		{
			if ($item['user_id_to'] == $this->myId && $item['question_is_read'] == 'no') {
				$question_id[] = (int)$item['id'];
			}

			if ($item['user_id_to'] == $this->myId && $item['answer_is_read'] == 'no') {
				$answer[] = (int)$item['id'];
			}

			// собираем ID стандартных вопросов которые еще не отвечены
			if($item['record_type'] == 'free' && is_null($item['answer'])) {
				$noAnswer[] = $item['question_id'];
			}
		}
		//Sas_Debug::dump($noAnswer);
		// Пробуем получить ответы
		if (is_array($noAnswer)) {
			$str = '';
			for($i=0; $i < count($noAnswer); $i++)
			{
				$str .= 'question_id = ' . (int) $noAnswer[$i];
				if(!empty($noAnswer[$i+1])) {
					$str .= ' OR ';
				}
			}

			$select = $this->db->select();
			$select->from($this->tableUserQuestions, array('question_id', 'answer'))
				->where('user_id_to = ?', $this->myId)
				->where('record_type = ?', 'free')
				->where('answer IS NOT NULL')
				->where($str);
			#Sas_Debug::dump($select->__toString());

			$answerOld = $this->db->fetchAll($select); // Ответы данные на такие же вопросы ранее
			#Sas_Debug::dump($answerOld);
		}

		// Подставляем старые ответы в результат
		if(is_array($answerOld)) {
			for($i=0; $i < count($rows); $i++) {
				$c_q_id = $rows[$i]['question_id'];
				foreach($answerOld as $old) {
					if($c_q_id == $old['question_id']) {
						$rows[$i]['old_answer'] = $old['answer'];
					}
				}
			}
		}

		if(!empty($question_id)) {
			$id = implode(',', $question_id);
			$data['question_is_read']   = 'yes';
			$data['date_read_question'] = date('Y-m-d H:i:s');
			$where = '`id` IN ('.$id.')';
			$this->db->update($this->tableUserQuestions, $data, $where);
		}

		if(!empty($answer)) {
			$id = implode(',', $answer);
			$data['answer_is_read']   = 'yes';
			$data['date_read_answer'] = date('Y-m-d H:i:s');
			$where = '`id` IN ('.$id.')';
			$this->db->update($this->tableUserQuestions, $data, $where);
		}

		/*// MONEY
		$select = $this->db->select();
		$select->from(array('uq'=>$this->tableUserQuestionsMoney), '*');
		$select->join(array('profile'=>$this->tableProfile), '(user_id_from = profile.id OR user_id_to = profile.id) AND profile.id != '.$this->myId, $this->columnProfileStandard);
		$select->columns($this->columnProfileImg);
		$select->columns(array('typeQ'=>'CONCAT("money")'));
		$select->where('`user_id_from` = ' . $this->myId . ' OR `user_id_to` = ' . $this->myId);
		$select->group('profile.id');
		$select->order('uq.id DESC');
		$rowsMoney = $this->db->fetchAll($select);
		Sas_Debug::dump($rowsMoney);

		$rows = array_merge($rowsFree, $rowsMoney);
		#$rows = $rowsFree + $rowsMoney;

		foreach($rows as $key=>$val) {
			$date[$key] = $val['date_create'];
		}
		array_multisort($date, SORT_DESC, $rows);*/
		#Sas_Debug::dump($rows);
		return $rows;
	}

	/*public function tFreeFrom()
	{
		$colFree = array('id', 'user_id_from', 'user_id_to',
			'text_answer' => 'answer',
			'question_is_read' => 'question_is_readed',
			'answer_is_read' => 'answer_is_readed',
			'date_create' => 'date_created'
		);

		// from - исходящие
		$select = $this->db->select();
		$select->from(array('uq'=>$this->tableUserQuestions), $colFree);
		$select->join(array('q'=>$this->tableQuestions), 'q.id = uq.question_id', array('text_question'=>'question_'.$this->lang));
		$select->join(array('profile'=>$this->tableProfile), 'profile.id = user_id_to', $this->columnProfileStandard);
		$select->columns($this->columnProfileImg);
		$select->columns(array('typeQ'=>'CONCAT("free")'));
		$select->where('user_id_from = ?', $this->myId);
		$select->group('user_id_to');
		//$select->order('date_created DESC');
		$select->order('uq.id DESC');
		#Sas_Debug::dump($select->__toString());
		$rowsFrom = $this->db->fetchAll($select);
		#Sas_Debug::dump($rowsFrom);

		// to - входящие
		$select->reset();
		$select->from(array('uq'=>$this->tableUserQuestions), $colFree);
		$select->join(array('q'=>$this->tableQuestions), 'q.id = uq.question_id', array('text_question'=>'question_'.$this->lang));
		$select->join(array('profile'=>$this->tableProfile), 'profile.id = user_id_from', $this->columnProfileStandard);
		$select->columns($this->columnProfileImg);
		$select->columns(array('typeQ'=>'CONCAT("free")'));
		$select->where('user_id_to = ?', $this->myId);
		$select->group('user_id_from');
		//$select->order('date_created DESC');
		$select->order('id DESC');
		#Sas_Debug::dump($select->__toString());
		$rowsTo = $this->db->fetchAll($select);
		#Sas_Debug::dump($rowsTo);

		// Money
		// from - исходящие
		$select->reset();
		$select->from(array('m'=>$this->tableUserQuestionsMoney), '*');
		$select->join(array('profile'=>$this->tableProfile), 'profile.id = user_id_to', $this->columnProfileStandard);
		$select->columns($this->columnProfileImg);
		$select->where('user_id_from = ?', $this->myId);
		$select->group('user_id_to');
		$select->order('id DESC');
		#Sas_Debug::dump($select->__toString());
		$rowsFromM = $this->db->fetchAll($select);
		#Sas_Debug::dump($rowsFromM);

		// to - входящие
		$select->reset();
		$select->from(array('m'=>$this->tableUserQuestionsMoney), '*');
		$select->join(array('profile'=>$this->tableProfile), 'profile.id = user_id_from', $this->columnProfileStandard);
		$select->columns($this->columnProfileImg);
		$select->where('user_id_to = ?', $this->myId);
		$select->group('user_id_from');
		$select->order('id DESC');
		#Sas_Debug::dump($select->__toString(), 'm-to');
		$rowsToM = $this->db->fetchAll($select);
		#Sas_Debug::dump($rowsToM);

		$rowsAllM = array_merge($rowsFromM, $rowsToM);
		$rowsAll = array_merge($rowsFrom, $rowsTo);
		$rows = array_merge($rowsAllM, $rowsAll);

		foreach($rows as $key=>$val) {
			$date[$key] = $val['date_create'];
		}
		array_multisort($date, SORT_DESC, $rows);

		#Sas_Debug::dump($date);
		return $rows;
	}*/

	/**
	 * Проверяет кол-во заданных вопросов
	 *
	 * @param $userIdTo ID пользователя которому задаются вопросы
	 * @return int
	 */
	public function checkQuestionsSend($userIdTo) {
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableUserQuestions, 'COUNT(*)');
		$select->where('user_id_from = ?', $this->myId);
		$select->where('user_id_to = ?', $userIdTo);
		$select->where('record_type = ?', 'free');
		#Sas_Debug::dump($select->__toString());

		return $this->db->fetchOne($select);
	}

	/**
	 * Возвращает категории вопросов с текстом вопросов
	 *
	 * @return array
	 */
	public function getQuestionsAndCategory()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableQuestionsCategory, $this->columnsCategory);
		$select->join($this->tableQuestions, 'questions_category.id = questions.category_id', $this->columnsQuestions);
		$select->where('gender != ?', Models_User_Model::getMySex());
		$select->where('questions_category.version = 3'); // Версия категорий вопросов
		$select->order('questions_category.id ASC');
		$select->order('questions.id ASC');

		#Sas_Debug::dump($select->__toString());

		$rows = $this->db->fetchAll($select);

		#Sas_Debug::dump($rows);

		// Перепаковываем данные
		$cntCat = -1;
		for($i = 0; $i < count($rows); $i++) {

			if ($i == 0 || $rows[$i]['categoryId'] != $rows[$i-1]['categoryId']) {
				$data[] = array(
					'categoryId'   => $rows[$i]['categoryId'],
					'categoryName' => $rows[$i]['categoryName'],
				);
				$cntCat ++;
			}

			$data[$cntCat]['data'][] = array(
				'questionsId' => $rows[$i]['questionsId'],
				'questionText' => $rows[$i]['questionText'],
			);
		}

		#Sas_Debug::dump($data);
		return $data;
	}

	/**
	 * Сохранение бесплатных вопросов
	 *
	 * @param $data
	 */
	public function saveQuestionsFree($data)
	{
		unset($data['question_text']); // удален текст платных вопросов
		$insertData = array(
			'user_id_from'     => $this->myId,
			'user_id_to'       => $data['user_id_to'],
			'record_type'      => 'free',
			'date_create'      => date('Y-m-d H:i:s'),
			'date_last_action' => date('Y-m-d H:i:s')
		);

		// язык получателя вопросов
		// Запрос по языку получателя отменён (тел. разговор с Альмиром 2013-07-02 14:46)
		/*$select = $this->db->select()
			->from($this->tableProfile, 'lang')
			->where('id = ?', $data['user_id_to'])
			->limit(1);
		$lang = $this->db->fetchOne($select);*/

		// Номера вопросов
		for($i = 0; $i < count($data['question_id']); $i++)
		{
			// Тексты вопросов на языке получателя
			$select = $this->db->select()
				->from($this->tableQuestions, array('question_text'=>'question_'.$this->lang)) // было $lang (язык получателя)
				->where('id = ?', $data['question_id'][$i])
				->limit(1);
			$question = $this->db->fetchOne($select);

			$insertData['question_id'] = $data['question_id'][$i];
			$insertData['question']    = $question;
			#Sas_Debug::dump($insertData);
			$this->db->insert($this->tableUserQuestions, $insertData);

			Models_Actions::add(10, $this->myId, $data['user_id_to'], $insertData['question_id']); // Задан стандартный вопрос
		}
	}

	/**
	 * Сохраняет платные (за караты) вопросы пользователей
	 *
	 * @param $userIdTo
	 * @param $questionText
	 * @return bool
	 * @throws Sas_Exception
	 */
	public function saveQuestionsMoney($userIdTo, $questionText)
	{
		$data = array(
			'user_id_from'     => $this->myId,
			'user_id_to'       => $userIdTo,
			'record_type'      => 'money',
			'question'         => $questionText,
			'date_create'      => date('Y-m-d H:i:s'),
			'date_last_action' => date('Y-m-d H:i:s')
		);

		// Пишем вопрос в базу
		$res = $this->db->insert($this->tableUserQuestions, $data);
		if (!is_int($res) || $res <= 0) {
			throw new Sas_Exception('Ошибка записи платного вопроса.');
		}

		$insertId = $this->db->lastInsertId();

		Models_Actions::add(8, $this->myId, $userIdTo, $insertId); // Задан платный вопрос

		return $insertId;
	}

	/**
	 * Сохранение ответов
	 * @param $data
	 * @return int ID пользователя которому ответили
	 */
	public function saveAnswer($data)
	{
		$date = date('Y-m-d H:i:s');
		$update = array(
			'answer' => htmlspecialchars(strip_tags(trim($data['answer']))),
			'question_is_read' => 'yes',
			'date_last_action' => $date,
			'date_answer' => $date,
			'date_read_question' => $date,
		);
		$this->db->update($this->tableUserQuestions, $update, $this->db->quoteInto('id = ?', $data['id']));

		$select = $this->db->select()
			->from($this->tableUserQuestions, 'user_id_from')
			->where('id = ?', $data['id'])
			->limit(1);
		$userIdFrom = $this->db->fetchOne($select);

		Models_Actions::add(9, $this->myId, $userIdFrom, $data['id']); // Отправлен ответ на вопрос

		return $userIdFrom;
	}

	/**
	 * Сохранение бесплатных ответов
	 *
	 * @param $data
	 * @return int ID пользователя которому ответили
	 */
	/*public function saveAnswerFree($data)
	{
		//$data['text'] = htmlspecialchars(strip_tags(trim($data['text'])));
		$insertData = array(
			'answer'   => $data['text'],
			'question_is_readed' => 'yes'
		);

		$this->db->update($this->tableUserQuestions, $insertData, '`id` = ' . (int) $data['id']);

		$select = $this->db->select();
		$select->from($this->tableUserQuestions, 'user_id_from');
		$select->where('id = ?', $data['id']);
		$select->limit(1);

		return $this->db->fetchOne($select);
	}*/

	/**
	 * Возвращает сгруппированный по пользователям список вопросов
	 * @return mixed
	 */
	/*public function getQuestionsGroupFree()
	{
		$myId = Models_User_Model::getMyId();

		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableUserQuestions, '*');

		// добавляем инфо по задающему вопрос
		$columnsUser = array(
			'userId' => 'id',
			'first_name',
			'company', 'education', 'hobby', 'fav_places'
		);
		$select->join($this->tableProfile, 'user_id_from = '.$this->tableProfile.'.id', $columnsUser);

		// Исключаем людей которые в чёрном списке
		$minusIdSelect = '`'.$this->tableProfile.'`.`id` NOT IN (';
		$ModelBlackList = new Models_User_BlackList();
		$blackList = $ModelBlackList->getBlackListId();
		if (!empty($blackList)) {
			for ($i = 0, $maxCnt = count($blackList); $i < $maxCnt; $i++) {
				$minusIdSelect .= $blackList[$i]['bl_user_id'] . ',';
			}
		}
		$minusIdSelect = substr($minusIdSelect, 0, -1);
		$minusIdSelect .= ')';
		if(strlen($minusIdSelect) > 26) $select->where($minusIdSelect); // исключили пустые запросы

		$select->where('user_id_to = ?', $myId);
		$select->where('answer IS NULL');
		$select->group('user_id_from');
		$select->order('date_created DESC');

		#Sas_Debug::dump($select->__toString());

		$rows = $this->db->fetchAll($select);
		#Sas_Debug::dump($rows, __METHOD__);

		return $rows;
	}*/

	/**
	 * Возвращает вопросы конкретного пользователя
	 * @param $userId
	 * @return array
	 */
	/*public function getQuestionsFree($userId)
	{
		$myId = Models_User_Model::getMyId();

		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableUserQuestions, '*');

		// добавляем вопросы
		$columnsUser = array(
			'userId' => 'id',
			'first_name'
		);
		$select->join($this->tableQuestions, $this->tableUserQuestions.'.question_id = '.$this->tableQuestions.'.id', $this->columnsQuestions);

		$select->where('user_id_from = ?', $userId);
		$select->where('user_id_to = ?', $myId);
		$select->where('answer IS NULL');

		#Sas_Debug::dump($select->__toString());

		$rows = $this->db->fetchAll($select);
		#Sas_Debug::dump($rows, __METHOD__);

		// TODO: при получении вопросов, отметить их как прочитанные
		$where = $this->db->quoteInto('user_id_from = ?', (int) $userId);
		$where .= ' AND ';
		$where .= $this->db->quoteInto('user_id_to = ?', (int) $myId);

		$update = array(
			'question_is_readed' => 'yes'
		);
		$this->db->update($this->tableUserQuestions, $update, $where);

		// Получить мой последний ответ на такой же вопрос
		for($i = 0; $i < count($rows); $i++) {
			$select->reset();
			$select->from($this->tableUserQuestions, 'answer');
			$select->where('user_id_to = ?', $this->myId);
			$select->where('question_id = ?', $rows[$i]['question_id']);
			$select->where('answer IS NOT NULL');
			$select->order('date_created DESC');
			$select->limit(1);
			#Sas_Debug::dump($select->__toString());
			$res = $this->db->fetchOne($select);
			#Sas_Debug::dump($res);
			$rows[$i]['answer_last'] = $res;
		}

		#Sas_Debug::dump($rows, __METHOD__);

		return $rows;
	}*/

	/**
	 * Возвращает массив платных вопросов адресованных "мне"
	 * @return array
	 */
	/*public function getQuestionsMoneyTo()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableUserQuestionsMoney, '*');
		$select->join($this->tableProfile, $this->tableProfile.'.id = ' . $this->tableUserQuestionsMoney . '.user_id_from', $this->columnProfileStandard);
		$select->where('user_id_to = ?', $this->myId);
		$select->where('text_answer IS NULL');
		$select->order('date_create DESC');

		#Sas_Debug::dump($select->__toString());
		return $this->db->fetchAll($select);
	}*/

	/**
	 * Сохранение платных ответов
	 * @param $data
	 * @return int ID пользователя которому ответили
	 */
	/*public function saveAnswerMoney($data)
	{
		$update = array(
			'text_answer' => htmlspecialchars(strip_tags(trim($data['text_answer']))),
			'question_is_read' => 'yes'
		);
		$this->db->update($this->tableUserQuestionsMoney, $update, $this->db->quoteInto('id = ?', $data['id']));

		$select = $this->db->select();
		$select->from($this->tableUserQuestionsMoney, 'user_id_from');
		$select->where('id = ?', $data['id']);
		$select->limit(1);
		return $this->db->fetchOne($select);
	}*/

	/**
	 * Возвращает ответы полученные на платные вопросы
	 * @return array
	 */
	/*public function getAnswerMoney()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableUserQuestionsMoney, '*');
		$select->join($this->tableProfile, $this->tableProfile.'.id = ' . $this->tableUserQuestionsMoney . '.user_id_to', $this->columnProfileStandard);
		$select->where('user_id_from = ?', $this->myId);
		$select->where('question_is_read = ?', 'yes');
		$select->where('answer_is_read = ?', 'no');
		$select->where('text_answer IS NOT NULL');
		$select->order('date_create DESC');

		#Sas_Debug::dump($select->__toString());
		return $this->db->fetchAll($select);
	}*/

	/**
	 * Возвращает ответы вопросы
	 * @return array
	 */
	/*public function getAnswerFree()
	{
		$select = new Zend_Db_Select($this->db);
		$select->from($this->tableUserQuestions, '*');
		$select->join($this->tableProfile, $this->tableProfile.'.id = ' . $this->tableUserQuestions . '.user_id_to', $this->columnProfileStandard);
		$select->join($this->tableQuestions, $this->tableQuestions.'.id = ' . $this->tableUserQuestions.'.question_id', array('text_question'=>'question_'.$this->lang));
		$select->where('user_id_from = ?', $this->myId);
		$select->where('question_is_readed = ?', 'yes');
		$select->where('answer_is_readed = ?', 'no');
		$select->where('answer IS NOT NULL');
		$select->order($this->tableUserQuestions.'.date_created DESC');

		#Sas_Debug::dump($select->__toString());
		return $this->db->fetchAll($select);
	}*/

	//----- HELPERS ---
	/*private function myArrayMerge($arr1, $arr2)
	{
		$cnt1 = count($arr1);
		$cnt2 = count($arr2);


	}*/

}
<?php

class Models_Admin
{
	private $login = 'Sasha';
	private $password = '9258052505';

	/**
	 * Отвечает на "вопрос" авторизован пользователь или нет
	 * @return bool true - авторизован | false - нет
	 */
	public function isAut()
	{
		if (isset($_SESSION['admin']['aut']) && $_SESSION['admin']['aut'] === true) {
			return true;
		}

		return false;
	}

	/**
	 * Проверка авторизации пользователя
	 * @param $login
	 * @param $password
	 * @return bool true - проверка пройдена | false - нет
	 */
	public function checkAut($login, $password)
	{
		if ($login == $this->login && $password == $this->password) {
			$_SESSION['admin']['aut'] = true;
			return true;
		}

		return false;
	}

	/**
	 * Выход
	 * @return bool всегда true
	 */
	public function quit()
	{
		session_destroy();
		$_SESSION = null;
		unset($_SESSION);

		return true;
	}
}
<?php

/**
 * Отладчик скриптов.
 * 
 * @category Sas
 * @package Sas_Debug
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2008 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 2.0
 */
class Sas_Debug {

	/**
	 * Кодировка по умолчанию для вывода сообщений от отладчика. 
	 *
	 * @var string
	 */
	static $encode = 'UTF-8';
	
	/**
	 * SAPI
	 *
	 * @var string
	 */
	protected static $_sapi = null;
	
	/**
	 * Устанавливает новую кодировку для отладчика.
	 * @param string $newEncode Новая кодировка
	 */
	public static function setEncode($newEncode) {
		self::$encode = $newEncode;
	}

	/**
	 * Возвращает текущую кодировку отладчика.
	 * 
	 * @return string
	 */
	public static function getEncode() {
		return self::$encode;
	}
	
	/**
	 * Вспомогательная функция для отладки скриптов.
	 *
	 * @param  mixed  $var   The variable to dump.
	 * @param  string $label OPTIONAL Label to prepend to output.
	 * @param  bool   $echo  OPTIONAL Echo output if true.
	 * @return string
	 */
	public static function dump($var, $label=null, $echo=true) {
		// format the label
		$label = ($label===null) ? '' : rtrim($label) . ' ';

		// var_dump the variable into a buffer and keep the output
		ob_start();
		var_dump($var);
		$output = ob_get_clean();

		// neaten the newlines and indents
		$output = preg_replace("/\\]\\=\\>\n(\\s+)/m", "] => ", $output);
		if (self::getSapi() == 'cli') {
			$output = PHP_EOL . $label
			. PHP_EOL . $output
			. PHP_EOL;
		} else {
			$output = '<pre class="debugDump">----------'
			. '<strong>' . $label . '</strong>' . "\n"
			. htmlentities($output, ENT_QUOTES, self::getEncode())
			. '----------</pre>';
		}

		if ($echo) {
			echo($output);
		}
		
		return $output;
	}

	/**
	 * Вспомогательная функция для отладки SQL.
	 *
	 * @param  mixed  $select   The variable to dump.
	 * @param  string  $text   Текст описания запроса.
	 * @return string
	 */
	public static function sql($select, $text = null) {
		ob_start();
		$query = $select->__toString();
		print $query;
		$output = ob_get_clean();

		$output = preg_replace("/\\]\\=\\>\n(\\s+)/m", "] => ", $output);
		if (self::getSapi() == 'cli') {
			$output = PHP_EOL . 'SQL: '
				. PHP_EOL . $output
				. PHP_EOL;
		} else {
			$output = '<p class="debugSQL"><strong>SQL ('.$text.'): '
				.'</strong><br/>' . "\n"
				. htmlentities($output, ENT_QUOTES, self::getEncode())
				//.$output
				. '</p>';
		}

		echo($output);

		return $output;
	}
	
	/**
	 * Get the current value of the debug output environment.
	 * This defaults to the value of PHP_SAPI.
	 *
	 * @return string;
	 */
    public static function getSapi() {
		if (self::$_sapi === null) {
			self::$_sapi = PHP_SAPI;
		}
		
		return self::$_sapi;
	}

	/**
	 * Set the debug ouput environment.
	 * Setting a value of null causes Sas_Debug to use PHP_SAPI.
	 *
	 * @param string $sapi
	 * @return void;
	 */
	public static function setSapi($sapi) {
		self::$_sapi = $sapi;
	}
}
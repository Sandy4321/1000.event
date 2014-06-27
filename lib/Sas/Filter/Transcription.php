<?php

/**
 * Библиотека Sas для работы с транскрипцией.
 * 
 * @category Sas
 * @package Sas_Filter
 * @author Alexander Klabukov
 * @copyright Copyright (c) 1975-2009 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 1.0
 */

class Sas_Filter_Transcription
{
	#private $ru = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я');
	#private $en = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
	
	static private $ru = array('а','б','в','г','д','е','ё','ж',  'з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц',  'ч', 'ш', 'щ', 'ъ','ы','ь',  'э','ю','я');
	static private $en = array('a','b','v','g','d','e','yo','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','shch','`','y','`','e','yu','ya');
	
	static public function ru_en($text)
	{
		return str_replace(self::$ru, self::$en, $text);
	} 
}
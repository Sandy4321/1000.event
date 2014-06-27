<?php

class Sas_Filter_Strto
{
	static $ru_lower = array('а','б','в','г','е','ё','й','ц','у','к','н','ш','щ','з','х','ъ','ф','ы','п','р','о','л','д','ж','э', 'я','ч','с','м','и','т','ь','ю');
	static $ru_upper = array('А','Б','В','Г','Е','Ё','Й','Ц','У','К','Н','Ш','Щ','З','Х','Ъ','Ф','Ы','П','Р','О','Л','Д','Ж','Э', 'Я','Ч','С','М','И','Т','Ь','Ю');
	
	static function lower_ru($text) {
    	return str_replace(self::$ru_upper,self::$ru_lower,strtolower($text));
	}
	
	static function upper_ru($text) {
    	return str_replace(self::$ru_lower,self::$ru_upper,strtoupper($text));
	}
}
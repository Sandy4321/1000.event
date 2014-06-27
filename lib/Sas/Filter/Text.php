<?php

class Sas_Filter_Text
{
	static function get($text) {
		$text = htmlspecialchars(strip_tags(trim($text)));
		return (!empty($text)) ? $text : null;
	}
}
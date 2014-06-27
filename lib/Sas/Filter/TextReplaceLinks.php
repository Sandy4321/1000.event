<?php

class Sas_Filter_TextReplaceLinks
{
	static function get($text) {
		return preg_replace("#(https?|ftp)://\S+[^\s.,> )\];'\"!?]#",'<a href="\\0" target="_blank">\\0</a>', $text);
	}
}
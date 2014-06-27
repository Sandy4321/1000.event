<?php

class Sas_Translate_Yandex
{
	private $host = 'https://translate.yandex.net/api/';
	private $version = 'v1.5';
	private $type = 'tr.json';
	private $api_key = 'trnsl.1.1.20140612T074828Z.defee056ee17096a.509e47d87fe44ff0ee39f7e8f938bab68d1dc784';
	private $url;

	private $text_original = null;
	private $text_translate = null;
	private $lang = null;
	private $lang_from = null;
	private $lang_to = null;

	private $error = false;

	public function __construct()
	{
		$this->url = $this->host . '/' . $this->version . '/' . $this->type . '/translate?key=' . $this->api_key;
	}

	public function translateText($text, $lang)
	{
		$this->setText($text);
		$this->setLang($lang);

		if(empty($text) || is_null($text)) {
			return $this->getTextOriginal();
		}

		try {
			$this->goYandex();
			return $this->getTextTranslate();
		} catch (Sas_Exception $e) {
			return $this->getTextOriginal();
		}
	}

	private function goYandex()
	{
		$get = $this->url .'&text='. urlencode($this->getTextOriginal()).'&lang='. $this->getLang();
		$json = @file_get_contents($get, false);
		$data = $json ? json_decode($json, true) : array();

		if($data['code'] == 200) {
			$this->setLang($data['lang']);
			$this->setTextTranslate($data['text'][0]);
		} else {
			$this->error = ($data['code']) ? $data['code'] : 'No code error';;
			throw new Sas_Exception('Error yandex translate', $data['code']);
		}
	}

	public function isError() {
		return ($this->error) ? true : false;
	}

	private function setText($text)
	{
		$this->text_original = $text;

		return $this;
	}

	public function getTextOriginal()
	{
		return $this->text_original;
	}

	public function getTextTranslate()
	{
		return $this->text_translate;
	}

	/**
	 * @param $text_translate
	 * @return $this
	 */
	private function setTextTranslate($text_translate)
	{
		$this->text_translate = $text_translate;

		return $this;
	}

	public function getLang()
	{
		return $this->lang;
	}

	/**
	 * @param $lang
	 * @return $this
	 */
	private function setLang($lang)
	{
		$this->lang = $lang;

		$l = explode('-', $lang);
		if(empty($l[1])) {
			$this->setLangFrom(null);
			$this->setLangTo($l[0]);
		} else {
			$this->setLangFrom($l[0]);
			$this->setLangTo($l[1]);
		}

		return $this;
	}

	/**
	 * @return null
	 */
	public function getLangFrom()
	{
		return $this->lang_from;
	}

	/**
	 * @return null
	 */
	public function getLangTo()
	{
		return $this->lang_to;
	}

	/**
	 * @param null $lang_from
	 */
	public function setLangFrom($lang_from)
	{
		$this->lang_from = $lang_from;
	}

	/**
	 * @param null $lang_to
	 */
	public function setLangTo($lang_to)
	{
		$this->lang_to = $lang_to;
	}

}
#https://translate.yandex.net/api/v1.5/tr.json/translate?key=trnsl.1.1.20140612T074828Z.defee056ee17096a.509e47d87fe44ff0ee39f7e8f938bab68d1dc784&text=Привет&lang=ru-en
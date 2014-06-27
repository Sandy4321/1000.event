<?php

/**
 * Библиотека Sas для работы с датами.
 * 
 * @category Sas
 * @package Sas_Date
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2008 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 1.0
 */
class Sas_Date
{
	/**
	 * Unix timestamp
	 *
	 * @var int
	 */
	private $date  = null;
	
	private $day    = null;
	private $month  = null;
	private $year   = null;
	private $hour   = null;
	private $minute = null;
	private $second = null;
	private $dayMonth = null;
	private $numberDayWeek  = null;
	
	function __construct($date = null)
	{
		if (is_null($date)) {
			$this->date = time();
		} else {
			$this->date = strtotime($date);
		}
		$this->day    = date('d', $this->date);
		$this->month  = date('m', $this->date);
		$this->year   = date('Y', $this->date);
		$this->hour   = date('H', $this->date);
		$this->minute = date('i', $this->date);
		$this->second = date('s', $this->date);
		$this->dayMonth = date('t', $this->date);
		$this->numberDayWeek = date('w', $this->date);
	}
	
	/**
	 * Возвращает Unix timestamp
	 *
	 * @return int
	 */
	public function getUnixTime()
	{
		return $this->date;
	}
	
	public function getDay()
	{
		return $this->day;
	}
	
	public function getMonth()
	{
		return $this->month;
	}
	
	public function getMonthString($type = 'месяц')
	{
		$m = '';
		switch ($this->month) {
			case 1:
				if ($type == 'месяц')  {$m = 'январь';}
				if ($type == 'месяца') {$m = 'января';}
				if ($type == 'месяце') {$m = 'январе';}
			break;
			case 2:
				if ($type == 'месяц')  {$m = 'февраль';}
				if ($type == 'месяца') {$m = 'февраля';}
				if ($type == 'месяце') {$m = 'феврале';}
			break;
			case 3:
				if ($type == 'месяц')  {$m = 'март';}
				if ($type == 'месяца') {$m = 'марта';}
				if ($type == 'месяце') {$m = 'марте';}
			break;
			case 4:
				if ($type == 'месяц')  {$m = 'апрель';}
				if ($type == 'месяца') {$m = 'апреля';}
				if ($type == 'месяце') {$m = 'апреле';}
			break;
			case 5:
				if ($type == 'месяц')  {$m = 'май';}
				if ($type == 'месяца') {$m = 'мая';}
				if ($type == 'месяце') {$m = 'мае';}
			break;
			case 6:
				if ($type == 'месяц')  {$m = 'июнь';}
				if ($type == 'месяца') {$m = 'июня';}
				if ($type == 'месяце') {$m = 'июне';}
			break;
			case 7:
				if ($type == 'месяц')  {$m = 'июль';}
				if ($type == 'месяца') {$m = 'июля';}
				if ($type == 'месяце') {$m = 'июле';}
			break;
			case 8:
				if ($type == 'месяц')  {$m = 'август';}
				if ($type == 'месяца') {$m = 'августа';}
				if ($type == 'месяце') {$m = 'августе';}
			break;
			case 9:
				if ($type == 'месяц')  {$m = 'сентябрь';}
				if ($type == 'месяца') {$m = 'сентября';}
				if ($type == 'месяце') {$m = 'сентябре';}
			break;
			case 10:
				if ($type == 'месяц')  {$m = 'октябрь';}
				if ($type == 'месяца') {$m = 'октября';}
				if ($type == 'месяце') {$m = 'октябре';}
			break;
			case 11:
				if ($type == 'месяц')  {$m = 'ноябрь';}
				if ($type == 'месяца') {$m = 'ноября';}
				if ($type == 'месяце') {$m = 'ноябре';}
			break;
			case 12:
				if ($type == 'месяц')  {$m = 'декабрь';}
				if ($type == 'месяца') {$m = 'декабря';}
				if ($type == 'месяце') {$m = 'декабре';}
			break;
		}
		return $m;
	}
	
	public function getYear()
	{
		return $this->year;
	}
	
	public function getTime()
	{
		return $this->hour.':'.$this->minute;
	}
	
	public function getTimeFull()
	{
		return $this->hour.':'.$this->minute.':'.$this->second;
	}
	
	public function getSqlDate()
	{
		return $this->year.'-'.$this->month.'-'.$this->day;
	}
	
	public function getSqlDateTime()
	{
		return $this->getSqlDate().' '.$this->getTimeFull();
	}
	
	public function getMonthToInt($monthString)
	{
		$mS = substr(strtolower($monthString), 0, 3);
		switch($mS)
		{
			case 'янв': $mI='01'; break;
			case 'ЯНВ': $mI='01'; break;
			
			case 'фев': $mI='02'; break;
			case 'ФЕВ': $mI='02'; break;
			
			case 'мар': $mI='03'; break;
			case 'МАР': $mI='03'; break;
			
			case 'апр': $mI='04'; break;
			case 'АПР': $mI='04'; break;
			
			case 'мая': $mI='05'; break;
			case 'МАЯ': $mI='05'; break;
			
			case 'июн': $mI='06'; break;
			case 'ИЮН': $mI='06'; break;
			
			case 'июл': $mI='07'; break;
			case 'ИЮЛ': $mI='07'; break;
			
			case 'авг': $mI='08'; break;
			case 'АВГ': $mI='08'; break;
			
			case 'сен': $mI='09'; break;
			case 'СЕН': $mI='09'; break;
			
			case 'окт': $mI='10'; break;
			case 'ОКТ': $mI='10'; break;
			
			case 'ноя': $mI='11'; break;
			case 'НОЯ': $mI='11'; break;
			
			case 'дек': $mI='12'; break;
			case 'ДЕК': $mI='12'; break;
		}
		return $mI;
	}
	
	/**
	 * Возвращает количество дней в месяце
	 *
	 * @return int
	 */
	public function getDayMonth()
	{
		return (int)$this->dayMonth;
	}
	
	/**
	 * Возвращает номер текущего дня недели
	 *
	 * @return int
	 */
	public function getNumberDayWeek()
	{
		return (int)$this->numberDayWeek;
	}
	
	public function getDateString()
	{
		return $this->getDay().' '.$this->getMonthString('месяца').' '.$this->getYear();
	}
}
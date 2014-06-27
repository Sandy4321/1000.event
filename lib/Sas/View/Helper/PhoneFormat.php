<?php

// см. http://www.webmaze.ru/formatirovanie-telefonnyh-nomerov-na-php/
/*$data = Array(
	'886'=>Array(
		'name'=>'Taiwan',
		'cityCodeLength'=>1,
		'zeroHack'=>false,
		'exceptions'=>Array(89,90,91,92,93,96,60,70,94,95),
		'exceptions_max'=>2,
		'exceptions_min'=>2
	),
);*/

class Sas_View_Helper_PhoneFormat extends Zend_View_Helper_Abstract
{
	public function PhoneFormat($phone = '', $phoneCodes = null)
	{
		if (empty($phone)) {
			return '';
		}

		if(is_null($phoneCodes)) {
			$phoneCodes = Array(
				'7'=>Array(
					'name'=>'Russia',
					'cityCodeLength' => 1,
					'zeroHack'       => false,
					'exceptions'     => Array(901,902,903,904,905,906,908,909,910,911,912,913,914,915,916,917,918,919,920,921,922,923,924,925,926,927,928,929,930,931,932,933,934,936,937,938,950,951,952,953,960,961,962,963,964,965,967,968,980,981,982,983,984,985,987,988,989,997),
					'exceptions_max' => 3,
					'exceptions_min' => 2
				),
			);
		}

		// очистка от лишнего мусора с сохранением информации о "плюсе" в начале номера
		$phone=trim($phone);
		$plus = ($phone[ 0] == '+');
		$phone = preg_replace("/[^0-9A-Za-z]/", "", $phone);
		$OriginalPhone = $phone;

		// конвертируем буквенный номер в цифровой
		if (!is_numeric($phone)) {
			$replace = array('2'=>array('a','b','c'),
							 '3'=>array('d','e','f'),
							 '4'=>array('g','h','i'),
							 '5'=>array('j','k','l'),
							 '6'=>array('m','n','o'),
							 '7'=>array('p','q','r','s'),
							 '8'=>array('t','u','v'),
							 '9'=>array('w','x','y','z'));

			foreach($replace as $digit=>$letters) {
				$phone = str_ireplace($letters, $digit, $phone);
			}
		}

		// заменяем 00 в начале номера на +
		if (substr($phone,  0, 2)=="00")
		{
			$phone = substr($phone, 2, strlen($phone)-2);
			$plus=true;
		}

		// если телефон длиннее 7 символов, начинаем поиск страны
		if (strlen($phone)>7)
			foreach ($phoneCodes as $countryCode=>$data)
			{
				$codeLen = strlen($countryCode);
				if (substr($phone,  0, $codeLen)==$countryCode)
				{
					// как только страна обнаружена, урезаем телефон до уровня кода города
					$phone = substr($phone, $codeLen, strlen($phone)-$codeLen);
					$zero=false;
					// проверяем на наличие нулей в коде города
					if ($data['zeroHack'] && $phone[0]=='0')
					{
						$zero=true;
						$phone = substr($phone, 1, strlen($phone)-1);
					}

					$cityCode = null;

					// сначала сравниваем с городами-исключениями
					if ($data['exceptions_max'] != 0)
						for ($cityCodeLen=$data['exceptions_max']; $cityCodeLen>=$data['exceptions_min']; $cityCodeLen--)
							if (in_array(intval(substr($phone,  0, $cityCodeLen)), $data['exceptions']))
							{
								$cityCode = ($zero ? "0" : "").substr($phone,  0, $cityCodeLen);
								$phone = substr($phone, $cityCodeLen, strlen($phone)-$cityCodeLen);
								break;
							}
					// в случае неудачи с исключениями вырезаем код города в соответствии с длиной по умолчанию
					if (is_null($cityCode))
					{
						$cityCode = substr($phone,  0, $data['cityCodeLength']);
						$phone = substr($phone, $data['cityCodeLength'], strlen($phone)-$data['cityCodeLength']);
					}
					// возвращаем результат
					return ($plus ? "+" : "").$countryCode.'('.$cityCode.')' . $this->phoneBlocks($phone);
				}
			}
		// возвращаем результат без кода страны и города
		return ($plus ? "+" : "") . $this->phoneBlocks($phone);
	}

	private function phoneBlocks($number){
		$add='';
		if (strlen($number)%2)
		{
			$add = $number[ 0];
			$add .= (strlen($number) <= 5 ? "-" : "");
			$number = substr($number, 1, strlen($number)-1);
		}
		return $add.implode("-", str_split($number, 2));
	}
}
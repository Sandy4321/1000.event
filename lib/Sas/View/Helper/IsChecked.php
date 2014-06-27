<?php

class Sas_View_Helper_IsChecked extends Zend_View_Helper_Abstract
{
	/**
	 * Проверяет соответствия значений и в случае совпадения возвращает " checked" (с ведущим пробелом!!!)
	 * @param $valueCheck
	 * @param $check
	 * @return null|string
	 */
	public function IsChecked($valueCheck, $check)
	{
		$result = null;
		if ($valueCheck == $check)
		{
			$result = ' checked="checked"';
		}

		return $result;
	}
}
<?php

class Sas_View_Helper_HtmlFormSelect extends Zend_View_Helper_Abstract
{
	/**
	 * Создание формы select
	 *
	 * @param $elementName название формы
	 * @param $arrayValue так же можно передать массив вида: array('range'=>'1960-2012', 'step'=>'10')
	 * @param $checkValue значение для сверки
	 * @param null $styleClass класс(ы) стилей вида 'oneClass bigClass actionView' и т.д. через пробел
	 * @param array $options prefix, postfix, nullValue, nullValue
	 *
	 * @return string
	 */
	public function HtmlFormSelect($elementName, $arrayValue, $checkValue, $styleClass = null, $options = array())
    {
		$prefix  = (isset($options['prefix']))  ? $options['prefix']  : null;
		$postfix = (isset($options['postfix'])) ? $options['postfix'] : null;
		$nullName  = (isset($options['nullName']))  ? $options['nullName']  : null;
		$nullValue = (isset($options['nullValue'])) ? $options['nullValue'] : null;

		$str = '<select name="'.$elementName.'" id="'.$elementName.'"';

		// Классы стилей
		if (!is_null($styleClass)) {
			$str .= ' class="'.$styleClass.'"';
		}

		$str .= '>';

		// NULL значение
		if (!is_null($nullName)) {
			$str .= '<option';
			if (!is_null($nullValue)) $str .= ' value="'.$nullValue.'"';
			$str .= '>'.$nullName.'</option>'."\n";
		}

		if(!$arrayValue['range']) {
			foreach($arrayValue as $key => $value) {
				$str .= '<option value="' . $key . '"';

				if($key == $checkValue) {
					$str .= ' selected="selected"';
				}

				$str .= '>';
				$str .=  $value;
				$str .= '</option>'."\n";
			}
		} else {
			$range = explode('-', $arrayValue['range']);
			for($i = $range[0]; $i <= $range[1]; $i += $arrayValue['step']) {
				$str .= '<option value="' . $i . '"';

				if($i == $checkValue) {
					$str .= ' selected="selected"';
				}

				$str .= '>';
				if (!is_null($prefix)) $str .= $prefix;
				$str .= $i;
				if (!is_null($postfix)) $str .= $postfix;
				$str .= '</option>'."\n";
			}
		}

		$str .= '</select>';

		return $str;
	}
}
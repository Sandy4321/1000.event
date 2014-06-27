<?php

/**
 * SiteActionSystems (SAS)
 *
 * Плагин постраничной прокрутки с кнопками.
 *
 * @category Sas
 * @package Sas_View
 * @subpackage Sas_View_Helper
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2013 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 1.0
 */
class Sas_View_Helper_RollerPageButton extends Zend_View_Helper_Abstract
{
	public function RollerPageButton($pageCurrent, $maxRows, $limit, $postData)
	{
		unset($postData['page']);

		$dopData = '';
		foreach($postData as $key => $val) {
			// т.к. языки идут в массиве, их обрабатываем отдельно!
			if($key == 'languages') {
				foreach ($val as $kL => $vL) {
					$dopData .=  '<input type="hidden" name="languages['.$kL.']" value="'.$vL.'">'."\n";
				}
			} elseif(is_array($val) && $key != 'languages') {
				foreach ($val as $kL => $vL) {
					$dopData .=  '<input type="hidden" name="'.$key.'[]" value="'.$vL.'">'."\n";
				}
			} else {
				$dopData .=  '<input type="hidden" name="'.$key.'" value="'.$val.'">'."\n";
			}
		}

		// Проверка деления на НОЛЬ
		if ($maxRows <= 0) return null;

		$back = null;
		$step = 2;
		$start = 1;

		// Делаем расчёты
		$pageMax = ceil($maxRows/$limit);
		$next = ($pageCurrent >= $pageMax) ? null : $pageCurrent + 1;
		$back = ($pageCurrent == 1) ? null : $pageCurrent - 1;

		// Формируем HTML код для постраничного просмотра.
		$html = '<div class="row-fluid roller_page">';

		// Ссылка назад
		$html .= '<div class="span4 pagination-centered back ">';
		if (!is_null($back)) {
			$html .= '<form action="'. $this->view->url().'" method="post"">';
			$html .= '<input type="hidden" name="page" value="'. $back .'">';
			$html .= $dopData;
			$html .= '<input type="submit" class="btn btn-link" value="&larr; '.$this->view->t('Назад').'">';
			$html .= '</form>';
		}
		$html .= '</div>';
		// END Ссылка назад

		// Вывод номеров страниц
		$html .= '<div class="span4 pagination-centered pagesNumber">';

		// Вывод ссылки на первую страницу
		if (!is_null($back) && $pageCurrent > $step + 1) {
			$html .= '<div><form action="'. $this->view->url().'" method="post"">';
			$html .= '<input type="hidden" name="page" value="'. $start .'">';
			$html .= $dopData;
			$html .= '<input type="submit" class="btn btn-link" value="1">';
			$html .= '</form></div>';
			if ($pageCurrent > $step + 2) {
				$html .= '<div>...</div>';
			}
		}
		// END Вывод ссылки на первую страницу

		// Вывод ссылок на промежуточные страницы
		$startCnt = ($pageCurrent - $step <= 0) ? 1 : $pageCurrent - $step;
		for ($i = $startCnt; $i <= $pageMax; $i++) {
			if ($i == $pageCurrent) {
				$html .= '<div class="current"><button class="btn btn-link">'.$i.'</button></div>';
			} else {
				$html .= '<div><form action="'. $this->view->url().'" method="post"">';
				$html .= '<input type="hidden" name="page" value="'. $i .'">';
				$html .= $dopData;
				$html .= '<input type="submit" class="btn btn-link" value="'.$i.'">';
				$html .= '</form></div>';
			}

			if ($pageCurrent + $step <= $i) {
				break;
			}
		}
		// END Вывод ссылок на промежуточные страницы

		// Вывод ссылки на последную страницу
		if (!is_null($next) && ($pageCurrent < $pageMax - $step)) {
			if ($pageCurrent < $pageMax - $step - 1) {
				$html .= '<div>...</div>';
			}

			$html .= '<div><form action="'.$this->view->url().'" method="post"">';
			$html .= '<input type="hidden" name="page" value="'. $pageMax .'">';
			$html .= $dopData;
			$html .= '<input type="submit" class="btn btn-link" value="'.$pageMax.'">';
			$html .= '</form></div>';
		}
		// END Вывод ссылки на последную страницу

		$html .= '</div>';
		// END Вывод номеров страниц

		// Ссылка далее
		$html .= '<div class="span4 pagination-centered next">';
		if (!is_null($next)) {
			$html .= '<form action="'.$this->view->url().'" method="post"">';
			$html .= '<input type="hidden" name="page" value="'. $next .'">';
			$html .= $dopData;
			$html .= '<input type="submit" class="btn btn-link" value="'.$this->view->t('Далее').' &rarr;">';
			$html .= '</form>';
		}
		$html .= '</div>';
		// END Ссылка далее

		$html .= '</div>';
		// END Формируем HTML код для постраничного просмотра.

		return $html;
	}
}
<?php

/**
 * SiteActionSystems (SAS)
 * 
 * Плагин постраничной прокрутки.
 * 
 * @category Sas
 * @package Sas_View
 * @subpackage Sas_View_Helper
 * @author Alexander Klabukov
 * @copyright Copyright (c) 2009 Alexander Klabukov. (http://www.klabukov.ru)
 * @version 1.0
 */
class Sas_View_Helper_RollerPage extends Zend_View_Helper_Abstract
{
	public function RollerPage($pageCurrent, $maxRows, $limit)
    {
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
    	$html = '<div class="roller_page">';
    	
    	// Ссылка назад
    	$html .= '<div class="back">';
		if (!is_null($back)) {
			$html .= '&larr; <a href="'.$this->view->url(array('page' => $back)).'/">'.$this->view->t('Назад').'</a>';
		}
    	$html .= '</div>';
    	// END Ссылка назад
    	
    	// Вывод номеров страниц
    	$html .= '<div class="pagesNumber">';
    	
    	// Вывод ссылки на первую страницу
		if (!is_null($back) && $pageCurrent > $step + 1) {
			$html .= '<div><a href="'.$this->view->url(array('page' => $start)).'/">'.$start.'</a></div>';
			if ($pageCurrent > $step + 2) {
				$html .= '<div>...</div>';
			}
		}
		// END Вывод ссылки на первую страницу
		
		// Вывод ссылок на промежуточные страницы
		$startCnt = ($pageCurrent - $step <= 0) ? 1 : $pageCurrent - $step;
		for ($i = $startCnt; $i <= $pageMax; $i++) {
			if ($i == $pageCurrent) {
				$html .= '<div class="current">'.$i.'</div>';
			} else {
				$html .= '<div><a href="'.$this->view->url(array('page' => $i)).'/">'.$i.'</a></div>';
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
			
			$html .=  '<div><a href="'.$this->view->url(array('page' => $pageMax)).'/">'.$pageMax.'</a></div>';
		}
		// END Вывод ссылки на последную страницу
	
    	$html .= '</div>';
    	// END Вывод номеров страниц
    	
    	// Ссылка далее
    	$html .= '<div class="next">';
		if (!is_null($next)) {
			$html .= '<a href="'.$this->view->url(array('page' => $next)).'/">'.$this->view->t('Далее').'</a> &rarr;';
		}
    	$html .= '</div>';
    	// END Ссылка далее
    	
    	$html .= '</div>';
    	// END Формируем HTML код для постраничного просмотра.
    	
    	return $html;
    }
}
<?php

class Sas_View_Helper_Like extends Zend_View_Helper_Abstract
{
	/**
	 * @param array $my
	 *        [id]     = мой ID,
	 *        [card]   = дата КК,
	 *        [status] = статус пользователя,
	 *
	 * @param array $data
	 *        [dataUserId] = id пользователя которому принадлежат данные,
	 *        [dataId]     = id данных которые лайкаем,
	 *        [ILikeKey]   = ключ, по наличию которых определяем лайкал ли я эти данные,
	 *        [cntLike]    = текущее кол-во лайков.
	 *
	 * @param array $text
	 *        [like]   = Нравится,
	 *        [likeMe] = Вам нравится,
	 *
	 * @param array $title
	 *        [likeNo]           = Заголовок при котором Вы не можете лайкнуть данные,
	 *        [likeClubOnly]     = Заголовок для поясняющий что лайки только для ЧК,
	 *        [listOpen]         = Заголовок для открытия списка лайкнувших,
	 *        [listOpenCardOnly] = Заголовок для открытия списка лайкнувших если нет КК.
	 *
	 * @param array $js
	 *        [module] = Модуль обработчика
	 *        [key]    = Название КЛЮЧА для js функций like('КЛЮЧ', dataId) и likeOpenPopupUsers('КЛЮЧ', dataId).
	 *
	 * @return string
	 */
	public function Like(array $my, array $data, array $text, array $title, array $js)
	{
		// Чьи данные для лайка?
		if($my['id'] == $data['dataUserId']) {
			// Это мои данные я не могу лайкать
			if($data['cntLike'] > 0) {
				// Владельцы КК могут открыть и посмотреть список лайкнувших
				if($my['card'] >= CURRENT_DATE) {
					// Разрешаем открыть список лайкнувших
					$result = $this->getBtn(false, $text['like'], $title['listOpen'], $data['cntLike'], array('fncName'=>'likeOpenPopupUsers', 'module'=>$js['module'], 'key'=>$js['key'], 'val'=>$data['dataId']));
				} else {
					// Простых пользователей перекидываем на покупку КК
					$result = $this->getBtn(false, $text['like'], $title['listOpenCardOnly'], $data['cntLike'], array('fncName'=>'goBuyCard', 'key'=>$title['listOpenCardOnly']));
				}

			} else {
				// Лайков ноль, смотреть нечего
				$result = $this->getBtn(false, $text['like'], $title['likeNo'], $data['cntLike']);
			}

		} else { // Это не мои данные

			// Я уже лайкал?
			if(isset($data['ILikeKey'])) {
				// Да, я уже лайкал, больше нельзя
				$result = $this->getBtn(false, $text['likeMe'], null, $data['cntLike']);
			} else {
				// Нет, я еще не лайкал
				// Я могу лайкнуть только ЕСЛИ я ЧК
				if($my['status'] >= 70) {
					$result = $this->getBtn(true, $text['like'], null, $data['cntLike'], array('fncName'=>'like', 'module'=>$js['module'], 'key'=>$js['key'], 'val'=>$data['dataId']));
				} else {
					$result = $this->getBtn(false, $text['like'], $title['likeClubOnly'], $data['cntLike'], array('fncName'=>'goWizard', 'key'=>$title['likeClubOnly']));
				}
			}
		}

		return $result;
	}

	private function getBtn($likeActive = true, $text = null, $title = null, $cnt = 0, $js = null) {
		$btn = '<button class="btn-like"';

		// js - onclick
		if(is_array($js)) {
			$btn .= ' onclick="'.$js['fncName'].'(';
			if($js['module']) $btn .= "'".$js['module']."', ";
			$btn .= "'".$js['key']."'";
			if(!empty($js['val'])) $btn .= ', '.$js['val'];
			$btn .= ')"';
		}

		// id елемента
		if(!empty($js['key']) && !empty($js['val'])) {
			$btn .= ' id="'.$js['key'].'-like-'.$js['val'].'"';
		}

		// title
		if(!is_null($title)) $btn .= ' title="'.$title.'"';

		$btn .= '>';

		$btn .= '<span class="btn-ico ';
		$btn .= ($likeActive) ? 'btn-ico-like' : 'btn-ico-like-no';
		$btn .= '"></span>';

		if(!is_null($text)) $btn .= '<span class="like-text">'.$text.'</span> ';

		$btn .= '<b class="like-cnt">'.$cnt.'</b>';

		$btn .= '</button>';

		return $btn;
	}
}
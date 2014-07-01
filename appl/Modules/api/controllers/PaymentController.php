<?php

/**
 * API платежей для закрытой части сайта
 */
class Api_PaymentController extends Sas_Controller_Action_User
{
	public function payUnitellerAction()
	{
		$data = $this->getRequest()->getPost();
		$json = array();

		$myId = Models_User_Model::getMyId();

		try {
			if($myId <= 0) throw new Sas_Models_Exception($this->t('Пользователя не существует.'), 1);
			if($data['item_type'] != 'karat' && $data['item_type'] != 'card')  throw new Sas_Models_Exception($this->t('Тип товара/услуги не соответствует допустимому.'), 1);
			if($data['item_quantity'] <= 0)  throw new Sas_Models_Exception($this->t('Количество товара/услуги не верное.'), 1);

			$money = ($data['item_type'] == 'card') ? $this->getPriceCard($data['item_quantity']) : $this->getPriceKarat($data['item_quantity']);

			$ModelUniteller = new Models_Uniteller();
			if($ModelUniteller->createOrder($myId, $data['item_type'], $data['item_quantity'], $money) != false) {
				$json['msg'] = 'ok';

				$json['form'] = '<form action="' . $ModelUniteller->getUrlForm() . '" method="post">';
				$json['form'] .= '<input type="hidden" name="Shop_IDP" value="'      . $ModelUniteller->getShopId() .      '">';
				$json['form'] .= '<input type="hidden" name="Order_IDP" value="'     . $ModelUniteller->getOrderID() .     '">';
				$json['form'] .= '<input type="hidden" name="Subtotal_P" value="'    . $ModelUniteller->getMoneyTotal() .  '">';
				$json['form'] .= '<input type="hidden" name="Lifetime" value="'      . $ModelUniteller->getLifeTime() .    '">';
				$json['form'] .= '<input type="hidden" name="Customer_IDP" value="'  . $ModelUniteller->getUserId() .      '">';
				$json['form'] .= '<input type="hidden" name="Signature" value="'     . $ModelUniteller->getSignature() .   '">';
				$json['form'] .= '<input type="hidden" name="URL_RETURN_OK" value="' . $ModelUniteller->getUrlReturnOk($this->getLang()) . '">';
				$json['form'] .= '<input type="hidden" name="URL_RETURN_NO" value="' . $ModelUniteller->getUrlReturnNo($this->getLang()) . '">';
				$json['form'] .= '<input type="hidden" name="MeanType" value="0">';
				$json['form'] .= '<input type="hidden" name="EMoneyType" value="0">';
				$json['form'] .= '<input type="submit" name="Submit" value="Ok">';
				$json['form'] .= '</form>';
			} else {
				throw new Sas_Exception($this->t('Ошибка создания счета для оплаты.'), 1);
			}

		} catch (Sas_Exception $e) {
			$json['error'] = $e->getMessage();
			$json['errno'] = $e->getCode();
		}

		$this->getJson($json);
	}

	/**
	 * Отметка согласия на рекурентные платежи
	 *
	 * Ожидает параметр key со значением yes|no
	 */
	public function recurrentAction()
	{
		$json = array();
		$myId = Models_User_Model::getMyId();

		$request = $this->getRequest();

		try {
			if($myId <= 0) throw new Sas_Models_Exception($this->t('Пользователя не существует.'), 1);

			$key = $request->getParam('key');
			if(!$key) throw new Sas_Models_Exception($this->t('Нет ключа (key) для отметки рекуррентных платежей.'), 1);
			if($key != 'yes' && $key != 'no') throw new Sas_Models_Exception($this->t('Ключ (key) для рекуррентных платежей не верный. Допустимое значение yes|no.'), 1);
			$key = ($key == 'no') ? 'no' : 'yes';
			$ModelProfile = new Models_Users($myId);

			if($ModelProfile->setRecurrent($key)->save()) {
				$json['msg'] = $ModelProfile->getRecurrentPayment();
			}

		} catch (Sas_Exception $e) {
			$json['error'] = $e->getMessage();
			$json['errno'] = $e->getCode();
		}

		$this->getJson($json);
	}

	/**
	 * Стоимость КК.
	 *
	 * @param $month
	 * @return int
	 */
	private function getPriceCard($month)
	{
		switch($month) {
			case 1: $price = 1000;break;
			case 2: $price = 1900;break;
			case 3: $price = 2700;break;
			case 4: $price = 3400;break;
			case 5: $price = 4000;break;

			default: $price = 1000;
		}

		return $price;
	}

	/**
	 * Стоимость Карат.
	 *
	 * @param $quantity
	 * @return int
	 */
	private function getPriceKarat($quantity)
	{
		if(Models_User_Model::getMySex() == 'female') {
			switch($quantity) {
				case 50: $price = 500;break;
				case 100: $price = 1000;break;
				case 300: $price = 2400;break;
				case 1000: $price = 7200;break;
				case 3000: $price = 19200;break;

				default: $price = 500;
			}
		} else {
			switch($quantity) {
				case 50: $price = 500;break;
				case 100: $price = 1000;break;
				case 300: $price = 3000;break;
				case 1000: $price = 9000;break;
				case 3000: $price = 24000;break;

				default: $price = 500;
			}
		}

		return $price;
	}

	#############################
	public function preDispatch()
	{
		$this->ajaxInit();
	}
}
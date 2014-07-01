<?php

class Partners_IndexController extends Sas_Controller_Action
{
	public function indexAction()
	{
		$restaurants = array(
			array("Moska Сafe", "", "moska-logo.png"),
			array("Ресторан RONI", "http://novikovgroup.ru/restaurants/roni/", "roni-logo.png"),
			array("Кафе Мечта", "http://www.mechta-cafe.ru/", "mechta-cafe-logo.png"),
			array("Рестораны Correa's", "http://www.correas.ru/", "correas-logo.png"),
			array("Ресторан Тан", "http://www.restorantan.ru/", "restorantan-logo.png"),
			array("Empress Hall", "http://www.happyland.su/", "empress-logo.png"),
			array("Rofl Cafe", "http://roflcafe.ru/", "roflcafe-logo.png"),
			array("Ресторан Gotinaza", "http://gotinaza.ru/", "gotinaza-logo.png"),
			array("Ресторан Костанай", "http://restkost.ru/", "restkost-logo.png"),
			array("Кафе &quot;Лубянка&quot; Караоке", "http://lubyankacafe.ru/", "lubyankacafe-logo.png"),
			array("Кафе &quot;Каша&quot;", "http://www.kasha-cafe.com/", "kasha-logo.png"),
			array("Кипрская таверна Старый Пафос", "http://starypafos.ru/", "starypafos-logo.png"),
			array("Кафе &quot;Чашки&quot;", "http://chashki.ru/", "chashki-logo.png"),
			array("Cafe Bali", "http://www.cafebali.ru/", "cafebali-logo.png"),
			array("Арт-Кафе Публика", "http://www.cafe-publika.ru/", "publika-logo.png"),
			array("Ресторан гриль &quot;Стейкс&quot;", "http://steaksgrill.ru/", "steaksgrill-logo.png"),
			array("Ресторан На Знаменке", "http://www.naznamenke.ru/", "naznamenke-logo.png"),
			array("Ресторан МоМо", "http://restoran-vip.ru/momo/m-menu/", "momo-logo.png"),
			array("Cafe Strudel", "http://www.facebook.com/strudelcafe", "strudel-logo.png"),
			array("Ресторан Courage", "http://www.couragebar.ru/", "couragebar-logo.png"),
			array("Ресторан Misato", "http://www.misato.ru/", "misato-logo.png"),
			array("Pane & Olio", "http://www.paneolio.ru/", "paneolio-logo.png"),
			array("Ресторан Гюго", "http://hugorest.ru/", "hugorest-logo.png"),
			array("Ресторан London Grill", "http://www.londongrill.ru/", "londongrill-logo.png")
		);

		$this->view->assign('vRestaurants', $restaurants);

		$infoPartner = array(
			array("http://www.megapolisfm.ru/", "megapolis-logo.png", "Мегаполис FM"),
			array("http://www.english-natali.ru/", "englishnatali-logo.png", "English Natali"),
			array("http://www.e-xecutive.ru/", "executive-logo.png", "E-xecutive"),
			array("http://meetpartners.ru/", "meetpartners-logo.png", "MeetPartners"),
			array("http://www.moevenpick-icecream.com/", "moevenpick-logo.png", "Moevenpick"),
			array("http://www.blackberry4u.ru/", "blackberry-logo.png", "Blackberry"),
			array("http://castweek.ru/", "castweek-logo.png", "Cast Week"),
			//array("http://www.evitastudio.ru/", "evita-logo.png", "Studio of beauty Evita")
			array("http://expatinrussia.com", "expatinrussia-logo.png", "EXPATinRUSSIA")
		);
		$this->view->assign('vInfoPartner', $infoPartner);

		#$vData[] =
		#$vData[] =
		//$vData[] = array('evita.jpg', 'www.evitastudio.ru');
		//$vData[] = array('logo_tobebride.jpg', 'www.tobebride.ru/?utm_source=onthelist&utm_medium=site&utm_campaign=271113');
		#$vData[] =
		#//$vData[] = array('cbs.jpg', 'www8.gsb.columbia.edu');
		#$vData[] =
		#$vData[] = array('cisclub.jpg', '#');
		#$vData[] = array('english-natali.jpg', '#');
		#$vData[] = array('executive.jpg', '');

		//$this->view->assign('vData', $vData);
	}
}

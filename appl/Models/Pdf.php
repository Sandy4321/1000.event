<?php

class Models_Pdf
{
	/**
	 * @var Sas_Pdf_FPDF
	 */
	private $pdf;

	public function createMegafonCertificate($firstName, $lastName, $id, $uid)
	{
		$imgPath = $_SERVER['DOCUMENT_ROOT'] . '/img/certificate/';

		$this->pdf = new Sas_Pdf_FPDF('L','mm','A4');
		$this->pdf->AddPage();

		$this->pdf->AddFont('Arial');
		$this->pdf->SetFont('Arial','',48);

		$this->pdf->Image($imgPath.'top-left.png',      15,  15, 50, 50, 'PNG');
		$this->pdf->Image($imgPath.'top-right.png',    230,  15, 50, 50, 'PNG');
		$this->pdf->Image($imgPath.'bottom-left.png',   15, 140, 50, 50, 'PNG');
		$this->pdf->Image($imgPath.'bottom-right.png', 230, 140, 50, 50, 'PNG');

		$this->pdf->Image($imgPath.'bg-big.png', $this->pdf->w/2 - 80, 20, 160, 160, 'PNG');
		$this->pdf->Image($imgPath.'gus.png', $this->pdf->w/2 - 10, 75, 20, 20, 'PNG');



		$this->pdf->Image($imgPath.'singature.png', 190, 125, 30, 30, 'PNG');
		$this->pdf->Image($imgPath.'stamp.png', 210, 110, 35, 35, 'PNG');


		$this->textCenter(30, 'Сертификат');
		$this->pdf->SetFontSize(10);
		$this->textCenter(5, 'Владелец настоящего сертификата  имеет право подключить до десяти абонентских номеров к');
		$this->textCenter(5, 'корпоративным тарифным планам Мегафон, разработанных специально для членов клуба OnTheList.');

		$this->pdf->Ln(50);
		$this->pdf->SetFontSize(16);
		$this->textCenter(5, 'Владелец сертификата:');
		$this->pdf->SetFontSize(22);
		$this->pdf->Ln(5);
		$this->textCenter(5, $firstName.' ' . $lastName);
		$this->pdf->SetFontSize(8);
		$this->textCenter(10, 'ID: '.$id.' UID: '.$uid);
		$this->pdf->Line(85, 117, $this->pdf->w - 85, 117);

		$this->pdf->Ln(20);
		$this->pdf->SetFontSize(10);
		$this->pdf->Line(165, 145, $this->pdf->w - 35, 145);
		$this->textRight(5, 'Генеральный директор ООО «ОнЗеЛист» Салимов А. М.                          ');


		$this->pdf->SetFontSize(10);
		$this->pdf->Ln(10);
		$this->textCenter(5, 'Подписанием настоящего сертификата компания подтверждает, что указанное лицо является членом клуба OnTheList.');
		$this->pdf->Ln(5);

		$this->textCenter(5, 'Для подключения к тарифам OnTheList Вам необходимо обратиться в офисы  МегаФон для VIP-клиентов по адресам:');
		$this->textCenter(5, 'г. Москва, ул. Тверская 22 либо г. Москва, ул. Новослободская 23, предъявить данный сертификат и паспорт.');

		return $this->pdf->Output('Certificate-Megafon.pdf', 'D');
	}

	private function utf8ToWin1251($text) {
		return iconv('UTF-8', 'WINDOWS-1251', $text);
	}

	private function textCenter($y, $text) {
		$this->pdf->Cell(0, $y, $this->utf8ToWin1251($text), 0, 1, 'C');
	}

	private function textRight($y, $text) {
		$this->pdf->Cell(0, $y, $this->utf8ToWin1251($text), 0, 1, 'R');
	}
}
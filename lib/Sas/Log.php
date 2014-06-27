<?php

class Sas_Log
{
	private $patch = PATH_DIR_LOG;

	public function __construct($newFileName, $dopDir = null) {
		if(!is_null($dopDir)) {
			$this->patch .= DIRECTORY_SEPARATOR . $dopDir;

			// Проверяем наличие директории
			if(!is_dir($this->patch))
			{
				// При отсутствии директории создаем её
				mkdir($this->patch, 0777, true);
			}
		}

		$this->patch .= DIRECTORY_SEPARATOR . date('Y-m-d') . '_'. $newFileName;
	}

	public function write($data) {
		$dt = date('Y-m-d H:i:s') . "\t";
		$fp = fopen($this->patch, 'a');
		fwrite($fp, $dt. $data ."\n");
		fclose($fp);
	}
}
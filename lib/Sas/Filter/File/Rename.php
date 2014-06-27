<?php

class Sas_Filter_File_Rename implements Zend_Filter_Interface
{
	private $newFileName = null;
	private $newFullPath = null;
	private $newFileExt = false;
	
	private $originalFullPath = null;
	private $originalFilePath = null;
	private $originalFileName = null;
	private $originalFileExt = false;
	
	/**
	 * Class constructor
	 *
	 * 'newFileName' => Новое имя файла
	 *
	 * @param  string $newFileName Новое имя файла
	 * @return void
	 */
	public function __construct($newFileName)
	{
		// Получаем расширение файла
		$this->newFileExt = strrchr($newFileName, '.');
		
		// Если расширение не задано
		if ($this->newFileExt === false) {
			$this->newFileName = $newFileName;
		} else {
			$this->newFileName = substr($newFileName, 0, strpos($newFileName, '.'));
		}
	}
	
	public function filter($value)
	{
		$this->originalFullPath = realpath($value);
		$this->originalFilePath = dirname($this->originalFullPath);
		$this->originalFileName = basename($this->originalFullPath);
		$this->originalFileExt  = strrchr($this->originalFileName, '.');
		
		#Sas_Debug::dump($this->originalFullPath, 'О полный путь с файлом');
		#Sas_Debug::dump($this->originalFilePath, 'О полный путь');
		#Sas_Debug::dump($this->originalFileName, 'О название файла');
		#Sas_Debug::dump($this->originalFileExt, 'О расширение файла');
		
		$this->setNewFullPath();
		
		return $this->renameFile();
	}
	
	private function renameFile()
	{
		$result = rename($this->originalFullPath, $this->getNewFullPath());
		
		if ($result === true) {
            return $this->newFullPath;
        }
        
        return false;
	}
	
	private function getNewFullPath() {
		return $this->newFullPath;
	}
	
	private function setNewFullPath()
	{
		if ($this->newFileExt === false) {
			$ext = strtolower($this->originalFileExt);
		} else {
			$ext = strtolower($this->newFileExt);
		}
		
		$this->newFullPath = $this->originalFilePath . DIRECTORY_SEPARATOR . $this->newFileName . $ext;
	}
	
}

<?php
class Sas_Filter_File_ResizeImage implements Zend_Filter_Interface
{
	private $_targetDir;
	private $_newWidth;
	private $_newHeight;
	private $_newSmallWidth;
	private $_newSmallHeight;
	private $_noBig;
	private $_quality;
	private $_saveOriginal;
	
	/**
	 * Constructor for filter
	 *
	 * @param array $options Options to set
	 */
	public function __construct ($options)
	{
		if ($options instanceof Zend_Config) {
			$options = $options->toArray();
		} elseif (is_string($options)) {
			$options = array('targetDir' => $options);
		} elseif (! is_array($options)) {
			throw new Zend_Filter_Exception('Invalid options argument provided to filter');
		}
		
		if (isset($options['targetDir']) && $options['targetDir']) {
			if (file_exists($options['targetDir'])) {
				if (is_writable($options['targetDir'])) {
					$this->_targetDir = $options['targetDir'];
				} else {
					throw new Zend_Filter_Exception('Directory is not writable. Invalid target directory argument provided to filter');
				}
			} else {
				throw new Zend_Filter_Exception('Directory doesn\'t exists .Invalid target directory argument provided to filter');
			}
		} else {
			throw new Zend_Filter_Exception('Invalid target directory argument provided to filter');
		}
		
		if (isset($options['newSize']) && $options['newSize']) {
			$arraySize = explode('*', $options['newSize']);
			
			if (count($arraySize) == 2 && intval($arraySize[0]) && intval($arraySize[1])) {
				$this->_newWidth = (int) $arraySize[0];
				$this->_newHeight = (int) $arraySize[1];
			} else {
				throw new Zend_Filter_Exception('Invalid image size argument provided to filter. It must be string "int*int".');
			}
		} else {
			throw new Zend_Filter_Exception('Invalid image size argument provided to filter');
		}
		
		if (isset($options['newSmallSize']) && $options['newSmallSize']) {
			$arraySmallSize = explode('*', $options['newSmallSize']);
			if (count($arraySmallSize) == 2 && intval($arraySmallSize[0]) && intval($arraySmallSize[1])) {
				$this->_newSmallWidth = (int) $arraySmallSize[0];
				$this->_newSmallHeight = (int) $arraySmallSize[1];
			} else {
				throw new Zend_Filter_Exception('Invalid image size argument provided to filter. It must be string "int*int".');
			}
		}
		
		if (isset($options['noBig']) && $options['noBig']) {
			$this->_noBig = $options['noBig'];
		} else {
			$this->_noBig = true;
		}
		
		if (isset($options['saveOriginal']) && $options['saveOriginal']) {
			$this->_saveOriginal = $options['saveOriginal'];
		} else {
			$this->_saveOriginal = true;
		}
	}
	
	/**
	 * Resize image and copy new file to need directory with equal filename
	 *
	 * @param string $value full filename
	 * @return string
	 */
	public function filter ($value)
	{
		$desrtoy = false;
		if (!$value) {
			return $value;
		}
		$fileName = basename($value);
		chmod($value, 0757);
		list ($imageWidth, $imageHeight, $ImageType) = getimagesize($value);
		$typeForFunction = "jpeg";
		$this->_quality = 70;
		/*if (IMAGETYPE_GIF == $ImageType) {
			$typeForFunction = "gif";
			$this->_quality = 7;
		} elseif (IMAGETYPE_PNG == $ImageType) {
			$typeForFunction = "png";
			$this->_quality = 7;
		}*/
		$widthKoef = $this->_newWidth / $imageWidth;
		$heightKoef = $this->_newHeight / $imageHeight;
		$koef = min($widthKoef, $heightKoef);
		$newWidth = $imageWidth * $koef;
		$newHeight = $imageHeight * $koef;
		
		if ($this->_newSmallWidth && $this->_newSmallHeight) {
			// Sasha
			$ext = strrchr($fileName, '.');
			$name = substr($fileName, 0, strpos($fileName, '.'));
			$fileSmallName = $name . '_thumbs' . $ext;
			
			$widthSmallKoef = $this->_newSmallWidth / $imageWidth;
			$heightSmallKoef = $this->_newSmallHeight / $imageHeight;
			$Smallkoef = min($widthSmallKoef, $heightSmallKoef);
			$newSmallWidth = $imageWidth * $Smallkoef;
			$newSmallHeight = $imageHeight * $Smallkoef;
		}
		
		// Сохранение оригинала
		if (isset($this->_saveOriginal)) {
			$ext = strrchr($fileName, '.');
			$name = substr($fileName, 0, strpos($fileName, '.'));
			
			copy($value, $this->_targetDir . '/' . $name . '_original' . $ext);
		}
		
		// создание большой фото
		if ($imageWidth <= $newWidth && $imageHeight <= $newHeight && $this->_noBig) {
			copy($value, $this->_targetDir . '/' . $fileName);
		} else {
			// Load
			$thumb = imagecreate($newWidth, $newHeight);
			$source = call_user_func("imagecreatefrom{$typeForFunction}", $value);
			#$source = call_user_func("imagecreatefromjpeg", $value);
			
			// Prepare thumb
			$thumb = imagecreatetruecolor($newWidth, $newHeight);
			// Resize
			imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);
			// Output
			call_user_func("image{$typeForFunction}", $thumb, $this->_targetDir . '/' . $fileName, $this->_quality);
			#call_user_func("imagejpeg", $thumb, $this->_targetDir . '/' . $fileName, $this->_quality);
			$desrtoy = true;
		}
		
		// создание маленькой фото (превью)
		if ($imageWidth <= $newSmallWidth && $imageHeight <= $newSmallHeight) {
			copy($value, $this->_targetDir . '/' . $fileSmallName);
		} else {
			##$thumb = imagecreate($newSmallWidth, $newSmallHeight);
			
			// в переменной value лежит путь у создаваемому изображению
			#$source = call_user_func("imagecreatefromjpeg", $value); 
			if (empty($source)) {
				$source = call_user_func("imagecreatefrom{$typeForFunction}", $value);
			}
			
			// Prepare thumb
			$thumb = imagecreatetruecolor($newSmallWidth, $newSmallHeight);
			
			// Resize
			imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newSmallWidth, $newSmallHeight, $imageWidth, $imageHeight);
			
			// Output
			call_user_func("image{$typeForFunction}", $thumb, $this->_targetDir . '/' . $fileSmallName, $this->_quality);
			#call_user_func("imagejpeg", $thumb, $this->_targetDir . '/' . $fileSmallName, $this->_quality);
			$desrtoy = true;
		}
		
		if ($desrtoy) {
			imagedestroy($thumb);
			imagedestroy($source);
		}
		return $value;
	}
}

<?php

class Sas_Image
{
	private $pathHost = '';

	/**
	 * Относительный путь для закрузки (относительно рабочей директории веб сервера)
	 * @var string
	 */
	private $imgDir = '';

	/**
	 * Полный путь для загрузки
	 * @var string
	 */
	private $fullUploadDir = '';

	/**
	 * Имя нового файла
	 * @var string
	 */
	private $imgNameMD5 = '';

	/**
	 * Оригинал файла
	 * @var string
	 */
	private $imgOriginalName = '';

	private $error = null;

	private $img = array();
	private $imgType = null;
	private $imgWidth = 0;
	private $imgHeight = 0;

	private $configOriginal = null;
	private $configOptimal  = null;
	private $configCrop     = null;


	/**
	 * Уменьшенная копия файла (превью)
	 * @var string
	 */
	private $imgThumbnailName = '';

	public function __construct() {
		$this->pathHost = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
	}

	/**
	 * Конфигурация сохранения оригинала фото
	 * @param string $name
	 * @param string $expansion
	 */
	public function configSaveOriginal($name = 'original', $expansion = 'jpg')
	{
		$this->configOriginal['name'] = $name;
		$this->configOriginal['ext']  = $expansion;
	}

	/**
	 * Конфигурация сохранения оптимальной фото
	 * @param null   $maxWidth
	 * @param null   $maxHeight
	 * @param string $name
	 * @param string $expansion
	 */
	public function configSaveOptimal($maxWidth = null, $maxHeight = null, $name = 'original', $expansion = 'jpg')
	{
		$this->configOptimal['maxWidth']  = $maxWidth;
		$this->configOptimal['maxHeight'] = $maxHeight;
		$this->configOptimal['name']      = $name;
		$this->configOptimal['ext']       = $expansion;
	}

	/**
	 * Конфигурация сохранения кропнутой фото
	 * @param null   $cropSize
	 * @param string $name
	 * @param string $expansion
	 */
	public function configSaveCrop($cropSize, $name = 'original', $expansion = 'jpg')
	{
		$this->configCrop['cropSize'] = $cropSize;
		$this->configCrop['name']     = $name;
		$this->configCrop['ext']      = $expansion;
	}

	/**
	 * Возвращает путь и оригинальное название фото
	 * @param bool $fullPath
	 * @return string
	 */
	public function getPathOriginalName($fullPath = null) {
		$img = DIRECTORY_SEPARATOR . $this->configOriginal['name'] . '.' . $this->configOriginal['ext'];
		$path = DIRECTORY_SEPARATOR . $this->imgDir . $img;
		if (!is_null($fullPath)) {
			$path = $this->getFullPath() .  $img;
		}

		return $path;
	}

	/**
	 * Возвращает путь и оптимальное название фото
	 * @param bool $fullPath
	 * @return string
	 */
	public function getPathOptimalName($fullPath = null) {
		$img = DIRECTORY_SEPARATOR . $this->configOptimal['name'] . '.' . $this->configOptimal['ext'];
		$path = DIRECTORY_SEPARATOR . $this->imgDir . $img;
		if (!is_null($fullPath)) {
			$path = $this->getFullPath() .  $img;
		}

		return $path;
	}

	/**
	 * Возвращает путь и кропнутое название фото
	 * @param bool $fullPath
	 * @return string
	 */
	public function getPathCropName($fullPath = null) {
		$img = DIRECTORY_SEPARATOR . $this->configCrop['name'] . '.' . $this->configCrop['ext'];
		$path = DIRECTORY_SEPARATOR . $this->imgDir . $img;
		if (!is_null($fullPath)) {
			$path = $this->getFullPath() .  $img;
		}

		return $path;
	}

	public function save($tmpName)
	{
		if(is_null($this->configOriginal) && is_null($this->configOptimal) && is_null($this->configCrop)) {
			$this->error = 'Конфигурация для загрузки не установлена.';
			return false;
		}

		$image_info = getimagesize($tmpName);
		$this->imgType = $image_info[2];
		$this->imgWidth = $image_info[0];
		$this->imgHeight = $image_info[1];

		if($this->imgType != IMAGETYPE_JPEG && $this->imgType != IMAGETYPE_PNG) {
			$this->error = 'Не соответствует формату jpeg или png';
			return false;
		}

		if(is_array($this->configOriginal))
		{
			$newOriginalName = $this->getFullPath() . DIRECTORY_SEPARATOR . $this->configOriginal['name'] . '.' . $this->configOriginal['ext'];
			if(!@move_uploaded_file($tmpName, $newOriginalName))
			{
				#$this->error = 'Ошибка сохранения оригинального файла (tmpName='.$tmpName.', newOriginalName='.$newOriginalName.').';
				$this->error = 'Ошибка сохранения оригинального файла.';
				return false;
			}
			if($this->imgType == IMAGETYPE_JPEG) {
				$this->img = imagecreatefromjpeg($newOriginalName);
			}
			if($this->imgType == IMAGETYPE_PNG) {
				$this->img = imagecreatefrompng($newOriginalName);
			}

		}

		if (is_array($this->configOptimal))
		{
			if($this->imgWidth > $this->imgHeight) {
				// Горизонтальная фото, сжимаем по ширине
				$width = (int)$this->configOptimal['maxWidth'];
				$ratio = $width / $this->imgWidth;
				$height = $this->imgHeight * $ratio;
				$height = (int)$height;
			} else {
				// Вертикальная фото, сжимаем по высоте
				$height = (int)$this->configOptimal['maxHeight'];
				$ratio = $height / $this->imgHeight;
				$width = $this->imgWidth * $ratio;
				$width = (int)$width;
			}

			$optImg = $this->resize($this->img, $width, $height, $this->imgWidth, $this->imgHeight);
			imagejpeg($optImg, $this->getPathOptimalName(true), 85);
			#Sas_Debug::dump('Save optimal');
		}

		if (is_array($this->configCrop) && !is_null($this->img))
		{
			// Может фото уже квадратное?
			if($this->imgWidth == $this->imgHeight)
			{
				// Уменьшаем до нужного размера
				$cropImg = $this->resize($this->img, $this->configCrop['cropSize'], $this->configCrop['cropSize'], $this->imgWidth, $this->imgHeight);
			} else {
				// Что больше ширина или высота
				if($this->imgWidth > $this->imgHeight) {
					// Горизонтальная фото, обрезаем влева и справа
					$nSize = $this->imgHeight;
					$x = ($this->imgWidth - $this->imgHeight) / 2;
					$y = 0;
				} else {
					$nSize = $this->imgWidth;
					$x = 0;
					$y = ($this->imgHeight - $this->imgWidth) / 2;
				}

				$newCropImg = imagecreatetruecolor($nSize, $nSize);
				imagecopy($newCropImg, $this->img, 0, 0, $x, $y, $nSize, $nSize);
				$cropImg = $this->resize($newCropImg, $this->configCrop['cropSize'], $this->configCrop['cropSize'], $nSize, $nSize);
			}

			imagejpeg($cropImg, $this->getPathCropName(true), 85);
			#Sas_Debug::dump('Save crop');
		}

		return true;
	}

	private function resize($img, $widthNew, $heightNew, $widthOriginal, $heightOriginal) {
		$new_image = imagecreatetruecolor($widthNew, $heightNew);
		imagecopyresampled($new_image, $img, 0, 0, 0, 0, $widthNew, $heightNew, $widthOriginal, $heightOriginal);
		return $new_image;
	}

	#------------
	public function saveOriginalImg($tmpName, $name = 'original', $expansion = 'jpg') {
		if(!move_uploaded_file($tmpName, $this->getFullPath() . DIRECTORY_SEPARATOR . $name . '.' . $expansion))
		{
			$this->error = 'Ошибка сохранения оригинального файла.';
			return false;
		}

		return $this->getFullPath() . DIRECTORY_SEPARATOR . $name . '.' . $expansion;
	}

	public function createThumbnail($originalFile, $name = 'thumbnail', $expansion = 'jpg')
	{
		if (!file_exists($originalFile)) {
			$this->error = 'Исходного файла для создания Thumbnail не существует.';
			return false;
		}

		$image_info = getimagesize($originalFile);
		$this->img = $image_info;
		$this->imgType = $image_info[2];
		$this->imgWidth = $image_info[0];
		$this->imgHeight = $image_info[1];

		if($this->imgType != IMAGETYPE_JPEG) {
			$this->error = 'Не соответствует формату jpeg';
			return false;
		}

		#Sas_Debug::dump($image_info);


		return $this->getFullPath() . DIRECTORY_SEPARATOR . $name . '.' . $expansion;
	}

	public function setPathHost($path) {
		$this->pathHost = trim($path, DIRECTORY_SEPARATOR);
	}

	/**
	 * Задаёт относительный путь для работы с фото и при необходимости создаёт директорию
	 * @param      $dir
	 * @param bool $createDir
	 * @param string  $mode
	 * @return $this
	 */
	public function setImgDir($dir, $createDir = false, $mode = null)
	{
		$mode = (is_null($mode)) ? $mode = 0777 : $mode;

		$this->imgDir = trim($dir, DIRECTORY_SEPARATOR);

		if ($createDir === true && !file_exists($this->getFullPath())) {
			mkdir($this->getFullPath(), $mode, true);
			#Sas_Debug::dump($mode, 'mode ' . __FILE__);
		}

	 	return $this;
	}

	public function getFullPath()
	{
		return $this->fullUploadDir = $this->pathHost . DIRECTORY_SEPARATOR . $this->imgDir;
	}

	public function getError() {
		return $this->error;
	}

}
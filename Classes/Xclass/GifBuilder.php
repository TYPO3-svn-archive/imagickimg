<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Tomasz Krawczyk <tomasz@typo3.pl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Contains GifBuilder Xclass object.
 *
 * @author Tomasz Krawczyk <tomasz@typo3.pl>
 */
 class ux_GifBuilder extends \TYPO3\CMS\Frontend\Imaging\GifBuilder {

	private $NO_IMAGICK = FALSE;
	private $extKey = 'imagickimg';
	private $quantumRange = -1;
	private $gfxConf;

	
	/**
	 * Init function. Must always call this when using the class.
	 * This function will read the configuration information from $GLOBALS['TYPO3_CONF_VARS']['GFX'] can set some values in internal variables.
	 *
	 * Additionaly function checks if PHP extension Imagick is loaded.
	 *
	 * @return	void
	 */
	public function init()	{

		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		if (!extension_loaded('imagick')) {
			
			$this->NO_IMAGICK = TRUE;
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagick'] = 0;
			
			$sMsg = 'PHP extension Imagick is not loaded. Extension Imagickimg is deactivated.';			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($sMsg, $this->extKey, 
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
				
			if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($sMsg, $this->extKey, 2);
		
		} else {

			$this->NO_IMAGICK = FALSE;
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagick'] = 1;

			// Get IM version and overwrite user settings
			$ver = $this->getIMversion(TRUE);
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] = $ver;

			if (($ver == 'im5') || ($ver == 'im6')) {
				
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_no_effects'] = 0;
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'] = 1;
			}
			else {
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_no_effects'] = 1;
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'] = 0;
			}
			
			if (TYPO3_DLOG) 
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, 0, $GLOBALS['TYPO3_CONF_VARS']['GFX']);
		}
		$this->gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		
		parent::init();
	}
	

   /**
     * Gets ImageMagick & Imagick versions.
     *
     * @param	boolean		If true short string version string will be returned (f.i. im5), else full version array.
     * @return	string/array	Version info
     *
     */
	private function getIMversion($returnString = true) {

		if ($this->NO_IMAGICK) return '';

		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
			
		$im_ver = '';
		try {
			$im = new Imagick();
			$a = $im->getVersion();
			$im->destroy();

				// $arr['versionString'] is string 'ImageMagick 6.7.9-1 2012-08-21 Q8 http://www.imagemagick.org' (length=60)
			if (is_array($a)) {
					// Add Imagick version info
				$a['versionImagick'] = 'Imagick ' . Imagick::IMAGICK_EXTVER;
				
				if (TYPO3_DLOG)
					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__ . ' found version', $this->extKey, 0, $a);

					// extract major IM version 
				$v = explode(' ', $a['versionString']);				
				if (count($v) >= 1) {
					$a = explode('.', $v[1]);
					if (count($a) >= 2) {
						$im_ver = 'im' . $a[0];
					}
				}
			}
			if (!$returnString) {
				$im_ver = $a;
			}
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}

		return $im_ver;
	}	
	
	
	private function getQuantumRangeLong() {

		if ($this->NO_IMAGICK) return;
	
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		try {
			$newIm = new Imagick();

			$qrArr = $newIm->getQuantumRange();
			if (is_array($qrArr))
				$this->quantumRange = intval($qrArr['quantumRangeLong']);
			else
				$this->quantumRange = 0;

			$newIm->destroy();
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}
	}	


	/**
	 * Converts $imagefile to another file in temp-dir of type $newExt (extension).
	 *
	 * @param	string		The image filepath
	 * @param	string		New extension, eg. "gif", "png", "jpg", "tif". If $newExt is NOT set, the new imagefile will be of the original format. If newExt = 'WEB' then one of the web-formats is applied.
	 * @param	string		Width. $w / $h is optional. If only one is given the image is scaled proportionally. If an 'm' exists in the $w or $h and if both are present the $w and $h is regarded as the Maximum w/h and the proportions will be kept
	 * @param	string		Height. See $w
	 * @param	string		Additional ImageMagick parameters.
	 * @param	string		Refers to which frame-number to select in the image. '' or 0 will select the first frame, 1 will select the next and so on...
	 * @param	array		An array with options passed to getImageScale (see this function).
	 * @param	boolean		If set, then another image than the input imagefile MUST be returned. Otherwise you can risk that the input image is good enough regarding messures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
	 * @return	array		[0]/[1] is w/h, [2] is file extension and [3] is the filename.
	 * @see getImageScale(), typo3/show_item.php, fileList_ext::renderImage(), tslib_cObj::getImgResource(), SC_tslib_showpic::show(), maskImageOntoImage(), copyImageOntoImage(), scale()
	 */
	public function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = '', $mustCreate = 0)	{

		if ($this->NO_IMAGICK)
			return parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);

		if (TYPO3_DLOG)
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, 0, array($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate));

		if ($info = $this->getImageDimensions($imagefile)) {
			$newExt = strtolower(trim($newExt));
			// If no extension is given the original extension is used
			if (!$newExt) {
				$newExt = $info[2];
			}
			if ($newExt == 'web') {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->webImageExt, $info[2])) {
					$newExt = $info[2];
				} else {
					$newExt = $this->gif_or_jpg($info[2], $info[0], $info[1]);
					if (!$params) {
						$params = $this->cmds[$newExt];
					}
				}
			}
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->imageFileExt, $newExt)) {
				if (strstr($w . $h, 'm')) {
					$max = 1;
				} else {
					$max = 0;
				}
				$data = $this->getImageScale($info, $w, $h, $options);
				$w = $data['origW'];
				$h = $data['origH'];
				// If no conversion should be performed
				// this flag is TRUE if the width / height does NOT dictate
				// the image to be scaled!! (that is if no width / height is
				// given or if the destination w/h matches the original image
				// dimensions or if the option to not scale the image is set)
				$noScale = !$w && !$h || $data[0] == $info[0] && $data[1] == $info[1] || $options['noScale'];
				if ($noScale && !$data['crs'] && !$params && !$frame && $newExt == $info[2] && !$mustCreate) {
					// Set the new width and height before returning,
					// if the noScale option is set
					if (!empty($options['noScale'])) {
						$info[0] = $data[0];
						$info[1] = $data[1];
					}
					$info[3] = $imagefile;
					return $info;
				}
				$info[0] = $data[0];
				$info[1] = $data[1];
				$frame = $this->noFramePrepended ? '' : intval($frame);
				if (!$params) {
					$params = $this->cmds[$newExt];
				}
				// Cropscaling:
				if ($data['crs']) {
					if (!$data['origW']) {
						$data['origW'] = $data[0];
					}
					if (!$data['origH']) {
						$data['origH'] = $data[1];
					}
					$offsetX = intval(($data[0] - $data['origW']) * ($data['cropH'] + 100) / 200);
					$offsetY = intval(($data[1] - $data['origH']) * ($data['cropV'] + 100) / 200);
					$params .= ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX . '+' . $offsetY . ' ';
				}
				$command = $this->scalecmd . ' ' . $info[0] . 'x' . $info[1] . '! ' . $params . ' ';
				$cropscale = $data['crs'] ? 'crs-V' . $data['cropV'] . 'H' . $data['cropH'] : '';
				if ($this->alternativeOutputKey) {
					$theOutputName = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($command . $cropscale . basename($imagefile) . $this->alternativeOutputKey . '[' . $frame . ']');
				} else {
					$theOutputName = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($command . $cropscale . $imagefile . filemtime($imagefile) . '[' . $frame . ']');
				}
				if ($this->imageMagickConvert_forceFileNameBody) {
					$theOutputName = $this->imageMagickConvert_forceFileNameBody;
					$this->imageMagickConvert_forceFileNameBody = '';
				}

				// Making the temporary filename:
				$this->createTempSubDir('pics/');
				$output = $this->absPrefix . $this->tempPath . 'pics/' . $this->filenamePrefix . $theOutputName . '.' . $newExt;				
				
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($imagefile))
					$imagefile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($imagefile, FALSE);

				$fullOutput = '';
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($output))
					$fullOutput = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($output, FALSE);
				else
					$fullOutput = $output;
				
					// Register temporary filename:
				$GLOBALS['TEMP_IMAGES_ON_PAGE'][] = $output;

				if ($this->dontCheckForExistingTempFile || !$this->file_exists_typo3temp_file($output, $imagefile))	{

					if (TYPO3_DLOG)
						\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__ . ' Conversion', $this->extKey, 0, array($imagefile,$fullOutput));

					try {
						$newIm = new Imagick($imagefile);
						$newIm->resizeImage($info[0], $info[1], $this->gfxConf['windowing_filter'], 1);

						$newIm->writeImage($fullOutput);
						$newIm->destroy();
						
							// apply additional params (f.e. effects, compression)
						if ($params) {
							$this->applyImagickEffect($output, $params);
						}
							// Optimize image
						$this->imagickOptimize($fullOutput);
					}
					catch(ImagickException $e) {
						
						\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
							__METHOD__ . ' >> ' . $e->getMessage(),
							$this->extKey,
							\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					}
				}
				if (file_exists($output))	{
					$info[3] = $output;
					$info[2] = $newExt;
						// params could realisticly change some imagedata!
					if ($params) {
						$info=$this->getImageDimensions($info[3]);
					}
					return $info;
				}
			}
		}
	}
	

	/**
	 * Executes a ImageMagick "convert" on two filenames, $input and $output using $params before them.
	 * Can be used for many things, mostly scaling and effects.
	 *
	 * @param string $input The relative (to PATH_site) image filepath, input file (read from)
	 * @param string $output The relative (to PATH_site) image filepath, output filename (written to)
	 * @param string $params ImageMagick parameters
	 * @param integer $frame Optional, refers to which frame-number to select in the image. '' or 0
	 * @return string The result of a call to PHP function "exec()
	 * @todo Define visibility
	 */
	public function imageMagickExec($input, $output, $params, $frame = 0) {
		
		if ($this->NO_IMAGICK)
			return parent::imageMagickExec($input, $output, $params, $frame);

		if (TYPO3_DLOG)
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, -1, array($input, $output, $params, $frame));
			
		// Unless noFramePrepended is set in the Install Tool, a frame number is added to
		// select a specific page of the image (by default this will be the first page)
		if (!$this->noFramePrepended) {
			$frame = '[' . intval($frame) . ']';
		} else {
			$frame = '';
		}
		/*
		$cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::imageMagickCommand('convert', $params . ' ' . $this->wrapFileName($input) . $frame . ' ' . $this->wrapFileName($output));
		$this->IM_commands[] = array($output, $cmd);
		$ret = \TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
		*/
		$fileInput = $input;
		$fileOutput = $output;
		
		try {	
			$newIm = new Imagick($fileInput);
		
			$newIm->writeImage($fileOutput);
			$newIm->destroy();
			
				// apply additional params (f.e. effects, compression)
			if ($params) {
				$this->applyImagickEffect($fileOutput, $params);
			}
				// Optimize image
			$this->imagickOptimize($fileOutput);
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}
		
		
		// Change the permissions of the file
		\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($output);
		return $ret;
	}

	
	/**
	 * Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
	 * Using ImageMagick
	 *
	 * @param	string		The relative (to PATH_site) image filepath
	 * @return	array
	 */	 
	public function imageMagickIdentify($imagefile) {
		
		if ($this->NO_IMAGICK)
			return parent::imageMagickIdentify($imagefile);

		if (TYPO3_DLOG)
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, -1, $arRes);
		
		// BE uses stdGraphics and absolute paths.
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($imagefile))
			$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($imagefile, FALSE);
		else
			$file = $imagefile;

		try {
			$newIm = new Imagick($file);
			// The $im->getImageGeometry() is faster than $im->identifyImage(false).
			$idArr = $newIm->identifyImage(false);
			
			$arRes = array();
			$arRes[0] = $idArr['geometry']['width'];
			$arRes[1] = $idArr['geometry']['height'];
			$arRes[2] = strtolower(pathinfo($idArr['imageName'], PATHINFO_EXTENSION));
			$arRes[3] = $imagefile;		

			$newIm->destroy();
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}			
		return $arRes;
	}

	/**
	 * Executes a ImageMagick "combine" (or composite in newer times) on four filenames - $input, $overlay and $mask as input files and $output as the output filename (written to)
	 * Can be used for many things, mostly scaling and effects.
	 *
	 * @param	string		The relative (to PATH_site) image filepath, bottom file
	 * @param	string		The relative (to PATH_site) image filepath, overlay file (top)
	 * @param	string		The relative (to PATH_site) image filepath, the mask file (grayscale)
	 * @param	string		The relative (to PATH_site) image filepath, output filename (written to)
	 * @param	[type]		$handleNegation: ...
	 * @return	void
	 */
	public function combineExec($input, $overlay, $mask, $output, $handleNegation = FALSE) {
		
		if ($this->NO_IMAGICK)
			return parent::combineExec($input, $overlay, $mask, $output, $handleNegation);
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($input))
			$fileInput = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($input, FALSE);
		else 
			$fileInput = $input;

		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($overlay))
			$fileOver = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($overlay, FALSE);
		else 
			$fileOver = $overlay;
			
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($mask))
			$fileMask = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($mask, FALSE);
		else 
			$fileMask = $mask;

		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($output))
			$fileOutput = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($output, FALSE);
		else 
			$fileOutput = $output;
		
		try {
			$baseObj = new Imagick();
			$baseObj->readImage(fileInput);
			
			$overObj = new Imagick();
			$overObj->readImage($fileOver);

			$maskObj = new Imagick();
			$maskObj->readImage($fileMask);
			
			// get input image dimensions
			$geo = $baseObj->getImageGeometry();
			$w = $geo['width'];
			$h = $geo['height'];
			
			// resize mask and overlay
			$maskObj->resizeImage($w, $h, Imagick::FILTER_LANCZOS, 1);
			$overObj->resizeImage($w, $h, Imagick::FILTER_LANCZOS, 1);
			
			// Step 1
			$maskObj->setImageColorspace(imagick::COLORSPACE_GRAY); // IM >= 6.5.7
			$maskObj->setImageMatte(FALSE); // IM >= 6.2.9

			/*if ($handleNegation) {
				//if ($this->maskNegate) {
					$maskObj->negateImage(1);
				//}
			}*/
			
			// Step 2
			$baseObj->compositeImage($maskObj, Imagick::COMPOSITE_SCREEN, 0, 0); // COMPOSITE_SCREEN
			$maskObj->negateImage(1);
			
			if ($baseObj->getImageFormat() == 'GIF') {
				$overObj->compositeImage($maskObj, Imagick::COMPOSITE_SCREEN, 0, 0); // COMPOSITE_SCREEN
			}
			$baseObj->compositeImage($overObj, Imagick::COMPOSITE_MULTIPLY, 0, 0); //COMPOSITE_MULTIPLY
			$baseObj->setImageMatte(FALSE); // IM >= 6.2.9

			$baseObj->writeImage($fileOutput);

			$maskObj->destroy();
			$overObj->destroy();
			$baseObj->destroy();

				// Optimize image
			$this->imagickOptimize($fileOutput);
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($output);
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(), 
				$this->extKey, 
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}
		
		return '';
	}


    /**
     * Compresses given image.
     *
	 * @param	string		file name
	 * @param	int		quality
	 * @return	void
     */
	private function imagickCompress($imageFile, $imageQuality) {
	
		if ($this->NO_IMAGICK) return;

		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($imageFile))
			$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($imageFile, FALSE);
		else
			$file = $imageFile;

		try {

			$im = new Imagick($file);

			$im->optimizeImageLayers();
			$this->imagickCompressObject($im, $imageQuality);
			
			$im->writeImage($file);
			$im->destroy();
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}

	}


    /**
     * Compresses given image.
     *
	 * @param	Imagick		Imagick object
	 * @return	void
     */
	private function imagickCompressObject(&$imageObj, $imageQuality = 0) {

		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
	
		$imgExt = strtolower($imageObj->getImageFormat());		
		switch($imgExt) {
			
			case 'gif':
				if (($imageQuality == 100) || ($this->jpegQuality == 100))
					$imageObj->setImageCompression(Imagick::COMPRESSION_RLE);
				else
					$imageObj->setImageCompression(Imagick::COMPRESSION_LZW);
				break;
			
			case 'jpg':
			case 'jpeg':
				if (($imageQuality == 100) || ($this->jpegQuality == 100))
					$imageObj->setImageCompression(Imagick::COMPRESSION_LOSSLESSJPEG);
				else
					$imageObj->setImageCompression(Imagick::COMPRESSION_JPEG);
				$imageObj->setImageCompressionQuality(($imageQuality == 0) ? $this->jpegQuality : $imageQuality);
				break;

			case 'png':
				$imageObj->setImageCompression(Imagick::COMPRESSION_ZIP);
				$imageObj->setImageCompressionQuality(($imageQuality == 0) ? $this->jpegQuality : $imageQuality);
				break;
			
			case 'tif':
			case 'tiff':
				if (($imageQuality == 100) || ($this->jpegQuality == 100))
					$imageObj->setImageCompression(Imagick::COMPRESSION_LOSSLESSJPEG);
				else
					$imageObj->setImageCompression(imagick::COMPRESSION_LZW);
				$imageObj->setImageCompressionQuality(($imageQuality == 0) ? $this->jpegQuality : $imageQuality);
				break;

			case 'tga':
				$imageObj->setImageCompression(Imagick::COMPRESSION_RLE);
				$imageObj->setImageCompressionQuality(($imageQuality == 0) ? $this->jpegQuality : $imageQuality);
				break;
		}
	}


    /**
     * Removes profiles and comments from the image.
     *
	 * @param	Imagick		Imagick object
	 * @return	void
     */
	private function imagickRemoveProfile(&$imageObj) {

		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		if ($this->gfxConf['im_useStripProfileByDefault']) {
		
			$profile = $this->gfxConf['im_stripProfileCommand'];
			if (substr($profile, 0, 1) == '+') {			
					// remove profiles
				if ( $this->gfxConf['im_stripProfileCommand'] == '+profile \'*\'') {
						// remove all profiles and comments
					$imageObj->stripImage();
				}
			}
		}
	}

    /**
     * Optimizes image resolution.
     *
	 * @param	Imagick		Imagick object
	 * @return	void
     */
	private function imagickOptimizeResolution(&$imageObj) {

		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
	
		$imgDPI = intval($this->gfxConf['imagesDPI']);

		if ($imgDPI > 0)
			$imageObj->setImageResolution($imgDPI, $imgDPI);
	}
	
    /**
     * Executes all optimization methods on the image. Execute it just before storing image to disk.
     * 
     * @param Imagick		Imagick object
	 * @return	void
     */
	private function imagickOptimize($imageFile) {
		
		if ($this->NO_IMAGICK) return;

		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($imageFile))
			$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($imageFile, FALSE);
		else
			$file = $imageFile;

		try {

			$im = new Imagick($file);

			$im->optimizeImageLayers();
			$this->imagickOptimizeObject($im);
			
			$im->writeImage($file);
			$im->destroy();
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}
	}


	private function imagickOptimizeObject(&$imObject) {
		
		if ($this->NO_IMAGICK) return;

		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		$imObject->optimizeImageLayers();
		
		$this->imagickRemoveProfile($imObject);
		$this->imagickOptimizeResolution($imObject);
		$this->imagickCompressObject($imObject);
	}


	private function imagickSetColorspace($file, $colorSpace) {

		if ($this->NO_IMAGICK) return;

		if (TYPO3_DLOG) t3lib_div::devLog(__METHOD__, $this->extKey);

		if (!t3lib_div::isAbsPath($file))
			$fileResult = t3lib_div::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);

			switch(strtoupper($colorSpace)) {
				case 'GRAY':
					$newIm->setImageColorspace(imagick::COLORSPACE_GRAY); // IM >= 6.5.7
					break;
				
				case 'RGB':
					$newIm->setImageColorspace(imagick::COLORSPACE_RGB); // IM >= 6.5.7
					break;
			}
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog(__METHOD__ . ' >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}		
	}


	/**
	 * Reduce colors in image using IM and create a palette based image if possible (<=256 colors)
	 *
	 * @param	string		Image file to reduce
	 * @param	integer		Number of colors to reduce the image to.
	 * @return	string		Reduced file
	 */
	public function IMreduceColors($file, $cols) {

		if ($this->NO_IMAGICK)
			return parent::IMreduceColors($file, $cols);

		if (TYPO3_DLOG)
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, 0, array($file, $cols));
		
		$fI = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($file);
		$ext = strtolower($fI['fileext']);
		$result = $this->randomName() . '.' . $ext;
		$reduce = $this->getIntRange($cols, 0, ($ext == 'gif' ? 256 : $this->truecolorColors), 0);
		if ($reduce > 0) {

			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
				$fileInput = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
			else 
				$fileInput = $file;

			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($result))
				$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($result, FALSE);
			else 
				$fileResult = $result;

			if (TYPO3_DLOG) 
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, 0, array($fileInput, $fileResult, $reduce));

			try {
				$newIm = new Imagick($fileInput);
			
				if ($reduce <= 256) {
					$newIm->setType(imagick::IMGTYPE_PALETTE);
				}
				if (($ext == 'png') && ($reduce <= 256)) {
					$newIm->setImageDepth(8);
					$newIm->setImageFormat('PNG8');
				}			
				
					// Reduce the amount of colors
				$newIm->quantizeImage($reduce, Imagick::COLORSPACE_RGB, 0, FALSE, FALSE);
				
				$newIm->writeImage($fileResult);
				$newIm->destroy();
				
				\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($fileResult);
				
				return $result;	
			}
			catch(ImagickException $e) {
				
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
					__METHOD__ . ' >> ' . $e->getMessage(),
					$this->extKey,
					\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			}			
		}
		return '';
	}

	/**
	 * Implements the "EFFECT" GIFBUILDER object
	 * The operation involves ImageMagick for applying effects
	 *
	 * @param	pointer		GDlib image pointer
	 * @param	array		TypoScript array with configuration for the GIFBUILDER object.
	 * @return	void
	 * @see tslib_gifBuilder::make(), applyImageMagickToPHPGif()
	 */
/*	function makeEffect(&$im, $conf) {

		if ($this->NO_IMAGICK)
			return parent::makeEffect(&$im, $conf);

		$commands = $this->IMparams($conf['value']);
		if (TYPO3_DLOG)
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, 0, array($commands));

		if ($commands) {
			$this->applyImageMagickToPHPGif($im, $commands);
		}
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
	}*/

	/**
	 * Applies an ImageMagick parameter to a GDlib image pointer resource by writing the resource to file, performing an IM operation upon it and reading back the result into the ImagePointer.
	 *
	 * @param	pointer		The image pointer (reference)
	 * @param	string		The ImageMagick parameters. Like effects, scaling etc.
	 * @return	void
	 */
/*	function applyImageMagickToPHPGif(&$im, $command) {
		
		if ($this->NO_IMAGICK)
			return parent::applyImageMagickToPHPGif(&$im, $command);
		
		if (TYPO3_DLOG)
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, -1, array($command));

		$tmpStr = $this->randomName();
		$theFile = $tmpStr . '.' . $this->gifExtension;
		$this->ImageWrite($im, $theFile);
		
		//$this->imageMagickExec($theFile, $theFile, $command);
		// IMagick here
		$this->applyImagickEffect($theFile, $command);
		
		$tmpImg = $this->imageCreateFromFile($theFile);
		if ($tmpImg) {
			ImageDestroy($im);
			$im = $tmpImg;
			$this->w = imagesx($im);
			$this->h = imagesy($im);
		}
		if (!$this->dontUnlinkTempFiles) {
			unlink($theFile);
		}

		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
	}*/
	
    /**
     * Main function applying Imagick effects
     *
	 * @param	pointer		The image pointer (reference)
	 * @param	string		The ImageMagick parameters. Like effects, scaling etc.
	 * @return	void
     */
	private function applyImagickEffect($file, $command) {

		if ($this->NO_IMAGICK || $this->NO_IM_EFFECTS || !$this->V5_EFFECTS) return;
		
		//if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		$command = strtolower(trim($command));
		$command = str_ireplace('-', '', $command);		
		$elems = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $command, true);
	
		if (TYPO3_DLOG)
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, 0, array($file, $elems));

			// Here we're trying to identify ImageMagick parameters
			// Image compression see tslib_cObj->image_compression
			// Image effects see tslib_cObj->image_effects
			
		if (count($elems) == 1) {

			switch($elems[0]) {
				/* effects */
				case 'normalize':
					$this->imagickNormalize($file);
					break;

				case 'contrast':
					$this->imagickContrast($file);
					break;
			}
		}
		elseif (count($elems) == 2) {

			switch($elems[0]) {
				/* effects */
				case 'rotate':
					$this->imagickRotate($file, $elems[1]);
					break;

				case 'colorspace':
					if ($elems[1] == 'gray')
						$this->imagickGray($file);
					else
						$this->imagickSetColorspace($file, $elems[1]);
					break;

				case 'sharpen':
					$this->imagickSharpen($file, $elems[1]);
					break;
					
				case 'gamma':	// brighter, darker
					$this->imagickGamma($file, $elems[1]);
					break;
				
				case '@sepia':
					$this->imagickSepia($file, intval($elems[1]));
					break;
					
				case '@corners':
					$this->imagickRoundCorners($file, intval($elems[1]));
					break;

				case '@polaroid':
					$this->imagickPolaroid($file, intval($elems[1]));
					break;

				/* compression */
				case 'colors':
					$reduced = $this->IMreduceColors($file, intval($elems[1]));
					if ($reduced) {
						@copy($reduced, $file);
						@unlink($reduced);
					}
					break;

				case 'quality':
					$this->imagickCompress($file, intval($elems[1]));
					break;
			}
		}
		elseif (count($elems) == 3) {
				
				// effects without parameters
			switch($elems[0]) {
			
				case 'normalize':
					$this->imagickNormalize($file);
					break;

				case 'contrast':
					$this->imagickContrast($file);
					break;
			}
				// compression 
			switch($elems[1]) {

				case 'colors':
					$reduced = $this->IMreduceColors($file, intval($elems[2]));
					if ($reduced) {
						@copy($reduced, $file);
						@unlink($reduced);
					}
					break;

				case 'quality':
					$this->imagickCompress($file, intval($elems[2]));
					break;
			}

		}
		elseif (count($elems) == 4) {

			/* effect */
			switch($elems[0]) {

				case 'rotate':
					$this->imagickRotate($file, $elems[1]);
					break;

				case 'colorspace':
					if ($elems[1] == 'gray')
						$this->imagickGray($file);
					else
						$this->imagickSetColorspace($file, $elems[1]);
					break;

				case 'sharpen':
					$this->imagickSharpen($file, intval($elems[1]));
					break;
					// brighter, darker
				case 'gamma':
					$this->imagickGamma($file, intval($elems[1]));
					break;
				
				case '@sepia':
					$this->imagickSepia($file, intval($elems[1]));
					break;
					
				case '@corners':
					$this->imagickRoundCorners($file, $elems[1]);
					break;

				case '@polaroid':
					$this->imagickPolaroid($file, intval($elems[1]));
					break;
			}
			
			/* compression */
			switch($elems[2]) {

				case 'colors':
					$reduced = $this->IMreduceColors($file, intval($elems[3]));
					if ($reduced) {
						@copy($reduced, $file);
						@unlink($reduced);
					}
					break;

				case 'quality':
					$this->imagickCompress($file, intval($elems[3]));
					break;
			}			
		}
		elseif (count($elems) == 6) {
		
			/* colorspace */
			switch($elems[0]) {
				case 'colorspace':
					if ($elems[1] == 'gray')
						$this->imagickGray($file);
					else
						$this->imagickSetColorspace($file, $elems[1]);
					break;
			}

			/* quality */
			switch($elems[2]) {
				case 'quality':
					$this->imagickCompress($file, intval($elems[3]));
					break;
			}
			
			/* effect */
			switch($elems[4]) {

				case 'rotate':
					$this->imagickRotate($file, $elems[5]);
					break;

				case 'colorspace':
					if ($elems[5] == 'gray')
						$this->imagickGray($file);
					else
						$this->imagickSetColorspace($file, $elems[5]);
					break;

				case 'sharpen':
					$this->imagickSharpen($file, intval($elems[5]));
					break;
					// brighter, darker
				case 'gamma':
					$this->imagickGamma($file, intval($elems[5]));
					break;
				
				case '@sepia':
					$this->imagickSepia($file, intval($elems[5]));
					break;
					
				case '@corners':
					$this->imagickRoundCorners($file, $elems[5]);
					break;

				case '@polaroid':
					$this->imagickPolaroid($file, intval($elems[5]));
					break;
			}

		} else {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
				__METHOD__ . ' > Not expected amount of parameters',
				$this->extKey, 2, $elems);
		}
		\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($file);
	}


	private function imagickGamma($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->gammaImage($value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}		
	}
	
	private function imagickBlur($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->blurImage($value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}		
	}
	
	private function imagickSharpen($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->sharpenImage(0, $value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickRotate($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->rotateImage(new ImagickPixel(), $value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickSolarize($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->solarizeImage($value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickSwirl($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->swirlImage($value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickWawe($file, $value1, $value2) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->waveImage($value1, $value2);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickCharcoal($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->charcoalImage($value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickGray($file) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		$fI = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($file);
		$ext = strtolower($fI['fileext']);
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			//$newIm->setImageColorspace(Imagick::COLORSPACE_GRAY);
			$newIm->setImageType(Imagick::IMGTYPE_GRAYSCALE);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickEdge($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->edgeImage($value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickEmboss($file) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->embossImage(0);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickFlip($file) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->flipImage();
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickFlop($file) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->flopImage();
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickColors($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->quantizeImage($value, $newIm->getImageColorspace(), 0, false, false);
				// Only save one pixel of each color
			$newIm->uniqueImageColors();
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickShear($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->shearImage($newIm->getImageBackgroundColor(), $value, $value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickInvert($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->negateImage(0);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(), 
				$this->extKey, 
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickNormalize($file) {

		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
	
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->normalizeImage();
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickContrast($file, $value = 1) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);

			$val = $this->getIntRange($value, 0, 9);
			$newIm->contrastImage($val);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(), 
				$this->extKey, 
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickSepia($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);

			if ($this->quantumRange < 0)
				$this->getQuantumRangeLong();
			
			$value = $this->getIntRange($value, 0, $this->quantumRange);

			$newIm->sepiaToneImage($value);

			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(), 
				$this->extKey, 
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}
	
	private function imagickRoundCorners($file, $value) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
		
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);

			$newIm->roundCorners($value, $value);
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(), 
				$this->extKey, 
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function imagickPolaroid($file, $value) {

		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($file))
			$fileResult = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($file, FALSE);
		else 
			$fileResult = $file;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);

				// polaroidImage() changes image geometry so we have to resize images after aplying the effect
			$geo = $newIm->getImageGeometry();
			
			$newIm->polaroidImage(new ImagickDraw(), $value); // IM >= 6.3.2
			
			$newIm->resizeImage($geo['width'], $geo['height'], $this->gfxConf['windowing_filter'], 1);

			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(), 
				$this->extKey, 
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			return FALSE;
		}
	}

	private function getIntRange($theInt, $theMin, $theMax, $theDefault = 0) {
		
		return \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($theInt, $theMin, $theMax, $theDefault);
	}
	
	/**
     * Returns an array with detailed image info.
     *
     * @param 	string	File path
	 * @return	array	Image information
     */
	public function imagickGetDetailedImageInfo($imagefile) {
		
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($imagefile))
			$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($imagefile, FALSE);
		else 
			$file = $imagefile;

		try {
			$im = new Imagick();
			$im->readImage($file);
			$identify = $im->identifyImage();

			$res = array(
				'Basic image properties' => array(
					'Image dimensions: ' => $identify['geometry']['width'] . 'x' . $identify['geometry']['height'],
					'Image format: ' => $identify['format'],
					'Image type: ' => $identify['type'],
					'Colorspace: ' => $identify['colorSpace'],
					'Units: ' => $identify['units'],
					'Compression: ' => $identify['compression']
				)
			);
			if (!empty($identify['resolution']['x'])) {			
				$res['Basic image properties'] = array_merge($res['Basic image properties'], 
					array(
						'Resolution: ' => $identify['resolution']['x'] . 'x' . $identify['resolution']['y'] . ' dpi'
					)
				);
			}

			$res['All image properties'] = array();
			foreach ( $im->getImageProperties() as $k => $v ) {
				$res['All image properties'] = array_merge($res['All image properties'], array($k => $v));
			}

			$res['All image profiles'] = array();
			foreach ( $im->getImageProfiles() as $k => $v ) {
				$res['All image profiles'] = array_merge($res['All image profiles'], array(
					$k => '(size: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize(strlen( $v ), ' | KB| MB| GB') . ')'
				));
			}

			$im->destroy();
			
			return $res;				
		}
		catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(), 
				$this->extKey, 
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
				
			return '';
		}
	}


    /**
     * Creates proportional thumbnails
     * 
     * @param <object> $imObj 
     * @param <int> $w - image width
     * @param <int> $h - image height 
     */
	private function imagickThumbProportional(&$imObj, $w, $h) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		// Resizes to whichever is larger, width or height
		if ($imObj->getImageHeight() <= $imObj->getImageWidth()) {
			// Resize image using the lanczos resampling algorithm based on width
			$imObj->resizeImage($w, 0, Imagick::FILTER_LANCZOS, 1);
		} else {
			// Resize image using the lanczos resampling algorithm based on height
			$imObj->resizeImage(0, $h, Imagick::FILTER_LANCZOS, 1);
		}
	}


    /**
     * Creates cropped thumbnails
     * 
     * @param <object> $imObj 
     * @param <int> $w - image width
     * @param <int> $h - image height 
     */
	private function imagickThumbCropped(&$imObj, $w, $h) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		$imObj->cropThumbnailImage($w, $h);
	}

	
    /**
     * Creates sampled thumbnails
     * 
     * @param <object> $imObj 
     * @param <int> $w - image width
     * @param <int> $h - image height 
     */
	private function imagickThumbSampled(&$imObj, $w, $h) {
	
		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		$imObj->sampleImage($w, $h);
	}	

	
	public function imagickThumbnailImage($fileIn, $fileOut, $w, $h) {

		if ($this->NO_IMAGICK) return;
		
		if (TYPO3_DLOG)
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, 0, array($fileIn, $fileOut, $w, $h));
	
		$bRes = FALSE;
		$imgDPI = intval($this->gfxConf['imagesDPI']);
		
		try {
			$newIm = new Imagick($fileIn);
			if ($imgDPI > 0)
				$newIm->setImageResolution($imgDPI, $imgDPI);

			if ($this->gfxConf['im_useStripProfileByDefault']) {
				$newIm->stripImage();
			}
			
			switch($this->gfxConf['thumbnailingMethod']) {
				case 'CROPPED':
					$this->imagickThumbCropped($newIm, $w, $h);
					break;
					
				case 'SAMPLED':
					$this->imagickThumbSampled($newIm, $w, $h);
					break;
					
				default:
					$this->imagickThumbProportional($newIm, $w, $h);
					break;							
			}
	
			$this->imagickOptimizeObject($newIm);
	
			$newIm->writeImage($fileOut);
			
			$newIm->destroy();

			if (TYPO3_DLOG)
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
			$bRes = TRUE;
		} catch(ImagickException $e) {
			
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog(
				__METHOD__ . $e->getMessage(),
				$this->extKey, 
				\TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}
		
		return $bRes;
	}
}
?>

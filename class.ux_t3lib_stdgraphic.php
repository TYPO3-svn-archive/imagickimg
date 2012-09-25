<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2012 Radu Dumbrăveanu <vundicind@gmail.com>
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
 * @author	Radu Dumbrăveanu <vundicind@gmail.com>
 * @author	Tomasz Krawczyk <tomasz@typo3.pl>
 */ 
 
class ux_t3lib_stdGraphic extends t3lib_stdGraphic {

	private $NO_IMAGICK = 0;
	private $extKey = 'imagickimg';
	
	/**
	 * Init function. Must always call this when using the class.
	 * This function will read the configuration information from $GLOBALS['TYPO3_CONF_VARS']['GFX'] can set some values in internal variables.
	 *
	 * Additionaly function checks if PHP extension Imagick is loaded.
	 *
	 * @return	void
	 */
	function init()	{
	
		if (!extension_loaded('imagick')) {
			
			$this->NO_IMAGICK = 1;
			$sMsg = 'PHP extension Imagick is not loaded. Extension Imagickimg is deactivated.';
			
			t3lib_div::sysLog($sMsg, $this->extKey, t3lib_div::SYSLOG_SEVERITY_WARNING);
			if (TYPO3_DLOG) 
				t3lib_div::devLog($sMsg, $this->extKey, 2);
		}
		else {
				// Get IM version and overwrite user settings
			$ver = $this->getIMversion(TRUE);
			$TYPO3_CONF_VARS['GFX']['im_version_5'] = $ver;

			if (($ver == 'im5') || ($ver == 'im6')) {
				
				$TYPO3_CONF_VARS['GFX']['im_no_effects'] = 0;
				$TYPO3_CONF_VARS['GFX']['im_v5effects'] = 1;
			}
			else {
				$TYPO3_CONF_VARS['GFX']['im_no_effects'] = 1;
				$TYPO3_CONF_VARS['GFX']['im_v5effects'] = 0;
			}
		}
		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->init', $this->extKey, -1);

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

		if ($this->NO_IMAGICK)
			return '';

		$im_ver = '';
		try {
			$im = new Imagick();
			$a = $im->getVersion();
			$im->destroy();

			if (is_array($a)) {				
					// $arr['versionString'] is string 'ImageMagick 6.7.9-1 2012-08-21 Q8 http://www.imagemagick.org' (length=60)
				$v = explode(' ', $a['versionString']);
					// Add Imagick version info
				$v[] = 'Imagick';
				$v[] = Imagick::IMAGICK_EXTVER;
				$v[] = Imagick::IMAGICK_EXTNUM;
				
				if (count($v) >= 1) {
					$a = explode('.', $v[1]);
					if (count($a) >= 2) {
						$im_ver = 'im' . $a[0];
					}
				}
			}
			if (!$returnString) {
				$im_ver = $v;
			}
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imageMagickConvert >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}

		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->getIMversion', $this->extKey, 0, array($im_ver));

		return $im_ver;
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

	function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = '', $mustCreate = 0)	{

		if ($this->NO_IMAGICK) {
			return parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);
		}

		if($info = $this->getImageDimensions($imagefile))	{
			$newExt = strtolower(trim($newExt));
			if (!$newExt) { // If no extension is given the original extension is used
				$newExt = $info[2];
			}
			if ($newExt == 'web')	{
				if (t3lib_div::inList($this->webImageExt, $info[2]))	{
					$newExt = $info[2];
				} else {
					$newExt = $this->gif_or_jpg($info[2], $info[0], $info[1]);
					if (!$params)	{
						$params = $this->cmds[$newExt];
					}
				}
			}
			if (t3lib_div::inList($this->imageFileExt, $newExt)) {

				if (strstr($w . $h, 'm')) {
					$max = 1;
				} else {
					$max = 0;
				}

				$data = $this->getImageScale($info, $w, $h, $options);
				$w = $data['origW'];
				$h = $data['origH'];

					// if no conversion should be performed
					// this flag is TRUE if the width / height does NOT dictate
					// the image to be scaled!! (that is if no width / height is
					// given or if the destination w/h matches the original image
					// dimensions or if the option to not scale the image is set)
				$noScale = (!$w && !$h) || ($data[0] == $info[0] && $data[1] == $info[1]) || $options['noScale'];

				if ($noScale && !$data['crs'] && !$params && !$frame && $newExt == $info[2] && !$mustCreate) {
						// set the new width and height before returning,
						// if the noScale option is set
					if ($options['noScale']) {
						$info[0] = $data[0];
						$info[1] = $data[1];
					}
					$info[3] = $imagefile;
					return $info;
				}
				$info[0] = $data[0];
				$info[1] = $data[1];
				
				$frame = $this->noFramePrepended ? '' : intval($frame);

				if (!$params)	{
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
					//var_dump('params', $params);
				}

				$command = $this->scalecmd . ' ' . $info[0] . 'x' . $info[1] . '! ' . $params . ' ';
				$cropscale = ($data['crs'] ? 'crs-V' . $data['cropV'] . 'H' . $data['cropH'] : '');

				if (TYPO3_DLOG)
					t3lib_div::devLog('ux_t3lib_stdGraphic->imageMagickConvert', $this->extKey, 0, 
						array($imagefile, $params, $command, $cropscale));

				if ($this->alternativeOutputKey)	{
					$theOutputName = t3lib_div::shortMD5($command.$cropscale.basename($imagefile).$this->alternativeOutputKey.'['.$frame.']');
				} else {
					$theOutputName = t3lib_div::shortMD5($command.$cropscale.$imagefile.filemtime($imagefile).'['.$frame.']');
				}
				if ($this->imageMagickConvert_forceFileNameBody)	{
					$theOutputName = $this->imageMagickConvert_forceFileNameBody;
					$this->imageMagickConvert_forceFileNameBody='';
				}

					// Making the temporary filename:
				$this->createTempSubDir('pics/');
				$output = $this->absPrefix.$this->tempPath.'pics/'.$this->filenamePrefix.$theOutputName.'.'.$newExt;

				$fullOutput = '';
				if (TYPO3_OS === 'WIN' ) {
					$imagefile = realpath($imagefile);
					$fullOutput = PATH_site . $output;
				}
				
					// Register temporary filename:
				$GLOBALS['TEMP_IMAGES_ON_PAGE'][] = $output;

				if ($this->dontCheckForExistingTempFile || !$this->file_exists_typo3temp_file($output, $imagefile))	{

					$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
					
					try {
						$newIm = new Imagick($imagefile);
						$newIm->resizeImage($info[0], $info[1], $gfxConf['windowing_filter'], 1);

						//$this->imagickOptimize($newIm);
						
						if (TYPO3_OS === 'WIN')
							$newIm->writeImage($fullOutput);
						else
							$newIm->writeImage($output);
						$newIm->destroy();
						
							// apply additional params (f.e. effects, compression)
						if ($params) {
							$this->applyImagickEffect($output, $params);
						}
						
						if (TYPO3_DLOG)
							t3lib_div::devLog('ux_t3lib_stdGraphic->imageMagickConvert', $this->extKey, -1);	
					}
					catch(ImagickException $e) {
						
						t3lib_div::sysLog('ux_t3lib_stdGraphic->imageMagickConvert >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
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
	 * Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
	 * Using ImageMagick
	 *
	 * @param	string		The relative (to PATH_site) image filepath
	 * @return	array
	 */	 
	function imageMagickIdentify($imagefile) {
		
		if ($this->NO_IMAGICK) {
			return parent::imageMagickIdentify($imagefile);
		}
		
		// BE uses stdGraphics and absolute paths.
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

			if (TYPO3_DLOG) 
				t3lib_div::devLog('ux_t3lib_stdGraphic->imageMagickIdentify', $this->extKey, 0, $arRes);
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imageMagickIdentify >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
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
	function combineExec($input, $overlay, $mask, $output, $handleNegation = FALSE) {
		
		if ($this->NO_IMAGICK) {
			return parent::combineExec($input, $overlay, $mask, $output, $handleNegation);
		}
		
		if (TYPO3_OS === 'WIN' ) {
			$fileInput = PATH_site . $input;
			$fileOver = PATH_site . $overlay;
			$fileMask = PATH_site . $mask;
			$fileOutput = PATH_site . $output;
		} else {
			$fileInput = $input;
			$fileOver = $overlay;
			$fileMask = $mask;
			$fileOutput = $output;
		}
		
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
			
			//$this->imagickOptimize($baseObj);

			$baseObj->writeImage($fileOutput);

			$maskObj->destroy();
			$overObj->destroy();
			$baseObj->destroy();

			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->combineExec', $this->extKey, -1);
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->combineExec() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
		
		return '';
	}

	
    /**
     * Compresses given image.
     *
	 * @param	Imagick		Imagick object
	 * @return	void
     */
	private function imagickCompress(&$imageObj) {
	
		$imgExt = strtolower($imageObj->getImageFormat());
		
		switch($imgExt) {
			
			case 'gif':
				if ($this->jpegQuality == 100)
					$imageObj->setImageCompression(Imagick::COMPRESSION_RLE);
				else
					$imageObj->setImageCompression(Imagick::COMPRESSION_LZW);
				break;
			
			case 'jpg':
			case 'jpeg':
				if ($this->jpegQuality == 100)
					$imageObj->setImageCompression(Imagick::COMPRESSION_LOSSLESSJPEG);
				else
					$imageObj->setImageCompression(Imagick::COMPRESSION_JPEG);
				$imageObj->setImageCompressionQuality($this->jpegQuality);
				break;
			
			case 'png':
				$imageObj->setImageCompression(Imagick::COMPRESSION_ZIP);
				$imageObj->setImageCompressionQuality($this->jpegQuality);
				break;
			
			case 'tif':
			case 'tiff':
				if ($this->jpegQuality == 100)
					$imageObj->setImageCompression(Imagick::COMPRESSION_LOSSLESSJPEG);
				else
					$imageObj->setImageCompression(imagick::COMPRESSION_LZW);
				$imageObj->setImageCompressionQuality($this->jpegQuality);
				break;
		}

		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->imagickCompress', $this->extKey, -1);		
	}

    /**
     * Removes profiles and comments from the image.
     *
	 * @param	Imagick		Imagick object
	 * @return	void
     */
	private function imagickProfile(&$imageObj) {
		/*
		Using -profile filename adds an ICM (ICC color management), IPTC (newswire information), or a generic profile to the image.
		Use +profile profile_name to remove the indicated profile. ImageMagick uses standard filename globbing, so wildcard expressions may be used to remove more than one profile. Here we remove all profiles from the image except for the XMP profile: +profile "!xmp,*".
		*/
		if ( $TYPO3_CONF['GFX']['im_useStripProfileByDefault']) {
			
			$profile = $TYPO3_CONF['GFX']['im_stripProfileCommand'];
			if (substr($profile, 0, 1) == '+') {
			
					// remove profiles
				if ( $TYPO3_CONF['GFX']['im_stripProfileCommand'] == '+profile \'*\'') {
						
						// remove all profiles and comments
					$imageObj->stripImage();
				}
				/*else {
					$imageObj->profileImage('*', NULL); // removes all profiles
					$imageObj->profileImage('EXIF', NULL); // removes EXIF
					$imageObj->profileImage('IPTC', NULL); // removes IPTC
					$imageObj->profileImage('ICC', NULL); // removes ICC
				}*/
			}
		}

		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->imagickProfile', $this->extKey, -1);		
	}

    /**
     * Optimizes image resolution.
     *
	 * @param	Imagick		Imagick object
	 * @return	void
     */
	private function imagickOptimizeResolution(&$imageObj) {
	
		$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		$imgDPI = intval($gfxConf['imagesDPI']);

		if (intval($gfxConf['imagesDPI']) > 0)
			$imageObj->setImageResolution($imgDPI, $imgDPI);

		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->imagickOptimizeResolution', $this->extKey, -1);		
	}
	
    /**
     * Executes all optimization methods on the image. Execute it just before storing image to disk.
     * 
     * @param Imagick		Imagick object
	 * @return	void
     */
	private function imagickOptimize(&$imageObj) {
		
		if ($this->NO_IMAGICK) {
			return;
		}

		$imageObj->optimizeImageLayers();		
		$this->imagickProfile($imageObj);
		$this->imagickOptimizeResolution($imageObj);
		$this->imagickCompress($imageObj);

		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->imagickOptimize', $this->extKey, -1);		
	}

	/**
	 * Reduce colors in image using IM and create a palette based image if possible (<=256 colors)
	 *
	 * @param	string		Image file to reduce
	 * @param	integer		Number of colors to reduce the image to.
	 * @return	string		Reduced file
	 */
	function IMreduceColors($file, $cols) {

		if ($this->NO_IMAGICK) {
			return parent::IMreduceColors($file, $cols);
		}

		$fI = t3lib_div::split_fileref($file);
		$ext = strtolower($fI['fileext']);
		$result = $this->randomName() . '.' . $ext;
		$reduce = $this->getIntRange($cols, 0, ($ext == 'gif' ? 256 : $this->truecolorColors), 0);
		if ($reduce > 0) {

			if (TYPO3_OS === 'WIN' )
				$fileResult = PATH_site . $result;
			else
				$fileResult = $result;

			try {
				$newIm = new Imagick();
				$newIm->readImage($fileResult);
			
				if ($reduce <= 256) {
					$newIm->setType(imagick::IMGTYPE_PALETTE);
				}
				if ($ext == 'png' && $reduce <= 256) {
					$newIm->setImageDepth(8);
					$newIm->setImageFormat('PNG8');
				}			
				
					// Reduce the amount of colors
				$newIm->quantizeImage($reduce, Imagick::COLORSPACE_RGB, 0, false, false);
					// Only save one pixel of each color
				$newIm->uniqueImageColors();
				
				$this->imagickOptimize($newIm);
				$newIm->writeImage($fileResult);
				$newIm->destroy();
				
				if (TYPO3_DLOG)
					t3lib_div::devLog('ux_t3lib_stdGraphic->IMreduceColors', $this->extKey, -1);
				
				return $result;	
			}
			catch(ImagickException $e) {
				
				t3lib_div::sysLog('ux_t3lib_stdGraphic->IMreduceColors() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
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
	 * /
	function makeEffect(&$im, $conf) {
		if ($this->NO_IMAGICK) {
			return parent::makeEffect(&$im, $conf);
		}

		$commands = $this->IMparams($conf['value']);
		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_tslib_gifBuilder->makeEffect', $this->extKey, 0, array($commands));

		if ($commands) {
			$this->applyImageMagickToPHPGif($im, $commands);
		}
		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->makeEffect', $this->extKey, -1);		
	}*/

	/**
	 * Applies an ImageMagick parameter to a GDlib image pointer resource by writing the resource to file, performing an IM operation upon it and reading back the result into the ImagePointer.
	 *
	 * @param	pointer		The image pointer (reference)
	 * @param	string		The ImageMagick parameters. Like effects, scaling etc.
	 * @return	void
	 * /
	function applyImageMagickToPHPGif(&$im, $command) {
		
		if ($this->NO_IMAGICK) {
			return parent::applyImageMagickToPHPGif(&$im, $command);
		}
		
		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->applyImageMagickToPHPGif', $this->extKey, -1, array($command));

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

		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->applyImageMagickToPHPGif', $this->extKey, -1);
	}*/
	
    /**
     * Main function applying Imagick effects
     *
	 * @param	pointer		The image pointer (reference)
	 * @param	string		The ImageMagick parameters. Like effects, scaling etc.
	 * @return	void
     */
	function applyImagickEffect($file, $command) {

		if ($this->NO_IMAGICK || $this->NO_IM_EFFECTS || !$this->V5_EFFECTS) {
			return;
		}

		$command = strtolower(trim($command));
		$command = str_ireplace('-', '', $command);		
		$elems = t3lib_div::trimExplode(' ', $command, true);
	
		if (TYPO3_DLOG)
			t3lib_div::devLog('applyImagickEffect', $this->extKey, 0, array($file, $elems));

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
					break;

				case 'sharpen':
					$this->imagickSharpen($file, $elems[1]);
					break;
					
				case 'gamma':	// brighter, darker
					$this->imagickGamma($file, $elems[1]);
					break;

				/* compression */
				case 'colors':
					$this->IMreduceColors($file, $elems[1]);
					break;

				case 'quality':
					$this->IMreduceColors($file, $elems[1]);
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
					$this->IMreduceColors($file, $elems[2]);
					break;

				case 'quality':
					$this->IMreduceColors($file, $elems[2]);
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
					break;

				case 'sharpen':
					$this->imagickSharpen($file, $elems[1]);
					break;
					// brighter, darker
				case 'gamma':
					$this->imagickGamma($file, $elems[1]);
					break;
			}
			
			/* compression */
			switch($elems[2]) {

				case 'colors':
					$this->IMreduceColors($file, $elems[3]);
					break;

				case 'quality':
					$this->IMreduceColors($file, $elems[3]);
					break;
			}
			
		}
		else {
			t3lib_div::devLog('ux_t3lib_stdGraphic->applyImagickEffect> Not expected amount of parameters', $this->extKey, 2, $elems);
		}
		
		if (TYPO3_DLOG)
			t3lib_div::devLog('ux_t3lib_stdGraphic->applyImagickEffect', $this->extKey, -1);
	}

	private function imagickGamma($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->gammaImage($value);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickGamma', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickGamma() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}		
	}
	
	private function imagickBlur($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->blurImage($value);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickBlur', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickBlur() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}		
	}
	
	private function imagickSharpen($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->sharpenImage(0, $value);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickSharpen', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickSharpen() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickRotate($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->rotateImage(new ImagickPixel(), $value);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickRotate', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickRotate() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickSolarize($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->solarizeImage($value);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickSolarize', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickSolarize() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickSwirl($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->swirlImage($value);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickSwirl', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickSwirl() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickWawe($file, $value1, $value2) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->waveImage($value1, $value2);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickWawe', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickWawe() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickCharcoal($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->charcoalImage($value);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickCharcoal', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickCharcoal() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickGray($file) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		$fI = t3lib_div::split_fileref($file);
		$ext = strtolower($fI['fileext']);
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			//$newIm->setImageColorspace(Imagick::COLORSPACE_GRAY);
			$newIm->setImageType(Imagick::IMGTYPE_GRAYSCALE);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickGray', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickGray() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickEdge($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->edgeImage($value);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickEdge', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickEdge() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickEmboss($file) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->embossImage(0);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickEmbross', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickEmbross() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickFlip($file) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->flipImage();
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickFlip', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickFlip() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickFlop($file) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->flopImage();
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickFlop', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickFlop() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickColors($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->quantizeImage($value, $newIm->getImageColorspace(), 0, false, false);
				// Only save one pixel of each color
			$newIm->uniqueImageColors();
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickColors', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickColors() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickShear($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->shearImage($newIm->getImageBackgroundColor(), $value, $value);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickShear', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickShear() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickInvert($file, $value) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->negateImage(0);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickInvert', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickInvert() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickNormalize($file) {
	
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->normalizeImage();
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickNormalize', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickNormalize() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	private function imagickContrast($file, $value = 1) {
	
		$val = $this->getIntRange($value, 0, 9);
		
		if (TYPO3_OS === 'WIN')
			$fileResult = PATH_site . $file;
		else
			$fileResult = $result;
			
		try {
			$newIm = new Imagick();
			$newIm->readImage($fileResult);
		
			$newIm->contrastImage($val);
		
			$this->imagickOptimize($newIm);
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickNormalize', $this->extKey, -1);
			
			return $result;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickNormalize() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}
	
    /**
     * Wraper function for compatibility with versions older than 4.6.
     * 
     * @param int theInt The integer value
     * @param int theMin Minimum value
     * @param int theMax Maximum vaue
     * @param int theDefault  Default value
	 * @return	int Returns value from range
     */
	private function getIntRange($theInt, $theMin, $theMax, $theDefault = 0) {
		
		$res = 0;

		if (version_compare(TYPO3_version, '4.6.0', '>='))
			$res = t3lib_utility_Math::forceIntegerInRange($theInt, $theMin, $theMax, $theDefault);
		else
			$res = t3lib_div::intInRange($theInt, $theMin, $theMax, $theDefault);
		
		return $res;
	}
	
	/**
     * Returns an array with detailed image info.
     *
     * @param 	string	File path
	 * @return	array	Image information
     */
	public function imagickGetDetailedImageInfo($imagefile) {
		/*
		if (TYPO3_OS === 'WIN')
			$file = $this->wrapFileName(PATH_site . $imagefile);
		else*/
			$file = $imagefile;

		try {
			$im = new Imagick();
			$im->readImage($file);
			$identify = $im->identifyImage();

			$res = array(
				'Basic image properties' => array(
					'Image geometry: ' => $identify['geometry']['width'] . 'x' . $identify['geometry']['height'],
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
			foreach ( $im->getImageProperties() as $k => $v )
			{
				$res['All image properties'] = array_merge($res['All image properties'], array($k => $v));
			}

			$res['All image profiles'] = array();
			foreach ( $im->getImageProfiles() as $k => $v )
			{
				$res['Profile name'] = array_merge($res['Profile name'], array($k => '(size: ' . strlen( $v ) . ')'));
			}

			if (TYPO3_DLOG)
				t3lib_div::devLog('ux_t3lib_stdGraphic->imagickGetDetailedImageInfo', $this->extKey, -1);

			$im->destroy();
			
			return $res;				
		}
		catch(ImagickException $e) {
			
			t3lib_div::sysLog('ux_t3lib_stdGraphic->imagickGetDetailedImageInfo() >> ' . $e->getMessage(), $this->extKey, t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

}

?>

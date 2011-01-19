<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Radu Dumbrăveanu <vundicind@gmail.com>
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
 */ 
 
class ux_t3lib_stdGraphic extends t3lib_stdGraphic	{


	/***********************************
	 *
	 * Scaling, Dimensions of images
	 *
	 ***********************************/

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

    if (!$this->NO_IMAGE_MAGICK) {	
      return parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);
    }  
    
		if($info = $this->getImageDimensions($imagefile))	{
			$newExt = strtolower(trim($newExt));
			if (!$newExt)	{	// If no extension is given the original extension is used
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
				if (strstr($w.$h, 'm')) {$max=1;} else {$max=0;}

				$data = $this->getImageScale($info, $w, $h, $options);
				$w = $data['origW'];
				$h = $data['origH'];

					// if no convertion should be performed
				$wh_noscale = (!$w && !$h) || ($data[0]==$info[0] && $data[1]==$info[1]);		// this flag is true if the width / height does NOT dictate the image to be scaled!! (that is if no w/h is given or if the destination w/h matches the original image-dimensions....

				if ($wh_noscale && !$data['crs'] && !$params && !$frame && $newExt==$info[2] && !$mustCreate) {
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
					if (!$data['origW']) { $data['origW'] = $data[0]; }
					if (!$data['origH']) { $data['origH'] = $data[1]; }
					$offsetX = intval(($data[0] - $data['origW']) * ($data['cropH']+100)/200);
					$offsetY = intval(($data[1] - $data['origH']) * ($data['cropV']+100)/200);
					$params .= ' -crop '.$data['origW'].'x'.$data['origH'].'+'.$offsetX.'+'.$offsetY.' ';
				}

				$command = $this->scalecmd.' '.$info[0].'x'.$info[1].'! '.$params.' ';
				$cropscale = ($data['crs'] ? 'crs-V'.$data['cropV'].'H'.$data['cropH'] : '');

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

					// Register temporary filename:
				$GLOBALS['TEMP_IMAGES_ON_PAGE'][] = $output;

				if ($this->dontCheckForExistingTempFile || !$this->file_exists_typo3temp_file($output, $imagefile))	{
					$newIm = new Imagick($imagefile);
					$newIm->resizeImage($info[0], $info[1], $GLOBALS['TYPO3_CONF_VARS']['GFX']['windowing_filter'], 1);

					switch($newExt) {
//					  case 'gif':
//					    if($gfxConf['gif_compress']) {
//								$newIm->setImageCompression(Imagick::COMPRESSION_LZW);
//								$newIm->setImageCompressionQuality(100);
//					    }
//					  	break;
						case 'jpg':
						case 'jpeg':
							$newIm->setImageCompression(Imagick::COMPRESSION_JPEG);
							$newIm->setImageCompressionQuality($this->jpegQuality);
							break;
					}
					
					$newIm->writeImage($output);
					$newIm->destroy();
				}
				if (file_exists($output))	{
					$info[3] = $output;
					$info[2] = $newExt;
					if ($params)	{	// params could realisticly change some imagedata!
						$info=$this->getImageDimensions($info[3]);
					}
					return $info;
				}
			}
		}
	}
}
?>

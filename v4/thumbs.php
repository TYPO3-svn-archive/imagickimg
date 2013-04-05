<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2013 Radu Dumbrăveanu <vundicind@gmail.com>
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
 
class ux_SC_t3lib_thumbs extends SC_t3lib_thumbs {

	private $extKey = 'imagickimg';

	/**
	 * Create the thumbnail
	 * Will exit before return if all is well.
	 *
	 * @return	void
	 */
	public function main()	{

		if (TYPO3_DLOG) t3lib_div::devLog(__METHOD__, $this->extKey);
	
		$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		if (TYPO3_DLOG)
			t3lib_div::devLog(__METHOD__, $this->extKey, -1, $gfxConf);

		if (!$gfxConf['imagick']) {
		  return parent::main();
		}

			// If file exists, we make a thumbsnail of the file.
		if ($this->input && file_exists($this->input))	{
				// Check file extension:
			$reg = array();
			if (preg_match('/(.*)\.([^\.]*$)/', $this->input, $reg)) {
				$ext=strtolower($reg[2]);
				$ext=($ext=='jpeg')?'jpg':$ext;
				if ($ext=='ttf')	{
					$this->fontGif($this->input);	// Make font preview... (will not return)
				} elseif (!t3lib_div::inList($this->imageList, $ext))	{
					$this->errorGif('Not imagefile!', $ext, basename($this->input));
				}
			} else {
				$this->errorGif('Not imagefile!', 'No ext!', basename($this->input));
			}

				// ... so we passed the extension test meaning that we are going to make a thumbnail here:
			if (!$this->size) 	$this->size = $this->sizeDefault;	// default

				// I added extra check, so that the size input option could not be fooled to pass other values. That means the value is exploded, evaluated to an integer and the imploded to [value]x[value]. Furthermore you can specify: size=340 and it'll be translated to 340x340.
			$sizeParts = explode('x', $this->size. 'x' .$this->size);	// explodes the input size (and if no "x" is found this will add size again so it is the same for both dimensions)
			if (version_compare(TYPO3_version, '4.6.0', '>=')) {
				$sizeParts = array(
					t3lib_utility_Math::forceIntegerInRange($sizeParts[0], 1, 1000),
					t3lib_utility_Math::forceIntegerInRange($sizeParts[1], 1, 1000)
				);	// Cleaning it up, only two parameters now.
			}
			else {
				$sizeParts = array(
					t3lib_div::intInRange($sizeParts[0], 1, 1000),
					t3lib_div::intInRange($sizeParts[1], 1, 1000)
				);	// Cleaning it up, only two parameters now.
			}
			$this->size = implode('x', $sizeParts);		// Imploding the cleaned size-value back to the internal variable
			$sizeMax = max($sizeParts);	// Getting max value

				// Init
			$outpath = PATH_site.$this->outdir;

				// Should be - ? 'png' : 'gif' - , but doesn't work (ImageMagick prob.?)
				// René: png work for me
			if (version_compare(TYPO3_version, '4.6.0', '>=')) {
				$thmMode = t3lib_utility_Math::forceIntegerInRange($gfxConf['thumbnails_png'], 0);
			} else {
				$thmMode = t3lib_div::intInRange($gfxConf['thumbnails_png'], 0);
			}
			$outext = ($ext!='jpg' || ($thmMode & 2)) ? ($thmMode & 1 ? 'png' : 'gif') : 'jpg';

			$outfile = 'tmb_'.substr(md5($this->input.$this->mtime.$this->size), 0, 10) . '.' . $outext;
			$this->output = $outpath.$outfile;
			
			if (TYPO3_DLOG)
				t3lib_div::devLog(__METHOD__, $this->extKey, 0, array($thmMode, $this->input, $this->output));
			
				// If thumbnail does not exist, we generate it
			if (!file_exists($this->output)) {			
				
				$graphics = t3lib_div::makeInstance('t3lib_stdGraphic');
				$graphics->init();
				$graphics->mayScaleUp = 0; 
				$graphics->imagickThumbnailImage(
					$this->input,
					$this->output,
					$sizeParts[0],
					$sizeParts[1]
				);					
					
				if (!file_exists($this->output))	{
					$this->errorGif('No thumb', 'generated!', basename($this->input));
				} else {
					t3lib_div::fixPermissions($this->output);
				}
			}
				// The thumbnail is read and output to the browser
			if($fd = @fopen($this->output, 'rb'))	{
				header('Content-type: image/' . $outext);
				fpassthru($fd);
				fclose($fd);
			} else {
				$this->errorGif('Read problem!', '', $this->output);
			}
		} else {
			$this->errorGif('No valid', 'inputfile!', basename($this->input));
		}
	}

	
}
?>

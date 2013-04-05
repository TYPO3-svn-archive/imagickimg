<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2013 Tomasz Krawczyk <tomasz@typo3.pl>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class ux_SC_show_item extends SC_show_item	{

	private $extKey = 'imagickimg';

	/**
	 * Main function. Will generate the information to display for the item set internally.
	 *
	 * @param	string		<a> tag closing/returning.
	 * @return	void
	 */
	public function renderFileInfo($returnLinkTag)	{

		if (TYPO3_DLOG) t3lib_div::devLog(__METHOD__, $this->extKey);

		// Initialize object to work on the image:
		$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
		$imgObj->init();
		$imgObj->mayScaleUp = 0;
		$imgObj->absPrefix = PATH_site;

			// Read Image Dimensions (returns FALSE if file was not an image type, otherwise dimensions in an array)
		$imgInfo = '';
		$imgInfo = $imgObj->getImageDimensions($this->file);

			// File information
		$fI = t3lib_div::split_fileref($this->file);
		$ext = $fI['fileext'];

		$code = '<div class="fileInfoContainer">';

			// Setting header:
		$fileName = t3lib_iconWorks::getSpriteIconForFile($ext) . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.file', TRUE) . ':</strong> ' . $fI['file'];
		if (t3lib_div::isFirstPartOfStr($this->file, PATH_site))	{
			$code.= '<a href="../'.substr($this->file, strlen(PATH_site)).'" target="_blank">'.$fileName.'</a>';
		} else {
			$code.= $fileName;
		}
		$code.=' &nbsp;&nbsp;'
			. '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.filesize') . ':</strong> '
			. t3lib_div::formatSize(@filesize($this->file)) . '</div>
			';
		if (is_array($imgInfo))	{
/*
			$code.= '<div class="fileInfoContainer fileDimensions">'
				. '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.dimensions') . ':</strong> '
				. $imgInfo[0] . 'x' . $imgInfo[1] . ' '
				. $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.pixels') . '</div>';
*/
			// Added code start
			$imageDetInfo = $imgObj->imagickGetDetailedImageInfo($this->file);
			if (is_array($imageDetInfo)) {

				foreach($imageDetInfo as $m => $g ) {
					
					if (!empty($g)) {
					
						$code .= '<h2>' . $m . '</h2>';
						$code .= '<table border="1" cellspacing="0" cellpadding="5" class="typo3-dblist" style="font-size: 11px; border-collapse:collapse;">';
					
						foreach($g as $k => $v) {
							$code .= '<tr><td nowrap="nowrap"><b>' . $k . '</b></td>';
							$code .= '<td>' . htmlspecialchars(trim($v), ENT_QUOTES | ENT_IGNORE, 'UTF-8') . '&nbsp;</td></tr>';						
						}
					}
					$code .= '</table>';
				}
			}			
			$code .= $this->doc->spacer(10);			
			// Added code stop
		}
		$this->content.=$this->doc->section('', $code);
		$this->content.=$this->doc->divider(2);

			// If the file was an image...:
		if (is_array($imgInfo))	{

			$imgInfo = $imgObj->imageMagickConvert($this->file, 'web', '520', '390m', '', '', '', 1);
			$imgInfo[3] = '../'.substr($imgInfo[3], strlen(PATH_site));
			$code = '<br />
				<div align="center">'.$returnLinkTag.$imgObj->imgTag($imgInfo).'</a></div>';
			$this->content.= $this->doc->section('', $code);
		} else {
			$this->content.= $this->doc->spacer(10);
			$lowerFilename = strtolower($this->file);

				// Archive files:
			if (TYPO3_OS!='WIN' && !$GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function'])	{
				if ($ext=='zip')	{
					$code = '';
					$t = array();
					t3lib_utility_Command::exec('unzip -l ' . $this->file, $t);
					if (is_array($t))	{
						reset($t);
						next($t);
						next($t);
						next($t);
						while(list(, $val)=each($t))	{
							$parts = explode(' ', trim($val), 7);
							$code.= '
								'.$parts[6].'<br />';
						}
						$code = '
							<span class="nobr">'.$code.'
							</span>
							<br /><br />';
					}
					$this->content.= $this->doc->section('', $code);
				} elseif($ext=='tar' || $ext=='tgz' || substr($lowerFilename, -6)=='tar.gz' || substr($lowerFilename, -5)=='tar.z')	{
					$code = '';
					if ($ext=='tar')	{
						$compr = '';
					} else {
						$compr = 'z';
					}
					$t = array();
					t3lib_utility_Command::exec('tar t' . $compr . 'f ' . $this->file, $t);
					if (is_array($t))	{
						foreach($t as $val)	{
							$code.='
								'.$val.'<br />';
						}

						$code.='
								 -------<br/>
								 '.count($t).' '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.files');

						$code = '
							<span class="nobr">'.$code.'
							</span>
							<br /><br />';
					}
					$this->content.= $this->doc->section('', $code);
				}
			} elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function']) {
				$this->content.= $this->doc->section('', $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.cannotDisplayArchive'));
			}

				// Font files:
			if ($ext=='ttf')	{
				$thumbScript = 'thumbs.php';
				$check = basename($this->file).':'.filemtime($this->file).':'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
				$params = '&file='.rawurlencode($this->file);
				$params.= '&md5sum='.t3lib_div::shortMD5($check);
				$url = $thumbScript.'?&dummy='.$GLOBALS['EXEC_TIME'].$params;
				$thumb = '<br />
					<div align="center">'.$returnLinkTag.'<img src="'.htmlspecialchars($url).'" border="0" title="'.htmlspecialchars(trim($this->file)).'" alt="" /></a></div>';
				$this->content.= $this->doc->section('', $thumb);
			}
		}

			// References:
		$this->content.= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesToThisItem'), $this->makeRef('_FILE', $this->file));
	}

}
?>

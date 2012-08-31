<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2012 Tomasz Krawczyk <tomasz@typo3.pl>
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


/**
 * Post processes the warning messages found in about modules.
 *
 * @author	Tomasz Krawczyk <tomasz@typo3.pl>
 * @package TYPO3
 * @subpackage reports
 */
class tx_imagickimg_WarningMessagePostProcessor {

	/**
	 * Checks if PHP extension Imagick is loaded. Displays BE warning if not.
	 *
	 * @param	array	An array of messages related to already found issues.
	 */
	public function displayWarningMessages_postProcess(array &$warningMessages) {

		if (!extension_loaded('imagick')) {
			$warningMessages['tx_imagickimg_imagickExtensionNotLoaded'] = 'PHP extension Imagick is not loaded. All image processing is disabled.';
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_warningmessagepostprocessor.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_warningmessagepostprocessor.php']);
}
?>

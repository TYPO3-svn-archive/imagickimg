<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// Get TYPO3 version
if (function_exists('t3lib_utility_VersionNumber::convertVersionNumberToInteger')) {
	$t3version = t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version);
} else {
    $t3version = t3lib_div::int_from_ver(TYPO3_version);
}

// load additional effects labels
t3lib_div::loadTCA('tt_content');


if (TYPO3_MODE === 'BE') {

	if ($t3version >= 4006000) {

		$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][10] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xlf:image_effects.I.10', 31);
		$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][11] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xlf:image_effects.I.11', 32);
		$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][12] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xlf:image_effects.I.12', 33);
	}
	else {
		
		$TCA['tt_content']['columns']['image_effects']['config']['items'][10] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xml:image_effects.I.10', 31);
		$TCA['tt_content']['columns']['image_effects']['config']['items'][11] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xml:image_effects.I.11', 32);
		$TCA['tt_content']['columns']['image_effects']['config']['items'][12] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xml:image_effects.I.12', 33);
	}
}

?>
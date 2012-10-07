<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

// load additional effects labels
t3lib_div::loadTCA('tt_content');

if (version_compare(TYPO3_version, '4.6.0', '>=')) {

	$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][10] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xlf:image_effects.I.10', 31);
	$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][11] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xlf:image_effects.I.11', 32);
	$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][12] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xlf:image_effects.I.12', 33);
} 
else {

	$TCA['tt_content']['columns']['image_effects']['config']['items'][10] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xml:image_effects.I.10', 31);
	$TCA['tt_content']['columns']['image_effects']['config']['items'][11] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xml:image_effects.I.11', 32);
	$TCA['tt_content']['columns']['image_effects']['config']['items'][12] = array('LLL:EXT:'.$_EXTKEY.'/lang/locallang_ttc.xml:image_effects.I.12', 33);
}
?>
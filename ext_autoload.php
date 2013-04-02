<?php
// Check TYPO3 version
// Use PHP function because t3lib_utility_VersionNumber::convertVersionNumberToInteger is not loaded yet.

if (version_compare(TYPO3_version, '6.0', '>=')) {

	$extPath = t3lib_extMgm::extPath('imagickimg');

	$arr = array(
		'ux_GraphicalFunctions' => $extPath . 'Classes/Xclass/GraphicalFunctions.php',
		'ux_GifBuilder' => $extPath . 'Classes/Xclass/GifBuilder.php',
		'ux_ThumbnailView' => $extPath . 'Classes/Xclass/ThumbnailView.php',
		'ux_LocalPreviewHelper' => $extPath . 'Classes/Xclass/LocalPreviewHelper.php',
		'ux_ContentObjectRenderer' => $extPath . 'Classes/Xclass/ContentObjectRenderer.php',
		'ux_ElementInformationController' => $extPath . 'Classes/Xclass/ElementInformationController.php'
	);
} else {
	$arr = array();
}

return $arr;
?>

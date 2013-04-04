<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$extPath = t3lib_extMgm::extPath('imagickimg');

// Disabling image processing before check if Imagick is loaded.
$GLOBALS['TYPO3_CONF_VARS']['GFX']['image_processing'] = 0;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] = 0;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'] = 0;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] = 0;

// Get TYPO3 version
//if (function_exists('\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger')) {
//	$t3version = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
//} else
if (function_exists('t3lib_utility_VersionNumber::convertVersionNumberToInteger')) {
	$t3version = t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version);
} else {
    $t3version = t3lib_div::int_from_ver(TYPO3_version);
}

if ($t3version >= 6000000) {

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions'] = 
		array('className' => 'ux_GraphicalFunctions');
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder'] = 
		array('className' => 'ux_GifBuilder');
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\View\\ThumbnailView'] = 
		array('className' => 'ux_ThumbnailView');
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Resource\\Processing\\LocalPreviewHelper'] = 
		array('className' => 'ux_LocalPreviewHelper');
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'] = 
		array('className' => 'ux_ContentObjectRenderer');
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\Controller\\ContentElement\\ElementInformationController'] = 
		array('className' => 'ux_ElementInformationController');

} else {

	$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']['tslib/class.tslib_gifbuilder.php'] = $extPath . 'v4/class.ux_tslib_gifbuilder.php';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS']['t3lib/class.t3lib_stdgraphic.php'] = $extPath . 'v4/class.ux_t3lib_stdgraphic.php';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS']['t3lib/thumbs.php']                 = $extPath . 'v4/thumbs.php';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS']['typo3/show_item.php']              = $extPath . 'v4/class.ux_SC_show_item.php';
	$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']['tslib/class.tslib_content.php']    = $extPath . 'v4/class.ux_tslib_content.php';
}

// Add a hook to show Backend warnings
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][$_EXTKEY] = $extPath . 'class.tx_imagickimg_WarningMessagePostProcessor.php:tx_imagickimg_WarningMessagePostProcessor';

$_EXTCONF = unserialize($_EXTCONF);	// unserializing the configuration

if (extension_loaded('imagick')) {

	// Imagick loaded, so turn on image processing
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['image_processing'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagick'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'] = ''; // Not necesary while using Imagick
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'] = ''; // Not necesary while using Imagick
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_combine_filename'] = ''; // Not necesary while using Imagick
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_no_effects'] = 0;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress'] = 0; // Don't use TYPO3 work around. Imagick will compress the images.

	switch($_EXTCONF['windowingFilter']) {
	  case 'POINT':
		$wF = Imagick::FILTER_POINT;
		break;
	  case 'BOX':
		$wF = Imagick::FILTER_BOX;
		break;    
	  case 'TRIANGLE':
		$wF = Imagick::FILTER_TRIANGLE;
		break;
	  case 'HERMITE':
		$wF = Imagick::FILTER_HERMITE;
		break;
	  case 'HANNING':
		$wF = Imagick::FILTER_HANNING;
		break;
	  case 'HAMMING':
		$wF = Imagick::FILTER_HAMMING;
		break;
	  case 'BLACKMAN':
		$wF = Imagick::FILTER_BLACKMAN;
		break;
	  case 'GAUSSIAN':
		$wF = Imagick::FILTER_GAUSSIAN;
		break;
	  case 'QUADRATIC':
		$wF = Imagick::FILTER_QUADRIC;
		break;
	  case 'CUBIC':
		$wF = Imagick::FILTER_CUBIC;
		break;
	  case 'CATROM':
		$wF = Imagick::FILTER_CATROM;
		break;
	  case 'MITCHELL':
		$wF = Imagick::FILTER_MITCHELL;
		break;
	  case 'LANCZOS':
		$wF = Imagick::FILTER_LANCZOS;
		break;
	  case 'BESSEL':
		$wF = Imagick::FILTER_BESSEL;
		break;
	  case 'SINC':
		$wF = Imagick::FILTER_SINC;
		break;
	  default:
		$wF = Imagick::FILTER_CATROM;
	}
		
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['windowing_filter'] = $wF;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagesDPI'] = $_EXTCONF['imagesDPI'];
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnailingMethod'] = $_EXTCONF['thumbnailingMethod'];
}
?>

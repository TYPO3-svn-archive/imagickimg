<?php
if (!defined ("TYPO3_MODE")) die ("Access denied.");

// Disabling image processing before check if Imagick is loaded
$TYPO3_CONF_VARS['GFX']['image_processing'] = FALSE;
$TYPO3_CONF_VARS['GFX']['im'] = FALSE;
$TYPO3_CONF_VARS['GFX']['gdlib'] = FALSE;
$TYPO3_CONF_VARS['GFX']['thumbnails'] = FALSE;

$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/class.tslib_gifbuilder.php']=t3lib_extMgm::extPath($_EXTKEY).'class.ux_tslib_gifbuilder.php';
$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_stdgraphic.php']=t3lib_extMgm::extPath($_EXTKEY).'class.ux_t3lib_stdgraphic.php';
$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/thumbs.php']=t3lib_extMgm::extPath($_EXTKEY).'thumbs.php';

// Add a hook to show Backend warnings
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/class.tx_imagickimg_WarningMessagePostProcessor.php:tx_imagickimg_WarningMessagePostProcessor';


$_EXTCONF = unserialize($_EXTCONF);	// unserializing the configuration

if (extension_loaded('imagick')) {

	// Imagick loaded, so turn on image processing
	$TYPO3_CONF_VARS['GFX']['image_processing'] = TRUE;
	$TYPO3_CONF_VARS['GFX']['im'] = FALSE;
	$TYPO3_CONF_VARS['GFX']['gdlib'] = TRUE;
	$TYPO3_CONF_VARS['GFX']['thumbnails'] = TRUE;
	$TYPO3_CONF_VARS['GFX']['im_v5effects'] = 1;


	switch($_EXTCONF['windowingFilter']) {
	  case 'POINT':
		$wF = imagick::FILTER_POINT;
		break;
	  case 'BOX':
		$wF = imagick::FILTER_BOX;
		break;    
	  case 'TRIANGLE':
		$wF = imagick::FILTER_TRIANGLE;
		break;
	  case 'HERMITE':
		$wF = imagick::FILTER_HERMITE;
		break;
	  case 'HANNING':
		$wF = imagick::FILTER_HANNING;
		break;
	  case 'HAMMING':
		$wF = imagick::FILTER_HAMMING;
		break;
	  case 'BLACKMAN':
		$wF = imagick::FILTER_BLACKMAN;
		break;
	  case 'GAUSSIAN':
		$wF = imagick::FILTER_GAUSSIAN;
		break;
	  case 'QUADRATIC':
		$wF = imagick::FILTER_QUADRIC;
		break;
	  case 'CUBIC':
		$wF = imagick::FILTER_CUBIC;
		break;
	  case 'CATROM':
		$wF = imagick::FILTER_CATROM;
		break;
	  case 'MITCHELL':
		$wF = imagick::FILTER_MITCHELL;
		break;
	  case 'LANCZOS':
		$wF = imagick::FILTER_LANCZOS;
		break;
	  case 'BESSEL':
		$wF = imagick::FILTER_BESSEL;
		break;
	  case 'SINC':
		$wF = imagick::FILTER_SINC;
		break;
	  default:
		$wF = imagick::FILTER_CATROM;
	}
		
	$TYPO3_CONF_VARS['GFX']['windowing_filter'] = $wF;
	$TYPO3_CONF_VARS['GFX']['imagesDPI'] = $_EXTCONF['imagesDPI'];
}
?>

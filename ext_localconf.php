<?php
if (!defined ("TYPO3_MODE")) die ("Access denied.");

// Disabling image processing before check if Imagick is loaded.
$TYPO3_CONF_VARS['GFX']['image_processing'] = 0;
$TYPO3_CONF_VARS['GFX']['im'] = 0;
$TYPO3_CONF_VARS['GFX']['gdlib'] = 0;
$TYPO3_CONF_VARS['GFX']['thumbnails'] = 0;

$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/class.tslib_gifbuilder.php']=t3lib_extMgm::extPath($_EXTKEY).'class.ux_tslib_gifbuilder.php';
$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_stdgraphic.php']=t3lib_extMgm::extPath($_EXTKEY).'class.ux_t3lib_stdgraphic.php';
$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/thumbs.php']=t3lib_extMgm::extPath($_EXTKEY).'thumbs.php';

// Add a hook to show Backend warnings
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/class.tx_imagickimg_WarningMessagePostProcessor.php:tx_imagickimg_WarningMessagePostProcessor';


$_EXTCONF = unserialize($_EXTCONF);	// unserializing the configuration

if (extension_loaded('imagick')) {

	// Imagick loaded, so turn on image processing
	$TYPO3_CONF_VARS['GFX']['image_processing'] = 1;
	$TYPO3_CONF_VARS['GFX']['im'] = 0;
	$TYPO3_CONF_VARS['GFX']['im_path'] = ''; // Not necesary while using Imagick
	$TYPO3_CONF_VARS['GFX']['im_path_lzw'] = ''; // Not necesary while using Imagick
	$TYPO3_CONF_VARS['GFX']['gdlib'] = 1;
	$TYPO3_CONF_VARS['GFX']['thumbnails'] = 1;
	$TYPO3_CONF_VARS['GFX']['im_v5effects'] = 1;
	$TYPO3_CONF_VARS['GFX']['im_no_effects'] = 0;
	$TYPO3_CONF_VARS['GFX']['gif_compress'] = 0; // Don't use TYPO3 work around. Imagick will compress the images.

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
		
	$TYPO3_CONF_VARS['GFX']['windowing_filter'] = $wF;
	$TYPO3_CONF_VARS['GFX']['imagesDPI'] = $_EXTCONF['imagesDPI'];
}
?>

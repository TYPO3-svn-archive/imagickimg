<?php
if (!defined ("TYPO3_MODE")) die ("Access denied.");

$TYPO3_CONF_VARS['GFX']['image_processing'] = '1';
$TYPO3_CONF_VARS['GFX']['im'] = '0';
$TYPO3_CONF_VARS['GFX']['gdlib'] = '1';
$TYPO3_CONF_VARS['GFX']['thumbnails'] = 1;

$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/class.tslib_gifbuilder.php']=t3lib_extMgm::extPath($_EXTKEY).'class.ux_tslib_gifbuilder.php';
$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_stdgraphic.php']=t3lib_extMgm::extPath($_EXTKEY).'class.ux_t3lib_stdgraphic.php';
$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/thumbs.php']=t3lib_extMgm::extPath($_EXTKEY).'thumbs.php';

$_EXTCONF = unserialize($_EXTCONF);	// unserializing the configuration

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

?>

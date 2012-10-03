<?php

########################################################################
# Extension Manager/Repository config file for ext "imagickimg".
#
# Auto generated 02-10-2012 21:09
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Image Processing via Imagick',
	'description' => 'Resize FE and BE images with Imagick PHP extension. Use all image effects available in standard CE elements like Image or Text with image. Useful on servers where exec() function is disabled.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.1.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Radu Dumbraveanu, Dmitri Paramonov, Tomasz Krawczyk',
	'author_email' => 'vundicind@gmail.com, dimirlan@mail.ru, tomasz@typo3.pl',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-4.6.0'
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:12:{s:51:"class.tx_imagickimg_WarningMessagePostProcessor.php";s:4:"c906";s:25:"class.ux_SC_show_item.php";s:4:"1da6";s:29:"class.ux_t3lib_stdgraphic.php";s:4:"1e9a";s:26:"class.ux_tslib_content.php";s:4:"9c5e";s:29:"class.ux_tslib_gifbuilder.php";s:4:"8f2e";s:21:"ext_conf_template.txt";s:4:"bd75";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"aef7";s:14:"ext_tables.php";s:4:"c1ad";s:10:"thumbs.php";s:4:"60b6";s:22:"lang/locallang_ttc.xlf";s:4:"93b6";s:22:"lang/locallang_ttc.xml";s:4:"f54e";}',
);

?>
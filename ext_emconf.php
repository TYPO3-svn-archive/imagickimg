<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "imagickimg".
 *
 * Auto generated 26-04-2013 08:33
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Image Processing via Imagick',
	'description' => 'Resize FE and BE images with Imagick PHP extension. Use all image effects available in standard CE elements like Image or Text with image. Useful on servers where exec() function is disabled.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.2.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Radu Dumbraveanu, Dmitri Paramonov, Tomasz Krawczyk',
	'author_email' => 'vundicind@gmail.com, dimirlan@mail.ru, tomasz@typo3.pl',
	'author_company' => '',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' => array (
		'depends' => array (
			'typo3' => '4.5.0-6.1.99',
			'php' => '5.3.0-5.4.99',
		),
		'conflicts' => array (
			'jb_gd_resize' => ''
		),
		'suggests' => array (
		),
	),
);

?>
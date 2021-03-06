<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Tomasz Krawczyk <tomasz@typo3.pl>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class ux_ContentObjectRenderer extends \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer {
	
	public function start($data, $table = '') {

		parent::start($data, $table);

		$this->image_effects[31] = '@sepia 80';
		$this->image_effects[32] = '@corners 15';
		$this->image_effects[33] = '@polaroid 5';
	}
	
}
?>
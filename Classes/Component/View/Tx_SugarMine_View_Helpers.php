<?php
/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Class where the view helper methods reside
 *
 * @package	TYPO3
 * @subpackage	Tx_FormhandlerGui
 * @version $Id: Tx_SugarMine_View_Helpers.php 24260 2009-09-10 17:25:16Z metti $
 */
class Tx_SugarMine_View_Helpers {

	/**
	 * Translation view helper
	 * 
	 * @param string $string string to translate
	 * @return string Translated string
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function translate($string) {
		global $LANG;
		return $LANG->getLL($msgid);
	}
}
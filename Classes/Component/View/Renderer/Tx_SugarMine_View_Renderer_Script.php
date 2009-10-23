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
 * The rendering class that extends the view helpers so that they can be 
 * accessed in the ViewScripts
 *
 * @package	TYPO3
 * @subpackage	Tx_FormhandlerGui
 * @version $Id: Tx_SugarMine_View_Renderer_Script.php 24834 2009-09-25 23:02:25Z metti $
 */
class Tx_SugarMine_View_Renderer_Script extends Tx_SugarMine_View_Helpers {
	
	/**
	 * Extracts the vars so that they can be accessed as usual PHP-Variables
	 * in ViewScripts and includes the view script
	 * 
	 * @param string $templateFile
	 * @param array $vars
	 * @return string The rendered content
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function render($templateFile, $vars=array()) {
		extract($vars);
		ob_start();
		include_once($templateFile);
		$templateContent = ob_get_contents();
		ob_end_clean();
		return $templateContent;
	}
}
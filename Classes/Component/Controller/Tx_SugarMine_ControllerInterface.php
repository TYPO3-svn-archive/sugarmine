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
 *
 * $Id: Tx_SugarMine_ControllerInterface.php 24260 2009-09-10 17:25:16Z metti $
 *                                                                        */

/**
 * Controller interface for Controller Classes of Formhandler
 *
 * @package	Tx_FormhandlerGui
 * @subpackage	Controller
 * @version $Id: Tx_SugarMine_ControllerInterface.php 24260 2009-09-10 17:25:16Z metti $
 * @author	Reinhard Führicht <rf@typoheads.at>
 */
interface Tx_SugarMine_ControllerInterface {

	/**
	 * Sets the content object
	 *
	 * @return void
	 */
	public function setContent($content);

	/**
	 * Returns the content object
	 *
	 * @return void
	 */
	public function getContent();

	/**
	 * Process all
	 *
	 * @return void
	 */
	public function process();
}
?>

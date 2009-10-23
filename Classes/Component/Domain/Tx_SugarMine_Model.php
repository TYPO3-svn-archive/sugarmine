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
 * The basic model class. Uses the magic function __call to automatically
 * wire DB-rows or arrays to a model while object factoring or later.
 *
 * @package TYPO3
 * @subpackage FormhandlerGui
 * @version $Id: Tx_SugarMine_Model.php 24840 2009-09-26 17:40:46Z metti $
 */
abstract class Tx_SugarMine_Model {
	
	/**
	 * Magic function to get/set vars in model (Not called when get/set method
	 * exists in model)
	 * 
	 * @param $func
	 * @param $arguments
	 * @return mixed Depending on the called function
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function __call($func, $arguments) {
		$parts = Tx_SugarMine_Func::explodeCamelCase($func, 1);
		
		if ($parts[0] == 'get') {
			$var = Tx_SugarMine_Func::implodeCamelCase(array_slice($parts,1));
			return $this->$var;
		}
		if ($parts[0] == 'set') {
			$var = Tx_SugarMine_Func::implodeCamelCase(array_slice($parts,1));
			$this->$var = $arguments[0];
		}
	}
}
?>
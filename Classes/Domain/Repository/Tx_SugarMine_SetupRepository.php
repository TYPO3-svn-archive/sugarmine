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
 * Contains the setup for sugarmine
 *
 * @package TYPO3
 * @subpackage SugarMine
 * @version $Id: Tx_FormhandlerGui_SetupRepository.php 24852 2009-09-28 02:26:04Z metti $
 */
class Tx_SugarMine_SetupRepository {
	
	/**
	 * @var string
	 */
	private $pathDelimiter = '.';
	
	/**
	 * @var array
	 */
	private $setup = array();
	
	public function __construct() {
		$this->setup = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_sugarmine.'];
		if (!is_array($this->setup)) {
			$this->setup = array();
		}
	}
	
	/**
	 * Adds value to the setup by a path (eg. property1.property2)
	 * 
	 * @param string $path
	 * @param string $value
	 * @return void
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function addValue($path, $value) {
		$array = $this->path2array($path);
		$eval = '$confArray[\''.$array.'\'] = $value;';
		$confArray = array();
		eval($eval);
		$this->setup = t3lib_div::array_merge_recursive_overrule($confArray, $this->setup);
	}
	
	/**
	 * Get a value from the setup by a path (eg. property1.property2)
	 * 
	 * @param string $path
	 * @param mixed $standard The value if requested one is empty
	 * @return Ambigous <$default, mixed>
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getValue($path, $default = null) {
		$array = $this->path2array($path);
		$eval = '$result = $this->setup[\''.$array.'\'];';
		eval($eval);
		return (empty($result)) ? $default : $result;
	}
	
	/**
	 * Parses a path to array string
	 * 
	 * @param string $path
	 * @return string The Array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	private function path2array($path) {
		$path = trim($path,$this->pathDelimiter);
		$pathArray = explode($this->pathDelimiter, $path);
		$path = implode("'.'.']['", $pathArray);
		
		$dot = (is_array($value)) ? '.' : '';
		return $path.$dot;
	}
	
	public function getSetup() {
		return $this->setup;
	}
}
?>
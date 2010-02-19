<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Stein <sebastian.stein@netzelf.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Contains the setup for sugarmine
 *
 * @package TYPO3
 * @subpackage SugarMine
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Domain_Repository_SetupRepository extends Tx_Extbase_Persistence_Repository {

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
	 * 
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
	 * @param boolean $isArray true if array is requested
	 * @param mixed $standard The value if requested one is empty
	 * 
	 * @return Ambigous <$default, mixed>
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getValue($path, $default = null) {
		$isArray = (substr($path, -1) === '.') ? true : null;
		$array = $this->path2array($path, $isArray); 
		$eval = '$result = $this->setup[\''.$array.'\'];'; // join into smth like this: $return=$this->setup['key'.'.']['key'.'.']['value'];
		eval($eval); 
		return (empty($result)) ? $default : $result;
	}
	
	/**
	 * Parses a path to array string
	 * 
	 * @param string $path
	 * @param mixed true if array is requested
	 * 
	 * @return string The Array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	private function path2array($path, $isArray = null) {
		$path = trim($path,$this->pathDelimiter);
		$pathArray = explode($this->pathDelimiter, $path);
		$path = implode("'.'.']['", $pathArray);
		
		//$dot = (is_array($value)) ? '.' : '';
		$dot = ($isArray === true) ? "'.'." : ''; // add one dot at the end of the path-string
		return $path.$dot;
	}
	
	public function getSetup() {
		return $this->setup;
	}
}
?>
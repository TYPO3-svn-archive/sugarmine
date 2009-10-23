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
 * Some methods to be used somewhere
 *
 * @package TYPO3
 * @subpackage FormhandlerGui
 * @version $Id: Tx_SugarMine_Func.php 24840 2009-09-26 17:40:46Z metti $
 */
class Tx_SugarMine_Func {
	
	/**
	 * Explodes a camelCase-String
	 * 
	 * @param string $string The original string
	 * @param integer transform uppercase (2), lowercase (1) or none (0)
	 * @return string The transformed string
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public static function explodeCamelCase($string, $transform = 0) {
	  	$parts = array();
 		$chars = str_split($string);
  		$pos = 0;
 		foreach ($chars as $i => $char) {
	    	if (ctype_upper($char) && $i > 0) {
	      		$pos++;
			}
			switch ($transform) {
				case 2:
					$parts[$pos] .= strtoupper($char);
					break;
				case 1:
					$parts[$pos] .= strtolower($char);
					break;
				default:
					$parts[$pos] .= $char;
			}
			
		}
 		return $parts;
	}
	
	/**
	 * Builds a camelCased string from an array
	 *
	 * @param array $array
	 * @param boolean $ucfirst If true the first letter of the resulting string will be uppercase
	 * @return string The camelCased string
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public static function implodeCamelCase($array, $ucfirst = false) {
		$string = '';
		foreach ($array as $part) {
			$string .= ($ucfirst) ? ucfirst(strtolower($part)) : strtolower($part);
			$ucfirst = true;
		}
		return $string;
	}
	
	/**
	 * Makes this: field_name => fieldName
	 *
	 * @param string $fieldName
	 * @param string $ucfirst If true the first letter of the resulting string will be uppercase
	 * @return string The camelCased string
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public static function tableFieldCamelCase($fieldName, $ucfirst = false) {
		$parts = explode('_',$fieldName);
		return self::implodeCamelCase($parts, $ucfirst);
	}
}
?>
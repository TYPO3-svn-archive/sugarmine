<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Stein <sebastian.stein@netzelf.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * An Case Validator:
 *
 * @scope singleton
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Domain_Validator_CaseValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {
	
	/**
	 * Validate the given post of the case form.
	 *
	 * @param	array	$post	post data array submitted from a fluid template
	 * @return	array	
	 */
	public function isValid($post) {
		
		foreach ($post['tx_sugarmine_sugarmine'] as $name => $value) {
			// pre-validation:
			if($name !== '__hmac' && $name !== '__referrer') {
				
				$field = explode(':',$name);
				
				if($value === null || $value === '') {
					$error = 'this field must be filled out';
				} else {
					switch($field[1]) {
						case 'varchar': {
							$error = (is_string($value)) ? false : $field[0].': The given text was not a valid string.';
						}
					}
				}
				
				if ($error === false) {
				
					 $validFields[]= array(
                        'name'  =>      $field[0],
                        'value' =>      $value  
               		 );
				
				} elseif (is_string($error)) {
					
					// catch and store error and values:
					$Fields['notValid'][$field[0]]['error'] = $error; 
					$Fields['notValid'][$field[0]]['value'] = $value;
					$errorFlag = true; // set error flag
					
				}
			}
		}
		if ($errorFlag === true) {
			return $Fields;
		} else {
			return $validFields;
		}
		
	}
}
?>
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
 * A Validator for posts of one of the project forms of SugarMine.
 *
 * @scope singleton
 * @package TYPO3
 * @subpackage SugarMine
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Domain_Validator_ProjectValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * Validate the given post.
	 *
	 * @param	array	$post post data array submitted from a fluid template
	 * @return	mixed	array if is valid, else FALSE
	 */
	public function isValid($post) {
	
		$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');
		
		foreach($RENDER['projects'] as $index => $record) { // pick appropriate project by unique project-id
						if($record['id'] === $post['tx_sugarmine_sugarmine']['recordId']){
               				$validFields['validFields'][]= array( // also id will be submitted to sugar, because it is only an record update
                        			'name'  =>      'id',
                       				'value' =>      $record['id']  
               				 );
							$recordIndex = $index;
							$project = $record;
						}
        } //var_dump($project);
       
        
		foreach ($post['tx_sugarmine_sugarmine'] as $name => $value) {
			// pre-validation:
			
			$value = trim($value);
			if($name !== '__hmac' && $name !== '__referrer' && $name !== 'recordId' && $value !== null && $value !== '') {
				
				$field = explode(':',$name); 
				
				switch($field[1]) {
					case 'varchar': {
						if ($field[0] === 'email1' || $field[0] === 'email2') {
					
							$valObj = t3lib_div::makeInstance('Tx_Extbase_Validation_Validator_EmailAddressValidator'); // this is useful, because its a quite long pattern!
							$error = ($valObj->isvalid($value) === true) ? false : 'The given subject was not a valid email address.';
				
						} else {
							$error = (is_string($value)) ? false : 'The given text was not a valid string.';
						}
					} break;
					case 'phone': {
						$error = (!preg_match('~[^-\s\d./()+]~', $value)) ? false : 'The given phone-number seems not to be valid.';
					} break;
					case 'datetime': {
						$error = (!preg_match('~[^0-9-: ]~', $value)) ? false : 'The given date should be defined like: "Y-M-D H:M:S".';
					} break;
					case 'encrypt': { // at this point, the value is still DEcrypted
						$error = (is_string($value)) ? false : 'The given text was not a valid string.';
						$passwChange = ($field[0] === $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passwField']) ? true : null;
					} break;
					case 'enum': {
						$error = (is_string($value)) ? false : 'The given option was not a valid string.';
					} break;
					case 'text': {
						$error = (strlen($value) < 600) ? false : 'The given text has more than 600 elements.' ;
					} break;
					case 'id': { 
						$error = (!preg_match('~[^a-z0-9-]~', $value)) ? false : 'Fatal error: contact-id is not valid!'; 
						if (is_string($error)) { //this is a fatal exception error that redirects to logout action!
							var_dump($error);
							$this->redirect('logout', 'Start');
						}
					} break;
					case 'bool': {
						$error = ($value === true || $value === false) ? false : 'The given value is not boolean.';
					} break;
					case 'assigned_user_name': {
						$error = (is_numeric($value)) ? false : 'The given value was not numeric.';
					} break;
					case 'relate': {
						$error = (is_string($value)) ? false : 'The given value was not a valid string.';
					} break;
					case 'name': {
						$error = (is_string($value)) ? false : 'The given value was not a valid string.';
					} break;
					case 'date': {
						$error = (is_string($value)) ? false : 'The given value was not a valid string.';
					} break;
					default: $error = 'ProjectValidator: The field type "'.$field[1].'" is not existent on the database-table "project" of SugarCRM';
				}
				
				if ($error === false) {
				
					$validFields['validFields'][]= array( // this array structure is defined by SugarCRM
                        'name'  =>      $field[0],
                        'value' =>      $value  
               		 );

					$err = null; // delete any existing error from data array
					$val = $value; // cache new VALID value for session

				} elseif (is_string($error)) {
					
					$err = $error; // catch error and store it
					$val = $value; // cache new WRONG value for session
					$errorFlag = true; // set error flag true
					
				}
				//var_dump($RENDER['projects'][$index]);
				foreach($project['fieldRecords'] as $recordName => $records) {
               		
					if($records['fieldset'] === true){
						foreach($records['fields'] as $fieldName => $fieldRecord) {	
							if($fieldName === $field[0]) {
								$fieldsetName = $recordName;
               					$fieldset = true;
               					$RENDER['projects'][$recordIndex]['fieldRecords'][$fieldsetName]['fields'][$field[0]]['value'] = $val;
               					$RENDER['projects'][$recordIndex]['fieldRecords'][$fieldsetName]['fields'][$field[0]]['error'] = $err;
               					
							}
               			}
               		} 
               	}
               	if ($fieldset === null) {
               		$RENDER['projects'][$recordIndex]['fieldRecords'][$field[0]]['value'] = $val;
               		$RENDER['projects'][$recordIndex]['fieldRecords'][$field[0]]['error'] = $err;
               	}
               	$fieldset = null;
               
              
			}
		}
		//var_dump($RENDER['projects']);
		$GLOBALS['TSFE']->fe_user->setKey('ses','cachedData', $RENDER);
		
		return $return = ($errorFlag === true) ? false : $validFields;
	}

}
?>
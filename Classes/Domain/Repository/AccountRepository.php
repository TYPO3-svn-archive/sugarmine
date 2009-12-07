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
 * Contains some business logic for the accountController of sugarmine
 *
 * @package TYPO3
 * @subpackage SugarMine
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Domain_Repository_AccountRepository extends Tx_Extbase_Persistence_Repository {
	
	/**
	 * Merge module data with field configuration from setup.txt.
	 * 
	 * @param	array	$moduleData	associative array
	 * @param	array	$viewField	associative array
	 * @param	array	$editField	associative array
	 * 
	 * @return	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function mergeModuleDataWithFieldConf ($moduleData, $viewField, $editField) {
		
		foreach($viewField as $name => $value) { // viewField and editField definitions are merged with field-values (contact data) from sugarcrm:
			
			if (array_key_exists($name, $moduleData)) {		 
				
				if ($value == 1 && $editField[$name] != 1) {
					$fieldConf[$name] = array('value'=>$moduleData[$name],'edit'=>false);
						
				} elseif ($value == 1 && $editField[$name] == 1) {
					$fieldConf[$name] = array('value'=>$moduleData[$name],'edit'=>true);
				}
			}
		}
		return $fieldConf;
	}
	
	/**
	 * Combines contact data and contact field conf into one array for fluid.
	 * 
	 * @param	array	$contactData['fields']
	 * @param	array	$fieldConf
	 * 
	 * @return	array	combined data array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function prepareForFluid ($contactData, $fieldConf) {
		
		foreach ($contactData as $id => $field) {	// $contactData['fields'] is an array with database-field-information
				
			foreach ($fieldConf as $name => $field2) { // $field2: is an array that contains all values and information about visibility and changeability of $name

				if ($name == 't3_password') { // case: contact authentication was against a typo3-db-password
					$DATA[$name] = array(
										'value'=>$field2['value'],
										'edit'=>false, // it is senseless to edit and submit a t3_password to SugarCRM. //TODO: this should be send to typo: SOLUTION: unique top key name and separate submit button outside the foreach loop
										'field'=>array(
											'name'=>$name,
											'label'=>'T3_Password:',
											'type'=>array(
												'encrypt'=>true,
												'name'=>'encrypt'
											)
										)
									);
					continue; //if condition is true: write special array field and start next foreach loop
				}
				if($name == $field['name']) {
					######################FLUID-PREPARATION######################
					if($field['type'] == 'enum') { 					// prepare for fluids options-attribute of viewHelper: <f:form.select/>
						foreach($field['options'] as $key) {		// options (array)		--->	options (array)
							$temp[$key['name']] = $key['value'];	//		key (array)		--->		name=>'value'
						} 											//			name (array)
						$field['options'] = $temp;					//				value (string)
					} 		
					
					$field['textbox'] = ($field['type'] === 'varchar' || $field['type'] === 'phone' || $field['type'] === 'bool' || $field['type'] === 'datetime' || $field['type'] === 'relate' || $field['type'] === 'assigned_user_name'  || $field['type'] === 'name') ? true: null;
					
					$field['type'] = array(  
										$field['type']=>true, // fluid isn't able to compare smth with pure strings, but array-values ;)
										'name'=>$field['type']
									); 
					#####################/FLUID-PREPARATION#######################
					$DATA[$name] = array(
										'value'=>$field2['value'], 
										'edit'=>$field2['edit'], 
										'field'=>$field,
										'error'=>null
									);
				}
			}
		}
		$DATA['id']['style'] = array('hidden'=>'hidden'); 
		
		return $DATA;
		/*$DATA =	name	(array) 
								value	(string)
								edit	(boolean)
								field	(array) //TODO: reducing of redundancy: existing field-definitions should be top keys
									name	(string)
									type	(string) 
									label	(string)
									required(int)
									options	(array)
										name=>'value'
										...  		*/
	}
	
	/**
	 * Combines case values and case labels into one array for fluid.
	 * 
	 * @param	array	$caseFields
	 * @param	array	$cases
	 * @return	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function prepareCasesForFluid($caseFields, $cases){
		
		foreach ($caseFields as $field) {
		$count = 0;
			foreach ($cases as $case) {
				
				if(array_key_exists($field['name'], $case)) {
				
				$array[$count][$field['name']] = array(
													'label'=>$field['label'],
													'value'=>$case[$field['name']]
												);
				}
				 $count++;
			}
		}
		return $array; /*$array =	0   (array)
											field-name	(array)
													label	(string)
													value	(string)
											...
									1	(array)
									...							*/
		
	}
	
}


?>
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
	 * @param	array	$alterField	associative array
	 * 
	 * @return	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function mergeModuleDataWithFieldConfNew ($moduleData, $viewFieldConf, $alterFieldConf) {
		
		$viewFields = explode(',', $viewFieldConf['fields']);

		$alterFields = explode(',', $alterFieldConf['fields']);

		if(!empty($viewFieldConf['fieldSets.'])) {
			$viewFields[] = array('fieldset'=>$viewFieldConf['fieldSets.']);
		}
		if(!empty($alterFieldConf['fieldSets.'])) {
			$alterFields[] = array('fieldset'=>$alterFieldConf['fieldSets.']);
		}
		

		foreach($viewFields as $id => $name)  {
			$name = trim($name);
			if(!is_array($viewFields[$id]['fieldset'])) { // case: ordinary field found
				if (array_key_exists($name, $moduleData)) {
					$fieldConf[$name] = array('value'=>$moduleData[$name],'alter'=>false);
					
				}
			} else { // case: fieldset found
				foreach($viewFields[$id]['fieldset'] as $fieldset => $fields) { // fetch fieldsets
					$fieldset = trim($fieldset,'.');
					$viewFieldsetFields = explode(',', $fields['fields']); // explode field names
					foreach ($viewFieldsetFields as $id2 => $name2) { // fetch fieldnames
						$name2 = trim($name2);
						if (array_key_exists($name2, $moduleData)) { // compare fieldnames with module data from SugarCRM and combine data
							$fieldConf[$fieldset]['fields'][$name2] = array('value'=>$moduleData[$name2],'alter'=>false);
							$fieldConf[$fieldset]['legend'] = $fields['legend'];
							$fieldConf[$fieldset]['fieldset'] = true;
						}
					}
				}
			}
		}
		
		// alterable fields are master fields (if field is alterable, no viewable field is necessary)
		foreach($alterFields as $id => $name)  { // to the same checks with alterable fields and overwrite, if necessary
			$name = trim($name);
			if(!is_array($alterFields[$id]['fieldset'])) {
				if (array_key_exists($name, $moduleData)) {
					$fieldConf[$name] = array('value'=>$moduleData[$name],'alter'=>true);
				}
			} else {
				foreach($alterFields[$id]['fieldset'] as $fieldset => $fields) {
					$fieldset = trim($fieldset,'.');
					$alterFieldsetFields = explode(',', $fields['fields']);
					foreach ($alterFieldsetFields as $id2 => $name2) {
						$name2 = trim($name2);
						if (array_key_exists($name2, $moduleData)) {
							$fieldConf[$fieldset]['fields'][$name2] = array('value'=>$moduleData[$name2],'alter'=>true);
							$fieldConf[$fieldset]['legend'] = $fields['legend'];
							$fieldConf[$fieldset]['fieldset'] = true;
						}
					}
				}
			}
		}
		return $fieldConf;
	}
	
	/**
	 * Combines contact data and contact field conf into one array for fluid.
	 * 
	 * @param	array	$fieldTypes	from SugarCRM
	 * @param	array	$fieldConf	from setup.txt with values from SugarCRM
	 * @param	string	$recordId	unique id of given module record
	 * 
	 * @return	array	combined data array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function prepareForFluidNew ($fieldTypes, $fieldConf, $recordId) {
		
		foreach ($fieldTypes as $id => $field) {	// $contactData['fields'] is an array with database-field-information

			foreach ($fieldConf as $name => $field2) { // $field2: is an array that contains all values and information about visibility and changeability of $name

				if($field2['fieldset'] !== true) { // CASE: normal field
					
					if ($name == 't3_password') { // case: contact authentication was against a typo3-db-password
						$DATA['fieldRecords'][$name] = array(
													'value'=>$field2['value'],
													'alter'=>false, // it is senseless to edit and submit a t3_password to SugarCRM. //TODO: this should be send to typo: SOLUTION: unique top key name and separate submit button outside the foreach loop
													'field'=>array(
														'name'=>$name,
														'label'=>'T3_Password:',
														'type'=>array(
															'encrypt'=>true,
															'name'=>'encrypt'
																),
														'textBox'=>$textBox	
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
								//$DATA['fieldTypes'][$field['name']] = $field; 		
							
							
							$fieldType = array(  
												$field['type']=>true, // fluid isn't able to compare smth with pure strings, but array-values ;)
												'typeName'=>$field['type']
											); 
							
							$textBox = ($field['type'] === 'varchar' || $field['type'] === 'phone' || $field['type'] === 'bool' || $field['type'] === 'datetime' || $field['type'] === 'relate' || $field['type'] === 'assigned_user_name'  || $field['type'] === 'name' || $field['type'] === 'date') ? true: null;
						#####################/FLUID-PREPARATION#######################

						$DATA['fieldRecords'][$name] = array(
											'value'=>$field2['value'], 
											'recordName'=>$field['name'],
											'alter'=>$field2['alter'], 
											'field'=>	array(
															'type'=>$fieldType,
															'textBox'=>$textBox,
															'label'=>$field['label'],
															'options'=>$temp
														),
											'error'=>null
										);
					} 
				} else { // CASE: fieldset
					
					foreach ($field2['fields'] as $fieldsetField => $fieldsetName) {
						if($fieldsetField === $field['name']) {
							
							######################FLUID-PREPARATION######################
							
								if($field['type'] == 'enum') { 					// prepare for fluids options-attribute of viewHelper: <f:form.select/>
									foreach($field['options'] as $key) {		// options (array)		--->	options (array)
										$temp[$key['name']] = $key['value'];	//		key (array)		--->		name=>'value'
									} 											//			name (array)
									//$field['options'] = $temp;					//				value (string)
								}
								//$DATA['fieldTypes'][$field['name']] = $field; 		
							
							
							$fieldType = array(  
												$field['type']=>true, // fluid isn't able to compare smth with pure strings, but array-values ;)
												'typeName'=>$field['type']
											); 
							
							$textBox = ($field['type'] === 'varchar' || $field['type'] === 'phone' || $field['type'] === 'bool' || $field['type'] === 'datetime' || $field['type'] === 'relate' || $field['type'] === 'assigned_user_name'  || $field['type'] === 'name' || $field['type'] === 'date') ? true: null;
							#####################/FLUID-PREPARATION#######################
							$DATA['fieldRecords'][$field2['legend']]['fieldset'] = true; //FIXME: legend should be VERY unique
							$DATA['fieldRecords'][$field2['legend']]['legend'] = $field2['legend'];
							$DATA['fieldRecords'][$field2['legend']]['fields'][$field['name']] = array(
																									'value'=>$fieldsetName['value'], 
																									'recordName'=>$field['name'],
																									'alter'=>$fieldsetName['alter'], 
																									'field'=>	array(
																													'type'=>$fieldType,
																													'textBox'=>$textBox,
																													'label'=>$field['label'],
																													'options'=>$temp
																											),
																									'error'=>null
																								);
						}		
					}
				}	
			}
		}
		$DATA['id'] = $recordId;
		//var_dump($DATA);
		return $DATA;
	}
}


?>
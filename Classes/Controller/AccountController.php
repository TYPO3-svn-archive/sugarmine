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
 * The AccountController handles Support-Center-Features.
 *
 */
class Tx_SugarMine_Controller_AccountController extends Tx_Extbase_MVC_Controller_ActionController {
	
	/**
	 * SugarMines Repository for NuSOAP-WebServices of SugarCRM.
	 * 
	 * @var Tx_SugarMine_Domain_Repository_SugarsoapRepository
	 */
	protected $sugarsoapRepository;

	/**
	 * Initializes the current action.
	 *
	 * @return void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function initializeAction() {
		
		$this->sugarsoapRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SugarsoapRepository');
	}
	
	/**
	 * Index Action of AccountController.
	 *
	 * @return void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function indexAction() {
		
		$this->forward('test');
	}
	
	/**
	 * Collects and synchronizes data into an all-in-one-array for FLUID to display the user-profile form:
	 * 
	 * @return void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function formAction() {
		
		var_dump('hello protected account action');
		$contactData = $GLOBALS['TSFE']->fe_user->getKey('ses','authorizedUser'); // get $contactData from authorized session
		
		// if authSystem is 'typo3', the appropriate password is already injected into $contactData:
		if ($contactData['authSystem'] == 'sugar') {
			// decode encrypted password and inject it into $contactData:
			$contactData['data'][$this->sugarsoapRepository->passwField] = $this->sugarsoapRepository->blowfishDecode($contactData['data'][$this->sugarsoapRepository->passwField]);
		}

		// viewField and editField definitions are merged with field-values from sugarcrm:
		foreach($this->sugarsoapRepository->viewField as $name => $value) {
			if (array_key_exists($name, $contactData['data'])) {		 
				if ($value == 1 && $this->sugarsoapRepository->editField[$name] != 1) {
					$fieldConf[$name] = array('value'=>$contactData['data'][$name],'edit'=>false);
						
				} elseif ($value == 1 && $this->sugarsoapRepository->editField[$name] == 1) {
					$fieldConf[$name] = array('value'=>$contactData['data'][$name],'edit'=>true);
				}
			}
		}
		
		//$fieldConf = $this->sugarsoapRepository->fieldConf;
		//$contactData['data'] = array_intersect_key($contactData['data'],$fieldConf);

		foreach ($contactData['fields'] as $id => $field) {	// $contactData['fields'] is an array with database-field-information
				
			foreach ($fieldConf as $name => $field2) { // $field2: is an array that contains all values and information about visibility and changeability of $name

				if ($name == 't3_password') { // case: contact authentication via typo3-db-password
					$DATA[$name] = array(
										'value'=>$field2['value'],
										'edit'=>$field2['edit'], 
										'field'=>array(
											'name'=>$name,
											'label'=>'T3_Password:',
											'type'=>array(
												'encrypt'=>'encrypt'
											)
										)
									);
					//TODO: if condition is true: start next loop
				}
				if($name == $field['name']) {
					######################FLUID######################
					if($field['type'] == 'enum') { 					// prepare for fluids viewHelper: options-attribute of <f:form.select/>
						foreach($field['options'] as $key) {		// options (array)		--->	options (array)
							$temp[$key['name']] = $key['value'];	//		key (array)		--->		name=>'value'
						} 											//			name (array)
						$field['options'] = $temp;					//				value (string)
					} 		
					// fluid isn't able to compare smth with pure strings, but array-values ;)
					$field['type'] = array(  
										$field['type']=>$field['type']
									); 
					#####################/FLUID#######################
					$DATA[$name] = array(
										'value'=>$field2['value'], 
										'edit'=>$field2['edit'], 
										'field'=>$field,
									);
				/*$DATA =	name	(array)
								value	(string)
								edit	(boolean)
								field	(array)
									name	(string)
									type	(string) 
									label	(string)
									required(int)
									options	(array)
										name=>'value'
										...  		*/
				}
			}
		} 
		//TODO: reducing of redundancy: existing field-definitions have to be a top key
		
		$DATA['id']['style'] = array('hidden'=>'hidden');
		//var_dump($DATA);
		
		if(is_array($DATA)) {
			
			$this->view->assign('contact', $DATA);
		} else {
			
			var_dump('ERROR: No valid field configuration available');
		}

	}
	
	/**
	 * Handles validation of posts and passes the approved data to a setEntry() method,
	 * which finally calls the apprppriate SugarCRM-WebService.
	 *
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function saveAction() {
		
		var_dump('hello protected form action');
		$post = t3lib_div::_POST();
		
		foreach ($post['tx_sugarmine_sugarmine'] as $name => $value) {
			// pre-validation:
			if($name !== '__hmac' && $name !== '__referrer' && $value !== null && $value !== '') {
				$field = explode(':',$name); 
				// raw field-type validation
				switch($field[1]) {
					case 'varchar': {
						$validValues[$field[0]] = (is_string($value)) ? $value : null;
					} break;
					default: { // test case
						$validValues[$field[0]] = $value;
					}	
				}
				// precise validation:
				if ($field[0] === 'email1' || $field[0] === 'email2') {
						$valObj = t3lib_div::makeInstance('Tx_Extbase_Validation_Validator_EmailAddressValidator');
						$validValues[$field[0]] = ($valObj->isvalid($value) === true) ? $value : null;
				}
			} if ($validValues[$field[0]] === null) {
				unset($validValues[$field[0]]);
			}
		}
		
		var_dump($validValues);
	}
	
	/**
	 * testAction: Only for test-purposes!
	 *
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function testAction() {
		
		var_dump('hello test action');
	}
}


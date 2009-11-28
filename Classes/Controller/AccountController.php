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
		
		 $this->forward('collect'); // collect data from session and setup.txt into one array
	}
	
	/**
	 * Collects and synchronizes data into an all-in-one-array for FLUID to display the user-profile form:
	 * 
	 * @return void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function collectAction() {
		
		var_dump('Controller: Account; Action: form');
		$contactData = $GLOBALS['TSFE']->fe_user->getKey('ses','authorizedUser'); // get $contactData from authorized session
		//var_dump($contactData);
		if ($contactData['authSystem'] == 'sugar') { // if authSystem is 'typo3', the appropriate password is already injected into $contactData:

			$contactData['data'][$this->sugarsoapRepository->passwField] = $this->sugarsoapRepository->blowfishDecode($contactData['data'][$this->sugarsoapRepository->passwField]); // decode encrypted password and inject it into $contactData:
			
		} 
		
		foreach($this->sugarsoapRepository->viewField as $name => $value) { // viewField and editField definitions are merged with field-values (contact data) from sugarcrm:
			
			if (array_key_exists($name, $contactData['data'])) {		 
				
				if ($value == 1 && $this->sugarsoapRepository->editField[$name] != 1) {
					$fieldConf[$name] = array('value'=>$contactData['data'][$name],'edit'=>false);
						
				} elseif ($value == 1 && $this->sugarsoapRepository->editField[$name] == 1) {
					$fieldConf[$name] = array('value'=>$contactData['data'][$name],'edit'=>true);
				}
			}
		}

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
					######################FLUID-PREPARATION######################
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
					#####################/FLUID-PREPARATION#######################
					$DATA[$name] = array(
										'value'=>$field2['value'], 
										'edit'=>$field2['edit'], 
										'field'=>$field,
									);
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
			}
		} 
		
		$DATA['id']['style'] = array('hidden'=>'hidden'); 
		//var_dump($DATA);
		
		if(is_array($DATA)) {
			
			$GLOBALS['TSFE']->fe_user->setKey('ses','collectedData', $DATA);
			$this->forward('form');
			
		} else {
			var_dump('ERROR: No valid field configuration available');
			
		}
	}
	
	protected function formAction() {
		
		$DATA = $GLOBALS['TSFE']->fe_user->getKey('ses','collectedData');
		$this->view->assign('contact', $DATA);
		
	}
	
	/**
	 * Handles validation of posts (with error-reporting) and passes the approved data to a setEntry() method,
	 * which finally calls the appropriate SugarCRM-WebService to set new record-values.
	 *
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function saveAction() {
		
		var_dump('Controller: Account; Action: save');
		$DATA = $GLOBALS['TSFE']->fe_user->getKey('ses','collectedData');
		$post = t3lib_div::_POST();
		
		foreach ($post['tx_sugarmine_sugarmine'] as $name => $value) { // TODO: maybe its better to move this procedures to a validatorRepository
			// pre-validation:
			if($name !== '__hmac' && $name !== '__referrer' && $value !== null && $value !== '') {
				
				$field = explode(':',$name); 
				$field[1] = ($name === 'id') ? 'id' : $field[1]; // hidden id field comes without type definition: create one
				
				switch($field[1]) {
					case 'varchar': {
						if ($field[0] === 'email1' || $field[0] === 'email2') {
					
							$valObj = t3lib_div::makeInstance('Tx_Extbase_Validation_Validator_EmailAddressValidator'); // this is useful, because its a quite long pattern!
							$error = ($valObj->isvalid($value) === true) ? false : 'The given subject was not a valid email address.';
				
						} else {
							$error = (is_string($value)) ? false : 'The given text was not a valid string.';
						}
					} break;
					case 'encrypt': { // at this point, the value is still DEcrypted
						$error = (is_string($value)) ? false : 'The given text was not a valid string.';
						$passwChange = ($field[0] === $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passwField']) ? true : null;
					} break;
					case 'enum': {
						$error = (is_string($value)) ? false : 'The given option was not a valid string.';
					} break;
					case 'phone': {
						$error = (!preg_match('~[^-\s\d./()+]~', $value)) ? false : 'The given phone-number seems not to be a valid one';
					} break;
					case 'id': { 
						$error = (!preg_match('~[^a-z0-9-]~', $value)) ? false : 'Fatal error: contact-id is not valid!'; 
						if (is_string($error)) { //this is a fatal exception error that redirects to logout action!
							var_dump($error);
							$this->redirect('logout', 'Start');
						}
					} break;
					default: $error = 'field type is currently unknown';
				}
				
				if ($error === false) {
				
					 $validFields[]= array(
                        'name'  =>      $field[0],
                        'value' =>      $value  
               		 );
					unset($DATA[$field[0]]['error']); // delete existing error from data array
				
				} elseif (is_string($error)) {
				
					$DATA[$field[0]]['error'] = $error; // catch and store error
					$errorFlag = true; // set error flag
					
				}
			}
		}

		if ($errorFlag === true) {
			
			unset($validFields);
			$GLOBALS['TSFE']->fe_user->setKey('ses','collectedData', $DATA);
			$this->redirect('form'); //TODO: write an own template with error-report
			
		} elseif ($validFields[1] !== null) { // successful validation of more fields than just id as fixed value:
			
			// inject unchanged decrypted password into setEntry value list (it is absolutely necessary to submit an existing custom password as PLAIN-TEXT to SugarCRM)
			// i donno why, but if you dont submit any custom password, it will get lost (shown as weird encryption on sugars frontend) and if you submit it ENcrypted, sugarcrm will ENcrypt it again and therefore destroy it. *NARF*
			// this is why it has currently be send DEcrypted as plain text password :S
			if($passwChange !== true && $GLOBALS['TSFE']->fe_user->getKey('ses','authSystemWas') === 'Sugar') { 
				$validFields[]= array(
                        'name'  =>      $this->sugarsoapRepository->passwField,
                        'value' =>      $DATA[$this->sugarsoapRepository->passwField]['value']  
               		 );
			}
			
			$GLOBALS['TSFE']->fe_user->setKey('ses','collectedData', $DATA);
			$this->sugarsoapRepository->setLogin();
			$result = $this->sugarsoapRepository->setEntry('Contacts', $validFields);
			//var_dump($validFields);
			
			if ($result['error']['number'] === '0') { // case: no error reported from SugarCRM
				$this->redirect('refresh', 'Start'); // lets have a look at your fresh changes from SugarCRM
			} else {
				var_dump('sry, there was an unknown problem with SugarCRM');
				var_dump($result); // shows reported results (including errors)
				$this->redirect('form');
			}		
		}
	}
	
	/**
	 * TestAction: Only for test-purposes!
	 *
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function testAction() {
		
		var_dump('hello test action');
	}
}


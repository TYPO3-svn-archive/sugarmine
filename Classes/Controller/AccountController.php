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
	 * SugarMines User-Profile Validator for submitted data from the frontend.
	 * 
	 * @var Tx_SugarMine_Domain_Validator_ProfileValidator
	 */
	protected $profileValidator;
	
	/**
	 * SugarMines Case Validator for submitted data from the frontend.
	 * 
	 * @var Tx_SugarMine_Domain_Validator_CaseValidator
	 */
	protected $caseValidator;

	/**
	 * Initializes the current action.
	 *
	 * @return void
	 */
	protected function initializeAction() {
		
		$this->sugarsoapRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SugarsoapRepository');
		$this->profileValidator = t3lib_div::makeInstance('Tx_SugarMine_Domain_Validator_ProfileValidator');
		$this->caseValidator = t3lib_div::makeInstance('Tx_SugarMine_Domain_Validator_CaseValidator');
	}

	/**
	 * Collects and synchronizes data into an all-in-one-array for FLUID to display the user-profile form:
	 * 
	 * @return void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function indexAction() { //TODO: MAKE THIS AS INDEX ACTION
		
		var_dump('Controller: Account; Action: form');
		//TODO: array collecting should be done into setupRepository
		$contactData = $GLOBALS['TSFE']->fe_user->getKey('ses','authorizedUser'); // get $contactData from authorized session
		//var_dump($contactData);
		if ($contactData['authSystem'] == 'sugar') { // if authSystem is 'typo3', the appropriate password is already injected into $contactData:

			$contactData['data'][$this->sugarsoapRepository->passwField] = $this->sugarsoapRepository->blowfishDecode($contactData['data'][$this->sugarsoapRepository->passwField]); // decode encrypted password and inject it into $contactData:
			
		} 
		
		unset($contactData['data']['account_id']); // account_id is still stored into session
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
										'edit'=>false, // it is senseless to edit and submit a t3_password to SugarCRM. //TODO: this should be send to typo ;)
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
										'error'=>null
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
		
		if ($DATA['t3_password'] !== null) { // if the authentication runs against typo: a configured sugar password is senseless
			unset($DATA[$this->sugarsoapRepository->passwField]);
		}
		
		$DATA['id']['style'] = array('hidden'=>'hidden'); 
		//var_dump($DATA);
		
		if(is_array($DATA)) {
			
			$GLOBALS['TSFE']->fe_user->setKey('ses','collectedData', $DATA);
			$this->forward('profile');
			
		} else {
			var_dump('ERROR: No valid field configuration available');
			
		}
	}
	
	/**
	 * - Renders formatted contact-profile-form of an authorized SugarMine user
	 * - Handles validation of posts from the user profile (with error-reporting) and passes the approved data to a setEntry() method,
	 *   which finally calls the appropriate SugarCRM-WebService to update the records.
	 * 
	 * @return	string	the renderd view
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function profileAction() {
		
		$DATA = $GLOBALS['TSFE']->fe_user->getKey('ses','collectedData');
		//var_dump($DATA);
		$this->view->assign('contact', $DATA);
		
		$post = t3lib_div::_POST();
		
		if (isset($post)) {
			
			$validFields = $this->profileValidator->isValid($post); // is array if is valid, else FALSE!
		
			$DATA = $GLOBALS['TSFE']->fe_user->getKey('ses','collectedData');
		
			if ($validFields === false) {
			
				unset($validFields);
				//$GLOBALS['TSFE']->fe_user->setKey('ses','collectedData', $DATA);
				$this->view->assign('contact', $DATA); //TODO: write an own template with error-report for current action
			
			} elseif ($validFields['validFields'][1] !== null) { // successful validation of more fields than just id as fixed value:
			
				// inject unchanged decrypted password into setEntry value list (it is absolutely necessary to submit an existing custom password as PLAIN-TEXT to SugarCRM)
				// i donno why, but if you dont submit any custom password, it will get lost (shown as weird encryption on sugars frontend) and if you submit it ENcrypted, sugarcrm will ENcrypt it again and therefore destroy it. *NARF*
				// this is why it has currently be send DEcrypted as plain text password :S
				
				if($validFields['passwChange'] !== true && $GLOBALS['TSFE']->fe_user->getKey('ses','authSystemWas') === 'Sugar') { 
					
					$validFields['validFields'][]= array(
                        	'name'  =>      $this->sugarsoapRepository->passwField,
                        	'value' =>      $DATA[$this->sugarsoapRepository->passwField]['value']  
               			 );
				}
			
				//$GLOBALS['TSFE']->fe_user->setKey('ses','collectedData', $DATA);
				$this->sugarsoapRepository->setLogin();
				$result = $this->sugarsoapRepository->setEntry('Contacts', $validFields['validFields']);
				//var_dump($validFields);
				
				if ($result['error']['number'] === '0') { // case: no error reported from SugarCRM
					$this->view->assign('contact', $DATA); // lets have a look at your fresh changes from SugarCRM
					//TODO: it is faster, to report cached data, instead of refreshing from SugarCRM!! 
				} else {
					var_dump('sry, there was an unknown problem with SugarCRM');
					var_dump($result); // shows reported results (including errors)
					$this->view->assign('contact', $DATA);
				}		
			}
		}
	}
	
	/**
	 * Shows case-list and newCase-form for an authorized SugarMine user.
	 * 
	 * @return	string	the renderd view
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function casesAction() {
		
		$this->sugarsoapRepository->setLogin();
		$IDs = $GLOBALS['TSFE']->fe_user->getKey('ses', 'IDs');
		$caseFields = $this->sugarsoapRepository->getCases($IDs['accountId']);
		//var_dump($cases['entry_list'][0]);
		$cases['get'] = $caseFields['entry_list'];
		$this->view->assign('case', $cases);
		
		$post = t3lib_div::_POST();
		$result = $this->caseValidator->isValid($post);
		if (array_key_exists('notValid',$result)) {
			$cases['notValid'] = $result['notValid'];
			//var_dump($cases);
			$this->view->assign('case', $cases);
		} elseif (is_array($result)) {
			$keyCount = count($result); // add account_id to submitted array (necessary for SugarCRM)
			$result[$keyCount] = array(
									'name' => 'account_id',
									'value' => $IDs['accountId']
									);
			$this->sugarsoapRepository->setLogin();
			$response = $this->sugarsoapRepository->setEntry('Cases', $result);
			unset($result);
			unset($post);
			$caseFields = $this->sugarsoapRepository->getCases($IDs['accountId']);
			$cases['get'] = $caseFields['entry_list'];
			$this->view->assign('case', $cases);
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


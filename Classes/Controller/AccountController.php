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
 * @package TYPO3
 * @subpackage SugarMine
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Controller_AccountController extends Tx_Extbase_MVC_Controller_ActionController {
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Repository_SugarsoapRepository	SugarMines Repository for NuSOAP-WebServices of SugarCRM.
	 */
	protected $sugarsoapRepository;
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Repository_AccountRepository  SugarMines Repository for AccountController business logic.
	 */
	protected $accountRepository;
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Validator_ProfileValidator	SugarMines User-Profile Validator for submitted data from the profile form.
	 */
	protected $profileValidator;
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Validator_CaseValidator	SugarMines Case Validator for submitted data from cases form.
	 */
	protected $caseValidator;
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Validator_CompanyValidator	SugarMines Company (SugarCRM: "account") Validator for submitted data from company form.
	 */
	protected $companyValidator;

	/**
	 * Initializes the current action.
	 *
	 * @return	void
	 */
	protected function initializeAction() {
		
		$this->sugarsoapRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SugarsoapRepository');
		$this->accountRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_AccountRepository');
		$this->profileValidator = t3lib_div::makeInstance('Tx_SugarMine_Domain_Validator_ProfileValidator');
		$this->companyValidator = t3lib_div::makeInstance('Tx_SugarMine_Domain_Validator_CompanyValidator');
		$this->caseValidator = t3lib_div::makeInstance('Tx_SugarMine_Domain_Validator_CaseValidator');
	}

	/**
	 * Combines authorized contact data into an render-ready array for FLUID to display the user-profile form:
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function indexAction() {
		
		var_dump('Controller: Account; Action: form');
		
		$contactData = $GLOBALS['TSFE']->fe_user->getKey('ses','authorizedUser'); // get user Data from authorized session
		
		// if auth was against sugar: decode encrypted password and inject it back into $contactData:
		$contactData['contact']['data'][$this->sugarsoapRepository->passwField] = ($contactData['authSystem'] == 'sugar') ? $this->sugarsoapRepository->blowfishDecode($contactData['contact']['data'][$this->sugarsoapRepository->passwField]) : $contactData['Contact']['data'][$this->sugarsoapRepository->passwField];
		
		unset($contactData['contact']['data']['account_id']); // account_id is still stored into session
		
		$fieldConf = $this->accountRepository->mergeModuleDataWithFieldConf($contactData['contact']['data'], $this->sugarsoapRepository->contactFields['view'], $this->sugarsoapRepository->contactFields['edit']);
		
		$RENDER['profile'] = $this->accountRepository->prepareForFluid($contactData['contact']['fields'], $fieldConf);
		
		if(is_array($RENDER['profile'])) {
			
			if ($PROFILE_DATA['t3_password'] !== null) { // if the authentication rans against typo: a configured sugar passwordField is senseless
				unset($PROFILE_DATA[$this->sugarsoapRepository->passwField]);
			}
			$GLOBALS['TSFE']->fe_user->setKey('ses','cachedData', $RENDER);
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
	 * @return	string	the rendered view
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function profileAction() {
		
		$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');

		$this->view->assign('contact', $RENDER['profile']);
		
		$post = t3lib_div::_POST();

		if (!empty($post)) {
			
			$validFields = $this->profileValidator->isValid($post); // is array if is valid, else FALSE!
		
			$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');
		
			if ($validFields === false) {
			
				unset($validFields);
				$this->view->assign('contact', $RENDER['profile']);
			
			} elseif ($validFields['validFields'][1] !== null) { // successful validation of more fields than just id as fixed value:
			
				// inject unchanged decrypted password into setEntry value list (it is absolutely necessary to submit an existing custom password as PLAIN-TEXT to SugarCRM)
				// FIXME: i donno why, but if you dont submit any custom password, it will get lost (shown as weird encryption on sugars frontend) and if you submit it ENcrypted, sugarcrm will ENcrypt it again and therefore destroy it. *NARF*
				// this is why it has currently be send DEcrypted as plain text password :S
				
				if($validFields['passwChange'] !== true && $GLOBALS['TSFE']->fe_user->getKey('ses','authSystemWas') === 'Sugar') { 
					
					$validFields['validFields'][]= array(
                        	'name'  =>      $this->sugarsoapRepository->passwField,
                        	'value' =>      $PROFILE_DATA[$this->sugarsoapRepository->passwField]['value']  
               			 );
				}
			
				$this->sugarsoapRepository->setLogin();
				$result = $this->sugarsoapRepository->setEntry('Contacts', $validFields['validFields']);
				
				if ($result['error']['number'] === '0') { // case: no error reported from SugarCRM
					$this->view->assign('contact', $RENDER['profile']); // lets have a look at your changes (still cached)
				} else {
					var_dump('sry, there was an unknown problem with SugarCRM');
					var_dump($result); // shows reported results (including errors)
					$this->view->assign('contact', $RENDER['profile']);
				}		
			}
		}
	}
	
	/**
	 * Shows a case list and an add-new-case form for an authorized SugarMine user.
	 * 
	 * @return	string	the rendered view
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function casesAction() { //TODO: introduce caching also for cases
		
		$this->sugarsoapRepository->setLogin();
		$IDs = $GLOBALS['TSFE']->fe_user->getKey('ses', 'IDs');
	
		$post = t3lib_div::_POST();
		if(!empty($post)) {
			
			$result = $this->caseValidator->isValid($post);
		
			if (array_key_exists('notValid',$result)) {
			
				$cases['notValid'] = $result['notValid'];
				//var_dump($cases);
				$this->view->assign('case', $cases); // show template with new values and errors
		
			} elseif (is_array($result)) {
			
				$keyCount = count($result); // add account_id to submitted array (necessary for SugarCRM)
				$result[$keyCount] = array(
										'name' => 'account_id',
										'value' => $IDs['accountId']
										);
				$response = $this->sugarsoapRepository->setEntry('Cases', $result); // try to store submitted data on SugarCRM

				unset($result);
				unset($post);
			}
		}
		$caseFields = $this->sugarsoapRepository->getCases($IDs['accountId']); // refresh cases-list
		$cases['get'] = $caseFields['entry_list'];
		$this->view->assign('case', $cases);
	}
	
	/**
	 * - Renders formatted company-form of an authorized SugarMine user
	 * - validates submitted post of the company-form and passes the approved data to a setEntry method to store it on SugarCRM.
	 * 
	 * @return	string	the rendered view
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function companyAction() {
			
		$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');
		$post = t3lib_div::_POST();

		if (!empty($post)) {

			$validFields = $this->companyValidator->isValid($post); // is array if is valid, else FALSE!
		
			$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');
		
			if ($validFields === false) {
			
				unset($validFields);
				$this->view->assign('company', $RENDER['company']);
			
			} elseif ($validFields['validFields'][1] !== null) { // successful validation of more fields than just id as fixed value:
				
				//var_dump($validFields);
				$this->sugarsoapRepository->setLogin();
				$result = $this->sugarsoapRepository->setEntry('Accounts', $validFields['validFields']);
				
				if ($result['error']['number'] === '0') { // case: no error reported from SugarCRM
					$this->view->assign('company', $RENDER['company']); // lets have a look at your changes (still cached)
				} else {
					var_dump('sry, there was an unknown problem with SugarCRM');
					var_dump($result); // shows reported results (including errors)
					$this->view->assign('company', $RENDER['company']);
				}	
				
			}
		} elseif (is_array($RENDER['company']) && empty($post)) {
			
			$this->view->assign('company', $RENDER['company']);
			
		} else { //TODO: this should be a process for a refresh action
		
			$IDs = $GLOBALS['TSFE']->fe_user->getKey('ses', 'IDs');
			$this->sugarsoapRepository->setLogin();

			$companyData = $this->sugarsoapRepository->getModuleDataById($IDs['accountId'],'Accounts'); //TODO: is it not valid to have more than 1 company as contact!?

			$fieldConf = $this->accountRepository->mergeModuleDataWithFieldConf($companyData['entry_list'][0], $this->sugarsoapRepository->companyFields['view'], $this->sugarsoapRepository->companyFields['edit']);
			$RENDER['company'] = $this->accountRepository->prepareForFluid($companyData['field_list'], $fieldConf);

			if(is_array($RENDER['company'])) {
			
				$GLOBALS['TSFE']->fe_user->setKey('ses','cachedData', $RENDER);
				$this->view->assign('company', $RENDER['company']);
			}
		}
	}
	
	/**
	 * Only for test-purposes!
	 *
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function testAction() {
		
		var_dump('hello test action');
	}
}


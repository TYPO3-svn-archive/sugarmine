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
	private $sugarsoapRepository;
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Repository_AccountRepository  SugarMines Repository for AccountController business logic.
	 */
	private $accountRepository;
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Validator_ProfileValidator	SugarMines User-Profile Validator for submitted data from the profile form.
	 */
	private $profileValidator;
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Validator_CaseValidator	SugarMines Case Validator for submitted data from cases form.
	 */
	private $caseValidator;
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Validator_CompanyValidator	SugarMines Company (SugarCRM: "Account") Validator for submitted data from company form.
	 */
	private $companyValidator;
	
	/**
	 * 
	 * @var Tx_SugarMine_Domain_Validator_CompanyValidator	SugarMines Company (SugarCRM: "Account") Validator for submitted data from company form.
	 */
	private $projectValidator;
	
	/**
	 * 	
	 * @var	Tx_SugarMine_Domain_Repository_RedminerestRepository	SugarMines Repository for RESTful-WebServices of Redmine.
	 */
	private $redminerestRepository;

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
		$this->projectValidator = t3lib_div::makeInstance('Tx_SugarMine_Domain_Validator_ProjectValidator');
		$this->redminerestRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_RedminerestRepository');
	}

	/**
	 * Combines authorized contact data into an render-ready array for FLUID to display the user-profile form:
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function indexAction() {
		
		//var_dump('Controller: Account; Action: form');
		
		$contactData = $GLOBALS['TSFE']->fe_user->getKey('ses','authorizedUser'); // get user Data from authorized session
		$IDs = $GLOBALS['TSFE']->fe_user->getKey('ses', 'IDs');

		// if auth was against sugar: decode encrypted password and inject it back into $contactData:
		$contactData['contact']['data'][$this->sugarsoapRepository->passwField] = ($contactData['authSystem'] == 'sugar') ? $this->sugarsoapRepository->blowfishDecode($contactData['contact']['data'][$this->sugarsoapRepository->passwField]) : $contactData['Contact']['data'][$this->sugarsoapRepository->passwField];
		
		unset($contactData['contact']['data']['account_id']); // account_id is still stored into session
		
		$fieldConf = $this->accountRepository->mergeModuleDataWithFieldConfNew($contactData['contact']['data'], $this->sugarsoapRepository->contactFields['view'], $this->sugarsoapRepository->contactFields['alter']);
		
		$RENDER['profile'] = $this->accountRepository->prepareForFluidNew($contactData['contact']['fields'], $fieldConf, $IDs['accountId']);
		
		if(is_array($RENDER['profile'])) {
			
			if ($RENDER['profile']['t3_password'] !== null) { // if the authentication rans against typo: a configured sugar passwordField is senseless
				unset($RENDER[$this->sugarsoapRepository->passwField]);
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
//var_dump($RENDER);
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
                        	'value' =>      $RENDER['profile']['fieldRecords'][$this->sugarsoapRepository->passwField]['value'] //FIXME: passwords are only as single field entry allowed  
               			 );
				}
			
				//$this->sugarsoapRepository->setLogin();
				//$result = $this->sugarsoapRepository->setEntry('Contacts', $validFields['validFields']);
				var_dump($validFields);
				if ($result['error']['number'] === '0') { // case: no error reported from SugarCRM
					$this->view->assign('contact', $RENDER['profile']); // lets have a look at your changes (still cached)
				} else {
					var_dump('sry, there was an unknown problem with SugarCRM');
					var_dump($result); // shows reported results (including errors)
					$this->view->assign('contact', $RENDER['profile']);
				}		
			}
		}//var_dump($RENDER['profile']);
	}
	
	/**
	 * Shows a case list and an add-new-case form for an authorized SugarMine user.
	 * 
	 * @return	string	the rendered view
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function casesAction() { //TODO: introduce caching also for cases
		
		$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');
		$IDs = $GLOBALS['TSFE']->fe_user->getKey('ses', 'IDs');
		$post = t3lib_div::_POST();
	
		unset($IDs['caseIds']);unset($RENDER['cases']); //deactivate CACHING
		
		if (!empty($post)) {
			
			$validFields = $this->caseValidator->isValid($post); // is array if is valid, else FALSE!
		
			$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');

			if ($validFields === false) {
			
				unset($validFields);
				$this->view->assign('cases', $RENDER['cases']);
			
			} elseif ($validFields['validFields'][1] !== null) { // successful validation of more fields than just id as fixed value:
				
				var_dump($validFields);
				//$this->sugarsoapRepository->setLogin();
				//$result = $this->sugarsoapRepository->setEntry('Project', $validFields['validFields']);
				
				if ($result['error']['number'] === '0') { // case: no error reported from SugarCRM
					$this->view->assign('cases', $RENDER['cases']); // lets have a look at your changes (still cached)
				} else {
					var_dump('sry, there was an unknown problem with SugarCRM');
					var_dump($result); // shows reported results (including errors)
					$this->view->assign('cases', $RENDER['cases']);
				}		
			}
		} elseif ($IDs['caseIds'] === null || $RENDER['cases'] === null) { //TODO: this should be a process for a refresh action
			
			unset($IDs['caseIds']);unset($RENDER['cases']);
			
			$this->sugarsoapRepository->setLogin();
			$response = $this->sugarsoapRepository->getCases($IDs['accountId']); // get ALL projects from SugarCRM

			foreach($response['entry_list'] as $case) {

				$IDs['caseIds'][] = $case['id'];
				
				$fieldConf = $this->accountRepository->mergeModuleDataWithFieldConfNew($case, $this->sugarsoapRepository->caseFields['view'], $this->sugarsoapRepository->caseFields['alter']);

				$RENDER['cases']['list'][] = $this->accountRepository->prepareForFluidNew($response['field_list'], $fieldConf, $case['id']);
			}
		
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'IDs', $IDs);
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'cachedData', $RENDER);
		
		$this->view->assign('cases', $RENDER['cases']);
		} else {
			$this->view->assign('cases', $RENDER['cases']);
		}
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
		$IDs = $GLOBALS['TSFE']->fe_user->getKey('ses', 'IDs');
		$post = t3lib_div::_POST();

		if (!empty($post)) {

			$validFields = $this->companyValidator->isValid($post); // is array if is valid, else FALSE!
		
			$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');
		
			if ($validFields === false) {
			
				unset($validFields);
				$this->view->assign('company', $RENDER['company']);
			
			} elseif ($validFields['validFields'][1] !== null) { // successful validation of more fields than just id as fixed value:
				
				var_dump($validFields);
				//$this->sugarsoapRepository->setLogin();
				//$result = $this->sugarsoapRepository->setEntry('Accounts', $validFields['validFields']);
				
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

			$companyData = $this->sugarsoapRepository->getModuleDataById($IDs['accountId'],'Accounts'); //TODO: is it not valid to have more than one company as one contact!?

			$fieldConf = $this->accountRepository->mergeModuleDataWithFieldConfNew($companyData['entry_list'][0], $this->sugarsoapRepository->companyFields['view'], $this->sugarsoapRepository->companyFields['alter']);
			$RENDER['company'] = $this->accountRepository->prepareForFluidNew($companyData['field_list'], $fieldConf, $IDs['accountId']);

			if(is_array($RENDER['company'])) {
			
				$GLOBALS['TSFE']->fe_user->setKey('ses','cachedData', $RENDER);
				$this->view->assign('company', $RENDER['company']);
			}
		}
	}
	
	/**
	 * Finds and displays existing projects related to the account of an authorized contact.
	 * 
	 * @return	string	the rendered view
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function projectsAction() {
		
		$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');
		$IDs = $GLOBALS['TSFE']->fe_user->getKey('ses', 'IDs');
		$post = t3lib_div::_POST();
		
		//unset($IDs['projectIds']);unset($RENDER['projects']); //deactivate CACHING
		
		if (!empty($post)) {
			
			$validFields = $this->projectValidator->isValid($post); // is array if is valid, else FALSE!
		
			$RENDER = $GLOBALS['TSFE']->fe_user->getKey('ses','cachedData');
			
			if ($validFields === false) {
			
				unset($validFields);
				$this->view->assign('projects', $RENDER['projects']);
			
			} elseif ($validFields['validFields'][1] !== null) { // successful validation of more fields than just id as fixed value:
				
				var_dump($validFields);
				//$this->sugarsoapRepository->setLogin();
				//$result = $this->sugarsoapRepository->setEntry('Project', $validFields['validFields']);
				
				if ($result['error']['number'] === '0') { // case: no error reported from SugarCRM
					$this->view->assign('projects', $RENDER['projects']); // lets have a look at your changes (still cached)
				} else {
					var_dump('sry, there was an unknown problem with SugarCRM');
					var_dump($result); // shows reported results (including errors)
					$this->view->assign('projects', $RENDER['projects']);
				}		
			}
		} elseif ($IDs['projectIds'] === null || $RENDER['projects'] === null) { //TODO: this should be a process for a refresh action
			
			unset($IDs['projectIds']);unset($RENDER['projects']);
			
			$this->sugarsoapRepository->setLogin();
			$response = $this->sugarsoapRepository->getModuleData('Project'); // get ALL projects from SugarCRM
			
			foreach($response['entry_list'] as $project) {
			
				$relations = $this->sugarsoapRepository->getAccountsRelatedToModule('Project',$project['id']); // catch all accounts, that are related to the fetched project
				//var_dump($relations);
			
				if (!empty($relations['ids'])) {
				
					foreach ($relations['ids'] as $relation) {
						if ($relation['id'] === $IDs['accountId']) { // is actual account related to the fetched project?
							$IDs['projectIds'][] = $project['id'];
							
							$fieldConf = $this->accountRepository->mergeModuleDataWithFieldConfNew($project, $this->sugarsoapRepository->projectFields['view'], $this->sugarsoapRepository->projectFields['alter']);
							$RENDER['projects'][] = $this->accountRepository->prepareForFluidNew($response['field_list'], $fieldConf, $project['id']);
						}
					}
				}
			}

		$GLOBALS['TSFE']->fe_user->setKey('ses', 'IDs', $IDs);
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'cachedData', $RENDER);
		
		$this->view->assign('projects', $RENDER['projects']);
		} else {
			$this->view->assign('projects', $RENDER['projects']);
		}
		//var_dump($RENDER);
	}
	
	protected function issuesAction() {
		
		$post = t3lib_div::_POST();
		if (!empty($post)) {
			
			foreach ($post['tx_sugarmine_sugarmine'] as $name => $value) {
				if($name !== '__hmac' && $name !== '__referrer' && $name !== 'recordId') {
					if($value !== '' && $value !== null) { // "validation"
						$issue[$name] = $value;
					}
				}
			}
			$issue['project_id'] = 1; //TODO: identifier to sugarcrm/redmine projects
			var_dump($issue);
			$this->redminerestRepository->createIssue($issue);
		}
			$issues = $this->redminerestRepository->findIssues(array('project_id' => 1));
			$this->view->assign('issues', $issues);
	}
	
	protected function pmsprojectsAction() {
		
		$projects = $this->redminerestRepository->findProjects('',1);
		$this->view->assign('projects', $projects);
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


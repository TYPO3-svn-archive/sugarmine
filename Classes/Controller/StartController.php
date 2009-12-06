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
 * The Standard controller of SugarMine.
 *
 * @package TYPO3
 * @subpackage SugarMine
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Controller_StartController extends Tx_Extbase_MVC_Controller_ActionController {
	
	/**
	 * 	
	 * @var	Tx_SugarMine_Domain_Repository_SugarsoapRepository	SugarMines Repository for NuSOAP-WebServices of SugarCRM.
	 */
	protected $sugarsoapRepository;

	/**
	 * Initializes the current action.
	 *
	 * @return	void
	 */
	protected function initializeAction() {

		$this->sugarsoapRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SugarsoapRepository');
	}

	/**
	 * Separates authentication systems, injects contact-data into session and forwards the process to the AccountController.
	 *
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function indexAction() { //TODO: id ses key is not necessary: information stucks also into authorizedUser

		$authSystem = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['system'];
		$serviceData = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['temp'];
		
		if($GLOBALS["TSFE"]->loginUser) {
			
			if ($serviceData['authSystem'] === 'sugar') { // case1: forwards FRESH SUGAR-authenticated contact-data
				
				var_dump('case: sugar-authenticated user');
				$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $serviceData); // put temporary contactData into current authorized session
				$GLOBALS['TSFE']->fe_user->setKey('ses','authSystemWas', 'Sugar'); // put information about authentication system into session data
				$IDs = array(
							'contactId'=>$serviceData['contact']['data']['id'],
							'accountId'=>$serviceData['contact']['data']['account_id']
						);
				$GLOBALS['TSFE']->fe_user->setKey('ses','IDs', $IDs); // put contact id into session data
				$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['temp'] = null;
				//var_dump($serviceData['data']);
				$this->forward('index','Account');
				
			} elseif (($authSystem === 'typo3' || $authSystem === 'both') && $serviceData['authSystem'] === null) { // case2: create (the first time) data of TYPO3-authenticated user
				
				var_dump('case: typo3-authenticated user');
				$user = $GLOBALS['TSFE']->fe_user->user; 
				
				$this->sugarsoapRepository->setLogin();
				$contactData = $this->sugarsoapRepository->getContactDataByTypoAuthUser($user['username'], $user['name']); // actually, $user['username'] contains an email address
				
				if (is_array($contactData)) {
					
					$IDs = array(
							'contactId'=>$contactData['contact']['data']['id'],
							'accountId'=>$contactData['contact']['data']['account_id']
						);
					$GLOBALS['TSFE']->fe_user->setKey('ses','IDs', $IDs);
					$GLOBALS['TSFE']->fe_user->setKey('ses','authSystemWas', 'Typo3'); // put information about authentication system into session data
					$contactData['contact']['data']['t3_password'] = $user['password']; // inject t3 password into sugars contact data:
					$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $contactData); // put temporary contactData into current authorized session
					$this->forward('index','Account');
				
				} else {
					var_dump('authenticated typo3-user was not found on SugarCRMs database');
				}
			}
		} else {
			$this->forward('test');
		}
	}
	
	/**
	 * Refreshs contact data, stores it into session and forwards to index of account.
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function refreshAction() { 
		
		$authSystemWas = $GLOBALS['TSFE']->fe_user->getKey('ses','authSystemWas');
		
		if ($authSystemWas === 'Sugar') { // case: refresh data of recently SUGAR-authenticated user
				
			var_dump('case: refresh contact data of recently SUGAR-authenticated user');
			$IDs = $GLOBALS['TSFE']->fe_user->getKey('ses','IDs');
 				
			$this->sugarsoapRepository->setLogin();
			$contactData = $this->sugarsoapRepository->getContactDataBySugarAuthUser($IDs['contactId']);
				
			if (is_array($contactData)) {
				$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $contactData); // put temporary contactData into current authorized session
				$this->forward('index','Account');
			} else {
				var_dump('authenticated SugarCRM-user was suddenly not found on SugarCRMs database');
			}
		} elseif ($authSystemWas === 'Typo3') { // case: refresh data of TYPO3-authenticated user
				
			var_dump('case: refresh contact data of recently TYPO3-authenticated user');
			$user = $GLOBALS['TSFE']->fe_user->user; 
				
			$this->sugarsoapRepository->setLogin(); 
			//TODO: refreshing via id is optimal!!!:
			$contactData = $this->sugarsoapRepository->getContactDataByTypoAuthUser($user['username'], $user['name']); // actually, $user['username'] contains an email address
			
			if (is_array($contactData)) {
					
				$contactData['contact']['data']['t3_password'] = $user['password']; // inject t3 password into sugars contact data:
				$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $contactData); // put temporary contactData into current authorized session
				$this->forward('index','Account');
				
			} else {
				var_dump('authenticated typo3-user was not found on SugarCRMs database');
			}
		}
		
	}
	
	/**
	 * Kills current session and forwards to logout form.
	 *
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function logoutAction() {
		
		$GLOBALS['TSFE']->fe_user->setKey('ses','cachedData', null);
		$GLOBALS['TSFE']->fe_user->setKey('ses','authSystemWas', null); //TODO: rename into recentAuthSys
		$GLOBALS['TSFE']->fe_user->setKey('ses','IDs', null);
		session_destroy();
		$this->redirectToURI($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['logoutURI']);
	}
	
	/**
	 * Only useful for test-purposes (authentication-independent)!
	 *
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function testAction() {
		var_dump('hello test action');
		
		$this->sugarsoapRepository->setLogin();
		//var_dump($response = $this->sugarsoapRepository->getAvailableModules());
		//var_dump($this->sugarsoapRepository->getModuleFields('Accounts'));
		//$response = $this->sugarsoapRepository->getModuleDataById('4ec68575-fe34-61b0-e5ae-4ace69791d22','Accounts');
		//var_dump($response);
		$this->sugarsoapRepository->setLogout();
		
	}

}

?>

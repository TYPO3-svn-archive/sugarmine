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
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Controller_StartController extends Tx_Extbase_MVC_Controller_ActionController {
	
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
	 * Index action: Injects contact-data into session and dispatches the process to the AccountController
	 *
	 * @return void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function indexAction() {

		$authSystem = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['system'];
		$serviceData = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['temp'];
		$sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses','collectedData');
		$authSystemWas = $GLOBALS['TSFE']->fe_user->getKey('ses','authSystemWas');
		
		if($GLOBALS["TSFE"]->loginUser) {
			// actually there are four different session cases:
			if (is_string($sessionData['id']['value'])) { // case1: forwards recently session-stored user data
				
				var_dump('case1: forwards recently session-stored user data');
				$this->forward('form','Account');
				
			} elseif ($serviceData['authSystem'] == 'sugar') { // case2: forwards FRESH SUGAR-authenticated contact-data
				
				var_dump('case2: forwards FRESH SUGAR-authenticated contact-data');
				$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $serviceData); // put temporary contactData into current authorized session
				$GLOBALS['TSFE']->fe_user->setKey('ses','authSystemWas', 'Sugar'); // put information about authentication system into session data
				$GLOBALS['TSFE']->fe_user->setKey('ses','contactId', $serviceData['data']['id']); // put contact id into session data
				$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['temp'] = null;
				$this->forward('index','Account');

			} elseif ($authSystemWas == 'Sugar' && !is_string($sessionData['id']['value'])) { // case3: refresh data of recently SUGAR-authenticated user
				
				var_dump('case3: refresh data of recently SUGAR-authenticated user');
				$contactId = $GLOBALS['TSFE']->fe_user->getKey('ses','contactId');
 
				$this->sugarsoapRepository->setLogin();
				$contactData = $this->sugarsoapRepository->getContactDataBySugarAuthUser($contactId);
				
				if (is_array($contactData)) {
					$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $contactData); // put temporary contactData into current authorized session
					$this->forward('index','Account');
				} else {
					var_dump('authenticated SugarCRM-user was suddenly not found on SugarCRMs database');
				}
				
			} elseif (($authSystem == 'typo3' || $authSystem == 'both') && !is_string($sessionData['id']['value']) && $authSystemWas != 'Sugar') { // case4: refresh or create (the first time) data of TYPO3-authenticated user
				
				var_dump('case4: refresh or create (the first time) data of TYPO3-authenticated user');
				$user = $GLOBALS['TSFE']->fe_user->user; 
				
				$this->sugarsoapRepository->setLogin();
				$contactData = $this->sugarsoapRepository->getContactDataByTypoAuthUser($user['username'], $user['name']); // actually, $user['username'] contains an email address
				
				if (is_array($contactData)) {
					
					$GLOBALS['TSFE']->fe_user->setKey('ses','authSystemWas', 'Typo3'); // put information about authentication system into session data
					$contactData['data']['t3_password'] = $user['password']; // inject t3 password into sugars contact data:
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
	 * Clears session data and redirects to index in order to refresh data from SugarCRM.
	 * 
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function refreshAction() {

		$GLOBALS['TSFE']->fe_user->setKey('ses','collectedData',null);
		$this->redirect('index');
	}
	
	/**
	 * Kills current session and forwards to logout form.
	 *
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function logoutAction() {
		
		$GLOBALS['TSFE']->fe_user->setKey('ses','collectedData',null);
		$GLOBALS['TSFE']->fe_user->setKey('ses','authSystemWas', null);
		session_destroy();
		$this->redirectToURI($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['logoutURI']);
	}
	
	/**
	 * Only for test-purposes!
	 *
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function testAction() {
		var_dump('hello test action');
		/*
		$this->sugarsoapRepository->setLogin();
		var_dump($response = $this->sugarsoapRepository->getModuleFields('Contacts'));
		$this->sugarsoapRepository->setLogout();
		*/
	}
	
	protected function soapAction() {
	}

}

?>

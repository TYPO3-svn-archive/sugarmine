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

	//TODO: logoutAction that kills session data and sets logout
	
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
			
			if (is_string($sessionData['id']['value'])) { // case: forwards ses-stored user data
				
				var_dump('collectedData found');
				var_dump($sessionData);
				$this->forward('form','Account');
				
			} elseif ($serviceData['authSystem'] == 'sugar') { // case: forwards service-sugar-authenticated user
				
				var_dump($serviceData);
				$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $serviceData); // put temporary contactData into current authorized session
				$GLOBALS['TSFE']->fe_user->setKey('ses','authSystemWas', 'Sugar');
				$GLOBALS['TSFE']->fe_user->setKey('ses','contactId', $serviceData['data']['id']);
				$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['temp'] = null;
				$this->forward('index','Account');

			} elseif ($authSystemWas == 'Sugar') { // case: refresh data of sugar-authenticated user
				
				$contactId = $GLOBALS['TSFE']->fe_user->getKey('ses','contactId');
 
				$this->sugarsoapRepository->setLogin();
				$contactData = $this->sugarsoapRepository->getContactDataBySugarAuthUser($contactId);
				
				if (is_array($contactData)) {
					$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $contactData);
					$this->forward('index','Account');
				} else {
					var_dump('authenticated SugarCRM-user was suddenly not found on SugarCRMs database');
				}
				//TODO: still without functionality: should call a getContactDataById() method of sugarsoapRepository
				
			} elseif ($authSystem == 'typo3' || $authSystem == 'both') { // case: get contact data after fresh authentication
				
				$user = $GLOBALS['TSFE']->fe_user->user; 
				
				$this->sugarsoapRepository->setLogin();
				$contactData = $this->sugarsoapRepository->getContactDataByTypoAuthUser($user['username'], $user['name']); // actually, $user['username'] contains an email address
				$this->sugarsoapRepository->setLogout();
				
				if (is_array($contactData)) {
					
					$GLOBALS['TSFE']->fe_user->setKey('ses','authSystemWas', 'Typo3');
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
	 * Refreshs session data and forwards to index.
	 * 
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	protected function refreshAction() {

		$GLOBALS['TSFE']->fe_user->setKey('ses','collectedData',null);
		//$sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses','collectedData');
		//tslib_feUserAuth::removeSessionData();
		$this->forward('index');
		
	}
	
	/**
	 * testAction: Only for test-purposes!
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
	
	protected function authAction() {
		
	}
	
	protected function soapAction() {
	
	}

}

?>

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
		$contactData = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['temp'];
		
		if($GLOBALS["TSFE"]->loginUser && $contactData['authSystem'] == 'sugar') {
			// put temporary contactData into current authorized session
			$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $contactData);
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['temp'] = null;
			$this->forward('form','Account');
		} elseif ($GLOBALS["TSFE"]->loginUser && ($authSystem == 'typo3' || $authSystem == 'both')) {
			$user = $GLOBALS['TSFE']->fe_user->user;
			// get contact-data by SugarCRM-database:
			$this->sugarsoapRepository->setLogin();
			//TODO: create mysql field-selector
			$contactData = $this->sugarsoapRepository->getSugarsContactData($user['username'], $user['name']); // actually, $user['username'] contains an email address
			$this->sugarsoapRepository->setLogout();
			if (is_array($contactData)) {
				// inject t3 password into sugars contact data:
				$contactData['data']['t3_password'] = $user['password'];
				// put temporary contactData into current authorized session
				$GLOBALS['TSFE']->fe_user->setKey('ses','authorizedUser', $contactData);
				$this->forward('form','Account');
			} else {
				var_dump('authenticated typo3-user was not found on SugarCRMs database');
			}	
		} else {
			$this->forward('form');
		}
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
	
	protected function loginAction() {
		
	}
	
	protected function authAction() {
		
	}
	
	protected function soapAction() {
	
	}

}

?>

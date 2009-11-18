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
 * The Standard controller for SugarMine.
 *
 */
class Tx_SugarMine_Controller_StartController extends Tx_Extbase_MVC_Controller_ActionController {

	
	/**
	 * @var Tx_Sugarmine_Domain_Repository_SugarsoapRepository
	 */
	protected $sugarsoapRepository;
	
	/**
	 * @var Tx_Sugarmine_Domain_Repository_SetupRepository
	 */
	protected $setupRepository;

	/**
	 * @var Tx_Sugarmine_Domain_Repository_AdministratorRepository
	 */
	protected $administratorRepository;

	/**
	 * Initializes the current action
	 *
	 * @return void
	 */
	public function initializeAction() {
		$this->setupRepository = t3lib_div::makeInstance('Tx_Sugarmine_Domain_Repository_SetupRepository');
		$this->sugarsoapRepository = t3lib_div::makeInstance('Tx_Sugarmine_Domain_Repository_SugarsoapRepository');
		$this->administratorRepository = t3lib_div::makeInstance('Tx_Sugarmine_Domain_Repository_AdministratorRepository');
	}

	/**
	 * Index action: if there is a valid temp value, the process is forwarded to the AccountController.
	 *
	 * @return void
	 */
	public function indexAction() {

		$contactData = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugarmine']['setup']['temp'];
		if($contactData !== null) {
	 		
			// put temporary contactData into current authorized session
			$GLOBALS['TSFE']->fe_user->setKey('ses','contact', $contactData);
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugarmine']['setup']['temp'] = null;
			$this->forward('index','Account');
		}
	}
	
	public function loginAction() {
		
		$this->view->assign('form','Anmeldeformular:');
	}
	
	public function authAction() {
		
	}
	
	protected function soapAction() {

		$this->sugarsoapRepository->setLogin();
		var_dump($response = $this->sugarsoapRepository->getAuth('support68@example.info','tralla'));
		//var_dump($response = $this->sugarsoapRepository->getModuleFields('Contacts'));
		//var_dump($response = $this->sugarsoapRepository->getAvailableModules());
		$this->sugarsoapRepository->setLogout();
	}
	
	protected function testAction() {
		
		$this->sugarsoapRepository->setLogin();
		var_dump($response = $this->sugarsoapRepository->getModuleFields('Contacts'));
		//$this->view->assign('soap', array('user'=>'hallo'));
		$this->sugarsoapRepository->setLogout();
	}

}

?>

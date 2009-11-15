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
 * The Service controller for SugarMine-user-authentication.
 *
 */
class Tx_SugarMine_Controller_ServiceController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var bool
	 */
	public $authResult = false;
	
	/**
	 * @var Tx_SugarMine_Domain_Repository_SugarsoapRepository
	 */
	protected $sugarsoapRepository;

	/**
	 * @var Tx_SugarMine_Domain_Repository_AdministratorRepository
	 */
	protected $administratorRepository;
	
	/**
	 * @var Tx_SugarMine_Domain_Repository_SetupRepository
	 */
	protected $setupRepository;

	/**
	 * Initializes the current action.
	 *
	 * @return void
	 */
	public function initializeAction() {
		$this->setupRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SetupRepository');
		$this->sugarsoapRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SugarsoapRepository');
		//$this->administratorRepository = t3lib_div::makeInstance('Tx_BlogExample_Domain_Repository_AdministratorRepository');
	}
	
/**
	 * Sugar-user-authentication action for the ServiceController.
	 *
	 *@param eMail string
	 *@param $passw string
	 *@return void
	 */
	public function authAction($email,$pass) {
		
		$this->sugarsoapRepository->setLogin();
		$response = $this->sugarsoapRepository->getAuth($email,$pass);
		$this->sugarsoapRepository->setLogout();
		
		if($response == true) {
			$this->authResult = true;
		} else {
			$this->authResult = false;
		}
	}
}


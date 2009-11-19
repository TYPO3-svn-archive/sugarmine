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
class Tx_SugarMine_Controller_AccountController extends Tx_Extbase_MVC_Controller_ActionController {
	
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
	protected function initializeAction() {
		$this->setupRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SetupRepository');
		$this->sugarsoapRepository = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SugarsoapRepository');
		$this->administratorRepository = t3lib_div::makeInstance('Tx_Sugarmine_Domain_Repository_AdministratorRepository');
	}
	
/**
	 * Index Action of AccountController.
	 *
	 *@return void
	 */
	protected function indexAction() {
		
		$this->forward('test');
	}
	
	protected function testAction() {
		
		var_dump('hello protected account action');
		$contactData = $GLOBALS['TSFE']->fe_user->getKey('ses','authorizedUser'); // get contactData from authorized session
		//var_dump($contactData);
		
		$this->sugarsoapRepository->setLogin();
		$moduleFields = $this->sugarsoapRepository->getModuleFields($contactData['source']); //source: should be "Contacts" for now
		//var_dump($moduleFields);
		
		$fieldStat = $this->sugarsoapRepository->fieldConf;
		$this->sugarsoapRepository->setLogout();
		
		//TODO: how to gain speed (1st foreach is deprecated): unset ifentifier of moduleFields and compare $field['name'] with $contactData or $fieldStat
		
		foreach ($moduleFields['module_fields'] as $id => $field) { // moduleFields: $field is an array with database-field-information
				
			foreach ($contactData['data'] as $name => $value) { // contactData: $value is a string with contact-values of every $name
				
				foreach ($fieldStat as $id2 => $field2) { // fieldConf: field2 is an array that contains information about visibility and changeability

					if($name == $field2['name'] && $name == $field['name']) {
					$DATA[$name] = array('value'=>$value, 'edit'=>$field2['edit'], 'field'=>$field,);
					/*	name	(array)
							value	(string)
							edit	(boolean)
							field	(array)
								name	(string)
								type	(string) 
								label	(string)
								required(int)
								options	(array)
									name (string
									value(string)*/
					}
				}
			}
		}

		//var_dump($DATA);

		if(is_array($DATA)) {
			
			$this->view->assign('test', $DATA);
			//$this->sugarsoapRepository->viewableFields = $equalFields;
			//$this->forward('form');
		} else {
			var_dump('ERROR: No valid field configuration');
		}

	}
	
	protected function formAction() {
		
	}
}


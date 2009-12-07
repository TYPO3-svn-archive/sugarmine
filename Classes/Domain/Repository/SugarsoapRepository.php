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

require_once(t3lib_extMgm::extPath('sugar_mine').'Classes/Domain/Repository/SetupRepository.php');
require_once(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/nusoap/lib/nusoap.php');
require_once(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/nusoap/lib/class.wsdlcache.php');
require_once(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/Blowfish/Blowfish.php');

//TODO: authentication methods that are defined by id (parameter) should call get_entry NOT get_entry_list (unnecessary query-request overhead)

/**
 * SugarMines Repository for NuSOAP-WebServices of SugarCRM.
 * 
 * @package TYPO3
 * @subpackage SugarMine
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Domain_Repository_SugarsoapRepository extends Tx_Extbase_Persistence_Repository {
	
	
	/**
	 *  
	 * @var	string	Site_url value of sugars config.php
	 */
	private $soapUrl = '';
	
	/**
	 * 
	 * @var	Tx_SugarMine_Domain_Repository_SetupRepository
	 */
	private $setup = '';

	/**
	 * 
	 * @var object	NuSoapclient
	 */
	private $client = '';
	
	/**
	 * @var string
	 */
	public $error = '';
	
	/**
	 * 
	 * @var string	User password of SugarCRM
	 */
	private $passw = '';
	
	/**
	 * 
	 * @var string	Blowfish encryption key of SugarCRM from: cache/blowfish
	 */
	private $passwKey = '';
	
	/**
	 * 
	 * @var string	Custom encrypted Password field of SugarCRM
	 */
	public $passwField = ''; // should be protected
	
	/**
	 * 
	 * @var string	User name of SugarCRM
	 */
	private $user = '';
	
	/**
	 * 
	 * @var string	Current Session Id of a nusoap session with SugarCRM
	 */
	private $session_id = '';
	
	/**
	 * @var array	Contains current login-data an user of SugarCRM
	 */
	private $auth_array = '';
	
	/**
	 *  
	 * @var array	Containst field configuration (view and edit) of contact fields from setup.txt.
	 */
	public $contactFields = '';
	
	/**
	 *  
	 * @var array	Containst field configuration (view and edit) of company fields from setup.txt.
	 */
	public $companyFields = '';
	
	/**
	 *  
	 * @var array	Containst field configuration (only VIEW!!!) of case fields from setup.txt.
	 */
	public $caseFields = '';
	
	/**
	 * Constructor of SugarsoapRepository: calls soapclient and loads global extension-vars.
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function __construct() {
		$this->setup = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SetupRepository');
		if(is_object($this->setup)) {
			
			$this->contactFields['view'] = $this->setup->getValue('sugar.contact.viewableFields.');
			$this->contactFields['edit'] = $this->setup->getValue('sugar.contact.editableFields.');
			$this->companyFields['view'] = $this->setup->getValue('sugar.company.viewableFields.');
			$this->companyFields['edit'] = $this->setup->getValue('sugar.company.editableFields.');
			$this->caseFields['view'] = $this->setup->getValue('sugar.case.viewableFields.'); //TODO: may a user change existing cases on SugarCRM!?? its a little bit absurd
			
			$this->contactFields['view']['id'] = 1;
			$this->contactFields['view']['account_id'] = 1;
			$this->contactFields['edit']['id'] = null;
			$this->contactFields['edit']['account_id'] = null;
			$this->companyFields['view']['id'] = 1;
			$this->companyFields['edit']['id'] = null;

			$this->soapUrl = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['url'];
			$this->user = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['user'];
			$this->passw = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passw'];
			$this->passwField = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passwField'];
			$this->passwKey = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passwKey'];
			
			// call soapclient if every necessary configuration is available in localconf.php and setup.txt:
			if($this->soapUrl !== '' && $this->user !== '' && $this->passw !== '' && $this->passwKey !== '' && $this->passwField !== '' && $this->contactFields !== '') {
				####WSDL-CACHING####
				$cache = new wsdlcache(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/nusoap/lib/tmp', 86400); // TODO: path to tempt should be defined via typos localconf
				$wsdl = $cache->get($this->soapUrl.'/soap.php?wsdl'); // try to get a cached wsdl-file. 
				if(is_null($wsdl)) // download one, if there is no cached wsdl file in tmp-folder.
				{
  					$wsdl = new wsdl($this->soapUrl.'/soap.php?wsdl', '', '', '', '', 5);
  					// Check for any errors from the wsdl object
  					$err = $wsdl->getError();
  						if ($err) {
  							var_dump('<h2>WSDL Constructor error (Expect - 404 Not Found)</h2><pre>' . $err . '</pre>');
  							var_dump('<h2>Debug</h2><pre>' . htmlspecialchars($wsdl->getDebug(), ENT_QUOTES) . '</pre>');
  							exit();
  						}
  					$cache->put($wsdl);
  					//var_dump('wsdl-file was downloaded');
				} else {
					$wsdl->clearDebug();
					$wsdl->debug('Retrieved from cache');
				}
				####/WSDL-CACHING####
				$this->client = new soapclientw($wsdl, true, '', '', '', '', 5);  
				// Check for any errors from the client object
				$err = $this->client->getError();
				if ($err) {
					$this->error = '<h2>Constructor error</h2><pre>' . $err . '</pre>';
					var_dump($this->error);
				}
			} else {
				exit('ERROR: Please define all necessary configurations in TYPOs localconf.php and sugarMines setup.txt!!!');
			}
		}
	}
	
	/**
	 * Define a SugarCRM-system-user and login or auto-login by an user, defined in localconf.php.
	 * 
	 * @param	string	$user
	 * @param	string	$pass
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function setLogin($user=null,$pass=null) {
		
		$this->user = ($user === null) ? $this->user : $user;
		$this->passw = ($pass === null) ? $this->passw : $pass;
		$this->auth_array = array(
   								'user_auth' => array(
     											'user_name' => $this->user,
     											'password' => md5($this->passw),
   												)
   								);
  		$login_results = $this->client->call('login',$this->auth_array);
  		$this->session_id = $login_results['id'];
	}
	
	/**
	 * Encrypts your plain text-password with blowfish-magic.
	 * 
	 * @param	string	$password (plain-text)
	 * 
	 * @return	string
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	private function blowfishEncode($password=null) {
		
		#prepare static blowfish-key (identical with sugarCRM-blowfish-key)
		$key = strval($this->passwKey);
		
		$BF = new Crypt_Blowfish($key);
		$encrPass = $BF->encrypt(strval($password));
		$encrPass = base64_encode($encrPass);
		return $encrPass;
	}
	
	/**
	 * Decrypts a blowfish encrypted password.
	 * 
	 * @param	string	$password (encrypted)
	 * 
	 * @return	string
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function blowfishDecode($password=null) {
		
		#prepare static blowfish-key (identical with sugarCRM-blowfish-key)
		$key = strval($this->passwKey);

		$BF2 = new Crypt_Blowfish($key);
		$decodedPass = base64_decode($password);
		$decrPass = $BF2->decrypt($decodedPass);
		$decrPass = ereg_replace("\?","",$decrPass); // strip off all questionmarks
		return $decrPass;
	}
	
	/**
	 * Get your predefined entry list from SugarCRM.
	 * 
	 * @param	string	$module
	 * @param	string	$query
	 * @param 	string	$order_bye
	 * @param 	int		$offset
	 * @param 	array	$fields
	 * @param 	int		$max_results
	 * @param 	int		$deleted
	 * 
	 * @return 	array
	 * @author 	Sebastian Stein <s.stein@netzelf.de>
	 */
	private function getEntryList($module,$where='',$order_by='',$offset=0,array $fields,$max_results=0,$deleted=0) {
		
		$result = $this->client->call('get_entry_list', array(
        													$this->session_id,
       														$module,
        													$where,
        													$order_by,
        													$offset,
        													$fields,
        													$max_results,
        													$deleted
    													)
    									);
    	#if there is a valid result, return entry_list and field_list, else dump error
		if($result['result_count'] > 0) {
    		foreach($result['entry_list'] as $record) {
    			$my_array=array();
    			while(list($name,$value) = each($record['name_value_list'])){
        			$my_array[$value['name']] = $value['value'];
    			}
    			$array['entry_list'][] = $my_array;             
    		}
    		$array['field_list'] = $result['field_list'];
    		//var_dump($array);
    		return $array;
    	} else {
    		return false;
    		var_dump($result['error']);	
    	}
	}
	
   /**
	* Update sugar entry with given data-array. 
	*
	* @param	string	$module (module-name usually: 'Contacts')
	* @param	array	$data (associative name-value array of contact-record-updates)
	*
	* @return 	mixed
	* @author 	Sebastian Stein <s.stein@netzelf.de>
	*/             
	public function setEntry($module,$data){
        
        $result = $this->client->call('set_entry', array(
        												$this->session_id,
        												$module,
            											$data
        											)
        							);
        return $result;
	} 
	
	/**
	 * Logout and kill current session-data.
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function setLogout() {
		
		$this->client->call('logout',$this->session_id);
		$this->auth_array = null;
		$this->session_id = null;
	}
	
	/**
	 * Get fresh contact-data from SugarCRM as sugar-authenticated user-session.
	 * 
	 * @param string $id contactId from SugarCRM (only stored into session, if auth-system was sugar)
	 * 
	 * @return array or false
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getContactDataBySugarAuthUser($contactId='') {
		
		$table = $module = 'Contacts';
		$contactId = trim($contactId);

		if($contactId == '') { 
			return false;
		}

		$query = $table.'.id="'.$contactId.'"';
		$matches = $this->getEntryList($module,$query,'',0,$selected_fields = array_keys($this->contactFields['view']),0,0);

		if(is_array($matches)) {
			
			$contactData['authSystem'] = 'sugar';
			$contactData['source'] = 'contact';
			$contactData['contact']['data'] = $matches['entry_list'][0];
			$contactData['contact']['fields'] = $matches['field_list'];
			return $contactData;
			
		} else {
			return false;
		}
	}
	
	/**
	 * Get cases from SugarCRM related to authorized SugarMine user.
	 * 
	 * @param string $account_id accountId from SugarCRM.
	 * 
	 * @return array or false
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getCases($accountId='') {
		
		$table = 'cases';
		$module = 'Cases';
		$accountId = trim($accountId);

		if($accountId == '') { 
			return false;
		}

		$query = $table.'.account_id="'.$accountId.'"';
		$matches = $this->getEntryList($module,$query,'',0,$selected_fields = array_keys($this->caseFields['view']),0,0);

		if(is_array($matches)) {
			
			return $matches;
		} else {
			
			return false;
		}
	}
	
	/**
	 * Get data from SugarCRM related to module name and id.
	 * 
	 * @param	string	$Id from SugarCRM.
	 * @param	string	$module	module name from SugarCRM
	 * 
	 * @return	array or false
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getModuleDataById($Id='', $module='') {

		$Id = trim($Id);

		if($Id == '') { 
			return false;
		}

		$matches = $this->getEntry($module, $Id, $select_fields = array_keys($this->companyFields['view']));

		if(is_array($matches)) {
			
			return $matches;
		} else {
			
			return false;
		}
	}
	
	/**
	 * Get contact-data from SugarCRM (only available for authentication against 'typo3' or 'both')
	 * 
	 * @param string $userEMail
	 * @param string $userName (delimiter between first and last name is: "blank")
	 * 
	 * @return array or false
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getContactDataByTypoAuthUser($userEMail='', $userName='') {
		
		$table = $module = 'Contacts';
		$userEMail = trim($userEMail);
		$userName = trim($userName);
		$userNames = explode(' ', $userName);
		if($userName == '' || $userEMail == '' || $userNames == null) { 
			return false;
		}
		
		$query = $table.'.first_name="'.$userNames[0].'" AND '.$table.'.last_name="'.$userNames[1].'"';
		$matches = $this->getEntryList($module,$query,'',0,$selected_fields = array_keys($this->contactFields['view']),0,0);
		if(is_array($matches)) {
			foreach ($matches['entry_list'] as $entry) { // loop matched users and compare email addresses
				if ($entry['email1'] == $userEMail || $entry['email2'] == $userEMail) {
					$contactData['authSystem'] = 'typo3';
					$contactData['source'] = 'contact';
					$contactData['contact']['data'] = $entry;
					$contactData['contact']['fields'] = $matches['field_list'];
				}
			} return $contactData;
		} else {
			return false;
		}
	}
	
	/**
	 * Get an entry of SugarCRM-database defined by top id of given module name.
	 * 
	 * @param	string	$moduleName
	 * @param	string	$id
	 * @param	array	$select_fields
	 * 
	 * @return	mixed
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	private function getEntry($moduleName='', $id='', $select_fields = array()) {
		
		if ($moduleName !== '' && $id !== '') {
			$result =  $this->client->call('get_entry', array(
												$this->session_id,
												$moduleName,
												$id,
												$select_fields
										)
							);
    		foreach($result['entry_list'] as $record) {
    				$my_array=array();
    				while(list($name,$value) = each($record['name_value_list'])){
        				$my_array[$value['name']] = $value['value'];
    				}
    				$array['entry_list'][] = $my_array;             
    			}
    			$array['field_list'] = $result['field_list'];
    			return $array;
    	} else {
    		return false;
    	}	
	}
	
	#####################--MINOR IMPORTANT NUSOAP CALLS--#########################
	## they were successfully tested, but are CURRENTLY not called by SugarMine ##
	##############################################################################
	
	/**
	 * Set a new contact on sugarCRMs database.
	 * 
	 * @param	string	$email
	 * @param	string	$password
	 * TODO: define some more contact parameters
	 * 
	 * @return	mixed
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function createNewContact($email='',$password=''){
        
		if($email !== '' && $password !== '') {
			return $this->client->setContact(array(
                        						'email1' => $email,
                        						'password_c' => $password
        									)
        								);
        } else {
        	return false;
        }
    } 
	
	/**
	 * Get related notes from SugarCRM.
	 * 
	 * @param	string	$moduleName
	 * @param	string	$moduleId
	 * @param	array	$select_fields
	 * 
	 * @return	mixed	array if request-success
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getRelatedNotes($moduleName='', $moduleId='', $select_fields=array()) {
		
		if($moduleName !== '' && $moduleId !== '') {
			$response = $this->client->call('get_related_notes', array(
														$this->session_id,
														$moduleName,
														$moduleId,
														$select_fields
													)
							);
			return $response;
		} else {
			return false;
		}
	}
	
	/**
	 * Get modified entries from SugarCRM.
	 * 
	 * @param	string	$module
	 * @param	string	$ids
	 * @param	array	$select_fields
	 * 
	 * @return	string	base64-decoded xml
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getModifiedEntries($module='', $ids='', array $select_fields) {

		if($module !== '' && $ids !== '') {
			$response = $this->client->call('get_modified_entries', array(
																		$this->session_id,
																		$module,
																		$ids,
																		$select_fields
																	)
											);
			return base64_decode($response['result']);
		} else {
			return false;
		}	
	}
	
    /**
	 * Get an array-list of all available SugarCRM-modules.
	 * 
	 * @return	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getAvailableModules() {
		
		return $this->client->call('get_available_modules',$this->session_id);
	}
	
	/**
	 * Get all existing fields of your given SugarCRM module name.
	 * 
	 * @param	string	$module
	 * 
	 * @return	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getModuleFields($module='') {
		
		if ($module !== '') {
			return $this->client->call('get_module_fields',array(
																$this->session_id,
																$module
															)
										);
		} else {
			return false;
		}
	}
	
	/**
	 * Get an user GUID-number.
	 * 
	 * @return	string
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getUserGuid() {
		
		$user_guid = $this->client->call('get_user_id',$this->session_id); 
  		return "\n".$this->auth_array['user_auth']['user_name'].' has a GUID of '  . $user_guid . "\n\n";
	}
	
	/**
	 * Get Relationships of an authorized SugarCRM-contact by contact-ID.
	 * 
	 * @param	string	$contactId
	 * 
	 * @return	mixed	array if request-success
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getContactRelationships($contactId = '') {
		
		if($contactId != '') {
			return $this->client->call('get_contact_relationships',array(
																		$this->user,
																		$this->password,
																		$contactId
																	)
										);
		} else {
			return false;
		}
	}
	
}
?>
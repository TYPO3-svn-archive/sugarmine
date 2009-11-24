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

/**
 * SugarMines Repository for NuSOAP-WebServices of SugarCRM.
 * 
 * @package TYPO3
 * @subpackage SugarMine
 * @author	Sebastian Stein <s.stein@netzelf.de>
 */
class Tx_SugarMine_Domain_Repository_SugarsoapRepository extends Tx_Extbase_Persistence_Repository {
	
	
	/**
	 * @var	string
	 */
	public $soapUrl;
	
	/**
	 * Contains all available contact data from sugarCRM 
	 * 
	 * @var array
	 */
	public $contactData = null;
	
	/**
	 * @var Tx_SugarMine_Domain_Repository_SetupRepository
	 */
	public $setup;

	/**
	 * 
	 * @var unknown_type
	 */
	private $client;
	
	/**
	 * @var string
	 */
	public $error;
	
	/**
	 * 
	 * @var string
	 */
	private $passw;
	
	/**
	 * 
	 * @var string
	 */
	private $passwKey;
	
	/**
	 * 
	 * @var string
	 */
	public $passwField;
	
	/**
	 * 
	 * @var string
	 */
	private $user;
	
	/**
	 * 
	 * @var unknown_type
	 */
	private $session_id;
	
	/**
	 * @var array
	 */
	private $auth_array;
	
	/**
	 * 
	 * @var string
	 */
	public $contactID;
	
	/**
	 * Defined by Configuration/TypoScript/setup.txt: Contains field-configuration of contact-data.
	 * 
	 * @var array
	 */
	public $viewField = '';
	
	/**
	 * Defined by Configuration/TypoScript/setup.txt: Contains field-configuration of contact-data.
	 * 
	 * @var array
	 */
	public $editField = '';
	
	/**
	 * Constructor of SugarsoapRepository: calls soapclient and loads global extension-vars.
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function __construct() {
		$this->setup = t3lib_div::makeInstance('Tx_SugarMine_Domain_Repository_SetupRepository');
		if(is_object($this->setup)) {
			
			$this->viewField = $this->setup->getValue('sugar.viewableFields.');
			$this->editField = $this->setup->getValue('sugar.editableFields.');
			$this->soapUrl = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['url'];
			$this->user = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['user'];
			$this->passw = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passw'];
			$this->passwField = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passwField'];
			$this->passwKey = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passwKey'];
			
			// call soapclient if every necessary configuration is available in localconf.php and setup.txt:
			if(!empty($this->soapUrl) && !empty($this->user) && !empty($this->passw) && !empty($this->passwKey) && !empty($this->passwField) && !empty($this->viewField)) {
				####WSDL-CACHING####
				$cache = new wsdlcache(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/nusoap/lib/tmp', 86400);
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
		
		$this->user = (empty($user)) ? $this->user : $user;
		$this->passw = (empty($pass)) ? $this->passw : $pass;
		$this->auth_array = array(
   			'user_auth' => array(
     		'user_name' => $this->user,
     		'password' => md5($this->passw),
   			));

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
	 * Decrypts your encrypted password.
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
		$decrPass = substr($decrPass, 0, -2); // there are two strange chars at the end of the string
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
		
		$result = $this->client->call('get_entry_list',
        array(
        $this->session_id,
        $module,
        $where,
        $order_by,
        $offset,
        $fields,
        $max_results,
        $deleted
    	));
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
	* @return 	boolean
	* @author 	Sebastian Stein <s.stein@netzelf.de>
	*/             
	public function setEntry($module,$data){
        
        $result = $this->client->call('set_entry', array(
        	$this->session_id,
        	$module,
            $data
        ));
        return $result;
	} 
	
	/**
	 * Get an array-list of all available SugarCE-modules.
	 * 
	 * @return	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getAvailableModules() {
		
		return $this->client->call('get_available_modules',$this->session_id);
	}
	
	/**
	 * Get all fields of your module.
	 * 
	 * @param	string	$module
	 * 
	 * @return	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getModuleFields($module) {
		
		return $this->client->call('get_module_fields',array($this->session_id,$module));
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
		$this->contactID = null;
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
	 * Get Relationships of an authorized SugarCRM-contact.
	 * 
	 * @return	array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getContactRelationships() {
		
		if($this->contactID != '') {
			return $this->client->call('get_contact_relationships',array($this->user,$this->password,$this->contactID));
		} else {
			/*var_dump('Sry, there is no authorisation available')*/;
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
	public function getSugarsContactData($userEMail='', $userName='') {
		
		$table = $module = 'Contacts';
		$userEMail = trim($userEMail);
		$userName = trim($userName);
		$userNames = explode(' ', $userName);
		if($userName == '' || $userEMail == '' || $userNames == null) { 
			return false;
		}
		$this->viewField['id'] = 1;
		$this->editField['id'] = null;
		$query = $table.'.first_name="'.$userNames[0].'" AND '.$table.'.last_name="'.$userNames[1].'"';
		$matches = $this->getEntryList($module,$query,'',0,$selected_fields = array_keys($this->viewField),0,0);
		if(is_array($matches)) {
			foreach ($matches['entry_list'] as $entry) { // loop matched users and compare email addresses
				if ($entry['email1'] == $userEMail || $entry['email2'] == $userEMail) {
					$contactData['authSystem'] = 'typo3';
					$contactData['source'] = 'Contacts';
					$contactData['data'] = $entry;
					$contactData['fields'] = $matches['field_list'];
				}
			} return $contactData;
		} else {
			return false;
		}
	}
	
}
?>
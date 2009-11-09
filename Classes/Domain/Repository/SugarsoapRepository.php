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
require_once(t3lib_extMgm::extPath('sugarmine').'Resources/Library/nusoap/lib/nusoap.php');
require_once(t3lib_extMgm::extPath('sugarmine').'Resources/Library/Blowfish/Blowfish.php');
/**
 * A repository for SugarCRM-SOAP-Magic
 * 
 * @package TYPO3
 * @subpackage SugarMine
 * @version 
 */
class Tx_Sugarmine_Domain_Repository_SugarsoapRepository extends Tx_Extbase_Persistence_Repository {
	
	/**
	 * 
	 * @var bool
	 */
	public $authentication = false;
	
	/**
	 * @var string
	 */
	public $soapUrl;
	
	/**
	 * @var Tx_Sugarmine_Domain_Repository_SetupRepository
	 */
	private $setup;

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
	 * @var array
	 */
	public $result;
	
	/**
	 * 
	 * @var string
	 */
	private $password;
	
	/**
	 * 
	 * @var string
	 */
	private $passwordKey;
	
	/**
	 * 
	 * @var string
	 */
	private $passwordField;
	
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
	private $contactID;
	
	/**
	 * Instantiates nusoapclient and loads sugar statics.
	 * 
	 * @param Tx_SugarMine_SetupRepository $setup
	 * @return void
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function __construct() {
		$this->setup = new Tx_Sugarmine_Domain_Repository_SetupRepository;
		$url = trim($this->setup->getValue('sugar.url'), '/');
		$this->soapUrl = $url.'/'.'soap.php';
		$this->user = trim($this->setup->getValue('sugar.user'));
		$this->password = trim($this->setup->getValue('sugar.password'));
		$this->passwordField = trim($this->setup->getValue('sugar.passwordField'));
		$this->passwordKey = trim($this->setup->getValue('sugar.passwordKey'));
		//$client = new soapclientw('http://www.nonplus.net/geek/samples/books.php?wsdl', true);
		$this->client = new soapclientw($this->soapUrl.'?wsdl',true,'','','','');  
		// Check for any errors from the remote service
		$err = $this->client->getError();
		if (!$err && $this->client !== NULL) {
    		//var_dump($this->client);
		} else {
			$this->error = '<p><b>Error: ' . $err . '</b></p>';
			var_dump($this->error);
		}
	}
	
	/**
	 * Define a SugarCE-user and login or auto-login by an available static-user.
	 * 
	 * @param string $user
	 * @param string $pass
	 * @return void
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function setLogin($user=null,$pass=null) {
		
		$this->user = (empty($user)) ? $this->user : $user;
		$this->password = (empty($pass)) ? $this->password : $pass;
		$this->auth_array = array(
   			'user_auth' => array(
     		'user_name' => $this->user,
     		'password' => md5($this->password),
   			));

  		$login_results = $this->client->call('login',$this->auth_array);
  		$this->session_id = $login_results['id'];
	}
	
	/**
	 * Encrypts your plain text-password with blowfish-magic.
	 * 
	 * @return string
	 */
	private function blowfishEncode($password=null) {
		
		#prepare static blowfish-key (identical with sugarCRM-blowfish-key)
		$key = strval($this->passwordKey);
		
		$BF = new Crypt_Blowfish($key);
		$encrPass = $BF->encrypt(strval($password));
		$encrPass = base64_encode($encrPass);
		return $encrPass;
	}
	
	/**
	 * Authenticates the SugarMine contact-logon by custom password and email-address from your SugarCRM-database.
	 *   
	 * @param string $emailAddr
	 * @param string $password
	 * @return bool
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getAuth($emailAddr=null,$password=null) {
		
		if(trim($emailAddr)=='' OR trim($password)=='') { 
			return false;
		}
		#encode password to be able to compare it with encoded passwords in sugars database
		$password = $this->blowfishEncode($password);
		
		#if there is a matching password, there will be an user id field-value in your custom table ..
		$passQuery = 'contacts_cstm.'.$this->passwordField.'="'.$password.'"';
		$matches = $this->getEntryList('Contacts',$passQuery,'',0,$fields=array(),0,0);
		$contactId = $matches[0]['id']; 
		if(!empty($contactId)) {
			
			#with that user-id you can get your unique contact data and compare the email addresses
			$mailQuery = 'contacts.id="'.$contactId.'"';
			$matches = $this->getEntryList('Contacts',$mailQuery,'',0,$fields=array(),0,0);
			if($matches[0]['email1'] == $emailAddr OR $matches[0]['email2'] == $emailAddr) {
				return array(true,$matches[0]['first_name'],$matches[0]['last_name']);
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	/**
	 * Get your predefined entry list.
	 * 
	 * @param string $module
	 * @param string $query
	 * @param string $order_bye
	 * @param int $offset
	 * @param array $fields
	 * @param int $max_results
	 * @param int $deleted
	 * @return array
	 * @author Sebastian Stein <s.stein@netzelf.de>
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
    	if($result['result_count']>0) { 
    		$i=0;
    		foreach($result['entry_list'] as $record) {
        		$i++;
    			$array[$i-1]= $this->nameValuePairToSimpleArray($record['name_value_list']);              
    		}
    		return $array;
    	}
	}
	
	/**
	 * Fetch name-value-pairs to more readable array.
	 * 
	 * @param $array
	 * @return array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	private function nameValuePairToSimpleArray($array){
    
		$my_array=array();
    	while(list($name,$value)=each($array)){
        	$my_array[$value['name']]=$value['value'];
    	}
    	return $my_array;
	} 
	
	/**
	 * Get an array-list of all available SugarCE-modules.
	 * 
	 * @return array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getAvailableModules() {
		
		return $this->client->call('get_available_modules',$this->session_id);
	}
	
	/**
	 * Get all fields of your module.
	 * 
	 * @param string $module
	 * @return array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getModuleFields($module) {
		
		return $this->client->call('get_module_fields',array($this->session_id,$module));
	}
	
	/**
	 * Logout and kill current session-data.
	 * 
	 * @return void
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function setLogout() {
		
		$this->client->call('logout',$this->session_id);
		$this->auth_array = null;
		$this->session_id = null;
		$this->authentication = false;
		$this->contactID = null;
	}
	
	/**
	 * Get an user GUID-number.
	 * 
	 * @return string
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getUserGuid() {
		
		$user_guid = $this->client->call('get_user_id',$this->session_id); 
  		return "\n".$this->auth_array['user_auth']['user_name'].' has a GUID of '  . $user_guid . "\n\n";
	}
	
	/**
	 * Get Relationships of an authorized SugarCRM-contact.
	 * 
	 * @return array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getContactRelationships() {
		
		if($this->authentication == true AND $this->contactID != '') {
			return $this->client->call('get_contact_relationships',array($this->user,$this->password,$this->contactID));
		} else {
			/*var_dump('Sry, there is no authorisation available')*/;
		}
	}
	
	/**
	 * Get an unique sugar-contact-id by firstName,lastName and addressCity (LIKE)
	 * This Method is independent from a custom contact-password and was only made for admin-purposes!!
	 * 
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $addressCity
	 * @return array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getContactIdByName($firstName,$lastName,$addressCity) {
		
		$module = 'Contacts'; $fields = array('id');
		var_dump($query = $module.'.first_name="'.$firstName.'" AND '.$module.'.last_name="'.$lastName.'" AND '.$module.'.primary_address_city LIKE "%'.$addressCity.'%"');
		$matches = $this->getEntryList($module,$query,'',0,$fields,0,0);
		if(count($matches)-1 == 0) {
			return $matches[0]['id'];
		} else {
			/*var_dump('Sry, there is no unique match')*/;
		}
	}
	
	
}
?>
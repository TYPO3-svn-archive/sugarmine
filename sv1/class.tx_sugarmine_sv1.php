<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009  <Sebastian Stein>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_t3lib.'class.t3lib_svbase.php');
require_once(t3lib_extMgm::extPath('sugarmine').'Resources/Library/nusoap/lib/nusoap.php');
require_once(t3lib_extMgm::extPath('sugarmine').'Resources/Library/Blowfish/Blowfish.php');

/**
 * This service tries to authenticate a typo3-login against your SugarCRM-database.
 *
 * @author	 <Sebastian Stein>
 * @package	TYPO3
 * @subpackage	tx_sugarmine
 */
class tx_sugarmine_sv1 extends tx_sv_authbase {
	
	var $prefixId = 'tx_sugarmine_sv1';					// Same as class name
	var $scriptRelPath = 'class.tx_sugarmine_sv1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'sugarmine';							// The extension key.
	
	/**
	 * Contact authentication result of the SugarCRM request.
	 * 
	 * @var boolean
	 */
	private $_isAuthenticated = false;
	
	/**
	 * Site_url to SugarCRM.
	 * 
	 * @var string
	 */
	private $soapUrl = '';
	
	/**
	 * User-name of SugarCRM.
	 * 
	 * @var string
	 */
	private $user = '';
	
	/**
	 * Password of SugarCRM.
	 * 
	 * @var string
	 */
	private $passw = '';
	
	/**
	 * Custom password field name of SugarCRM. 
	 *
	 * @var string
	 */
	private $passwField = '';
	
	/**
	 * Cashed Blowfish encryption-key of SugarCRM.
	 * 
	 * @var string
	 */
	private $passwKey = '';
	
	/**
	 * Dummy TYPO3 user name of an existing fe_user.
	 * 
	 * @var string
	 */
	private $dummy = '';
	
	/**
	 * NuSOAP-Client of SugarCRM.
	 * 
	 * @var unknown_type
	 */
	private $client = '';
	
	/**
	 * NuSOAP-Client-SessionID.
	 * 
	 * @var unknown_type
	 */
	private $session_id = '';
	
	// from tx_sv_authbase:

    // var $pObj; // Parent object

    // var $mode;// Subtype of the service which is used to call the service.

    // var $login=array();// Submitted login form data

    // var $authInfo=array();// Various data

    // var $db_user=array();// User db table definition

    // var $db_groups=array();// Usergroups db table definition

    // var $writeAttemptLog = false;// If the writelog() functions is called if a login-attempt has be tried without success

    // var $writeDevLog = false;// If the t3lib_div::devLog() function should be used
	
	/**
	 * Init of class t3lib_svbase and constructor of tx_sv_authbase-vars.
	 *
	 * @return	boolean
	 */
	function init()	{	
		//$this->writeDevLog = TRUE;
		// init service-configuration from typos global localconf:
		$this->soapUrl = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugarmine']['setup']['url'];
		$this->user = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugarmine']['setup']['user'];
		$this->passw = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugarmine']['setup']['passw'];
		$this->passwField = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugarmine']['setup']['passwField'];
		$this->passwKey = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugarmine']['setup']['passwKey'];
		$this->dummy = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugarmine']['setup']['t3DummyUserName'];
		var_dump('SERVICE_INIT:');
		if($this->soapUrl != '' && $this->user != '' && $this->passw != '' && $this->passwField != '' && $this->passwKey != '' && $this->dummy != '') {
		var_dump('INIT: OK');
			$available = parent::init();
			return $available;
		} else {
			return false;
		}
	}

	/**
	 * Authenticate login-data of an user.
	 *
	 * @param	array		User-data
	 * @return	boolean
	 */
	function authUser($user)	{

		// return values:

        // 200 - authenticated and no more checking needed - useful for IP checking without password

        // 100 - Just go on. User is not authenticated but there's still no reason to stop.

        // false - this service was the right one to authenticate the user but it failed

        // true - this service was able to authenticate the user
		
		$OK = 100;
		
		if ($this->login['uident'] && $this->login['uname'] && $this->_isAuthenticated == true)	{
					
			var_dump($OK = $this->compareUident($user, $this->login)); // true if login-data matched

			if(!$OK) {
				// Failed login attempt (wrong password) - write that to the log!
				if ($this->writeAttemptLog) {
					$this->writelog(255,3,3,1,
						"Login-attempt from %s (%s), username '%s', password not accepted!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));
					t3lib_div::sysLog(
						sprintf( "Login-attempt from %s (%s), username '%s', password not accepted!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname'] ),
						'Core',
						0
					);
				}
				if ($this->writeDevLog) 	t3lib_div::devLog('Password not accepted: '.$this->login['uident'], 'tx_sv_auth', 2);
			}
				
			if ($OK && $user['lockToDomain'] && $user['lockToDomain']!=$this->authInfo['HTTP_HOST']) {
					// Lock domain didn't match, so error:
				if ($this->writeAttemptLog) {
					$this->writelog(255,3,3,1,
						"Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']));
					t3lib_div::sysLog(
						sprintf( "Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST'] ),
						'Core',
						0
					);
				}
				$OK = false;
			}
			
		} return $OK;
	}
	

	/**
	 * Get an existing sugarCRM-contact via nusoap authentication against sugarCRM-database.
	 *
	 * @return	mixed	user array or false
	 */
	function getUser() {

		$user = false;
		
		if ($this->login['status']=='login' && $this->login['uident'])	{
			######################### SugarCRM-REQUEST ###############################
			$this->setLogin();
			$result = $this->getSugarContact();
			$this->client->call('logout',$this->session_id); // direct logout!
			######################### /SugarCRM-REQUEST ###############################

			// evaluation of sugars response:
			if($result['auth'] == true) {

				$user = $this->fetchUserRecord($this->dummy);
				// maps authenticated login-data over your current fe_user:
				$user['username'] = $this->login['uname'];
				$user['password'] = $this->login['uident'];
				
				$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugarmine']['setup']['temp'] = $result;
				$this->_isAuthenticated = true;
			}

			if(!is_array($user)) {
				// Failed login attempt (no username found)
				$this->writelog(255,3,3,2,
					"Login-attempt from %s (%s), username '%s' not found!!",
					Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));	// Logout written to log
				t3lib_div::sysLog(
					sprintf( "Login-attempt from %s (%s), username '%s' not found!", $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname'] ),
					'Core',
					0
				);
			} else {
				if ($this->writeDevLog) t3lib_div::devLog('User found: '.t3lib_div::arrayToLogString($user, array($this->db_user['userid_column'],$this->db_user['username_column'])), 'tx_sv_auth');
			}
		} return $user;
	}
	
	/**
	 * Call NuSoap-Client and set session_id.
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	private function setLogin() {
		
		$this->client = new soapclientw($this->soapUrl.'/soap.php?wsdl',true,'','','','');
		$auth_array = array(
   			'user_auth' => array(
     		'user_name' => $this->user,
     		'password' => md5($this->passw),
   			));

  		$login_results = $this->client->call('login',$auth_array);
  		$this->session_id = $login_results['id'];
	}
	
    /**
	 * Authenticates the SugarMine contact-logon by custom blowfish-password and email-address from SugarCRM-database.
	 * 
	 * @return	mixed false or array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	private function getSugarContact() {

		$SOURCE = 'Contacts'; // TODO: should be defined in localconf.php!!!!!!
		
		$emailAddr = trim($this->login['uname']);
		$password = trim($this->login['uident']);
		if($emailAddr == '' OR $password == '') { 
			return false;
		}
		#encode password to be able to detect it in sugars database:
		$key = strval($this->passwKey); // prepare static blowfish-key (identical with sugarCRM-blowfish-key)
		
		$BF = new Crypt_Blowfish($key);
		$encrPass = $BF->encrypt(strval($password));
		$password = base64_encode($encrPass); // encrypted and encoded password
		
		#if there is a matching password, there will be an user id field-value in your custom table ..
		$passQuery = 'contacts_cstm.'.$this->passwField.'="'.$password.'"';
		$matches = $this->getEntryList('Contacts',$passQuery,'',0,$fields=array(),0,0);
		$contactId = $matches[0]['id']; // pick user id
		if(!empty($contactId)) {
			#with that user-id you can get your unique contact data and compare the email addresses
			$mailQuery = 'contacts.id="'.$contactId.'"';
			$matches = $this->getEntryList('Contacts',$mailQuery,'',0,$fields=array(),0,0);
			#delete the second or-condition, if only the primary email address should be valid:
			if($matches[0]['email1'] == $emailAddr OR $matches[0]['email2'] == $emailAddr) {
				//var_dump($matches);
				return array('auth'=>true, 'source'=>$SOURCE, 'data'=>$matches[0]);
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
	 * Get your predefined entry list of sugarCRM-database.
	 * 
	 * @param	string	$module
	 * @param	string	$query
	 * @param 	string	$order_bye
	 * @param 	int		$offset
	 * @param 	array	$fields
	 * @param 	int		$max_results
	 * @param 	int		$deleted
	 * 
	 * @return 	mixed	array or void (dumps error)
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
    	#if there is a valid result, return entry_list-array, else dump error
    	if($result['result_count']>0) { 
    		$i=0;
    		foreach($result['entry_list'] as $record) {
        		$i++;
    			$my_array=array();
    			while(list($name,$value) = each($record['name_value_list'])){
        		$my_array[$value['name']] = $value['value'];
    			}
    			$array[$i-1] = $my_array;             
    		}
    		return $array;
    	} elseif($result['result_count']<0) {
    		var_dump($result['error']);	
    	}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sugarmine/sv1/class.tx_sugarmine_sv1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sugarmine/sv1/class.tx_sugarmine_sv1.php']);
}

?>
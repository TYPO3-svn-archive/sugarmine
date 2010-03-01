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
require_once(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/nusoap/lib/nusoap.php');
require_once(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/nusoap/lib/class.wsdlcache.php');
require_once(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/Blowfish/Blowfish.php');

require_once(t3lib_extMgm::extPath('sugar_mine').'Classes/Utils/Debug.php');

/**
 * This service tries to authenticate a typo3-login against your SugarCRM-database or typos fe_user table.
 *
 * @author	 <Sebastian Stein>
 * @package	TYPO3
 * @subpackage	tx_sugarmine
 */
class tx_sugarmine_sv1 extends tx_sv_authbase {
	
	var $prefixId = 'tx_sugarmine_sv1';					// Same as class name
	var $scriptRelPath = 'class.tx_sugarmine_sv1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'sugar_mine';							// The extension key.
	
	/**
	 * 
	 * @var string	Site_url to SugarCRM.
	 */
	private $soapUrl = '';
	
	/**
	 * 
	 * @var string	User-name of SugarCRM.
	 */
	private $user = '';
	
	/**
	 * 
	 * @var string	Password of SugarCRM.
	 */
	private $passw = '';
	
	/**
	 * 
	 * @var string	Custom password field name of SugarCRM. 
	 */
	private $passwField = '';
	
	/**
	 * 
	 * @var string	Cashed Blowfish encryption-key of SugarCRM.
	 */
	private $passwKey = '';
	
	/*
	 * 
	 * @var string	Dummy TYPO3 user name of an existing fe_user.
	 */
	private $dummy = '';
	
	/**
	 * 
	 * @var object	NuSOAP-Client of SugarCRM.
	 */
	private $client = '';
	
	/**
	 * 
	 * @var string	NuSOAP-Client-SessionID.
	 */
	private $session_id = '';
	
	/**
	 * 
	 * @var string	The System for user-authentication (typo3 or sugar).
	 */
	private $authSystem = '';
	
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
	function init()	{	//TODO: flash-error messages!
		//$this->writeDevLog = TRUE;
		// init service-configuration from typos global localconf:
		$this->soapUrl = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['url'];
		$this->user = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['user'];
		$this->passw = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passw'];
		$this->passwField = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passwField'];
		$this->nameField = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['nameField'];
		$this->passwKey = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['sugar']['passwKey'];
		$this->dummy = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['t3DummyUserName'];
		$this->authSystem = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['system'];
		$available = false;
		if ($this->authSystem == 'sugar' || $this->authSystem == 'both') {
			if($this->soapUrl != '' && $this->user != '' && $this->passw != '' && $this->passwField != '' && $this->passwKey != '' && $this->dummy != '') {
				//var_dump('SERVICE_INIT: SUGAR OR BOTH');
				$available = parent::init();
			}
		} elseif ($this->authSystem == 'typo3') {
			if ($this->soapUrl != '' && $this->user != '' && $this->passw != '') {
				//var_dump('SERVICE_INIT: TYPO3');
				$available = parent::init();
			}
		} return $available;
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
		if ($this->login['uident'] && $this->login['uname'])	{
				$OK = $this->compareUident($user, $this->login); // true if login-data matched	
			
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
				if ($this->writeDevLog) {
					t3lib_div::devLog('Password not accepted: '.$this->login['uident'], 'tx_sv_auth', 2);
				}
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
	 * Get an existing sugarCRM-contact via nusoap authentication against sugarCRM-database OR authenticate against typos fe_user table.
	 *
	 * @return	mixed	user array or false
	 */
	function getUser() {

		$user = false;
		if ($this->login['status']=='login' && $this->login['uident'])	{
			
			if ($this->authSystem == 'typo3' || $this->authSystem == 'both') { // at first, we are looking for a t3_fe_user, because its the fastest way!
				$user = $this->fetchUserRecord($this->login['uname']);
			}
			
			if (($this->authSystem == 'sugar' || $this->authSystem == 'both') && !is_array($user)) {
				######################### SugarCRM-REQUEST ################################
				$this->callLogin();
				$result = $this->getSugarContact();
				$this->client->call('logout',$this->session_id); // call logout!
				######################### /SugarCRM-REQUEST ###############################
//Tx_SugarMine_Utils_Debug::dump($result);
				// evaluation of sugars response:
				if(is_array($result)) {
					
					$user = $this->fetchUserRecord($this->dummy);
					// maps authenticated login-data over the current fe_user (defined by a global var: $this->dummy):
					$user['username'] = $this->login['uname'];
					$user['password'] = $this->login['uident'];//Tx_SugarMine_Utils_Debug::dump($user);
					// i cant store contact-data ($result) into the current session, because we are still INSIDE the authentication-service (this is done by the sugarmine plugin!!!) THATS why i have to store it temporarily into a global var!
					$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['sugar_mine']['auth']['temp'] = $result; // store contact data
				}
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
	 * Call NuSoap-Client and get the session_id of the SugarCRM-WebService.
	 * 
	 * @return	void
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	private function callLogin() {
		
		####WSDL-CACHING####
			$cache = new wsdlcache(t3lib_extMgm::extPath('sugar_mine').'Resources/Library/nusoap/lib/tmp', 86400); // 1st parameter should be a path to typos global temp
			$wsdl = $cache->get($this->soapUrl.'/soap.php?wsdl'); // try to get a cached wsdl-file.
			// download one, if there is no cached wsdl file in the tmp-folder.
			if(is_null($wsdl))
			{
  				$wsdl = new wsdl($this->soapUrl.'/soap.php?wsdl', '', '', '', '', 5);
  				$err = $wsdl->getError();
  				// Check for any errors from the wsdl object:
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
		$this->client = new soapclientw($wsdl,true, '', '', '', '', 5);
		// Check for any errors from the client object
		$err = $this->client->getError();
		if ($err) {
			$this->error = '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			var_dump($this->error);
		}
		// define authentication-array
		$auth_array = array(
   						'user_auth' => array(
     									'user_name' => $this->user,
     									'password' => md5($this->passw),
										)
						);
  		$login_results = $this->client->call('login',$auth_array); // call the login service
  		$this->session_id = $login_results['id']; // store the session_id
	}
	
  /**
	 * Authenticates the SugarMine Login-User against SugarCRM-database by a custom blowfish-password and an email-address.
	 * 
	 * @return	mixed false or array
	 * @author	Sebastian Stein <s.stein@netzelf.de>
	 */
	private function getSugarContact() {

		$SOURCE = 'contact'; // FIXME: its only an approach, works only with auth against contacts and should be defined in typos localconf.php as source var!!!!!!
		switch($SOURCE) {
			case 'contact': {
				$module = 'Contacts';
				$table = 'contacts';
			} break;
			case 'account': {
				$module = 'Accounts';
				$table = 'accounts';
			}
		}
		
		$name = trim($this->login['uname']);
		$password = trim($this->login['uident']);
		if($name == '' OR $password == '') { 
			return false;
		}
		#encode password to be able to detect it in sugars database:
		$key = strval($this->passwKey); // prepare static blowfish-key (identical with sugarCRM-blowfish-key)
		
		$BF = new Crypt_Blowfish($key);
		$encrPass = $BF->encrypt(strval($password));
		$password = base64_encode($encrPass); // encrypted and encoded password
		
		#if there is a matching password, there will be an user id field-value in your custom table ..
		//$passQuery = $table.'_cstm.'.$this->passwField.'="'.$password.'"';
		$Query = $table.'_cstm.'.$this->passwField.'="'.$password.'" AND '.$table.'_cstm.'.$this->nameField.'="'.$name.'"';
		$matches = $this->getEntryList($module,$Query,'',0,$fields=array(),0,0);
		$Id = $matches[0]['id']; // pick user id
		if(!empty($Id)) {
			#with that user-id you can get your unique contact data and compare the email addresses
			$contactQuery = $table.'.id="'.$Id.'"';
			$matches = $this->getEntryList($module,$contactQuery,'',0,$fields=array(),0,0);
			#delete the 2nd or-condition, if only the primary email address should be valid:
			//if($matches[0]['email1'] == $emailAddr OR $matches[0]['email2'] == $emailAddr) {
				return array(
							'authSystem'=>'sugar',
							'source'=>$SOURCE, 
							$SOURCE=>array(
											'data'=>$matches[0], 
											'fields'=>$matches['field_list']
										)
						);
			/*}
			else {
				return false;
			}*/
		}
		else {
			return false;
		}
	}
	
	/**
	 * Get your predefined entry list of thes sugarCRM-database.
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
    	#if there is a valid result, return entry_list and field_list, else dump error
    	if($result['result_count'] > 0 && $result['result_count'] < 4) {
    		foreach($result['entry_list'] as $record) {
    			$my_array=array();
    			while(list($name,$value) = each($record['name_value_list'])){
        			$my_array[$value['name']] = $value['value'];
    			}
    			$array[] = $my_array;             
    		}
    		$array['field_list'] = $result['field_list'];
    		//var_dump($array);
    		return $array;
    	} else {
    		return false;
    		var_dump($result['error']);	
    	}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sugar_mine/sv1/class.tx_sugarmine_sv1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sugar_mine/sv1/class.tx_sugarmine_sv1.php']);
}

?>
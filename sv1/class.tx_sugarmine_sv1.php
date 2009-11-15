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
require_once(t3lib_extMgm::extPath('sugarmine').'Classes/Domain/Repository/SugarsoapRepository.php');

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
	private $auth = false;
	
	/**
	 * Site_url to SugarCRM.
	 * 
	 * @var string
	 */
	private $url = '';
	
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
	private $t3DummyUserName = '';
	
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
		
		// init service-configuration from ext_localconf:
		$this->url = t3lib_svbase::getServiceOption('url');
		$this->user = t3lib_svbase::getServiceOption('user');
		$this->passw = t3lib_svbase::getServiceOption('password');
		$this->passwField = t3lib_svbase::getServiceOption('passwordField');
		$this->passwKey = t3lib_svbase::getServiceOption('passwordKey');
		$this->t3DummyUserName = t3lib_svbase::getServiceOption('t3DummyUserName');
		
		if($this->url != '' && $this->user != '' && $this->passw != '' && $this->passwField != '' && $this->passwKey != '' && $this->t3DummyUserName != '') {
		
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
		
		if ($this->login['uident'] && $this->login['uname'] && $this->auth = true)	{
					
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
			$repo = new Tx_SugarMine_Domain_Repository_SugarsoapRepository;
			
			// direct-call of sugars nusoap client:
			$repo->callClient($this->url,$this->user,$this->passw,$this->passwField,$this->passwKey);
			
			$repo->setLogin();
			$result = $repo->getAuth($this->login['uname'],$this->login['uident']);
			$repo->setLogout();
			######################### /SugarCRM-REQUEST ###############################
			
			// evaluation of sugars response:
			if($result[0] == true) {
				//$result = array('username' => $user[1], 'password' => $this->login['uident']); // this would be an option to personalise every user

				$user = $this->fetchUserRecord($this->t3DummyUserName);
				// maps authenticated login-data over your current fe_user:
				$user['username'] = $this->login['uname'];
				$user['password'] = $this->login['uident'];
			
				$this->auth = true;
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

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sugarmine/sv1/class.tx_sugarmine_sv1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sugarmine/sv1/class.tx_sugarmine_sv1.php']);
}

?>
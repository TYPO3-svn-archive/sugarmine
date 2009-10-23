<?php
/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
//define('sugarEntry', TRUE); 
require_once(t3lib_extMgm::extPath('sugarmine').'Resources/Library/nusoap/lib/nusoap.php');
/**
 * Contains the account data for sugarmine
 *
 * @package TYPO3
 * @subpackage SugarMine
 * @version 
 */
class Tx_SugarMine_AccountRepository {
	
	/**
	 * @var string
	 */
	public $soapUrl = '';
	
	/**
	 * @var Tx_SugarMine_SetupRepository
	 */
	private $setup;

	/**
	 * 
	 * @var unknown_type
	 */
	public $client;
	
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
	 * @param Tx_SugarMine_SetupRepository $setup
	 * @return void
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function __construct(Tx_SugarMine_SetupRepository $setup) {
		$this->setup = $setup;
		$url = trim($this->setup->getValue('sugar.url'), '/');
		$this->soapUrl = $url.'/'.'soap.php';
		$this->user = trim($this->setup->getValue('sugar.user'));
		$this->password = trim($this->setup->getValue('sugar.password'));
		
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
	 * SugarCE login
	 * 
	 * @return void
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function setLogin() {
		$this->auth_array = array(
   			'user_auth' => array(
     		'user_name' => $this->user,
     		'password' => md5($this->password),
   			));

  		$login_results = $this->client->call('login',$this->auth_array);
  		$this->session_id =  $login_results['id'];
	}
	
	/**
	 * Get an user guid
	 * 
	 * @return string
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getUserGuid() {
		$this->setLogin();
		
		$user_guid = $this->client->call('get_user_id',$this->session_id); 
  		return "\n".$this->auth_array['user_auth']['user_name'].' has a GUID of '  . $user_guid . "\n\n";
	}
	
	/**
	 * Get your entry list
	 * 
	 * @param string $module
	 * @param string $where
	 * @param string $order_bye
	 * @param int $offset
	 * @param array $fields
	 * @param int $max_results
	 * @param int $deleted
	 * @return array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getEntryList($module,$where='',$order_by='',$offset=0,array $fields,$max_results=0,$deleted=0) {
		$this->setLogin();
		
		return $this->client->call('get_entry_list',
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
	}
	
	/**
	 * Get an array-list of all available SugarCE-modules
	 * 
	 * @return array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getAvailableModules() {
		$this->setLogin();
		
		return $this->client->call('get_available_modules',$this->session_id);
	}
	
	/**
	 * Get all fields of your module 
	 * 
	 * @param string $module
	 * @return array
	 * @author Sebastian Stein <s.stein@netzelf.de>
	 */
	public function getModuleFields($module) {
		$this->setLogin();
		
		return $this->client->call('get_module_fields',array($this->session_id,$module));
	}
	
	
}
?>
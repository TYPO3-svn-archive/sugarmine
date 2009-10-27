<?php

class Tx_SugarMine_StandardController extends Tx_SugarMine_ActionController 
{
	/**
	 * @var Tx_SugarMine_SetupRepository
	 */
	protected $setupRepository;
	
	/**
	 * 
	 * @var Tx_SugarMine_SugarsoapRepository
	 */
	protected $sugarsoapRepository;
	
	public function init() {
		//$this->view->setNoRender(true);
	}
	
	public function indexAction() {
		$this->_forward('service');	
	}
	
	/**
	 * Authentication action
	 * 
	 * @return void
	 */
	/*public function authAction() {
	//var_dump($this->getParam('firstName'));
		
		$this->sugarsoapRepository->setLogin();
		var_dump($this->sugarsoapRepository->getAuth('sandy','lemen','dubidu','Contacts'));
		//var_dump($this->sugarsoapRepository->getContactRelationships());
		$this->sugarsoapRepository->setLogout();
	
	}*/
	
	public function serviceAction() {

		if (is_object($serviceObj = t3lib_div::makeInstanceService('sugarAuth'))) {
		var_dump($authentication = $serviceObj->process('','',array('firstName' => 'sandy', 'lastName' => 'lemen', 'pass' => 'dubidu', 'auth' => false)));
		} else var_dump('error');
		
		//var_dump($this->sugarsoapRepository->getContactIdByName('Lenore','Jarboe','St. petersburg'));
		//$this->view->test='SHAZAM!';
		//$name = 'Tom';
		//$fields = array('id','first_name');
		//$entryList = $this->accountRepository->getEntryList('Contacts','contacts.first_name="'.$name.'"','',0,$fields,0,0);
		//var_dump($entryList);
		
		//$this->view->sugar = var_dump($this->accountRepository->getModuleFields('Calls'));
		//$fields = array('id','first_name','last_name',);
		//$this->view->sugar = var_dump($this->accountRepository->getEntryList('Contacts','contacts.deleted = 0','contacts.last_name asc',0,$fields,0,0));
		//$this->view->sugar = var_dump($this->accountRepository->getAvailableModules());
		//$this->accountRepository->getEntryList();
		//$this->view->sugar = $this->accountRepository->result;
		//var_dump($this->accountRepository->result);
	}
}
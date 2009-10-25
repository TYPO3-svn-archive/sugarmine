<?php

class Tx_SugarMine_StandardController extends Tx_SugarMine_ActionController 
{
	/**
	 * @var Tx_SugarMine_SetupRepository
	 */
	protected $setupRepository;
	
	/**
	 * @var Tx_SugarMine_SugarRepository
	 */
	protected $sugarRepository;
	
	/**
	 * 
	 * @var Tx_SugarMine_AccountRepository
	 */
	protected $accountRepository;
	
	public function init() {
		//$this->view->setNoRender(true);
	}
	
	public function indexAction() {
		$this->_forward('auth');	
	}
	
	/**
	 * Authentication action
	 * 
	 * @return void
	 */
	public function authAction() {
		
		$this->accountRepository->setLogin();
		var_dump($this->accountRepository->getAuth('sandy','lemen','dubidu','Contacts'));
		$this->accountRepository->setLogout();
	
	}
	
	public function testAction() {
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
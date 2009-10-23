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
		$this->view->test='maseltov!';
		
		//$this->view->sugar = var_dump($this->accountRepository->getModuleFields('Calls'));
		//$fields = array('id','first_name','last_name',);
		$this->view->sugar = var_dump($this->accountRepository->getEntryList('Contacts','contacts.deleted = 0','contacts.last_name asc',0,$fields,0,0));
		//$this->view->sugar = var_dump($this->accountRepository->getAvailableModules());
		//$this->accountRepository->getEntryList();
		//$this->view->sugar = $this->accountRepository->result;
		//var_dump($this->accountRepository->result);
	}
	
	/**
	 * Just for demo
	 * 
	 * @return void
	 */
	public function blaAction() {
		
		$this->_forward('index'); //blaAction an indexAction weiterleiten
	}
}
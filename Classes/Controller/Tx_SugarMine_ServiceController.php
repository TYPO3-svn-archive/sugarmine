<?php

class Tx_SugarMine_ServiceController extends Tx_SugarMine_ActionController 
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
		//return $this->_forward('auth');	
	}
	
	/**
	 * Authentication action
	 * 
	 * @return void
	 */
	public function authAction() {

		$this->sugarsoapRepository->setLogin();
		$this->sugarsoapRepository->getAuth($this->getParam('firstName'),$this->getParam('lastName'),$this->getParam('pass'),'Contacts');
		if($this->sugarsoapRepository->authentication == true) {
			$this->setParams(array('auth' => true));
		}
		$this->sugarsoapRepository->setLogout();
	}

}
<?php
require_once (t3lib_extMgm::extPath('sugarmine') . 'Classes/Component/Tx_SugarMine_ComponentManager.php');

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

/**
 * Prepares controller and views
 *
 * @package	TYPO3
 * @subpackage	Tx_FormhandlerGui
 * @version $Id: Tx_SugarMine_Dispatcher.php 24843 2009-09-26 20:33:44Z metti $
 */
class Tx_SugarMine_ServiceDispatcher {
	
	/**
	 * @var Tx_SugarMine_ComponentManager
	 */
	private $componentManager;
	
	/**
	 * @var array
	 */
	private $params = array();
	
	/**
	 * Prepare controller and view and render it
	 *
	 * @param string $controller The controller that has to be fetched
	 * @param string $action The action for the controller
	 * @param array $params Params
	 * @return string The rendered view
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function dispatch($controller=null, $action=null, $params=null) {
		$controller = ($controller === null) ? $this->controller : $controller;
		$action = ($action === null) ? $this->action : $action;
		$params = ($params === null) ? $this->params : $params;
		
		$this->componentManager = Tx_SugarMine_ComponentManager::getInstance();
		
		$controllerClassName = Tx_SugarMine_Configuration::getControllerClassName($controller);
		
		$controllerClass = $this->componentManager->getComponent($controllerClassName);
		$controllerClass->setParams($params);
		
		//$viewClass = $this->componentManager->getComponent('Tx_SugarMine_View');
		
		//$viewClass->init($controller, $action);
		$controllerClass->run($action, $params);
		//$controllerClass->setView($viewClass);
		return $controllerClass->getParam('auth');
		
		//return $viewClass->render();
	}
	
	/**
	 * Sets a controller to call
	 * 
	 * @param string $controller The controller name
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function setController($controller) {
		$this->controller = $controller;
	}
	
	/**
	 * Sets a action to call
	 *
	 * @param string $action The action name
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function setAction($action) {
		$this->action = $action;
	}
	
	/**
	 * Sets params for the controller
	 *
	 * @param array $params Key value pairs of parameters
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function setParams(array $params) {
		$this->params = $params;
	}
}
?>
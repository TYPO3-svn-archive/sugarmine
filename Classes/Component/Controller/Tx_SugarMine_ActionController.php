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
 * Public License for more details.                                       */

/**
 * Abstract class for Controller Classes used by FormhandlerGui
 *
 * @package	TYPO3
 * @subpackage	Tx_FormhandlerGui
 * @abstract
 * @version $Id: Tx_SugarMine_ActionController.php 24846 2009-09-27 14:21:21Z metti $
 */
abstract class Tx_SugarMine_ActionController /*implements Tx_SugarMine_ControllerInterface*/ {

	/**
	 * @var Tx_SugarMine_ComponentManager
	 */
	protected $componentManager;

	/**
	 * @var Tx_SugarMine_Configuration
	 */
	protected $config;

	/**
	 * @var Tx_SugarMine_View
	 */
	protected $view;

	/**
	 * Indicates if the controller is running
	 * @var boolean
	 */
	private $controllerRunning;
	
	/**
	 * @var stdClass
	 */
	protected $params;

	/**
	 * Just puting the objects to the instance
	 *
	 * @param Tx_SugarMine_ComponentManager $componentManager
	 * @param Tx_SugarMine_Configuration $configuration
	 * @param Tx_SugarMine_View $view
	 * @return void
	 * @author Christian Opitz
	 */
	public function __construct(
	Tx_SugarMine_ComponentManager $componentManager,
	Tx_SugarMine_Configuration $configuration
	) {
		$this->componentManager = $componentManager;
		$this->config = $configuration;
		$this->params = new stdClass();
		
		//Look up for repositorys to inject (no account for inject-tag yet)
		$class = new ReflectionClass(get_class($this));
		$properties = $class->getProperties();
		foreach ($properties as $property) {
			$propName = $property->getName();
			if (strpos($propName,'Repository') > 2) {
				$repo = $this->config->getPrefixedPackageKey().'_'.ucfirst($propName);
				$this->$propName = $componentManager->getComponent($repo);
			}
		}
	}
	
	/**
	 * Sets the view for the controller
	 *
	 * @param Tx_SugarMine_View $viewClass
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function setView(Tx_SugarMine_View $viewClass) {
		$this->view = $viewClass;
	}
	
	/**
	 * Sets the parameters for the controller
	 *
	 * @param array $params Key value pairs of params
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function setParams($params = array()) {
		foreach ($params as $name => $value) {
			$param = strval($name);
			if (strlen($name)) {
				$this->params->$name = $value;
			}
		}
	}
	
	/**
	 * Returns a parameter by its name
	 *
	 * @param string $name The name of the parameter
	 * @return mixed The value of the parameter
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function getParam($name) {
		return $this->params->$name;
	}

	/**
	 * Sets the internal attribute "langFile"
	 *
	 * @param string $langFile
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function setLangFile($langFile) {
		global $LANG;
		$this->langFile = $langFile;
		$LANG->includeLLFile($this->langFileRoot.$langFile);
	}

	/**
	 * Returns the right settings for the formhandler (Checks if predefined form was selected)
	 *
	 * @return array The settings
	 * @author Reinhard Führicht <rf@typoheads.at>
	 */
	public function getSettings() {
		$settings = $this->config->getSettings();

		if($this->predefined) {

			$settings = $settings['predef.'][$this->predefined];
		}
		return $settings;
	}

	/**
	 * Runs an action - must not be called from within the controller
	 * 
	 * @param string $action
	 * @param array $params
	 * @return void
	 */
	public function run($action = 'index', $params = null) {
		if ($this->controllerRunning) {
			throw new Exception('Tx_SugarMine_AbstractController::runAction() can not be executed from within controllers!');
			return;
		}

		$this->setRunning(true);

		if (method_exists($this, 'init')) {
			$this->init();
		}

		$actionMethod = $action.'Action';

		if (method_exists($this,$actionMethod)) {
			$this->$actionMethod();
		}else{
			throw new Exception('Action method '.$actionMethod.' not found in '.get_class($this));
		}

		$this->setRunning(false);
	}

	/**
	 * Stops the current run-process and forwards to another action
	 * without resetting the view
	 *
	 * @param $action - the action to be executed
	 * @param $controller - optional: another controller
	 * @param $params - optional
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	protected function _forward($action, $controller = null, $params = null) {
		$this->setRunning(false);
		
		if (is_object($this->view)) {
			if (method_exists($this->view, 'setActionName')) {
				$this->view->setActionName($action);
			}
		}

		if ($controller === null) {
			$this->run($action, $params);
		}else{
			$controllerClassName = Tx_SugarMine_Configuration::getControllerClassName($controller);
			$controllerClass = $this->componentManager->getComponent($controllerClassName);
			$controllerClass->run($action, $params);
		}
	}

	/**
	 * Stops the current run-process, resets the view and forwards to another action
	 *
	 * @param $action - the action to be executed
	 * @param $controller - optional: another controller
	 * @param $params - optional
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	private function _redirect($action, $controller = null, $params = null) {
		$this->setRunning(false);
		$dispatcher = $this->componentManager->getComponent('Tx_SugarMine_Dispatcher');
		$dispatcher->dispatch($controller,$action,$params);
	}

	/**
	 * Sets controller running status in controller and view
	 *
	 * @param boolean $status If running or not
	 * @return void
	 */
	private function setRunning($status) {
		$this->controllerRunning = $status;
		if (is_object($this->view)) {
			if (method_exists($this->view, 'setActionName')) {
				$this->view->setControllerRunning($status);
			}
		}
	}
}
?>

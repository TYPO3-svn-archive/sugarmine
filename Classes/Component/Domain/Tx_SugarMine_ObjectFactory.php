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

/**
 * Creates the model objects
 *
 * @package TYPO3
 * @subpackage FormhandlerGui
 * @version $Id: Tx_SugarMine_ObjectFactory.php 24843 2009-09-26 20:33:44Z metti $
 */
class Tx_SugarMine_ObjectFactory {
	
	/**
	 * @var Tx_SugarMine_ComponentManager
	 */
	private $componentManager;
	
	/**
	 * @var Tx_SugarMine_Configuration
	 */
	private $config;
	
	public function __construct(
	Tx_SugarMine_ComponentManager $componentManager,
	Tx_SugarMine_Configuration $configuration) {
		$this->componentManager = $componentManager;
		$this->config = $configuration;
	}
	
	/**
	 * Instanciates a model class and calls the (mostly magic) set[FieldName]-methods
	 * 
	 * @param string $model The model class
	 * @param array $row The result from a DB-query or other key-value-pairs
	 * @return <$model> The entity object
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function &create($model, $row = array()) {
		$object = $this->componentManager->getComponent($model, $row);
		foreach ($row as $key => $val) {
			$func = 'set'.Tx_SugarMine_Func::tableFieldCamelCase($key, true);
			$object->$func($val);
		}
		return $object;
	}
}
?>
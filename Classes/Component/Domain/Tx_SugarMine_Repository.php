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
 * The base class for repositorys. Sure it's not really clean but 
 * SQL-queries are done here too
 *
 * @package TYPO3
 * @subpackage FormhandlerGui
 * @version $Id: Tx_SugarMine_Repository.php 24846 2009-09-27 14:21:21Z metti $
 */
class Tx_SugarMine_Repository {
	
	/**
	 * @var Tx_SugarMine_Configuration
	 */
	private $config;
	
	/**
	 * @var Tx_SugarMine_ObjectFactory
	 */
	private $objectFactory;
	
	/**
	 * @var string
	 */
	private $objectType;
	
	private $table;
	
	public function __construct(
	Tx_SugarMine_Configuration $configuration,
	Tx_SugarMine_ObjectFactory $objectFactory
	) {
		$this->config = $configuration;
		$this->objectFactory = $objectFactory;
		
		$className = get_class($this);
		
		$this->table = strtolower(str_replace('Repository','',$className)).'s';
		$this->objectType = str_replace('Repository','Model',$className);
	}
	
	public function __call($func, $arguments) {
		$parts = Tx_SugarMine_Func::explodeCamelCase($func, 1);
		
		if ($parts[0] == 'find') {
			if ($parts[1] == 'by') {
				return $this->findBy($parts[2],$arguments[0], $arguments[1]);
			}
			if ($parts[1] == 'one') {
				return $this->findOneBy($parts[3],$arguments[0]);
			}
		}
		return NULL;
	}
	
	public function add($what, $where) {
		
	}
	
	public function remove($what, $where) {
		
	}
	
	public function findBy($where, $is, $limit = '') {
		if (is_array($is)) {
			$values = array();
			foreach($is as $value) {
				$value = strval($value);
				if (strlen($value) > 0) { 
					$values[] = "'".$value."'";
				}
			}
			if (count($values) == 0) {
				return array();
			}
			$whereClause = $where.' IN ('.implode(',',$values).')';
		}else{
			$whereClause = $where."='".strval($is)."'";
		}
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->table,
			$whereClause,
			'',
			'',
			$limit
		);
		$objects = array();
		foreach ($res as $row) {
			$objects[] = $this->objectFactory->create($this->objectType, $row);
		}
		return $objects;
	}
	
	public function findOneBy($where, $is) {
		$result = $this->findBy($where, $is, '1');
		return $result[0];
	}
}
?>
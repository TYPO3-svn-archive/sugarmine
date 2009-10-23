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
 * Contains the setup for sugarmine
 *
 * @package TYPO3
 * @subpackage SugarMine
 * @version $Id: Tx_FormhandlerGui_SetupRepository.php 24852 2009-09-28 02:26:04Z metti $
 */
class Tx_SugarMine_SugarRepository {
	
	/**
	 * @var string
	 */
	public $soapUrl = '';
	
	/**
	 * @var Tx_SugarMine_SetupRepository
	 */
	private $setup;
	
	public function __construct(Tx_SugarMine_SetupRepository $setup) {
		$this->setup = $setup;
		$url = trim($this->setup->getValue('sugar.url'), '/');
		$this->soapUrl = $url.'/'.'soap.php';
		//var_dump($this->soapUrl);
	}
}
?>
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
 * The model for fields table
 *
 * @scope prototype
 * @package TYPO3
 * @subpackage FormhandlerGui
 * @version $Id: Tx_FormhandlerGui_FieldModel.php 24846 2009-09-27 14:21:21Z metti $
 */
class Tx_FormhandlerGui_FieldModel extends Tx_FormhandlerGui_Model {

	/**
	 * The field identifier.
	 * @var string
	 * @identity
	 */
	protected $identifier = 'uid';

	/**
	 * @var stdClass
	 */
	protected $langConf;

	/**
	 * @var stdClass
	 */
	protected $fieldConf;

	/**
	 * Reads the flexForm in this field, selects the right language
	 * and writes the contents as key-value-pairs in $this->langConf
	 *
	 * @param string $flex
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function setLangConf($flex) {
		$this->langConf = new stdClass();

		$flexArray = $this->readFlex($flex);

		$contents = array();
		foreach ($flexArray['data']['sDEF'] as $lang => $fields) {
			$lang = ltrim($lang,'l');
			foreach($fields as $key => $value) {
				$contents[$lang][$key] = $value['v'.$lang];
			}
		}

		//TODO: Select right language and fallbacks!
		foreach ($contents['DEF'] as $key => $value) {
			$this->langConf->$key = $value;
		}
	}

	/**
	 * Reads the flexForm in this field and writes the contents as 
	 * key-value-pairs in $this->fieldConf
	 *
	 * @param string $flex
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function setFieldConf($flex) {
		$this->fieldConf = new stdClass();

		$flexArray = $this->readFlex($flex);

		foreach ($flexArray['data']['sDEF']['lDEF'] as $key => $value) {
			$this->fieldConf->$key = $value['vDEF'];
		}
	}

	/**
	 * Tries to read a flexForm and return a proper array in any case
	 *
	 * @param string $flex
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	private function readFlex($flex) {
		$dummyArray = array();
		$dummyArray['data']['sDEF']['lDEF'] = array();
		if (strlen($flex) == 0) {
			return $dummyArray;
		}
		$flexArray = t3lib_div::xml2array($flex);
		if (!is_array($flexArray)) {
			return $dummyArray;
		}
		return $flexArray;
	}
}
?>
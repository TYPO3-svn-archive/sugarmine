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
 * The configuration of the extension FormhandlerGui
 *
 * @package	TYPO3
 * @subpackage	Tx_MyPackage
 * @version $Id: Tx_SugarMine_Configuration.php 24846 2009-09-27 14:21:21Z metti $
 * @author	Jochen Rau <jochen.rau@typoplanet.de>
 * @author Christian Opitz <co@netzelf.de>
 */
class Tx_SugarMine_Configuration implements ArrayAccess {

	/**
	 * Must be lowercase the same as the extension key
	 * @var string
	 */
	const PACKAGE_KEY = 'SugarMine';
	
	/**
	 * @var Tx_SugarMine_Configuration_View
	 */
	public $view;
	
	protected $setup;

	public function __construct() {
		$this->setup = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->getPrefixedPackageKey() . '.'];
	}

	public function merge($setup) {
		if (is_array($setup)) {
			$settings = $this->setup['settings.'];
			$settings = t3lib_div::array_merge_recursive_overrule($settings, $setup);
			$this->setup['settings.'] = $settings;
		}
	}

	public function offsetGet($offset) {
		return $this->setup['settings.'][$offset];
	}

	public function offsetSet($offset, $value) {
		$this->setup['settings.'][$offset] = $value;
	}

	public function offsetExists($offset) {
		if (isset($this->setup['settings.'][$offset])) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function offsetUnset($offset) {
		$this->setup['settings.'][$offset] = NULL;
	}

	public function getSettings() {
		return $this->setup['settings.'];
	}

	public function getSourcesConfiguration() {
		return $this->setup['sources.'];
	}

	public static function getPackageKey() {
		return self::PACKAGE_KEY;
	}

	public static function getPackageKeyLowercase() {
		return strtolower(self::PACKAGE_KEY);
	}

	public static function getPrefixedPackageKey() {
		return Tx_SugarMine_ComponentManager::PACKAGE_PREFIX . '_' . self::PACKAGE_KEY;
	}

	public static function getPrefixedPackageKeyLowercase() {
		return strtolower(Tx_SugarMine_ComponentManager::PACKAGE_PREFIX . '_' . self::PACKAGE_KEY);
	}

	public static function getPackagePath() {
		return t3lib_extMgm::extPath(strtolower(self::PACKAGE_KEY));
	}
	
	public static function getControllerClassName($controller) {
		$controllerClassName = self::getPrefixedPackageKey();
		$controllerClassName .= '_'.ucfirst($controller).'Controller';
		return $controllerClassName;
	}
}
?>
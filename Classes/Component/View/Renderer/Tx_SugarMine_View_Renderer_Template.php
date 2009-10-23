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
 * Renders to HTML-Templates
 *
 * @scope prototype
 * @package TYPO3
 * @subpackage FormhandlerGui
 * @version $Id: Tx_SugarMine_View_Renderer_Template.php 24840 2009-09-26 17:40:46Z metti $
 */
class Tx_SugarMine_View_Renderer_Template {
	
	/**
	 * @var t3lib_parsehtml
	 */
	private $parser;
	
	/**
	 * The template content
	 * @var string
	 */
	private $template;
	
	/**
	 * @var array
	 */
	private $markers = array();
	
	private $subParts = array();
	
	public function __construct() {
		$this->parser = t3lib_div::makeInstance('t3lib_parsehtml');
	}
	
	public function render($templateFile, $vars=array()) {
		$this->template = t3lib_div::getURL($templateFile);
		
		foreach ($vars as $name => $value) {
			$markerName = $this->treatCamelCase($name);
			$this->markers['###'.$markerName.'###'] = strval($value);
		}
		
		return $this->parser->substituteMarkerArray($this->template, $this->markers);
	}
	
	/**
	 * Makes this:	HelloImAVar => HELLO_IM_A_VAR 
	 * and this:	helloImAVar => HELLO_IM_A_VAR
	 * 
	 * @param string $string The original string
	 * @return string The transformed string
	 * @author Christian Opitz <co@netzelf.de>
	 */
	private function treatCamelCase($string) {
	  
		$parts = Tx_SugarMine_Func::explodeCamelCase($string,2);
 
		return implode('_',$parts);
	}
}
?>
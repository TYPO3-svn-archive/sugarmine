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
 * @scope prototype
 * @package TYPO3
 * @subpackage FormhandlerGui
 * @version $Id: Tx_SugarMine_Configuration_View.php 24846 2009-09-27 14:21:21Z metti $
 */
class Tx_SugarMine_Configuration_View {

	/**
	 * ViewScript directory
	 * @var string
	 */
	const DIRECTORY_VIEWSCRIPTS = 'Resources/ViewScripts/';

	/**
	 * Template directory
	 * @var string
	 */
	const DIRECTORY_TEMPLATES = 'Resources/Templates/';

	/**
	 * The default rendering method - can be VIEWSCRIPT or TEMPLATE
	 * Can be overruled by passing a file extension to the view
	 * render method
	 * @see Tx_SugarMine_View::render
	 * @var string
	 */
	const DEFAULT_RENDERMETHOD = 'VIEWSCRIPT';

	/**
	 * The file extensions that can be used as templates. If you pass
	 * a file with one of these extensions the view will render the
	 * template file from the template directory
	 * @var string
	 */
	const TEMPLATE_EXTENSIONS = 'html,htm,tmpl';

	/**
	 * The file extensions that can be used as viewScripts. If you pass
	 * a file with one of these extensions the view will render the
	 * viewScript file from the viewScript directory
	 * @var string
	 */
	const VIEWSCRIPT_EXTENSIONS = 'php,phtml';

	private $renderPath;
	private $viewFile;
	private $renderExtension;
	private $renderMethod;

	public function getRenderPath($renderMethod = NULL) {
		if (isset($this->viewFile)) {
			return dirname($this->renderFile).'/';
		}else{
			if ($renderMethod === NULL) {
				$renderMethod = $this->getRenderMethod();
			}
			$dir = ($renderMethod == 'VIEWSCRIPT') ? self::DIRECTORY_VIEWSCRIPTS : self::DIRECTORY_TEMPLATES;
			return Tx_SugarMine_Configuration::getPackagePath().$dir;
		}
	}

	public function getRenderExtensions($renderMethod = NULL) {
		if ($renderMethod === NULL) {
			$renderMethod = $this->getRenderMethod();
		}
		$ext = ($renderMethod == 'VIEWSCRIPT') ? self::VIEWSCRIPT_EXTENSIONS : self::TEMPLATE_EXTENSIONS;
		return explode(',',$ext);
	}

	public function getRenderMethod() {
		if (isset($this->viewFile)) {
			$file = pathinfo($this->viewFile);
			return $this->getRenderMethodByExtension($file['extension']);
		}
		return (isset($this->renderMethod)) ? $this->renderMethod : self::DEFAULT_RENDERMETHOD;
	}
	
	public function getRenderMethodByExtension($ext) {
		$extensions = explode(',', self::VIEWSCRIPT_EXTENSIONS);
		if (in_array($ext,$extensions)) {
			return 'VIEWSCRIPT';
		}
		$extensions = explode(',', self::TEMPLATE_EXTENSIONS);
		if (in_array($ext,$extensions)) {
			return 'TEMPLATE';
		}
		throw new Exception('The requested view-file with extension '.$ext.' is of the wrong file type ('.
		self::VIEWSCRIPT_EXTENSIONS.','.
		self::TEMPLATE_EXTENSIONS.' allowed)'
		);
	}

	public function setRenderMethod($method) {
		$this->renderMethod = $method;
	}
	
	public function getViewFile() {
		return (isset($this->viewFile)) ? $this->viewFile : NULL;
	}
	
	public function setViewFile($file) {
		$this->viewFile = t3lib_div::getFileAbsFileName($file);
	}
	
	public function reset() {
		unset(
			$this->viewFile,
			$this->renderMethod
		);
	}
}
?>
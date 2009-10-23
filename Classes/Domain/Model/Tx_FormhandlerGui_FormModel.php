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
 * The model for forms table
 * 
 * @scope prototype
 * @package TYPO3
 * @subpackage FormhandlerGui
 * @version $Id: Tx_FormhandlerGui_FormModel.php 24843 2009-09-26 20:33:44Z metti $
 */
class Tx_FormhandlerGui_FormModel extends Tx_FormhandlerGui_Model {

	/**
	 * The form identifier.
	 * @var string
	 * @identity
	 */
	protected $identifier = 'uid';
	
	/**
	 * The fields contained in this form
	 * @var array<Tx_FormhandlerGui_FieldModel>
	 */
	protected $fields = array();
	
	/**
	 * Gets the fields for this form from the fields repository
	 * 
	 * @param string $fieldList uid-list of fields
	 * @return void
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function setFields($fieldList) {
		$fields = explode(',',$fieldList);
		$cm = Tx_FormhandlerGui_ComponentManager::getInstance();
		$fieldsRepository = $cm->getComponent('Tx_FormhandlerGui_FieldRepository');
		
		$this->fields = $fieldsRepository->findByUid($fields);
	}
}
?>
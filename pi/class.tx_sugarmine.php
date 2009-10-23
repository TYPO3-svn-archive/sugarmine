<?php

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once (t3lib_extMgm::extPath('sugarmine') . 'Classes/Component/Tx_SugarMine_Dispatcher.php');

class tx_sugarmine extends tslib_pibase {
		/**
	 * Same as class name
	 * @var string
	 */
	public $prefixId      = 'tx_sugarmine';

	/**
	 * Path to this script relative to the extension dir.
	 * @var string
	 */
	public $scriptRelPath = 'pi/class.tx_sugarmine.php';

	/**
	 * The extension key
	 * @var string
	 */
	public $extKey = 'sugarmine';

	public $pi_checkCHash = true;

	public $conf;
	
	public function main($content, $conf){
		$this->pi_setPiVarDefaults();
		
		$dispatcher = t3lib_div::makeInstance('Tx_SugarMine_Dispatcher');
	
		$controller = (!empty($this->piVars['controller'])) ? $this->piVars['controller'] : null;
		$action = (!empty($this->piVars['action'])) ? $this->piVars['action'] : null;
		$params = $this->piVars;
		
		try{
			$result = $dispatcher->dispatch($controller,$action,$params);
		}catch (Exception $e) {
			$result = '<div class="error">'.$e->getMessage().'</div>';
		}
		
		return $this->pi_wrapInBaseClass($result);
	}
}
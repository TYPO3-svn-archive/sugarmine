<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
//t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/Components/', 'Components');
//t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/Settings/', 'Settings');
//t3lib_extMgm::addPlugin(array('SugarMine', $_EXTKEY), 'list_type');

t3lib_extMgm::addPItoST43('sugarmine');

if (TYPO3_MODE == 'BE')  {	
	t3lib_extMgm::addPlugin(array('SugarMine', $_EXTKEY), 'list_type');
}


elseif($GLOBALS['TSFE']->id)  {
	
	/*if(!$TSObj->setup['plugin.']['F3_SugarMine_Plugin.']['userFunc']) {
	t3lib_div::debug('No static template found! Make sure to include "Settings (sugarmine)" in your TypoScript template!');
	}*/
}

/*t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(array(
	'LLL:EXT:cmtest/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
),'list_type');


if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_cmtest_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_cmtest_pi1_wizicon.php';
}*/
?>
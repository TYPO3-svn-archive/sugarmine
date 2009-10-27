<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}
$_EXTKEY = 'sugarmine';
t3lib_extMgm::addService($_EXTKEY,  'sugarAuth' /* sv type */,  'tx_sugarmine_sv1' /* sv key */,
		array(

			'title' => 'SugarMineAuthentication',
			'description' => 'Authenticates SugarMine-Users',

			'subtype' => '',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_sugarmine_sv1.php',
			'className' => 'tx_sugarmine_sv1',
		)
	);
?>
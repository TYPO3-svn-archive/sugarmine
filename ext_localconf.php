<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

/**
 * Configure the Plugin to call the
 * right combination of Controller and Action according to
 * the user input (default settings, FlexForm, URL etc.)
 */
Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,													// The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
	'SugarMine',												// A unique name of the plugin in UpperCamelCase
	array(														// An array holding the controller-action-combinations that are accessible 
		'Start' => 'index,refresh,logout,test',					// The first controller and its first action will be the default 
		'Account' => 'index,profile,cases,company,test',
		),
	array(														// An array of non-cachable controller-action-combinations (they must already be enabled)
		'Start' => 'index,refresh,logout,test',
		'Account' => 'index,profile,cases,company,test',
		)
);

$subTypes = 'authUserFE,getUserFE';

t3lib_extMgm::addService($_EXTKEY,  'auth' /* sv type */,  'tx_sugarmine_sv1' /* sv key */,
		array(

			'title' => 'SugarMine-Authentication',
			'description' => 'Authenticates SugarMine-Users',

			'subtype' => $subTypes,

			'available' => TRUE,
			'priority' => 100,
			'quality' => 100,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_sugarmine_sv1.php',
			'className' => 'tx_sugarmine_sv1',
		)
	);

?>
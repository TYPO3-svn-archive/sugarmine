<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

/**
 * Configure the Plugin to call the
 * right combination of Controller and Action according to
 * the user input (default settings, FlexForm, URL etc.)
 */
Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,											// The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
	'Pi1',												// A unique name of the plugin in UpperCamelCase
	array(												// An array holding the controller-action-combinations that are accessible 
		'Standard' => 'index,soap,test,auth,login',		// The first controller and its first action will be the default 
		'Service' => 'index,soap,test,auth,login',
		),
	array(												// An array of non-cachable controller-action-combinations (they must already be enabled)
		'Standard' => 'index,soap,test,auth,login',
		'Service' => 'index,soap,test,auth,login',
		)
);

$TYPO3_CONF_VARS['SVCONF']['auth']['tx_sugarmine_sv1']['url'] = 'http://127.0.0.1/sugarce'; // site url to sugarCRM
$TYPO3_CONF_VARS['SVCONF']['auth']['tx_sugarmine_sv1']['user'] = 'admin'; // user name of sugarCRM 
$TYPO3_CONF_VARS['SVCONF']['auth']['tx_sugarmine_sv1']['password'] = 'sugar'; // user password of sugarCRM
$TYPO3_CONF_VARS['SVCONF']['auth']['tx_sugarmine_sv1']['passwordField'] = 'password_c'; // custom password of sugarCRM
$TYPO3_CONF_VARS['SVCONF']['auth']['tx_sugarmine_sv1']['passwordKey'] = 'cedebcbe-5716-4375-7549-4af87afbc989'; // cashed blowfish-key of sugarCRM
$TYPO3_CONF_VARS['SVCONF']['auth']['tx_sugarmine_sv1']['t3DummyUserName'] = 'testpilot'; // user name of an existing TYPO3!!! fe-user

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
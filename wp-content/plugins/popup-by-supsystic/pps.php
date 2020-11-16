<?php
/**
 * Plugin Name: Popup by Supsystic
 * Plugin URI: https://supsystic.com/plugins/popup-plugin/
 * Description: The Best WordPress popup plugin to help you gain more subscribers, social followers or advertisement. Responsive popups with friendly options
 * Version: 1.10.1
 * Author: supsystic.com
 * Author URI: https://supsystic.com
 * Text Domain: popup-by-supsystic
 * Domain Path: /languages
 **/
	/**
	 * Base config constants and functions
	 */
    require_once(dirname(__FILE__). DIRECTORY_SEPARATOR. 'config.php');
    require_once(dirname(__FILE__). DIRECTORY_SEPARATOR. 'functions.php');
	/**
	 * Connect all required core classes
	 */
    importClassPps('dbPps');
    importClassPps('installerPps');
    importClassPps('baseObjectPps');
    importClassPps('modulePps');
    importClassPps('modelPps');
    importClassPps('viewPps');
    importClassPps('controllerPps');
    importClassPps('helperPps');
    importClassPps('dispatcherPps');
    importClassPps('fieldPps');
    importClassPps('tablePps');
    importClassPps('framePps');
	/**
	 * @deprecated since version 1.0.1
	 */
    importClassPps('langPps');
    importClassPps('reqPps');
    importClassPps('uriPps');
    importClassPps('htmlPps');
    importClassPps('responsePps');
    importClassPps('fieldAdapterPps');
    importClassPps('validatorPps');
    importClassPps('errorsPps');
    importClassPps('utilsPps');
    importClassPps('modInstallerPps');
	importClassPps('installerDbUpdaterPps');
	importClassPps('datePps');
	/**
	 * Check plugin version - maybe we need to update database, and check global errors in request
	 */
    installerPps::update();
    errorsPps::init();
    /**
	 * Start application
	 */
    framePps::_()->parseRoute();
    framePps::_()->init();
    framePps::_()->exec();
	
	//var_dump(framePps::_()->getActivationErrors()); exit();

	// Aaaaaaaaand another test for update:)

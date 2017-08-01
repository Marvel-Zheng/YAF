<?php
	date_default_timezone_set('Asia/Chongqing');

	define('APPLICATION_PATH', dirname(__FILE__).'/../');
	define('APP_PATH', dirname(__FILE__).'/../');
	define('ENV', 'DEV');

	ini_set('display_errors','On');

	if(!extension_loaded("yaf")){
		include(APPLICATION_PATH.'/globals/framework/loader.php');
	}

	$application = new Yaf_Application( APPLICATION_PATH. "/conf/application.ini");
	$application->bootstrap()->run();
?>

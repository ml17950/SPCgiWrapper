<?php
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set('display_errors', 1);

	if (file_exists('credentials.php'))
		include_once('credentials.php');
	else
		die('credentials.php not found');
	include_once('sp-cgi-wrapper.php');

	// =========================================================================
	// =========================================================================

	$firewall = new SPCgiWrapper(FIREWALL_IP);

// 	$firewall->debugmode = true;

	// =========================================================================
	// =========================================================================

	if ($firewall->login(FIREWALL_USER, FIREWALL_PASS)) {
// 		$firewall->sendCommand('system', 'info', null);

// 		$firewall->appmgmt('named', 'start');

		$sysinfo = $firewall->sysinfo();
		$firewall->dgbarr($sysinfo);

// 		$appinfo = $firewall->appinfo();
// 		$firewall->dgbarr($appinfo);

// 		$ifaceinfo = $firewall->ifaceinfo();
// 		$firewall->dgbarr($ifaceinfo);

		$firewall->logout();
	}
	else
		die('Login failed');
?>
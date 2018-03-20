<?php
/* Wrapper Class for SecurePoint API (using wget on cli)
 * by ml17950 / March 2018
 * Resources: https://wiki.securepoint.de/api.php
 * Licence GPL-2.0+
*/

if (!defined('FIREWALL_IP'))   { define('FIREWALL_IP', ''); }
if (!defined('FIREWALL_USER')) { define('FIREWALL_USER', ''); }
if (!defined('FIREWALL_PASS')) { define('FIREWALL_PASS', ''); }

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    define('LINUX', false); // aka WINDOWS
else
    define('LINUX', true);

class SPCgiWrapper {
	var $firewall_ip;
	var $sessionid;
	var $response;
	var $debugmode;

	function __construct($firewall_ip) {
		$this->debugmode = false;
		$this->firewall_ip = $firewall_ip;
		$this->sessionid = '';
	}

	function dgbarr(&$arr) {
		echo highlight_string(print_r($arr, true));
	}

	function login($username, $password) {
		if (empty($this->firewall_ip))
			die('firewall_ip not defined');

		$outputfile = 'login.json';
		$cmd = 'wget -q --no-check-certificate -O '.$outputfile.' "--header=Content-Type: application/json" "--post-data={\"module\":\"auth\",\"command\":[\"login\"],\"sessionid\":\"\",\"arguments\":{\"user\":\"'.$username.'\",\"pass\":\"'.$password.'\"}}" https://'.$this->firewall_ip.':11115/spcgi.cgi';

// 		if ($this->debugmode)
// 			echo "<pre>C: ",$cmd,"</pre>";

		exec($cmd);
		$response = file_get_contents($outputfile);
		unlink($outputfile);

		$this->response = json_decode($response, true);

// 		if ($this->debugmode)
// 			echo highlight_string(print_r($this->response, true));

		$this->sessionid = $this->response['sessionid'];

		if (!empty($this->sessionid))
			return true;
		else
			return false;
	}

	function logout() {
		if (empty($this->firewall_ip))
			die('firewall_ip not defined');
		if (empty($this->sessionid))
			die('sessionid not defined / not logged in');

		$outputfile = 'logout.json';
		$cmd = 'wget -q --no-check-certificate -O '.$outputfile.' "--header=Content-Type: application/json" "--post-data={\"module\":\"auth\",\"command\":[\"logout\"],\"sessionid\":\"'.$this->sessionid.'\"}" https://'.$this->firewall_ip.':11115/spcgi.cgi';

// 		if ($this->debugmode)
// 			echo "<pre>C: ",$cmd,"</pre>";

		exec($cmd);
		$response = file_get_contents($outputfile);
		unlink($outputfile);

		$this->response = json_decode($response, true);

// 		if ($this->debugmode)
// 			echo highlight_string(print_r($this->response, true));

		$this->sessionid = $this->response['sessionid'];

		if (!empty($this->sessionid))
			return true;
		else
			return false;
	}

	function sendCommand($module, $command, $arguments) {
		if (empty($this->firewall_ip))
			die('firewall_ip not defined');
		if (empty($this->sessionid))
			die('sessionid not defined / not logged in');

		$outputfile = 'response.json';

        $data['module'] = $module;
        if (is_array($command))
        	$data['command'] = $command;
        else
        	$data['command'] = array($command);
        if (!is_null($arguments)) {
        	$data['arguments'] = $arguments;
        }
        $data['sessionid'] = $this->sessionid;

        $json = json_encode($data);

        if ($this->debugmode)
        	echo "<hr><pre>J: ",$json,"</pre>";

		$outputfile = 'cmd'.time().'.json';
		$cmd = 'wget -q --no-check-certificate -O '.$outputfile.' "--header=Content-Type: application/json" "--post-data='.addcslashes($json, '"').'" https://'.$this->firewall_ip.':11115/spcgi.cgi';

		if ($this->debugmode)
			echo "<pre>C: ",$cmd,"</pre>";

		exec($cmd);
		$response = file_get_contents($outputfile);
		unlink($outputfile);

		$this->response = json_decode($response, true);

		if ($this->debugmode)
			echo highlight_string(print_r($this->response, true));

		if ($this->response['result']['status'] == 'OK')
			return true;
		else
			return false;
	}

	// $appname	= named, pop3_proxy, ...
	// $action	= start, stop, restart
	function appmgmt($appname, $action) {
		switch ($action) {
			case 'start':
			case 'stop':
			case 'restart':
				return $this->sendCommand('appmgmt', $action, array('application'=>$appname));
				break;

			default:
				return false;
		}
	}

	function sysinfo() {
		if ($this->sendCommand('system', 'info', null)) {
			$retarr = array();

			foreach ($this->response['result']['content'] as $id => $data) {
				$retarr[$data['attribute']] = $data['value'];
			}

			return $retarr;
		}
		else
			return array();
	}

	function appinfo() {
		if ($this->sendCommand('appmgmt', 'status', null)) {
			$retarr = array();

			foreach ($this->response['result']['content'] as $id => $data) {
				$retarr[$data['application']] = $data['state'];
			}

			return $retarr;
		}
		else
			return array();
	}

	function ifaceinfo() {
		if ($this->sendCommand('interface', 'get', null)) {
			$retarr = array();

			foreach ($this->response['result']['content'] as $id => $data) {
				$retarr[$data['name']] = $data['state'];
			}

			return $retarr;
		}
		else
			return array();
	}
}
?>
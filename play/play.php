<?php
set_time_limit(0);
date_default_timezone_set('Europe/Amsterdam');
error_reporting(E_ALL | E_STRICT);
ini_set('memory_limit', '256M');
ini_set('magic_quotes_runtime', 0);

if (!function_exists('pr')) {
	function pr($arr) {
		if (php_sapi_name() !=='cli') {
			echo '<pre>'."\n";
		}
		if (is_array($arr) && count($arr)) {
			print_r($arr);
		} else {
			var_dump($arr);
		}
		if (php_sapi_name() !=='cli') {
			echo '</pre>';
		}

		echo "\n";
	}
}
if (!function_exists('prd')) {
	function prd($arr) {
		pr($arr);
		die();
	}
}

define('DIR_PLAY_ROOT', dirname(__FILE__));
require_once dirname(dirname(__FILE__)) .'/TrueApi.php';

class Play {

	public function  __construct() {
		$employee = false;
		if ($employee) {
			$this->TrueApi = new TrueApi(array(
				'log-print-level' => 'debug',
				'verifySSL' => false,
				'service' => 'http://admin.true.kev/cakephp/',
			));
			$this->TrueApi->auth('munin',
				file_get_contents(DIR_PLAY_ROOT.'/pw'),
				file_get_contents(DIR_PLAY_ROOT.'/apikey'),
				'Employee'
			); // , 'Employee'
		} else {
			$this->TrueApi = new TrueApi(array(
				'log-print-level' => 'debug',
				'verifySSL' => false,
				'format' => 'json',
				'service' => 'http://care.true.kev/',
				'meta' => array(
					'dryrun' => true,
				),
			));

			$this->TrueApi->auth('1823',
				file_get_contents(DIR_PLAY_ROOT.'/pw_cust'),
				file_get_contents(DIR_PLAY_ROOT.'/apikey_cust'),
				'Customer'
			); // , 'Customer'
		}

	}

	public function ips () {
		$a = $this->TrueApi->getIPs();

		prd($a);
	}
	public function highlander () {
		$a = $this->TrueApi->VmHosts->get_schedule('kvm04.true.nl');

		prd(compact('records', 'a'));
	}

	public function dns () {
//        $this->TrueApi->DnsRecords->edit(12873834, array(
//            'content' => 'www3.google.com',
//        ));

		$records = $this->TrueApi->DnsRecords->index('ladolcevilla.nl');
//
//        $a = $this->TrueApi->DnsRecords->add(array(
//            'name' => 'piet.ladolcevilla.nl',
//            'type' => 'CNAME',
//            'content' => 'www3.google.com',
//        ));

		prd(compact('records', 'a'));

	}

	public function servers () {
		$y = $this->TrueApi->Servers->edit(2862, array('color' => 'gray', 'os_serial' => 'x'));
		die();
		$this->TrueApi->Servers->apiBuffer(true);
		$w = $this->TrueApi->Servers->add(array('color' => 'gray', 'os_serial' => 'x'));
		$y = $this->TrueApi->Servers->edit(2313, array('color' => 'gray', 'os_serial' => 'x'));
		$z = $this->TrueApi->Servers->apiBuffer('flush');
		pr(compact('w', 'x', 'y', 'z'));

		die();
		$x = $this->TrueApi->Servers->edit(2862, array('relatie_id' => 1378));
		die();
		$servers = $this->TrueApi->Servers->index();
		print_r($servers);
	}

	public function main () {
		return $this->servers();
		die();

		return $this->dns();
		die();

		return $this->ips();
		die();

		return $this->highlander();
		die();


		$x = $this->TrueApi->rest('get', 'servers/index');

		prd(compact('x'));

		die();
		$x = $this->TrueApi->MonitoringServices->snapshot();

		prd(compact('x'));

		die();


		$x = $this->TrueApi->PharosNotifications->store(array(
			1 => array(
				'pharos_data_id' => '567101645',
				'relatie_id' => '1378',
				'server_id' => '3736',
				'port' => 'ssh',
				'status' => 'down',
				'sent' => '6',
				'mstamp' => '2009-11-20 13:31:14',
				'stamp' => '2009-11-20 13:31:20',
			),
			2 => array(
				'pharos_data_id' => '567101645',
				'relatie_id' => '1378',
				'server_id' => '3736',
				'port' => 'ssh',
				'status' => 'down',
				'sent' => '6',
				'mstamp' => '2009-11-20 13:31:14',
				'stamp' => '2009-11-20 13:31:20',
			),
		));
		prd($x);

		die();
		$servers = $this->TrueApi->Servers->index();
		print_r($servers);

		die();

		$this->TrueApi->opt(array(
			'returnData' => false,
			'format' => 'json',
			'buffer' => true,
		));

		$x = $this->TrueApi->ConfigFiles->view('munin');
		prd($x);

//
//        $x = $this->TrueApi->ApiControllers->index();
//        #prd($x);
//        die()


		die();



		#$w = $this->TrueApi->Servers->index();


		prd(compact('x', 'w'));





		die();

		$x = $this->TrueApi->PharosNotifications->store(array(
			1 => array(
				'pharos_data_id' => '567101645',
				'relatie_id' => '1378',
				'server_id' => '3736',
				'port' => 'ssh',
				'status' => 'down',
				'sent' => '6',
				'mstamp' => '2009-11-20 13:31:14',
				'stamp' => '2009-11-20 13:31:20',
			),
			2 => array(
				'pharos_data_id' => '567101645',
				'relatie_id' => '1378',
				'server_id' => '3736',
				'port' => 'ssh',
				'status' => 'down',
				'sent' => '6',
				'mstamp' => '2009-11-20 13:31:14',
				'stamp' => '2009-11-20 13:31:20',
			),
		));
		prd($x);

		prd($this->TrueApi->rest('put', 'servers/edit/2313', array('color' => 'black')));

		$feedback = array();
		if (false !== ($response = $this->TrueApi->Servers->edit(2313, array('color' => 'gray', 'os_serial' => 'x')))) {
			$feedback[] = $response;
		}
		if (false !== ($response = $this->TrueApi->Servers->view(2313))) {
			$feedback[] = $response;
		}
	//        if (false !== ($response = $this->TrueApi->Servers->monitored())) {
	//            $feedback[] = $response;
	//        }

		return $feedback;
	}

}

$Play = new Play();
prd($Play->main());

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

class Docs {
	public function main () {
		$Api = new TrueApi(array(
			'log-print-level' => 'info',
			'verifySSL' => false,
			'format' => 'json',
			'service' => 'https://care.true.nl/',
			'meta' => array(
				'dryrun' => true,
			),
		));

		$Api->auth('1823',
			file_get_contents(DIR_PLAY_ROOT.'/pw_cust'),
			file_get_contents(DIR_PLAY_ROOT.'/apikey_cust'),
			'Customer'
		);
		$docs = $this->_docs($Api);
		$docs = 'Currently exposed features: ' . PHP_EOL . $docs;
		$file = dirname(DIR_PLAY_ROOT) . '/customer-features.md';
		file_put_contents($file, $docs);
		$Api->info('Written %s', $file);
		die();

		$Api = new TrueApi(array(
			'log-print-level' => 'info',
			'verifySSL' => false,
			'service' => 'https://admin.true.nl/cakephp/',
			'meta' => array(
				'dryrun' => true,
			),
		));
		$Api->auth('munin',
			file_get_contents(DIR_PLAY_ROOT.'/pw'),
			file_get_contents(DIR_PLAY_ROOT.'/apikey'),
			'Employee'
		);
		$docs = $this->_docs($Api);
		prd($docs);
	}
	public function _docs ($Api) {
		$incr   = 2;
		$indent = 4;

		$buf  = sprintf('' . PHP_EOL);
		$buf .= sprintf('%s%s' . PHP_EOL, str_repeat(' ', $indent), 'https://care.true.nl');
		$indent += $incr;
		foreach ($Api->controllers as $name => $actions) {
			$underscore = $Api->underscore($name);
			$buf .= sprintf('%s/%s' . PHP_EOL, str_repeat(' ', $indent), $underscore);
			$indent += $incr;
			foreach ($actions as $action => $props) {
				$buf .= sprintf('%s/%s (method: %s)' . PHP_EOL, str_repeat(' ', $indent), $action, strtoupper(@$props['method']));
				unset($props['method']);
				$indent += $incr;

				if (($id = @$props['id'])) {
					$buf .= sprintf('%s/%s' . PHP_EOL, str_repeat(' ', $indent), '<id>');
					$indent += $incr;
					unset($props['id']);
				}
				if (($scope = @$props['scopeVar'])) {
					$buf .= sprintf('%s[/%s:%s]' . PHP_EOL, str_repeat(' ', $indent), $scope, '<' .$scope . '>');
					$indent += $incr;
					unset($props['scopeVar']);
				}

				foreach ($props as $key => $val) {
					if ($key === 'id') {

					} else {
						$buf .= sprintf('%s%s: %s' . PHP_EOL, str_repeat(' ', $indent), $key, $val);
					}
				}

				if ($id) {
					$indent -= $incr;
				}
				if ($scope) {
					$indent -= $incr;
				}
				$indent -= $incr;
			}
			$buf .= sprintf('' . PHP_EOL);
			$indent -= $incr;
		}
		$indent -= $incr;

		return $buf;
	}
}

$Docs = new Docs();
$Docs->main();

<?php
if (!defined('DIR_TRUEAPI_ROOT')) {
	define('DIR_TRUEAPI_ROOT', dirname(__FILE__));
}
if (!defined('DIR_RESTCLIENT_ROOT')) {
	if (is_dir('/home/kevin/workspace/rest_client')) {
		define('DIR_RESTCLIENT_ROOT', '/home/kevin/workspace/rest_client');
	} else {
		define('DIR_RESTCLIENT_ROOT', DIR_TRUEAPI_ROOT."/vendors/rest_client");
	}
}
if (!defined('DIR_EGGSHELL_ROOT')) {
	if (file_exists('/home/kevin/workspace/eggshell/Base.php')) {
		define('DIR_EGGSHELL_ROOT', '/home/kevin/workspace/eggshell');
	} else {
		define('DIR_EGGSHELL_ROOT', DIR_TRUEAPI_ROOT."/vendors/eggshell");
	}
}

require_once DIR_EGGSHELL_ROOT.'/Base.php';
require_once DIR_TRUEAPI_ROOT.'/TrueApiController.php';
require_once DIR_TRUEAPI_ROOT.'/libs/BluntXml.php';
require_once DIR_RESTCLIENT_ROOT.'/RestClient.php';

/**
 * Generic interface to True's API
 *
 * @link http://www.truecare.nl
 * @link http://github.com/true/true-api
 *
 * @author kvz
 */
class TrueApi extends Base {
	public    $BluntXml;
	public    $RestClient     = false;
	public    $controllers    = array();

	protected $_apiApp        = 'True Api';
	protected $_apiVer        = '0.3';
	protected $_apiUrl        = 'http://github.com/true/true-api/raw/master/TrueApi.php';

	protected $_authorization = array();
	protected $_response = array();

	protected $_options = array(
		'service' => 'https://care.true.nl/',
		'format' => 'json',
		'verifySSL' => true,
		'returnData' => false,
		'fetchControllers' => true,
		'checkVersion' => true,
		'checkTime' => 600, // 10 minutes
		'meta' => array(
		),
		'metaCallback' => null,

		'log-date-format' => 'Y-m-d H:i:s',
		'log-file' => '/var/log/true-api.log',
		'log-break-level' => 'crit',
		'app-root' => DIR_TRUEAPI_ROOT,
		'class-autobind' => true,
		'class-autosetup' => true,
	);

	public function __get ($name) {
		if (!isset($this->controllers[$name])) {
			if (empty($this->controllers)) {
				$this->crit(
					'Controller: %s not loaded. Looks like you need to call ' .
					'->auth() first which will automatically call buildControllers.',
					$name
				);
			} else {
				$this->crit(
					'Controller: %s not loaded. It either doesn\'t exist or ' .
					'you don\'t have access.',
					$name
				);
			}
		}
	}

	/**
	 * Loads a remote list of controllers and sets them up as
	 * child objects for easy interaction.
	 *
	 * @return boolean
	 */
	public function buildControllers () {
		$this->ApiControllers = new TrueApiController(
			'api_controllers',
			array('index' => array()),
			array($this, 'rest')
		);

		$this->debug('Retrieving possible controllers');
		$response = $this->ApiControllers->index();

		if ($this->opt('checkVersion')) {
			$remoteVersion = $this->find(
				$this->_response,
				'version',
				'meta'
			);

			$compare = version_compare($this->_apiVer, $remoteVersion);
			if ($compare != 0) {
				$this->warning(
					'Your version %s is %s than the server\'s %s',
					$this->_apiVer,
					$compare < 0 ? 'lower' : 'higher',
					$remoteVersion
				);
			}
		}

		$remoteTime = $this->find(
			$this->_response,
			'time_epoch',
			'meta'
		);
		$diff = gmdate('U') - $remoteTime;
		$this->debug('Time difference of %s second(s) with server', $diff);

		if (abs($diff) > $this->opt('checkTime')) {
			$this->crit('Time difference exceeds limit of %s seconds', $this->opt('checkTime'));
		}

		$this->controllers = $this->find(
			$this->_response,
			'controllers'
		);

		if (!$this->controllers) {
			return $this->err('Unable to fetch controllers');
		}

		foreach ($this->controllers as $controller => $actions) {
			if (is_numeric($controller) || !is_array($actions)) {
				return $this->crit(
					'Invalid controller formatting. Please upgrade your API client'
				);
			}

			$underscore = $this->underscore($controller);
			$class      = $this->camelize($underscore);
			if (isset($this->{$class}) && is_object($this->{$class})) {
				continue;
			}
			$this->{$class} = new TrueApiController(
				$underscore,
				$actions,
				array($this, 'rest')
			);
		}

		return true;
	}

	/**
	 * Finds the data with in an array. Optionally narrow down to
	 * one key in that data.
	 *
	 * @param array  $data
	 * @param mixed null or string $key
	 *
	 * @return array
	 */
	public function find ($data, $key = null, $parent = 'data') {
		if (isset($data[$parent])) {
			$data = $data[$parent];
		}

		if ($key !== null) {
			if (isset($data[$key])) {
				$data = $data[$key];
			}
		}

		return $data;
	}

	/**
	 * Set authentication & loads controllers
	 *
	 * @param <type> $username Your truecare username
	 * @param <type> $password Your truecare password
	 * @param <type> $apikey   Your personal API Key. Request one at truecare.nl
	 * @param <type> $class    Optional. Your user class. Probably: Customer
	 *
	 * @return <type>
	 */
	public function auth ($username, $password, $apikey, $class = null) {
		$username = trim($username);
		$password = trim($password);
		$apikey   = trim($apikey);
		$class    = trim($class);

		if (!$class) {
			$class = 'Customer';
		}

		$query = http_build_query(compact(
			'username',
			'password',
			'apikey',
			'class'
		));
		$this->_authorization = sprintf('TRUEREST %s', $query);

		if ($this->opt('fetchControllers')) {
			$this->buildControllers();
		}

		// easy for testing:
		return $this->_authorization;
	}

	protected function _badResponse ($dump = '', $reason = 'no reason') {
		return $this->crit(
			'Invalid response from server: %s. Raw dump: %s',
			$reason,
			"\n\n" . $this->indent($dump) . "\n\n"
		);
	}

	/**
	 * Get a machine's IPs
	 *
	 * @return mixed boolean on failure, string on $first == true, or array
	 */
	public function getIPs ($first = false, $ipv6 = true) {
		$cmd = '/sbin/ifconfig';
		if (!file_exists($cmd)) {
			return false;
		}

		$ifconfig = `${cmd}`;
		if (!preg_match_all('/inet' . ($ipv6 ? '6?' : '') . ' addr: ?([^ ]+)/', $ifconfig, $ips)) {
			return false;
		}
		if ($first === true) {
			return $ips[1][0];
		}
		return $ips[1];
	}

	public function response ($parsed) {
		if (is_array(@$parsed['meta']['feedback'])) {
			$fail = false;
			foreach ($parsed['meta']['feedback'] as $feedback) {
				$this->log($feedback['level'], 'server-side: %s', $feedback['message']);
				if ($feedback['level'] === 'error') {
					$fail = true;
				}
			}
			if ($fail) {
				return $this->crit('Can\'t continue after this');
			}
		}

		if ($parsed['meta']['status'] === 'error') {
			return false;
		}

		if (!is_array(@$parsed['data'])) {
			return $this->_badResponse($parsed, 'No data');
		}

		$this->_response = $parsed;

		if ($this->opt('returnData')) {
			return $parsed['data'];
		}

		return $parsed;
	}

	public function preParse ($curlResponse) {
		if (empty($curlResponse)) {
			// Should be handled by next step in ->rest()
			return $curlResponse;
		}

		if (!isset($curlResponse->body)) {
			return $this->_badResponse(
				$curlResponse,
				'No body in curl response'
			);
		}

		if ($curlResponse->body === '') {
			if ($this->RestClient()->error) {
				return $this->crit($this->RestClient()->error);
			}

			return $this->_badResponse(
				$curlResponse,
				'Empty body in curl response'
			);
		}

		return $curlResponse->body;
	}

	public function parseJson ($curlResponse) {
		if (false === ($body = $this->preParse($curlResponse))) {
			return false;
		}

		if (!is_array(($response = json_decode($curlResponse->body, true)))) {
			return $this->_badResponse(
				$curlResponse->body,
				'json parse error'
			);

		}

		return $response;
	}

	public function parseXml ($curlResponse) {
		if (false === ($body = $this->preParse($curlResponse))) {
			return false;
		}

		$response = $this->BluntXml->decode($curlResponse->body);
		if (false === ($response)) {
			return $this->_badResponse($curlResponse->body, 'XML parse error');
		}

		return $response;
	}

	public function RestClient() {
		// Permanent setup
		if (!$this->RestClient) {
			$restOpts = array(
				'verifySSL' => $this->opt('verifySSL'),
				'cookieFile' => false,
				'userAgent' => sprintf(
					'%s v%s',
					$this->_apiApp,
					$this->_apiVer
				),
			);
			$this->RestClient = new RestClient(false, false, $restOpts);
			$this->RestClient->add_response_type(
				'json',
				array($this, 'parseJson'),
				'.json'
			);
			$this->RestClient->add_response_type(
				'xml',
				array($this, 'parseXml'),
				'.xml'
			);
		}
		return $this->RestClient;
	}

	public function meta ($params) {
		$meta = $this->opt('meta');
		return $meta;
	}

	public function rest ($method, $path, $vars = array()) {
		$this->_response = array();

		// Validate
		if (!method_exists($this->RestClient(), $method)) {
			return $this->err('Rest method "%s" does not exist.', $method);
		}
		if (empty($this->_authorization)) {
			return $this->err('You need to set proper authentication first.');
		}

		// Dynamic options
		$this->RestClient()->headers('Authorization', $this->_authorization);
		$this->RestClient()->set_response_type($this->opt('format'));
		$this->RestClient()->request_prefix = $this->opt('service');
		$this->RestClient()->request_suffix = '.'.$this->opt('format');

		// Wrap any data in the data var
		$payload = array();
		if (!empty($vars)) {
			$payload['data'] = $vars;
		}

		if (null === ($cb = $this->opt('metaCallback'))) {
			$cb = array($this, 'meta');
		}

		if (!is_callable($cb)) {
			return $this->err('Invalid meta callback: %s', $cb);
		}
		$payload['meta'] = call_user_func($cb, array(
			'method' => $method,
			'path' => $path,
			'vars' => $vars,
		));
		$this->debug('requesting path: %s', $path);

		// Make the call
		$parsed = call_user_func(
			array($this->RestClient(), $method),
			$path,
			$payload
		);


		if ($parsed) {
			$response = $this->response($parsed);
		}

		if (!$parsed && $this->RestClient()->error) {
			return $this->crit($this->RestClient()->error);
		}
		if (!$parsed) {
			return $this->err('a parse error occured');
		}
		if ($this->RestClient()->error) {
			return $this->crit($this->RestClient()->error);
		}

		return $response;
	}
}

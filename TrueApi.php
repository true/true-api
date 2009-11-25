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
require_once DIR_TRUEAPI_ROOT.'/Xml.php';
require_once DIR_RESTCLIENT_ROOT.'/RestClient.php';

class TrueApi extends Base {
    public    $Xml;
    public    $RestClient     = false;
    protected $_apiApp        = 'True Api';
    protected $_apiVer        = '0.1';
    protected $_authorization = array();
    public    $controllers    = array(
    );
    protected $_options     = array(
        'apiService' => 'http://www.truecare.dev/cakephp/',
        'apiFormat' => 'json',
        'returnData' => false,
        'fetchControllers' => true,

        'log-file' => '/var/log/true-api.log',
        'log-break-level' => 'crit',
        'app-root' => DIR_TRUEAPI_ROOT,
        'class-autobind' => true,
        'class-autosetup' => true,
    );

    /**
     * Loads a remote list of controllers and sets them up as
     * child objects for easy interaction.
     *
     * @return boolean
     */
    public function buildControllers() {
        $this->ApiControllers = new TrueApiController('api_controllers',
            array($this, 'rest'));

        $this->debug('Retrieving possible controllers');
        $this->controllers = $this->data($this->ApiControllers->index(),
            'controllers');

        if (!$this->controllers) {
            return $this->err('Unable to fetch controllers');
        }

        foreach ($this->controllers as $controller) {
            $underscore = $this->underscore($controller);
            $class = $this->camelize($underscore);
            if (isset($this->{$class}) && is_object($this->{$class})) {
                continue;
            }
            $this->{$class} = new TrueApiController($underscore,
                array($this, 'rest'));
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
    public function data($data, $key = null) {
        if (isset($data['data'])) {
            $data = $data['data'];
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
    public function auth($username, $password, $apikey, $class = 'Customer') {
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

    protected function _badResponse($dump = '', $reason = 'no reason') {
        $this->debug('Received invalid response: %s', $dump);
        return $this->err('Invalid response from server: %s', $reason);
    }
    
    public function response($parsed) {
        if (!is_array(@$parsed['meta']['feedback'])) {
            return $this->_badResponse($parsed, 'No feedback array');
        }
        if (!is_array(@$parsed['data'])) {
            return $this->_badResponse($parsed, 'No data');
        }
        
        $fail = false;
        foreach ($parsed['meta']['feedback'] as $feedback) {
            if ($feedback['level'] === 'error') {
                $fail = true;
                $this->err('Server said: %s', $feedback['message']);
            }
        }
        if ($fail) {
            return $this->crit('Can\'t continue after this');
        }

        if ($parsed['meta']['status'] === 'error') {
            return false;
        }

        if ($this->opt('returnData')) {
            return $parsed['data'];
        }
        
        return $parsed;
    }

    public function preParse($curlResponse) {
        if (empty($curlResponse)) {
            // Should be handled by next step in ->rest()
            return $curlResponse;
        }

        if (!isset($curlResponse->body)) {
            return $this->_badResponse($curlResponse,
                'No body in curl response');
        }

        if ($curlResponse->body === '') {
            return $this->_badResponse($curlResponse,
                'Empty body in curl response');
        }

        return $curlResponse->body;
    }

    public function parseJson($curlResponse) {
        if (false === ($body = $this->preParse($curlResponse))) {
            return false;
        }

        if (!is_array(($response = json_decode($curlResponse->body, true)))) {
            return $this->_badResponse($curlResponse->body,
                'json parse error');

        }

        return $response;
    }

    public function parseXml($curlResponse) {
        if (false === ($body = $this->preParse($curlResponse))) {
            return false;
        }
        
        // @todo: A working Unserialize XML:
        if (false === ($response = $this->Xml->parse($curlResponse->body))) {
            return $this->_badResponse($curlResponse, 'XML parse error');
        }
        
        return $response;
    }
    
    public function rest($method, $path, $vars) {
        // Permanent setup
        if (!$this->RestClient) {
            $restOpts = array(
                'cookieFile' => false,
                'userAgent' => sprintf('%s v%s',
                    $this->_apiApp, $this->_apiVer),
            );
            $this->RestClient = new RestClient(false, false, $restOpts);
            $this->RestClient->add_response_type('json',
                array($this, 'parseJson'), '.json');
            $this->RestClient->add_response_type('xml',
                array($this, 'parseXml'), '.xml');
        }

        // Validate
        if (!method_exists($this->RestClient, $method)) {
            return $this->err('Rest method "%s" does not exist.', $method);
        }
        if (empty($this->_authorization)) {
            return $this->err('You need to set proper authentication first.');
        }

        // Dynamic options
        if (strtolower($this->opt('apiFormat')) == 'xml') {
            return $this->err('XML Not yet supported');
        }
        $this->RestClient->headers('Authorization', $this->_authorization);
        $this->RestClient->set_response_type($this->opt('apiFormat'));
        $this->RestClient->request_prefix = $this->opt('apiService');
        $this->RestClient->request_suffix = '.'.$this->opt('apiFormat');

        // Wrap any data in the data var
        if (!empty($vars)) {
            $vars = array('data' => $vars);
        }
        
        $this->debug('requesting path: %s', $path);

        // Make the call
        $parsed = call_user_func(array($this->RestClient, $method),
            $path, $vars);

        if (!empty($this->RestClient->error)) {
            return $this->crit($this->RestClient->error);
        }

        if (false === $parsed) {
            return $this->err('a parse error occured');
        }

        // Return response
        return $this->response($parsed);
    }
}
?>
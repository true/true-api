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
        'servers',
        'dns_domains',
    );
    protected $_options     = array(
        'apiService' => 'http://admin.true.dev/cakephp/',
        'apiFormat' => 'json',
        'returnData' => false,

        'log-file' => '/var/log/true-api.log',
        'log-break-level' => 'crit',
        'app-root' => DIR_TRUEAPI_ROOT,
        'class-autobind' => true,
        'class-autosetup' => true,
    );

    public function __setup() {
        foreach ($this->controllers as $controller) {
            $model = $this->classify($controller);
            $this->{$model} = new TrueApiController($controller, array($this, 'rest'));
        }
    }

    /**
     * Set authentication
     *
     * @param <type> $username Your truecare username
     * @param <type> $password Your truecare password
     * @param <type> $apikey   Your personal API Key. Request one at truecare.nl
     * @param <type> $class    Optional. Your user class. Probably: Customer
     *
     * @return <type>
     */
    public function auth($username, $password, $apikey, $class = 'Customer') {
        $query       = http_build_query(compact('username', 'password', 'apikey', 'class'));
        return ($this->_authorization = sprintf('TRUEREST %s', $query));
    }

    protected function _invalidResponse($dump = '', $reason = '') {
        if ($reason) $reason = ' .'.$reason;
        $this->debug('Received invalid response%s: %s',$reason, $dump);
        return $this->err('Invalid response from server');
    }

    public function response($parsed) {
        if (!is_array(@$parsed['meta']['feedback'])) {
            return $this->_invalidResponse($parsed, 'No feedback array');
        }
        if (!is_array(@$parsed['data'])) {
            return $this->_invalidResponse($parsed, 'No data');
        }
        
        foreach ($parsed['meta']['feedback'] as $feedback) {
            if ($feedback['level'] === 'error') {
                $this->warning('Server said: %s', $feedback['message']);
            }
        }

        if ($parsed['meta']['status'] === 'error') {
            return false;
        }

        if ($this->opt('returnData')) {
            return $parsed['data'];
        }
        
        return $parsed;
    }
    
    public function parseJson($curlResponse) {
        if (!isset($curlResponse->body)) {
            return $this->_invalidResponse($curlResponse, 'No body in curl response');
        }

        if (false === ($response = json_decode($curlResponse->body, true))) {
            return $this->_invalidResponse($curlResponse, 'json parse error');
        }

        return $response;
    }

    public function parseXml($curlResponse) {
        if (!isset($curlResponse->body)) {
            return $this->_invalidResponse($curlResponse, 'No body in curl response');
        }
        
        // @todo: A working Unserialize XML:
        if (false === ($response = $this->Xml->parse($curlResponse->body))) {
            return $this->_invalidResponse($curlResponse, 'XML parse error');
        }
        
        return $response;
    }
    
    public function rest($method, $path, $vars) {
        // Permanent setup
        if (!$this->RestClient) {
            $restOpts = array(
                'userAgent' => sprintf('%s v%s', $this->_apiApp, $this->_apiVer),
            );
            $this->RestClient = new RestClient(false, false, $restOpts);
            $this->RestClient->add_response_type('json', array($this, 'parseJson'), '.json');
            $this->RestClient->add_response_type('xml', array($this, 'parseXml'), '.xml');
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

        // Make the call
        $parsed = call_user_func(array($this->RestClient, $method), $path, $vars);

        // Return response
        return $this->response($parsed);
    }
}
?>
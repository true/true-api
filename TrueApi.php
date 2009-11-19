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
    public    $RestClient   = false;
    public    $Xml;
    protected $_apiApp      = 'True Api';
    protected $_apiVer      = '0.1';
    protected $_auth        = array();
    protected $_controllers = array(
        'servers',
        'dns_domains',
    );
    protected $_options     = array(
        'apiService' => 'http://admin.true.dev/cakephp/',
        'apiFormat' => 'json',
        'returnData' => false,

        'log-file' => '/var/log/true-api.log',
        'app-root' => DIR_TRUEAPI_ROOT,
        'class-autobind' => true,
        'class-autosetup' => true,
    );

    public function __setup() {
        foreach ($this->_controllers as $controller) {
            $model = $this->classify($controller);
            $this->{$model} = new TrueApiController($controller, $this);
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
        $this->_auth = compact('username', 'password', 'apikey', 'class');
        return true;
    }

    protected function _invalidResponse($dump = '') {
        $this->debug('Received invalid response: %s', $dump);
        $this->err('Invalid response from server');

        return false;
    }

    protected function _handleResponse($response) {
        if (!is_array(@$response['meta']['feedback'])) {
            return $this->_invalidResponse($response);
        }
        if (!is_array(@$response['data'])) {
            return $this->_invalidResponse($response);
        }
        
        foreach ($response['meta']['feedback'] as $feedback) {
            if ($feedback['level'] === 'error') {
                $this->warning('Server said: %s', $feedback['message']);
            }
        }

        if ($response['meta']['status'] === 'error') {
            return false;
        }

        if ($this->opt('returnData')) {
            return $response['data'];
        }

        return $response;
    }
    
    public function parseJson($curlResponse) {
        if (!isset($curlResponse->body)) {
            return $this->_invalidResponse($curlResponse);
        }
        
        $body     = $curlResponse->body;
        $response = json_decode($body, true);

        return $this->_handleResponse($response);
    }

    public function parseXml($curlResponse) {
        if (!isset($curlResponse->body)) {
            return $this->_invalidResponse($curlResponse);
        }
        $body = $curlResponse->body;

        // @todo: A better Unserialize XML:
        if (!($response = $this->Xml->parse($body))) {
            return $this->_invalidResponse($curlResponse);
        }
        
        return $this->_handleResponse($response);
    }

    /**
     * Pass unmatched calls through to RestClient
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    public function  __call($name,  $args) {
        return $this->_request($name, $args);
    }

    protected function _request($name, $args) {
        // Permanent options
        if (!$this->RestClient) {
            $restOpts = array(
                'userAgent' => sprintf('%s v%s', $this->_apiApp, $this->_apiVer),
            );
            $this->RestClient = new RestClient(false, false, $restOpts);
            $this->RestClient->add_response_type('json', array($this, 'parseJson'), '.json');
            $this->RestClient->add_response_type('xml', array($this, 'parseXml'), '.xml');
        }

        // Dynamic options
        if ($this->opt('apiFormat') == 'xml') {
            $this->err('XML Not yet supported');
        }
        $this->RestClient->set_response_type($this->opt('apiFormat'));
        $this->RestClient->request_prefix = $this->opt('apiService');
        $this->RestClient->request_suffix = '.'.$this->opt('apiFormat');

        // Make the call
        if (!method_exists($this->RestClient, $name)) {
            return $this->err('Method %s does not exist.', $name);
        }
        if (empty($this->_auth)) {
            return $this->err('You need to set proper authentication first with ->auth().');
        }

        $query       = http_build_query($this->_auth);
        $this->RestClient->headers('Authorization', sprintf('TRUEREST %s', $query));
        
        return call_user_func_array(array($this->RestClient, $name),
            $args);
    }
}
?>
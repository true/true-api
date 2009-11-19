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
require_once DIR_TRUEAPI_ROOT.'/TrueApiModel.php';
require_once DIR_RESTCLIENT_ROOT.'/RestClient.php';

class TrueApi extends Base {
    public    $RestClient   = false;
    protected $_apiApp      = 'True Api';
    protected $_apiVer      = '0.1';
    protected $_auth        = array();
    protected $_controllers = array(
        'servers',
        'dns_domains',
    );
    protected $_options     = array(
        'apiUrl' => 'http://admin.true.dev/cakephp/',
        'apiExt' => '.json',

        'log-file' => '/var/log/true-api.log',
        'app-root' => DIR_TRUEAPI_ROOT,
        'class-autobind' => true,
        'class-autosetup' => true,
    );

    public function __setup() {
        $restOpts = array(
            'userAgent' => sprintf('%s %s', $this->_apiApp, $this->_apiVer),
        );

        $this->RestClient = new RestClient($this->_options['apiUrl'], $this->_options['apiExt'], $restOpts);
        $this->RestClient->add_response_type('json', array(get_class($this), 'parseJson'), '.json');
        $this->RestClient->add_response_type('xml', array(get_class($this), 'parseXml'), '.xml');
        $this->RestClient->set_response_type('json');

        foreach ($this->_controllers as $controller) {
            $model = $this->classify($controller);
            $this->{$model} = new TrueApiModel($controller, $this);
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
        $query       = http_build_query($this->_auth);
        $this->RestClient->headers('Authorization', sprintf('TRUEREST %s', $query));
        return true;
    }
    
    public function parseJson($curl_response) {
    
        prd(compact('curl_response'));

        return json_decode($curl_response, true);
    }

    public function parseXml($curl_response) {
        return $curl_response;
    }

    /**
     * Pass unmatched calls through to RestClient
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function  __call($name,  $arguments) {
        if (method_exists($this->RestClient, $name)) {
            if (empty($this->_auth)) {
                return $this->err('You need to set proper authentication first.');
            }

            return call_user_func_array(array($this->RestClient, $name),
                $arguments);
        }
    }
}
?>
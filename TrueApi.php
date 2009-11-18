<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


if (!defined('DIR_TRUEAPI')) {
    define('DIR_TRUEAPI', dirname(__FILE__));
}


if (!defined('DIR_RESTCLIENT_ROOT')) {
	if (is_dir('/home/kevin/workspace/rest_client')) {
		define('DIR_RESTCLIENT_ROOT', '/home/kevin/workspace/rest_client');
	} else {
		define('DIR_RESTCLIENT_ROOT', DIR_TRUEAPI."/vendors/rest_client");
	}
}

require_once DIR_RESTCLIENT_ROOT.'/RestClient.php';

class TrueApi extends RestClient {
    public $request_prefix = 'http://admin.true.dev/';
    protected $_controllers = array(
        'servers',
        'dns_domains',
    );

    public static function classify ($lowerCaseAndUnderscoredWord) {
        return str_replace(" ", "", ucwords(str_replace("_", " ", $lowerCaseAndUnderscoredWord)));
    }

    public function  __construct($request_prefix = false, $request_suffix = false) {
        parent::__construct($request_prefix, $request_suffix);
        $this->add_response_type('json', array(get_class($this), 'parseJson'), '.json');
        $this->add_response_type('xml', array(get_class($this), 'parseXml'), '.xml');

        foreach ($this->_controllers as $controller) {
            $model = TrueApi::classify($controller);
            $this->{$model} = new TrueApiModel($controller, $this);
        }
    }

    public function parseJson($curl_response) {
        return json_decode($curl_response, true);
    }

    public function parseXml($curl_response) {
        return $curl_response;
    }
}
?>

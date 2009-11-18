<?php
class TrueApiModel {
    public $TrueApi;
    public $controller;
    public function __construct($controller, $apiObject) {
        $this->TrueApi = $apiObject;
        $this->controller = $controller;
    }

    protected function _get($action, $vars) {
        $path = sprintf('%s/%s', $this->controller, $action);
        return $this->TrueApi->get($path, $vars);
    }

    public function index($vars = array()) {
        return $this->_get(__FUNCTION__, $vars);
    }
}
?>
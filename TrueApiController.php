<?php
class TrueApiController {
    protected $_callback;
    public    $controller;
    
    public function __construct($controller, $callback) {
        $this->_callback  = $callback;
        $this->controller = $controller;
    }

    protected function _rest($method, $action, $vars) {
        $method = str_replace('_', '', $method);
        return call_user_func_array($this->_callback,
            array($method, sprintf('%s/%s', $this->controller, $action), $vars));
    }

    protected function _get($action, $vars) {
        return $this->_rest(__FUNCTION__, $action, $vars);
    }
    protected function _put($action, $vars) {
        return $this->_rest(__FUNCTION__, $action, $vars);
    }
    protected function _post($action, $vars) {
        return $this->_rest(__FUNCTION__, $action, $vars);
    }
    protected function _delete($action, $vars) {
        return $this->_rest(__FUNCTION__, $action, $vars);
    }
    
    public function monitored($vars = array()) {
        return $this->_get(__FUNCTION__, $vars);
    }
    public function index($vars = array()) {
        return $this->_get(__FUNCTION__, $vars);
    }
    public function view($id, $vars = array()) {
        return $this->_get(sprintf('%s/%s', __FUNCTION__, $id), $vars);
    }
    public function edit($id, $vars = array()) {
        return $this->_put(sprintf('%s/%s', __FUNCTION__, $id), $vars);
    }
    public function delete($id, $vars = array()) {
        #return $this->_delete(__FUNCTION__.'/'.$id, $vars);
    }
}
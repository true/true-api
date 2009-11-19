<?php
class TrueApiController {
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
    protected function _put($action, $vars) {
        $path = sprintf('%s/%s', $this->controller, $action);
        return $this->TrueApi->put($path, $vars);
    }
    protected function _delete($action, $vars) {
        $path = sprintf('%s/%s', $this->controller, $action);
        return $this->TrueApi->delete($path, $vars);
    }
    
    public function monitored($vars = array()) {
        return $this->_get(__FUNCTION__, $vars);
    }

    public function index($vars = array()) {
        return $this->_get(__FUNCTION__, $vars);
    }
    
    public function view($id, $vars = array()) {
        return $this->_get(__FUNCTION__.'/'.$id, $vars);
    }
    
    public function edit($id, $vars = array()) {
        $vars = array('data' => $vars);
        return $this->_put(__FUNCTION__.'/'.$id, $vars);
    }

    public function delete($id, $vars = array()) {
        #return $this->_delete(__FUNCTION__.'/'.$id, $vars);
    }
}
?>
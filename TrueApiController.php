<?php
class TrueApiController {
    public    $controller;
    protected $_callback;
    public    $buffer = false;
    
    public function __construct($controller, $callback) {
        $this->controller = $controller;
        $this->_callback  = $callback;
    }

    /**
     * ->buffer(true) turns on buffering, false turns it off,
     * 'flush' executes. other kind of parameters will be appended to
     * the actual buffer.
     *
     * @param <type> $action
     * @param <type> $id
     * @param <type> $vars
     *
     * @return mixed null or boolean
     */
    public function apiBuffer($action, $id = null, $vars = null) {
        if ($action === true) {
            $this->buffer = array();
            return null;
        } 
        if ($action === 'flush') {
            if (empty($this->buffer)) {
                return $this->err('Buffer is empty');
            }
            if (count($this->buffer) > 1) {
                return $this->err('Buffer can only contain 1 kind of '.
                    ' bulk action. e.g. Don\'t mix deletes with edits.');
            }
            
            foreach($this->buffer as $bulkaction=>$vars) {
                if (false === ($res = call_user_func(array($this, $bulkaction),
                            $vars))) {
                    return false;
                }
            }
            
            return $res;
        }

        if (!is_array($this->buffer)) {
            return false;
        }

        if ($id) {
            $vars['id'] = $id;
        }
        
        $bulkaction = false;
        if ($action === 'edit' || $action === 'add') {
            $bulkaction = 'store';
        }

        if (!$bulkaction) {
            return $this->err('Cant buffer action: %s', $action);
        }

        $this->buffer[$bulkaction][] = $vars;
        return true;
    }


    /**
     * Error
     *
     * @param <type> $format
     */
    public function err($format) {
        $args = func_get_args();
        $format  = array_shift($args);
        if (count($args)) {
            $format = vsprintf($format, (array)$args);
        }
        trigger_error($format,
            E_USER_ERROR);
        return false;
    }

    /**
     * Makes the actual rest call through a callback
     *
     * @param <type> $method
     * @param <type> $action
     * @param <type> $vars
     * 
     * @return <type>
     */
    protected function _rest($method, $action, $vars) {
        $method = str_replace('_', '', $method);
        return call_user_func_array($this->_callback,
            array($method, sprintf('%s/%s',
                    $this->controller, $action), $vars));
    }

    protected function _get($action, $vars = array()) {
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

    /**
     * Read functions. Can't buffer them.
     *
     * @param <type> $name
     * @param <type> $arguments
     * 
     * @return <type>
     */
    public function  __call($name, $arguments) {
        if (count($arguments) === 0) {
            // Index methods
            return $this->_get($name);
        } elseif (count($arguments) === 1) {
            // View methods
            return $this->_get(sprintf('%s/%s', $name, $arguments[0]));
        }

        return false;
    }



    /**
     * Index. Can't buffer it.
     *
     * @param <type> $vars
     * 
     * @return <type>
     */
    public function index($scope = null, $vars = array()) {
        // View methods
        return $this->_get(sprintf('%s%s', __FUNCTION__,
                is_string($scope) ? '/scope:' . $scope : ''), $vars);
    }

    /**
     * Add. Buffering allowed.
     *
     * @param <type> $vars
     *
     * @return <type>
     */
    public function add($vars = array()) {
        if ($this->apiBuffer(__FUNCTION__, 0, $vars)) {
            return null;
        }

        return $this->_put(sprintf('%s/%s', __FUNCTION__, $id), $vars);
    }
    /**
     * Edit. Buffering allowed.
     *
     * @param <type> $id
     * @param <type> $vars
     *
     * @return <type>
     */
    public function edit($id, $vars = array()) {
        if ($this->apiBuffer(__FUNCTION__, $id, $vars)) {
            return null;
        }

        return $this->_put(sprintf('%s/%s', __FUNCTION__, $id), $vars);
    }
    public function store($vars = array()) {
        return $this->_put(__FUNCTION__, $vars);
    }
    public function delete($id, $vars = array()) {
        #return $this->_delete(__FUNCTION__.'/'.$id, $vars);
    }
}
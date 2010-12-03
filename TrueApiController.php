<?php
class TrueApiController {
    protected $_callback;

    public $controller;
    public $buffer = false;
    
    public function __construct ($controller, $actions, $callback) {
        $this->controller = $controller;
        $this->actions    = $actions;
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
    public function apiBuffer ($action, $id = null, $vars = null) {
        if ($action === true) {
            $this->buffer = array();
            return null;
        } 
        if ($action === 'flush') {
            if (empty($this->buffer)) {
                return $this->err('Buffer is empty');
            }
            if (count($this->buffer) > 1) {
                return $this->err(
                    'Buffer can only contain 1 kind of '.
                    'bulk action. e.g. Don\'t mix deletes with edits.'
                );
            }
            
            foreach ($this->buffer as $bulkaction => $vars) {
                $res = call_user_func(array($this, $bulkaction), $vars);
                if (false === ($res)) {
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
    public function err ($format) {
        $args = func_get_args();
        $format  = array_shift($args);
        if (count($args)) {
            $format = vsprintf($format, (array)$args);
        }
        trigger_error($format, E_USER_ERROR);
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
    protected function _rest ($method, $action, $vars) {
        $method = str_replace('_', '', $method);
        return call_user_func(
            $this->_callback,
            $method,
            sprintf('%s/%s', $this->controller, $action),
            $vars
        );
    }

    protected function _get ($action, $vars = array()) {
        return $this->_rest(__FUNCTION__, $action, $vars);
    }
    protected function _put ($action, $vars) {
        return $this->_rest(__FUNCTION__, $action, $vars);
    }
    protected function _post ($action, $vars) {
        return $this->_rest(__FUNCTION__, $action, $vars);
    }
    protected function _delete ($action, $vars) {
        return $this->_rest(__FUNCTION__, $action, $vars);
    }

    /**
     * Read functions. Can't buffer them.
     *
     * @param <type> $action
     * @param <type> $vars
     * 
     * @return <type>
     */
    public function  __call ($action, $vars) {
        if (!isset($this->actions[$action])) {
            return $this->err(
                'Action %s not implemented for REST on controller %s',
                 $action,
                 $this->controller
            );
        }

        if (!isset($this->actions[$action]['method'])) {
            return $this->err(
                'Server did not specify what "method" is supposed to used for action %s',
                 $action
            );
        }
        if (!isset($this->actions[$action]['id'])) {
            return $this->err(
                'Server did not specify if "id" is supposed to used for action %s',
                 $action
            );
        }

        $method = $this->actions[$action]['method'];

        if ($this->actions[$action]['id']) {
            $id = array_shift($vars);
            $action = sprintf('%s/%s', $action, $id);
        }

        return $this->_rest($method, $action, array_shift($vars));
    }

    /**
     * Index
     *
     * @param mixed array or string $scope  Either named scope as string or /key:val/ params as array
     * @param array                 $vars   Post variables
     *
     * @return array
     */
    public function index ($scope = null, $vars = array()) {
        $path = __FUNCTION__;
        if (is_string($scope)) {
            if (!isset($this->actions[__FUNCTION__]['scopeVar'])) {
                return $this->err(
                    'Server did not specify which "scopeVar" should be used for action %s',
                     __FUNCTION__
                );
            }

            $path = sprintf(
                '%s/%s:%s',
                $path,
                $this->actions[__FUNCTION__]['scopeVar'],
                $scope
            );
        } elseif (is_array($scope)) {
            foreach ($scope as $k => $v) {
                $path .= '/' . $k . ':' . $v;
            }
        }

        $data = $this->_get($path, $vars);
        return $data;
    }

    public function add ($vars = array()) {
        if ($this->apiBuffer(__FUNCTION__, 0, $vars)) {
            return null;
        }

        return $this->_put(sprintf('%s', __FUNCTION__), $vars);
    }

    public function edit ($id, $vars = array()) {
        if ($this->apiBuffer(__FUNCTION__, $id, $vars)) {
            return null;
        }

        return $this->_put(sprintf('%s/%s', __FUNCTION__, $id), $vars);
    }
}
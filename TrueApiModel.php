<?php
class TrueApiModel {
    public $TrueApi;
    public function __construct($controller, $apiObject) {
        $this->TrueApi = $apiObject;
    }

    public function  __call($name,  $arguments) {
        prd($name);
        $data = $this->get(sprintf('%s/%s', $arguments[0], $name));
        return $data;
    }
}
?>

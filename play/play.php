<?php
define('DIR_PLAY_ROOT', dirname(__FILE__));
require_once dirname(dirname(__FILE__)) .'/TrueApi.php';

error_reporting(E_ALL);
if (!function_exists('pr')) {
    function pr($arr) {
        if (is_array($arr) && count($arr)) {
            print_r($arr);
        } else {
            var_dump($arr);
        }
        echo "\n";
    }
}
if (!function_exists('prd')) {
    function prd($arr) {
        pr($arr);
        die();
    }
}
if (!function_exists('d')) {
    function d() {
        $args = func_get_args();
        if (count($args) == 1) {
            prd($args[0]);
        } else {
            prd($args);
        }
    }
}

class Play {

    public function  __construct() {
        $this->TrueApi = new TrueApi(array(
            'log-print-level' => 'debug',
        ));
        $this->TrueApi->auth('kevin',
            file_get_contents(DIR_PLAY_ROOT.'/pw'),
            file_get_contents(DIR_PLAY_ROOT.'/apikey'),
            'Employee');
    }
    
    public function main() {
        $this->TrueApi->opt(array(
            'returnData' => false,
            'apiFormat', 'json',
        ));
        
        $x = $this->TrueApi->PharosNotifications->store(array(
            1 => array(
                'pharos_data_id' => '567101645',
                'relatie_id' => '1378',
                'server_id' => '3736',
                'port' => 'ssh',
                'status' => 'down',
                'sent' => '6',
                'mstamp' => '2009-11-20 13:31:14',
                'stamp' => '2009-11-20 13:31:20',
            ),
            2 => array(
                'pharos_data_id' => '567101645',
                'relatie_id' => '1378',
                'server_id' => '3736',
                'port' => 'ssh',
                'status' => 'down',
                'sent' => '6',
                'mstamp' => '2009-11-20 13:31:14',
                'stamp' => '2009-11-20 13:31:20',
            ),
        ));
        prd($x);

        prd($this->TrueApi->rest('put', 'servers/edit/2313', array('color' => 'black')));

        $feedback = array();
        if (false !== ($response = $this->TrueApi->Servers->edit(2313, array('color' => 'gray', 'os_serial' => 'x')))) {
            $feedback[] = $response;
        }
        if (false !== ($response = $this->TrueApi->Servers->view(2313))) {
            $feedback[] = $response;
        }
    //        if (false !== ($response = $this->TrueApi->Servers->monitored())) {
    //            $feedback[] = $response;
    //        }

        return $feedback;
    }

}

$Play = new Play();
prd($Play->main());
?>
<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (function_exists('add_action') && !function_exists('WPReporting')) {

    global $WPReporting;
    function WPReporting(){
        global $WPReporting;
        if(!$WPReporting){
            require_once __DIR__.'/reporting.php';
            $WPReporting = new \WPReporting\WP_Reporting();
        }
        return $WPReporting;
    }

    WPReporting();
}
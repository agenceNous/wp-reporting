<?php
/**
 * WP Reporting
 *
 * @package WPReporting
 * @version 1.8.0
 */


if (function_exists('add_action') && !function_exists('WPReporting')) {

    global $WPReporting;
    function WPReporting(){
        global $WPReporting;
        if(!$WPReporting){
            require_once __DIR__.'/WPReporting/Reporting.php';
            $WPReporting = new \WPReporting\Reporting();
        }
        return $WPReporting;
    }

    WPReporting();
}
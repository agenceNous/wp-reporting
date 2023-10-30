<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__.'/functions.php';

if (function_exists('add_action') && !function_exists('WPReporting')) {

    global $WPReporting;
    global $WPReportingVersion;
    function WPReporting(){
        global $WPReporting;
        global $WPReportingVersion;
        $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'));
        $version = WPReportingVersion($composer->version);
        $versionInstancier = 'WPReportinga'.$version;
        if((!$WPReporting || !$WPReportingVersion || $WPReportingVersion < $version)  && function_exists($versionInstancier)){
            $WPReporting = $versionInstancier();
            $WPReportingVersion = $version;
        }
        return $WPReporting;
    }

    WPReporting();
}
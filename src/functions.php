<?php

if (!function_exists('WPReportingVersion')) {
    function WPReportingVersion(string $versionXYZ){
        $XYZ = explode('.', $versionXYZ);
        list($X, $Y, $Z) = $XYZ;
        global $WPReportingVersion;
        
        return (int)  str_pad($X, 3, "0", STR_PAD_LEFT).str_pad($Y, 3, "0", STR_PAD_LEFT).str_pad($Z, 3, "0", STR_PAD_LEFT);
    }
}

if (!function_exists('WPReporting1005000')) {
    function WPReporting1005000(){
        require_once __DIR__.'/reporting.php';
        return new \WPReporting_1_5_0\WP_Reporting();
    }
}

var wp_reporting = wp_reporting || {};

wp_reporting.log_error = function (project, err){
    console.log('[WP-Reporting] An error occured.', {details:err});
    var data = {
        'action': '',
        'project': project,
        'error': {
            'message': err.message,
            'stack': err.stack,
            'file': err.fileName,
            'line': err.lineNumber,
            'column': err.columnNumber,        
        },
        'nonce': wp_reporting.nonce,
    };

    wp.ajax.post('wpreporting_logerror', data)
    .done(function (response) {
        console.log('✅ The error has been reported.');
    }).fail(function (response) {
        console.log('⛔ The error could not be reported');
    });

};

wp_reporting.setting_refresh = function (e){
    jQuery('.wp-reporting-level input[type=radio]:checked').each(function(){
        var val = jQuery(this).val();
        if(val == 0){
            jQuery('.wp-reporting-project-context-levels', jQuery(this).parents('.wp-reporting-project')).hide(100);
        }
        else{
            jQuery('.wp-reporting-project-context-levels', jQuery(this).parents('.wp-reporting-project')).show(100);
        }
    });
}

jQuery(document).ready(function () {
    if (jQuery('.wp-reporting-project').length){
        jQuery('.wp-reporting-level input[type=radio]').on('change', wp_reporting.setting_refresh);
        wp_reporting.setting_refresh();
    }
});
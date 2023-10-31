
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
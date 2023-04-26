# wp-reporting

Utility for sending log reports in Wordpress themes &amp; plugins

Include it in your own plugin or theme:

```bash
composer require agencenous/wp-reporting
```

## Usage

```php
<?php
require 'vendor/agencenous/wp-reporting/wp-reporting.php';

// Register each project
\WPReporting()->register('project-name', [
    'label' => 'Project name', // translate it with __('Project name', 'project-textdomain')
    'description' => 'Send logs by emails', // translate it with __('Description', 'project-textdomain')
    'category' => 'plugin', // plugin, theme, main
    'to' => 'bm91c0BhdmVjbm91cy5ldQ==', // email addresse, plain or BASE64 encoded (to prevent spam when source is open)
]);


// Add it in any function or class
try{
    // Your code goes here
}
catch(\Throwable $e){ // For PHP 7
    \WPReporting()->send($e, 'project-name');
}
catch(\Exception $e){ // For PHP 5
    \WPReporting()->send($e, 'project-name');
}
?>
```

## Privacy

Email addresses can be base64 encoded. So you do not expose theme directly in source-code.

Emails are sent using the `wp_mail()` function.

Calling `WPReporting()` will automatically add a setting page in the Wordpress dashboard to let admin enable/disable error reporting for each registered project.  
Reports will only be sent if the admin has enabled it.

## Morehover

Enable settings on network in a multisite context.

Just add this line in your `wp-config.php` file.

```php
defined('WP_REPORTING_NETWORK', true);
```
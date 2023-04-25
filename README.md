# wp-reporting

Utility for sending log reports in Wordpress themes &amp; plugins

Include it in your own plugin or theme:

```bash
composer require agencenous/wp-reporting
```

## Usage

```php
<?php
require 'vendor/autoload.php';
use Agencenous\WpReporting\Reporting;

// [...]
// Add it in any function or class

try{
    // Your code
}
catch(Exception $e){
    $reporting = new Reporting();
    $reporting->send($e);
}
?>
```

GDPR compliant:  
Including the `WpReporting` class will automatically add a setting page in the Wordpress dashboard to let admin enable/disable error reporting.


send the report to the admin email address.
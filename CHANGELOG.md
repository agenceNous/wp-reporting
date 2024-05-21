## Changelog

### 1.8.1

- Removes an exit if not in WP context

### 1.8.0

- Adds autoload declaration
- Adds `wp-reporting-encode-email` bin command

### 1.7.0

- Highlight new projects in the settings page
- Refactor files structure according to PSR2
- Fix error when JS script is loaded too soon

### 1.6.0

- Add options to add context per project
- Remove ABSPATH from trace
- Wrap data in readable blocks
- Make properties private
- Allows to pass default value to settings::Get

### 1.5.1

- Uses wp_add_inline_script instead of wp_localize_script 
- Uses wp_register_script after wp_enqueue_scripts

### 1.5.0

- Adds support for Javascript errors

### 1.4.0

- Send logs in HTML format
- Reduce trace stack, because too much data causes errors
- cleanup wp-reporting from stack trace

### 1.3.0

- Add `only_in_dir` option
- Add file & line in log

### 1.2.0

- Add `listen()` / `stop()` method to listen to exceptions
- Add `trace_in_logs` option
- Add global trace of exceptions

### 1.1.3

- Test "to" parameters before parsing. Fix strstr(): Passing null to parameter #1 ($haystack) of type string is deprecated

### 1.1.2

- More flexible get_project output (retro compatibility with PHP7.4)

### 1.1.1

- Accept all types of exceptions (retro compatibility with PHP7.4)

### 1.1.0

- Add notice when a setting is missing for a project
- Add logs when `WP_DEBUG_LOG` is enabled

### 1.0.0

First release

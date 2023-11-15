## Changelog

### 1.6

- Add options to add context per project
- Remove ABSPATH from trace
- Wrap data in readable blocks
- Make properties private
- Allows to pass default value to settings::Get

### 1.5.1

- Uses wp_add_inline_script instead of wp_localize_script 
- Uses wp_register_script after wp_enqueue_scripts

### 1.5

- Adds support for Javascript errors

### 1.4

- Send logs in HTML format
- Reduce trace stack, because too much data causes errors
- cleanup wp-reporting from stack trace

### 1.3

- Add `only_in_dir` option
- Add file & line in log

### 1.2

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
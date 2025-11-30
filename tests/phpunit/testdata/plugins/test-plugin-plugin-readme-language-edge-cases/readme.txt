
=== Test Plugin Readme Language Edge Cases ===

Contributors:      plugin-check
Requires at least: 6.0
Tested up to:      6.1
Requires PHP:      7.4
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Tags:              testing, security

This plugin tests edge cases with `code snippets`, URLs like https://example.com, and email@example.com addresses that should be handled properly.

== Description ==

This is a comprehensive test of the language detection system. It includes various edge cases:

* Code snippets like `function test() { return true; }`
* URLs such as https://wordpress.org and https://github.com
* Email addresses: support@wordpress.org
* Technical terms: API, REST, AJAX, JSON, XML, HTTP, HTTPS
* Short sentences for testing minimum length requirements.

The plugin should detect this as English despite the technical content and code examples.

```php
// This is a code block
function example_function() {
    return 'This code should not confuse the language detector';
}
```

For more information, visit https://developer.wordpress.org or contact us at plugins@wordpress.org


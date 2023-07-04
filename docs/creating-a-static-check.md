# Creating a Static Check

Static checks are used to perform static analysis against a plugin's codebase.

The Static checks, which analyze the code, either using PHP CodeSniffer sniffs or custom logic e.g. using regular expressions.

```php
use WordPress\Plugin_Check\Checker\Check_Result;

class Custom_Check extends Static_Check {
  public function run( Check_Result $result );
    // Handle running the check and adding warnings or errors to the result.
  }
}
```

Here are two more concrete ways to write static checks.

## Creating a new Check using PHP CodeSniffer.

A new Check class should be created for the static check and should implement the following attributes.

- The class name should be suffixed with `_Check`.
- The class should extend the `Abstract_PHP_CodeSniffer_Check` class.
- The class should implement the `get_args()` method.

## Creating a new Check using Abstract_File_Check.

A new Check class should be created for the static check and should implement the following attributes.

- The class name should be suffixed with `_Check`.
- The class should extend the `Abstract_File_Check` class.
- The class should implement the `check_files()` method.

### PHPCS Arguments

Rather than write all the logic required to run PHPCS, the `Abstract_PHP_CodeSniffer_Check` handles this logic so developers only need to supply the arguements to PHPCS in order to get their check working.

This is done by implementing the `get_args()` method in the new Check class defined within the `Abstract_PHP_CodeSniffer_Check`.

The `get_args()` method should return an associative array containing the PHPCS arguments including the file extension, code standard and specific sniff to run.

Below is an example of a Static Check class that checks for i18n usage in the plugins codebase.

```php
/**
 * Check for running WordPress internationalization sniffs.
 */
class I18n_Usage_Check extends Abstract_PHP_CodeSniffer_Check {

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args() {
		return array(
			'extensions' => 'php',
			'standard'   => 'WordPress',
			'sniffs'     => 'WordPress.WP.I18n',
		);
	}
}
```

## Add the Check to the Plugin Checker

In order to run the check as part of the Plugin Checker process it needs to be added to the Plugin Checkers list of available checks.

This is done by using the `wp_plugin_check_checks` filter to register an instance of the check with its slug.

- If you're contributing to the actual plugin checker, add the check to the list in the `Abstract_Check_Runner::register_checks()` method.
- If you're implementing a check in code outside of the actual plugin checker, use the approach you've described here so far.

```php
add_filter(
  'wp_plugin_check_checks',
  function ( array $checks ) {
    // Add the check to the map of all available checks.
    $checks[ 'i18n_usage' ] = new I18n_Usage_Check();

    return $checks;
  }
)
```

# Amending the check result object

The checks `run()` method will hold all the logic to test the plugin and raise any warnings or errors that are found.

The run method accepts an instance of the `Check_Results` class which is used to add messages to the results list.

The warnings and errors are added via the `add_message()` method which accepts 3 parameters.

- `$error (bool)` - Whether the message is an error or warning. True for error, false for warning.
- `$message (string)` - The error/warning message.
- `$args (array)` - Additional message arguements to add context.
  - `$code (string)` - Violation code according to the message. Default empty string.
  - `$file (string)` - The file in which the message occurred. Default empty string (unknown file).
  - `$line (int)` - The line on which the message occurred. Default 0 (unknown line).
  - `$column (int)` - The column on which the message occurred. Default 0 (unknown column).

In addition to adding messages, the `Check_Result` instance also provides access to the `Plugin_Context`. The plugin context is useful for getting the plugins path and urls when performing checks.

Below is an example showing how to access the plugin context and add messages using the `Check_Results` instance.

```php
/**
	 * Runs the check on the plugin and amends results.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The check results to amend and the plugin context.
	 */
	public function run( Check_Result $result ) {

    // Get the absolute file path for a specific file in the plugin.
    $plugin_file = $result->plugin()->path( 'plugin-file.php' );
    
    // Run logic to test the plugin for warnings/errors...

    // When an issue is found add a warning.
    $result->add_message(
      false, 
			'Warning message content.', 
			array(
				'code'   => 'warning_code',
				'file'   => $pluging_file,
				'line'   => 1,
				'column' => 10,
			)
    );
  }
```

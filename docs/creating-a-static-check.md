# Creating a Static Check

Static checks are used to perform static analysis against a plugin's codebase.

Static checks analyze the code, either using PHP CodeSniffer sniffs or custom logic e.g. using regular expressions.

```php
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

class My_Custom_Check implements Static_Check {

  use Stable_Check;
  
  public function get_categories() {
    // Return an array of check categories.
    // See the `WordPress\Plugin_Check\Checker\Check_Categories` class for available categories.
  }

  public function run( Check_Result $result );
    // Handle running the check and adding warnings or errors to the result.
  }
}
```

Here are two more concrete ways to write static checks.

## Creating a new check using PHP CodeSniffer

A new class should be created for the static check, extending the `Abstract_PHP_CodeSniffer_Check` class. Here is an example:

```php
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for running WordPress internationalization sniffs.
 */
class My_Custom_Check extends Abstract_PHP_CodeSniffer_Check {
  
  use Stable_Check;

  /**
   * Gets the categories for the check.
   *
   * Every check must have at least one category.
   *
   * @since n.e.x.t
   *
   * @return array The categories for the check.
   */
  public function get_categories() {
    return array( Check_Categories::CATEGORY_GENERAL );
  }

  /**
   * Returns an associative array of arguments to pass to PHPCS.
   *
   * @return array An associative array of PHPCS CLI arguments.
   */
  protected function get_args() {
    return array(
      'extensions' => 'php',
      'standard'   => 'WordPress',
      'sniffs'     => 'WordPress.WP.CapitalPDangit',
    );
  }
}
```

## Creating a new check using file search for strings / regular expressions

A new class should be created for the static check, extending the `Abstract_File_Check` class. Here is an example:

```php
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for detecting "Wordpress" in plugin files.
 *
 * @since n.e.x.t
 */
class My_Custom_Check extends Abstract_File_Check {

	use Stable_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since n.e.x.t
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Check the "Wordpress" in files.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
    // Get all php files in the plugin.
		$php_files = self::filter_files_by_extension( $files, 'php' );

    // Check files for instances of the "Wordpress".
		$file = self::file_str_contains( $php_files, 'Wordpress' );

		if ( $file ) {
			$result->add_message(
				true,
				__( 'Please spell "WordPress" correctly.', 'plugin-check' ),
				array(
					'code' => 'misspelled_wordpress_in_files',
					'file' => $file,
				)
			);
		}
	}
}
```

## Amending the check result object

The check's `run()` method will hold all the logic to test the plugin and raise any warnings or errors that are found.

The run method accepts an instance of the `Check_Result` class which is used to add errors and warnings to the results list.

The warnings and errors are added via the `add_message()` method which accepts 3 parameters.

- `$error (bool)` - Whether the message is an error or warning. True for error, false for warning.
- `$message (string)` - The error/warning message.
- `$args (array)` - Additional message arguements to add context.
  - `$code (string)` - Violation code according to the message. Default empty string.
  - `$file (string)` - The file in which the message occurred. Default empty string (unknown file).
  - `$line (int)` - The line on which the message occurred. Default 0 (unknown line).
  - `$column (int)` - The column on which the message occurred. Default 0 (unknown column).

In addition to adding messages, the `Check_Result` instance also provides access to the plugin's `Check_Context`. The plugin context is useful for getting the plugin's path and URL when performing checks.

Below is an example showing how to access the plugin context and add messages using the `Check_Result` instance.

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

## Add the Check to the Plugin Checker

In order to run the check as part of the Plugin Checker process, it needs to be added to the Plugin Checker's list of available checks.

- If you're contributing to the actual plugin checker, add the check to the list in the `Abstract_Check_Runner::register_checks()` method.
- If you're implementing a check in code outside of the actual plugin checker, use the `wp_plugin_check_checks` filter, as seen in the example below.

```php
add_filter(
  'wp_plugin_check_checks',
  function ( array $checks ) {
    // Add the check to the map of all available checks.
    $checks['my_custom_check'] = new My_Custom_Check();

    return $checks;
  }
);
```

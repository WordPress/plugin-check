# Creating a Static Check

Static checks are employed to conduct static analysis on a plugin's codebase.

Static checks analyze the code, either by using PHP CodeSniffer sniffs or custom logic, such as regular expressions.

The Plugin Checker offers the `Static_Check` interface, which serves to identify a static check. This interface does not include any methods but acts as a marker for static checks.

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

  public function run( Check_Result $result ) {
    // Handle running the check and adding warnings or errors to the result.
  }
}
```

Here are two more specific approaches to writing static checks.

## Creating a new check using PHP CodeSniffer

To create the static check, you should develop a new class that extends the `Abstract_PHP_CodeSniffer_Check` class. Below is an example:

```php
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for detecting incorrect casing of the word "WordPress" using PHP CodeSniffer.
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

To create the static check, you should develop a new class that extends the `Abstract_File_Check` class. Below is an example:

```php
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for detecting incorrect casing of the term "WordPress" (specifically "Wordpress") using string search in files.
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

The `run()` method of the check will encompass all the logic required to test the plugin and raise any warnings or errors that are detected.

The `run` method accepts an instance of the `Check_Result` class, enabling the addition of errors and warnings to the results list.

The warnings and errors are added using the `add_message()` method, which accepts three parameters.

- `$error (bool)` - Whether the message is an error or warning. `true` for error, `false` for warning.
- `$message (string)` - The error/warning message.
- `$args (array)` - Additional message arguments to add context.
  - `$code (string)` - The violation code associated with the message. Default is an empty string.
  - `$file (string)` - The file for which the message occurred. Default empty string (unknown file).
  - `$line (int)` - The line for which the message occurred. Default 0 (unknown line).
  - `$column (int)` - The column for which the message occurred. Default 0 (unknown column).

In addition to adding messages, the `Check_Result` instance also grants access to the plugin's `Check_Context` instance, which proves useful for retrieving the plugin's path and URL during check execution.

Below is an example demonstrating how to access the plugin context and add messages using the `Check_Result` instance.

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

## Adding the Check to the Plugin Checker

To include the check as part of the Plugin Checker process, it must be added to the Plugin Checker's list of available checks.

- If you're contributing to the Plugin Checker, add the check to the list in the `Abstract_Check_Runner::register_checks()` method.
- If you're implementing a check in code outside of the Plugin Checker, use the `wp_plugin_check_checks` filter, as seen in the example below.

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

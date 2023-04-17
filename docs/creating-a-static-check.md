
# Creating a Static Check

Static checks are used to perform static analysis against a plugins codebase.

The Plugin Checker uses PHPCS to perform static checks against a plugin and provides the `Abstract_PHP_CodeSniffer_Check` class to help create additional static checks that use PHPCS sniffs.

## Creating a new Check class

A new Check class should be created for the static check and should implement the following attributes.

- The class name should be suffixed with `_Check`.
- The class should extend the `Abstract_PHP_CodeSniffer_Check` class.
- The class should implement the `get_args()` method.

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

This is done by using the `wp_plugin_check_checks` filter to register an instance of the Check with it's slug.

```php
add_filter(
  'wp_plugin_check_checks',
  function ( array $checks ) {
    // Add the check to the map of all available checks.
    $checks[ 'i18n_usage' ] = new I18n_Usage_Check();

    return $checks;
  }
)

<?php
/**
 * Class Credential_Storage_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Security;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Experimental_Check;

/**
 * Check for credentials stored outside the Secrets API.
 *
 * @since 2.3.0
 */
class Credential_Storage_Check extends Abstract_PHP_CodeSniffer_Check {

	use Experimental_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 2.3.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_SECURITY );
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 2.3.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args( Check_Result $result ) {
		return array(
			'extensions' => 'php',
			'standard'   => 'PluginCheck',
			'sniffs'     => 'PluginCheck.Security.CredentialStorage',
		);
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 2.3.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Recommends the WordPress Secrets API for options that look like they store credentials.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 2.3.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/security/', 'plugin-check' );
	}
}

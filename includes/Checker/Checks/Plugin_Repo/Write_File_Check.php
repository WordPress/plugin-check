<?php
/**
 * Class Write_File_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check to detect plugins saving data in the plugin folder.
 *
 * @since 2.0.0
 */
class Write_File_Check extends Abstract_PHP_CodeSniffer_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Bitwise flags to control check behavior.
	 *
	 * @since 2.0.0
	 * @var int
	 */
	protected $flags = 0;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 2.0.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 2.0.0
	 *
	 * @param Check_Result $result The check result to amend.
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args( Check_Result $result ) {
		return array(
			'extensions' => 'php',
			'standard'   => 'PluginCheck',
			'sniffs'     => 'PluginCheck.CodeAnalysis.WriteFile',
		);
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 2.0.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects if plugins save data in the plugin folder instead of using the uploads directory or database.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 2.0.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#saving-data-in-the-plugin-folder', 'plugin-check' );
	}
}

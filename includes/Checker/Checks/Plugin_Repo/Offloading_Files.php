<?php
/**
 * Class Offloading_Files.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check to detect loading files from external sites.
 *
 * @since n.e.x.t.
 */
class Offloading_Files extends Abstract_PHP_CodeSniffer_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Bitwise flags to control check behavior.
	 *
	 * @since n.e.x.t.
	 * @var int
	 */
	protected $flags = 0;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since n.e.x.t.
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 1.0.0
	 *
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args() {
		return array(
			'extensions' => 'php',
			'standard'   => 'PluginCheck',
			'sniffs'     => 'PluginCheck.EnqueuedResourceOffloading,PluginCheck.Offloading',
		);
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since n.e.x.t.
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Prevents using remote services that are not necessary.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since n.e.x.t.
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/common-issues/#calling-files-remotely', 'plugin-check' );
	}
}

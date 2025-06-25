<?php
/**
 * Class Plugin_Uninstall_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for plugin uninstallation.
 *
 * @since 1.6.0
 */
class Plugin_Uninstall_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.6.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 1.6.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$plugin_path = $result->plugin()->path();

		$plugin_uninstall_file = $plugin_path . 'uninstall.php';

		if ( file_exists( $plugin_uninstall_file ) ) {
			// Check the uninstall constant.
			$this->check_constant( $result, $plugin_uninstall_file );
		}
	}

	/**
	 * Checks the WP_UNINSTALL_PLUGIN constant in uninstall file.
	 *
	 * @since 1.6.0
	 *
	 * @param Check_Result $result         The Check Result to amend.
	 * @param string       $uninstall_file Uninstall file.
	 */
	private function check_constant( Check_Result $result, string $uninstall_file ) {
		$constant_regex = '#defined\s*\(.*WP_UNINSTALL_PLUGIN.*\)#';
		$matches        = array();

		$uninstall_constant = self::file_preg_match( $constant_regex, array( $uninstall_file ), $matches );

		if ( ! $uninstall_constant ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: Constant name. */
					__( 'Check for %s constant missing.', 'plugin-check' ),
					'WP_UNINSTALL_PLUGIN'
				),
				'uninstall_missing_constant_check',
				$uninstall_file,
				0,
				0,
				'https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/#method-2-uninstall-php',
				7
			);
		}
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.6.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Checks related to plugin uninstallation.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.6.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/#method-2-uninstall-php', 'plugin-check' );
	}
}

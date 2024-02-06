<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Plugin_Header_Text_Domain_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for plugin header text domain.
 *
 * @since 1.0.0
 */
class Plugin_Header_Text_Domain_Check implements Static_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.0.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	public function run( Check_Result $result ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_main_file = WP_PLUGIN_DIR . '/' . $result->plugin()->basename();
		$plugin_header    = get_plugin_data( $plugin_main_file );
		$plugin_slug      = basename( $result->plugin()->path() );

		if (
			! empty( $plugin_slug ) &&
			! empty( $plugin_header['TextDomain'] ) &&
			$plugin_slug !== $plugin_header['TextDomain']
		) {
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: 1: plugin header text domain, 2: plugin slug */
					__( 'The TextDomain header in the plugin file does not match the slug. Found "%1$s", expected "%2$s".', 'plugin-check' ),
					esc_html( $plugin_header['TextDomain'] ),
					esc_html( $plugin_slug )
				),
				'textdomain_mismatch',
				$plugin_main_file
			);
		}
	}
}

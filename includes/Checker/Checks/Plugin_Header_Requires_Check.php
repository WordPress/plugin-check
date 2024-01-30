<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Plugin_Header_Requires_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Find_Readme;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPressdotorg\Plugin_Directory\Readme\Parser;

/**
 * Check for requires in plugin header.
 *
 * @since n.e.x.t
 */
class Plugin_Header_Requires_Check implements Static_Check {

	use Amend_Check_Result;
	use Find_Readme;
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
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @since n.e.x.t
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

		$fields = array(
			'RequiresWP'  => array(
				'label'      => __( 'Requires at least', 'plugin-check' ),
				'parser_key' => 'requires',
			),
			'RequiresPHP' => array(
				'label'      => __( 'Requires PHP', 'plugin-check' ),
				'parser_key' => 'requires_php',
			),
		);

		// Look for the readme.
		$plugin_files = glob( $result->plugin()->location() . '*' );
		$readme_files = $this->filter_files_for_readme( $plugin_files, $result->plugin()->path() );
		$readme_file  = reset( $readme_files );

		// Check if single file plugin or missing readme file.
		if ( $result->plugin()->is_single_file_plugin() || empty( $readme_file ) ) {
			foreach ( $fields as $field_key => $field ) {
				if ( empty( $plugin_header[ $field_key ] ) ) {
					$this->add_result_error_for_file(
						$result,
						sprintf(
							/* translators: %s: plugin header tag */
							'The "%s" header is missing.',
							$field['label']
						),
						'missing_plugin_header',
						$plugin_main_file
					);
				}
			}
		} else {
			$parser = new Parser( $readme_file );

			foreach ( $fields as $field_key => $field ) {
				if ( empty( $parser->{$field['parser_key']} ) && empty( $plugin_header[ $field_key ] ) ) {
					$this->add_result_error_for_file(
						$result,
						sprintf(
							/* translators: %s: plugin header tag */
							'The "%s" header is missing. It should be defined in the main plugin file, or in readme.',
							$field['label']
						),
						'missing_plugin_header',
						$plugin_main_file
					);
				}
			}
		}
	}
}

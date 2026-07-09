<?php
/**
 * Class AI_Name_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPress\Plugin_Check\Traits\AI_Check_Names;
use WordPress\Plugin_Check\Traits\AI_Utils;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
use WordPress\Plugin_Check\Admin\Settings_Page;

/**
 * Check for plugin name issues using AI.
 *
 * @since 2.1.0
 */
class AI_Name_Check implements Static_Check {

	use Amend_Check_Result;
	use Stable_Check;
	use AI_Check_Names;
	use AI_Utils;

	/**
	 * Gets the categories for the check.
	 *
	 * @since 2.1.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Runs the AI Name Check.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result The check result to amend.
	 */
	public function run( Check_Result $result ) {
		$runner = Plugin_Request_Utility::get_runner();

		// Only run when AI analysis is enabled.
		if ( ! $runner || ! method_exists( $runner, 'should_use_ai' ) || ! $runner->should_use_ai() ) {
			return;
		}

		if ( is_wp_error( $this->check_ai_prerequisites() ) || is_wp_error( $this->check_ai_connectors() ) ) {
			return;
		}

		// Retrieve model preference.
		$model_preference = '';
		if ( method_exists( $runner, 'get_ai_model_preference' ) ) {
			$model_preference = $runner->get_ai_model_preference();
		}
		if ( empty( $model_preference ) && class_exists( Settings_Page::class ) ) {
			$model_preference = Settings_Page::get_model_preference();
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_main_file = $result->plugin()->main_file();
		$plugin_header    = get_plugin_data( $plugin_main_file );
		$plugin_name      = isset( $plugin_header['Name'] ) ? $plugin_header['Name'] : '';
		$author_name      = isset( $plugin_header['AuthorName'] ) ? $plugin_header['AuthorName'] : '';

		if ( empty( $plugin_name ) ) {
			return;
		}

		$analysis = $this->run_name_analysis( $model_preference, $plugin_name, $author_name );
		if ( is_wp_error( $analysis ) ) {
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: %s: Error message. */
					__( 'AI plugin name check failed: %s', 'plugin-check' ),
					$analysis->get_error_message()
				),
				'ai_name_check_failed',
				$plugin_main_file
			);
			return;
		}

		$parsed = $this->parse_analysis( $analysis );

		// If there is a verdict indicating issues:
		if ( isset( $parsed['processed_data'] ) ) {
			$data = $parsed['processed_data'];

			// 1. If disallowed:
			if ( ! empty( $data['disallowed'] ) ) {
				$msg = isset( $data['disallowed_explanation'] ) ? $data['disallowed_explanation'] : __( 'The plugin name is disallowed.', 'plugin-check' );
				$this->add_result_error_for_file(
					$result,
					$msg,
					'plugin_name_disallowed',
					$plugin_main_file
				);
			}

			// 2. If possible naming issues:
			if ( ! empty( $data['possible_naming_issues'] ) ) {
				$msg = isset( $data['naming_explanation'] ) ? $data['naming_explanation'] : __( 'The plugin name has possible naming issues.', 'plugin-check' );
				$this->add_result_warning_for_file(
					$result,
					$msg,
					'plugin_name_issue',
					$plugin_main_file
				);
			}

			// 3. If possible owner/trademark issues:
			if ( ! empty( $data['possible_owner_issues'] ) ) {
				$msg = isset( $data['owner_explanation'] ) ? $data['owner_explanation'] : __( 'The plugin name has possible trademark or ownership issues.', 'plugin-check' );
				$this->add_result_warning_for_file(
					$result,
					$msg,
					'plugin_name_trademark_issue',
					$plugin_main_file
				);
			}

			// 4. Group list of similar plugins/trademarks into recommendations:
			if ( ! empty( $parsed['confusion_existing_plugins'] ) && is_array( $parsed['confusion_existing_plugins'] ) ) {
				$plugins_list = array();
				foreach ( $parsed['confusion_existing_plugins'] as $plugin ) {
					$plugins_list[] = sprintf( '%s (%s, %s active installs)', $plugin['name'], $plugin['similarity_level'], $plugin['active_installations'] );
				}
				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: %s: List of similar plugins. */
						__( 'Plugin name is similar to existing plugins: %s', 'plugin-check' ),
						implode( '; ', $plugins_list )
					),
					'plugin_name_similarity',
					$plugin_main_file
				);
			}
		}
	}

	/**
	 * Gets the description for the check.
	 *
	 * @since 2.1.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Checks the plugin name for guideline compliance, trademark conflicts, and similarity with existing plugins using AI.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * @since 2.1.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/', 'plugin-check' );
	}
}

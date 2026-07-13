<?php
/**
 * Class AI_Name_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use WordPress\Plugin_Check\Admin\Settings_Page;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\AI_Check_Names;
use WordPress\Plugin_Check\Traits\AI_Utils;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

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
		if ( ! $this->should_run_ai_check( $runner ) ) {
			return;
		}

		$model_preference = $this->get_model_preference( $runner );
		$plugin_data      = $this->get_plugin_name_and_author( $result );
		if ( ! $plugin_data ) {
			return;
		}

		$analysis = $this->run_name_analysis( $model_preference, $plugin_data['name'], $plugin_data['author'] );
		$this->handle_analysis_response( $result, $analysis, $plugin_data['file'] );
	}

	/**
	 * Determines if the AI check should run.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $runner The active runner.
	 * @return bool True if the check should run, false otherwise.
	 */
	private function should_run_ai_check( $runner ) {
		if ( ! $runner ) {
			return false;
		}
		if ( ! method_exists( $runner, 'should_use_ai' ) ) {
			return false;
		}
		if ( ! $runner->should_use_ai() ) {
			return false;
		}
		if ( is_wp_error( $this->check_ai_prerequisites() ) ) {
			return false;
		}
		if ( is_wp_error( $this->check_ai_connectors() ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Gets the selected AI model preference.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $runner The active runner.
	 * @return string The model preference.
	 */
	private function get_model_preference( $runner ) {
		$model = '';
		if ( $runner && method_exists( $runner, 'get_ai_model_preference' ) ) {
			$model = $runner->get_ai_model_preference();
		}
		if ( empty( $model ) && class_exists( Settings_Page::class ) ) {
			$model = Settings_Page::get_model_preference();
		}
		return $model;
	}

	/**
	 * Gets the plugin name and author from headers.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result The check result.
	 * @return array|null Name and author if found, null otherwise.
	 */
	private function get_plugin_name_and_author( Check_Result $result ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$file   = $result->plugin()->main_file();
		$header = get_plugin_data( $file );
		$name   = isset( $header['Name'] ) ? $header['Name'] : '';
		$author = isset( $header['AuthorName'] ) ? $header['AuthorName'] : '';

		if ( empty( $name ) ) {
			return null;
		}

		return array(
			'name'   => $name,
			'author' => $author,
			'file'   => $file,
		);
	}

	/**
	 * Handles the AI analysis response and parses results.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result           The check result.
	 * @param mixed        $analysis         The analysis response or WP_Error.
	 * @param string       $plugin_main_file The plugin main file path.
	 */
	private function handle_analysis_response( Check_Result $result, $analysis, string $plugin_main_file ) {
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
		$this->process_analysis_results( $result, $parsed, $plugin_main_file );
	}

	/**
	 * Process analysis results and add appropriate warnings/errors.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result           The check result.
	 * @param array        $parsed           The parsed analysis data.
	 * @param string       $plugin_main_file The plugin main file path.
	 */
	private function process_analysis_results( Check_Result $result, array $parsed, string $plugin_main_file ) {
		if ( ! isset( $parsed['processed_data'] ) ) {
			return;
		}

		$data = $parsed['processed_data'];

		$this->check_disallowed_name( $result, $data, $plugin_main_file );
		$this->check_naming_issues( $result, $data, $plugin_main_file );
		$this->check_owner_issues( $result, $data, $plugin_main_file );
		$this->check_similar_plugins( $result, $parsed, $plugin_main_file );
	}

	/**
	 * Check if the plugin name is disallowed.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result           The check result.
	 * @param array        $data             The parsed analysis data.
	 * @param string       $plugin_main_file The plugin main file path.
	 */
	private function check_disallowed_name( Check_Result $result, array $data, string $plugin_main_file ) {
		if ( ! empty( $data['disallowed'] ) ) {
			$msg = isset( $data['disallowed_explanation'] ) ? $data['disallowed_explanation'] : __( 'The plugin name is disallowed.', 'plugin-check' );
			$this->add_result_error_for_file( $result, $msg, 'plugin_name_disallowed', $plugin_main_file );
		}
	}

	/**
	 * Check for possible naming issues.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result           The check result.
	 * @param array        $data             The parsed analysis data.
	 * @param string       $plugin_main_file The plugin main file path.
	 */
	private function check_naming_issues( Check_Result $result, array $data, string $plugin_main_file ) {
		if ( ! empty( $data['possible_naming_issues'] ) ) {
			$msg = isset( $data['naming_explanation'] ) ? $data['naming_explanation'] : __( 'The plugin name has possible naming issues.', 'plugin-check' );
			$this->add_result_warning_for_file( $result, $msg, 'plugin_name_issue', $plugin_main_file );
		}
	}

	/**
	 * Check for possible owner issues.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result           The check result.
	 * @param array        $data             The parsed analysis data.
	 * @param string       $plugin_main_file The plugin main file path.
	 */
	private function check_owner_issues( Check_Result $result, array $data, string $plugin_main_file ) {
		if ( ! empty( $data['possible_owner_issues'] ) ) {
			$msg = isset( $data['owner_explanation'] ) ? $data['owner_explanation'] : __( 'The plugin name has possible trademark or ownership issues.', 'plugin-check' );
			$this->add_result_warning_for_file( $result, $msg, 'plugin_name_trademark_issue', $plugin_main_file );
		}
	}

	/**
	 * Check for similar plugins.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result           The check result.
	 * @param array        $parsed           The parsed analysis data.
	 * @param string       $plugin_main_file The plugin main file path.
	 */
	private function check_similar_plugins( Check_Result $result, array $parsed, string $plugin_main_file ) {
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

<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Plugin_Readme_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Find_Readme;
use WordPress\Plugin_Check\Traits\Stable_Check;
use WordPressdotorg\Plugin_Directory\Readme\Parser;

/**
 * Check the plugins readme file and contents.
 *
 * @since n.e.x.t
 */
class Plugin_Readme_Check extends Abstract_File_Check {

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
	 * Check the readme file.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {

		// Check if single file plugin, then bail early.
		if ( $result->plugin()->is_single_file_plugin() ) {
			return;
		}

		$plugin_relative_path = $result->plugin()->path();

		// Filter the readme files.
		$readme = $this->filter_files_for_readme( $files, $plugin_relative_path );

		// If the readme file does not exist, add a warning and skip other tests.
		if ( empty( $readme ) ) {
			$this->add_result_warning_for_file(
				$result,
				__( 'The plugin readme.txt does not exist.', 'plugin-check' ),
				'no_plugin_readme',
				'readme.txt'
			);

			return;
		}

		$readme_file = reset( $readme );

		$parser = new Parser( $readme_file );

		// Check the readme file for default text.
		$this->check_default_text( $result, $readme_file, $parser );

		// Check the readme file for a valid license.
		$this->check_license( $result, $readme_file, $parser );

		// Check the readme file for a valid version.
		$this->check_stable_tag( $result, $readme_file, $parser );

		// Check the readme file for warnings.
		$this->check_for_warnings( $result, $readme_file, $parser );
	}

	/**
	 * Checks the readme file for default text.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_default_text( Check_Result $result, string $readme_file, Parser $parser ) {
		$short_description = $parser->short_description;
		$tags              = $parser->tags;
		$donate_link       = $parser->donate_link;

		if (
			in_array( 'tag1', $tags, true )
			|| str_contains( $short_description, 'Here is a short description of the plugin.' )
			|| str_contains( $donate_link, '//example.com/' )
		) {
			$this->add_result_warning_for_file(
				$result,
				__( 'The readme appears to contain default text.', 'plugin-check' ),
				'default_readme_text',
				$readme_file
			);
		}
	}

	/**
	 * Checks the readme file for a valid license.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_license( Check_Result $result, string $readme_file, Parser $parser ) {
		$license = $parser->license;

		if ( empty( $license ) ) {
			$this->add_result_error_for_file(
				$result,
				__( 'Your plugin has no license declared. Please update your readme with a GPLv2 (or later) compatible license.', 'plugin-check' ),
				'no_license',
				$readme_file
			);

			return;
		}

		// Test for a valid SPDX license identifier.
		if ( ! preg_match( '/^([a-z0-9\-\+\.]+)(\sor\s([a-z0-9\-\+\.]+))*$/i', $license ) ) {
			$this->add_result_warning_for_file(
				$result,
				__( 'Your plugin has an invalid license declared. Please update your readme with a valid SPDX license identifier.', 'plugin-check' ),
				'invalid_license',
				$readme_file
			);
		}
	}

	/**
	 * Checks the readme file stable tag.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_stable_tag( Check_Result $result, string $readme_file, Parser $parser ) {
		$stable_tag = $parser->stable_tag;

		if ( 'trunk' === $stable_tag ) {
			$this->add_result_error_for_file(
				$result,
				__( "It's recommended not to use 'Stable Tag: trunk'.", 'plugin-check' ),
				'trunk_stable_tag',
				$readme_file
			);
		}

		// Check the readme file Stable tag against the plugin's main file version.
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $result->plugin()->basename() );

		if (
			$stable_tag && ! empty( $plugin_data['Version'] ) &&
			$stable_tag !== $plugin_data['Version']
		) {
			$this->add_result_error_for_file(
				$result,
				__( 'The Stable Tag in your readme file does not match the version in your main plugin file.', 'plugin-check' ),
				'stable_tag_mismatch',
				$readme_file
			);
		}
	}

	/**
	 * Checks the readme file warnings.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result      The Check Result to amend.
	 * @param string       $readme_file Readme file.
	 * @param Parser       $parser      The Parser object.
	 */
	private function check_for_warnings( Check_Result $result, string $readme_file, Parser $parser ) {
		$warnings = $parser->warnings ? $parser->warnings : array();

		$warning_keys = array_keys( $warnings );

		$ignored_warnings = array(
			'contributor_ignored',
		);

		/**
		 * Filter the list of ignored readme parser warnings.
		 *
		 * @since n.e.x.t
		 *
		 * @param array  $ignored_warnings Array of ignored warning keys.
		 * @param Parser $parser           The Parser object.
		 */
		$ignored_warnings = (array) apply_filters( 'wp_plugin_check_ignored_readme_warnings', $ignored_warnings, $parser );

		$warning_keys = array_diff( $warning_keys, $ignored_warnings );

		if ( ! empty( $warning_keys ) ) {
			$this->add_result_warning_for_file(
				$result,
				sprintf(
					/* translators: list of warnings */
					esc_html__( 'The following readme parser warnings were detected: %s', 'plugin-check' ),
					esc_html( implode( ', ', $warning_keys ) )
				),
				'readme_parser_warnings',
				$readme_file
			);
		}
	}
}

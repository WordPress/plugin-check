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

		// Check the readme file for default text.
		$this->check_default_text( $result, $readme );

		// Check the readme file for a valid license.
		$this->check_license( $result, $readme );

		// Check the readme file for a valid version.
		$this->check_stable_tag( $result, $readme );
	}

	/**
	 * Checks the readme file for default text.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	private function check_default_text( Check_Result $result, array $files ) {
		$default_text_patterns = array(
			'Here is a short description of the plugin.',
			'Tags: tag1',
			'Donate link: http://example.com/',
		);

		foreach ( $default_text_patterns as $pattern ) {
			$file = self::file_str_contains( $files, $pattern );
			if ( $file ) {
				$this->add_result_warning_for_file(
					$result,
					__( 'The readme appears to contain default text.', 'plugin-check' ),
					'default_readme_text',
					$file
				);
				break;
			}
		}
	}

	/**
	 * Checks the readme file for a valid license.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	private function check_license( Check_Result $result, array $files ) {
		$matches = array();
		// Get the license from the readme file.
		$file = self::file_preg_match( '/(License:|License URI:)\s*(.+)*/i', $files, $matches );

		if ( empty( $matches ) ) {
			return;
		}

		// Test for a valid SPDX license identifier.
		if ( ! preg_match( '/^([a-z0-9\-\+\.]+)(\sor\s([a-z0-9\-\+\.]+))*$/i', $matches[2] ) ) {
			$this->add_result_warning_for_file(
				$result,
				__( 'Your plugin has an invalid license declared. Please update your readme with a valid SPDX license identifier.', 'plugin-check' ),
				'invalid_license',
				$file
			);
		}
	}

	/**
	 * Checks the readme file stable tag.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	private function check_stable_tag( Check_Result $result, array $files ) {
		$matches = array();
		// Get the Stable tag from readme file.
		$file = self::file_preg_match( '/Stable tag:\s*([a-z0-9\.]+)/i', $files, $matches );
		if ( ! $file ) {
			return;
		}

		$stable_tag = isset( $matches[1] ) ? $matches[1] : '';

		if ( 'trunk' === $stable_tag ) {
			$this->add_result_error_for_file(
				$result,
				__( "It's recommended not to use 'Stable Tag: trunk'.", 'plugin-check' ),
				'trunk_stable_tag',
				$file
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
				$file
			);
		}
	}
}

<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Plugin_Readme_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Check the plugins readme.txt file and contents.
 *
 * @since n.e.x.t
 */
class Plugin_Readme_Check extends Abstract_File_Check {

	/**
	 * Check the readme.txt file.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		// Find the readme file.
		$readme = self::filter_files_by_regex( $files, '/readme\.txt$/' );

		// If the readme.txt does not exist, add a warning and skip other tests.
		if ( empty( $readme ) ) {
			$result->add_message(
				false,
				__( 'The plugin readme.txt does not exist.', 'plugin-check' ),
				array(
					'file' => 'readme.txt',
					'code' => 'no_plugin_readme',
				)
			);

			return;
		}

		// Check the readme.txt for default text.
		$this->check_default_text( $result, $readme );

		// Check the readme.txt for a valid license.
		$this->check_license( $result, $readme );

		// Check the readme.txt for a valid version.
		$this->check_stable_tag( $result, $readme );
	}

	/**
	 * Checks the readme.txt for default text.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	private function check_default_text( Check_Result $result, array $files ) {
		if (
			self::file_str_contains( $files, 'Here is a short description of the plugin.' ) ||
			self::file_str_contains( $files, 'Tags: tag1' ) ||
			self::file_str_contains( $files, 'Donate link: http://example.com/' )
		) {
			$result->add_message(
				false,
				__( 'The readme.txt appears to contain default text.', 'plugin-check' ),
				array(
					'code' => 'default_readme_text',
					'file' => $result->plugin()->path( '/readme.txt' ),
				)
			);
		}
	}

	/**
	 * Checks the readme.txt for a valid license.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	private function check_license( Check_Result $result, array $files ) {
		// Get the license from the readme file.
		self::file_preg_match( '/(License:|License URI:)\s*(.+)*/i', $files, $matches );

		if ( empty( $matches ) ) {
			return;
		}

		// Test for a valid valid SPDX license identifier.
		if ( ! preg_match( '/^([a-z0-9\-\+\.]+)(\sor\s([a-z0-9\-\+\.]+))*$/i', $matches[2] ) ) {
			$result->add_message(
				false,
				__( 'Your plugin has an invalid license declared. Please update your readme.txt with a valid SPDX license identifier.', 'plugin-check' ),
				array(
					'code' => 'invalid_license',
					'file' => $result->plugin()->path( '/readme.txt' ),
				)
			);
		}
	}

	/**
	 * Checks the readme.txt stable tag.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	private function check_stable_tag( Check_Result $result, array $files ) {
		// Get the readme.txt Stable tag.
		if ( ! self::file_preg_match( '/Stable tag:\s*([a-z0-9\.]+)/i', $files, $matches ) ) {
			return;
		}

		$stable_tag = isset( $matches[1] ) ? $matches[1] : '';

		if ( 'trunk' === $stable_tag ) {
			$result->add_message(
				false,
				__( "It's recommended not to use 'Stable Tag: trunk'.", 'plugin-check' ),
				array(
					'code' => 'trunk_stable_tag',
					'file' => $result->plugin()->path( '/readme.txt' ),
				)
			);
		}

		// Check the readme.txt Stable tag against the plugin's main file version.
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $result->plugin()->basename() );

		if (
			$stable_tag && ! empty( $plugin_data['Version'] ) &&
			$stable_tag !== $plugin_data['Version']
		) {
			$result->add_message(
				false,
				__( 'The Stable Tag in your readme.txt file does not match the version in your main plugin file.', 'plugin-check' ),
				array(
					'code' => 'stable_tag_mismatch',
					'file' => $result->plugin()->path( '/readme.txt' ),
				)
			);
		}
	}
}

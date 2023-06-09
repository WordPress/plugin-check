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
		$readme = self::filter_files_by_regex( $files, '/readme\.txt/' );

		// If the readme.txt does not exist, add a warning and skip other tests.
		if ( empty( $readme ) ) {
			$result->add_message(
				false,
				__( 'The plugins readme.txt does not exist.', 'plugin-check' ),
				array(
					'file' => 'readme.txt',
					'code' => 'plugin_readme.does_not_exist',
				)
			);

			return;
		}

		// Check the readme.txt for default text.
		$this->readme_check_default_text( $result, $readme );

		// Check the readme.txt for a valid license.
		$this->readme_check_license( $result, $readme );

		// Check the readme.txt for a valid version.
		$this->readme_check_version( $result, $readme );
	}

	/**
	 * Checks the readme.txt for default text.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	private function readme_check_default_text( Check_Result $result, array $files ) {
		if ( self::file_str_contains( $files, 'Here is a short description of the plugin.' ) ) {
			$result->add_message(
				false,
				__( 'The plugins readme.txt appears to contain default text.', 'plugin-check' ),
				array(
					'code' => 'plugin_readme.contains_default_text',
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
	private function readme_check_license( Check_Result $result, array $files ) {
		if (
			! self::file_str_contains( $files, 'License:' ) ||
			! self::file_str_contains( $files, 'License URI:' )
		) {
			$result->add_message(
				false,
				__( 'The plugins readme.txt does not include a valid license.', 'plugin-check' ),
				array(
					'code' => 'plugin_readme.missing_license',
					'file' => $result->plugin()->path( '/readme.txt' ),
				)
			);
		}
	}

	/**
	 * Checks the readme.txt for a valid version.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	private function readme_check_version( Check_Result $result, array $files ) {
		if ( ! self::file_str_contains( $files, 'Stable tag:' ) ) {
			$result->add_message(
				false,
				__( 'The plugins readme.txt does not include a valid stable tag.', 'plugin-check' ),
				array(
					'code' => 'plugin_readme.missing_stable_tag',
					'file' => $result->plugin()->path( '/readme.txt' ),
				)
			);
		}
	}
}

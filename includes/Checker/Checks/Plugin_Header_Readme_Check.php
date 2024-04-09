<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Plugin_Header_Readme_Check
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
 * @since 1.0.0
 */
class Plugin_Header_Readme_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Find_Readme;
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
	 * Check the readme file and plugin header.
	 *
	 * @since 1.0.0
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

		$readme = $this->filter_files_for_readme( $files, $plugin_relative_path );

		$readme_file   = reset( $readme );
		$readme_parser = new Parser( $readme_file );

		$plugin_main_file = WP_PLUGIN_DIR . '/' . $result->plugin()->basename();

		// Check the readme file and plugin header for a valid license.
		$this->check_license( $result, $readme_file, $readme_parser, $plugin_main_file );
	}

	/**
	 * Checks the readme file and plugin header for a valid license.
	 *
	 * @since 1.1.0
	 *
	 * @param Check_Result $result        The Check Result to amend.
	 * @param string       $readme_file   The readme file.
	 * @param object       $readme_parser The readme parser object.
	 * @param string       $plugin_main_file Plugin main file.
	 */
	private function check_license( Check_Result $result, string $readme_file, object $readme_parser, string $plugin_main_file ) {

		// Filter the readme files.
		$license_readme = $readme_parser->license;
		if ( empty( $license_readme ) ) {
			$this->add_result_error_for_file(
				$result,
				__( 'Your plugin has no license declared. Please update your readme with a GPLv2 (or later) compatible license.', 'plugin-check' ),
				'no_license',
				$readme_file
			);
		} else {
			$license_readme = $this->normaliceLicenses( $license_readme );
		}

		$pattern     = preg_quote( 'License', '/' );
		$has_license = self::file_preg_match( "/(*ANYCRLF)^.*$pattern\s*:\s*(.*)$/im", array( $plugin_main_file ), $matches );
		if ( ! $has_license ) {
			$this->add_result_error_for_file(
				$result,
				__( 'Your plugin has no license declared in Plugin Header. Please update your plugin header with a GPLv2 (or later) compatible license.', 'plugin-check' ),
				'no_license',
				$plugin_main_file
			);
		} else {
			$plugin_license = $this->normaliceLicenses( $matches[1] );
		}

		// Checks for a valid license in Plugin Header.
		if ( ! empty( $plugin_license ) && ! preg_match( '/GPL|GNU|MIT|FreeBSD|New BSD|BSD-3-Clause|BSD 3 Clause|OpenLDAP|Expat/im', $plugin_license ) ) {
			$this->add_result_error_for_file(
				$result,
				__( 'Your plugin has an invalid license declared in Plugin Header. Please update your readme with a valid GPL license identifier.', 'plugin-check' ),
				'invalid_license',
				$plugin_main_file
			);
		}

		// Check different license types.
		if ( ! empty( $plugin_license ) && ! empty( $license_readme ) && $license_readme !== $plugin_license ) {
			$this->add_result_warning_for_file(
				$result,
				__( 'Your plugin has a different license declared in the readme file and plugin header. Please update your readme with a valid GPL license identifier.', 'plugin-check' ),
				'different_license',
				$readme_file
			);
		}
	}

	/**
	 * Normalice the licenses
	 * Author: Fran Torres
	 *
	 * @param string $license The license to normalice.
	 * @return string
	 */
	private function normaliceLicenses( $license ) {
		$license = trim( $license );
		$license = str_replace( '  ', ' ', $license );

		// Remove some strings at the end.
		$strings_to_remove = array(
			'.',
			'http://www.gnu.org/licenses/old-licenses/gpl-2.0.html',
			'https://www.gnu.org/licenses/old-licenses/gpl-2.0.html',
			'https://www.gnu.org/licenses/gpl-3.0.html',
			' or later',
			'-or-later',
			'+',
		);
		foreach ( $strings_to_remove as $string_to_remove ) {
			$position = strrpos( $license, $string_to_remove );

			if ( false !== $position ) {
				// To remove from the end, the string to remove must be at the end.
				if ( $position + strlen( $string_to_remove ) === strlen( $license ) ) {
					$license = trim( substr( $license, 0, $position ) );
				}
			}
		}

		// Versions.
		$license = str_replace( '-', '', $license );
		$license = str_replace( ' v2', 'v2', $license );
		$license = str_replace( ' v3', 'v3', $license );

		$license = str_replace( 'GNU General Public License (GPL)', 'GPL', $license );
		$license = str_replace( 'GNU General Public License', 'GPL', $license );
		$license = str_replace( 'v2.0', 'v2', $license );
		$license = str_replace( 'v3.0', 'v3', $license );
		$license = str_replace( '2.0', 'v2', $license );
		$license = str_replace( '3.0', 'v3', $license );

		$license = str_replace( 'GPL2', 'GPLv2', $license );
		$license = str_replace( 'GPL3', 'GPLv3', $license );

		$license = str_replace( '.', '', $license );

		return $license;
	}
}

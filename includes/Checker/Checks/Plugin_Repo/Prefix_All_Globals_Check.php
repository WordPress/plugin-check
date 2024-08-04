<?php
/**
 * Class Prefix_All_Globals_Check.php.
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
 * Check for running WordPress enqueued resources sniffs.
 *
 * @since 1.0.2
 */
class Prefix_All_Globals_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.0.2
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );

		// Looks for saved plugin options.
		$this->look_for_name_variables( $result, $php_files );
	}

	/**
	 * Looks for plugin updater routines in plugin files and amends the given result with an error if found.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_name_variables( Check_Result $result, array $php_files ) {

		foreach ( $php_files as $php_file ) {
			$lines = file( $php_file );
			foreach ( $lines as $line_number => $line ) {
				$matches = array();

				if ( preg_match( '/\$[a-zA-Z0-9_]*\s*=\s*(get_option|add_option|add_site_option|update_option|update_site_option)\(/', $line, $matches ) ) {


				}
			}
		}

		foreach ( $look_for_regex as $regex ) {
			$matches      = array();
			$updater_file = self::file_preg_match( $regex, $php_files, $matches );
			if ( $updater_file ) {
				$this->add_result_error_for_file(
					$result,
					sprintf(
						/* translators: %s: The match file name. */
						__( 'Detected code which may be altering WordPress update routines. Detected: %s', 'plugin-check' ),
						esc_html( $matches[0] )
					),
					'update_modification_detected',
					$updater_file
				);
			}
		}
	}

}

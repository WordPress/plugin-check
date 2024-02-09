<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Localhost_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for detecting localhost in plugin files.
 *
 * @since 1.0.0
 */
class Localhost_Check extends Abstract_File_Check {

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
	 * Check the localhost in files.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );
		$file      = self::file_preg_match( '#https?://(localhost|127.0.0.1)#', $php_files );
		if ( $file ) {
			$this->add_result_error_for_file(
				$result,
				__( 'Do not use Localhost/127.0.0.1 in your code.', 'plugin-check' ),
				'localhost_code_detected',
				$file
			);
		}
	}
}

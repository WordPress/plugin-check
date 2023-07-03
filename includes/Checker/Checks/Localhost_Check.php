<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Localhost_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Check for detecting localhost in plugin files.
 *
 * @since n.e.x.t
 */
class Localhost_Check extends Abstract_File_Check {

	/**
	 * Gets the category of the check.
	 *
	 * @since n.e.x.t
	 */
	public function get_category() {
		return Check_Categories::CATEGORY_SECURITY;
	}

	/**
	 * Check the localhost in files.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );
		$file      = self::file_preg_match( '#https?://(localhost|127.0.0.1)#', $php_files );
		if ( $file ) {
			$result->add_message(
				true,
				__( 'Do not use Localhost/127.0.0.1 in your code.', 'plugin-check' ),
				array(
					'code' => 'localhost_code_detected',
					'file' => $file,
				)
			);
		}
	}
}

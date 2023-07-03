<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\No_Unfiltered_Uploads_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;

/**
 * Check for detecting "ALLOW_UNFILTERED_UPLOADS" constant in plugin files.
 *
 * @since n.e.x.t
 */
class No_Unfiltered_Uploads_Check extends Abstract_File_Check {

	/**
	 * Gets the category of the check.
	 *
	 * @since n.e.x.t
	 */
	public function get_category() {
		return Check_Categories::CATEGORY_SECURITY;
	}

	/**
	 * Check the "ALLOW_UNFILTERED_UPLOADS" constant in file.
	 *
	 * @since n.e.x.t
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );
		$file      = self::file_str_contains( $php_files, 'ALLOW_UNFILTERED_UPLOADS' );
		if ( $file ) {
			$result->add_message(
				true,
				__( 'ALLOW_UNFILTERED_UPLOADS is not permitted.', 'plugin-check' ),
				array(
					'code' => 'allow_unfiltered_uploads_detected',
					'file' => $file,
				)
			);
		}
	}
}

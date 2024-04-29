<?php
/**
 * Class No_Unfiltered_Uploads_Check.
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
 * Check for detecting "ALLOW_UNFILTERED_UPLOADS" constant in plugin files.
 *
 * @since 1.0.0
 */
class No_Unfiltered_Uploads_Check extends Abstract_File_Check {

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
	 * Check the "ALLOW_UNFILTERED_UPLOADS" constant in file.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The Check Result to amend.
	 * @param array        $files  Array of plugin files.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );
		$file      = self::file_str_contains( $php_files, 'ALLOW_UNFILTERED_UPLOADS' );
		if ( $file ) {
			$this->add_result_error_for_file(
				$result,
				sprintf(
					/* translators: %s: ALLOW_UNFILTERED_UPLOADS */
					__( '%s is not permitted.', 'plugin-check' ),
					'<code>ALLOW_UNFILTERED_UPLOADS</code>'
				),
				'allow_unfiltered_uploads_detected',
				$file
			);
		}
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.1.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return sprintf(
			/* translators: %s: ALLOW_UNFILTERED_UPLOADS */
			__( 'Detects disallowed usage of %s.', 'plugin-check' ),
			'<code>ALLOW_UNFILTERED_UPLOADS</code>'
		);
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.1.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/', 'plugin-check' );
	}
}

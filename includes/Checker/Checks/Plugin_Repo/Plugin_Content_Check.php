<?php
/**
 * Class Plugin_Content_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Plugin_Repo;

use Exception;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_File_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check to detect PHP code obfuscation.
 *
 * @since 1.0.0
 */
class Plugin_Content_Check extends Abstract_File_Check {

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

		$this->look_for_five_star_reviews( $result, $php_files );
	}

	/**
	 * Looks for five star reviews and amends the given result with an error if found.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result    The check result to amend, including the plugin context to check.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function look_for_five_star_reviews( Check_Result $result, array $php_files ) {
		$files = self::files_preg_match_all( '/(?:https?:\/\/)?(?:wordpress\.org|wp\.org)\/.*reviews\/\?filter=5/', $php_files ); // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText
		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				$this->add_result_error_for_file(
					$result,
					__( 'Linking directly to 5 stars reviews is not allowed.', 'plugin-check' ),
					'five_star_reviews_detected',
					$file['file'],
					$file['line'],
					$file['column'],
					'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/',
					7
				);
			}
		}
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.7.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects content that does not comply with the WordPress.org plugin guidelines.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.7.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/', 'plugin-check' );
	}
}

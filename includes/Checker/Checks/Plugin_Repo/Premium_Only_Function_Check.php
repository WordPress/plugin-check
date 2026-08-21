<?php
/**
 * Class Premium_Only_Function_Check.
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
 * Check to detect functions ending in __premium_only.
 *
 * @since 1.2.0
 */
class Premium_Only_Function_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.2.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 1.2.0
	 *
	 * @param Check_Result $result The check result to amend.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );
		$matches   = self::files_preg_match_all( '/\bfunction\s+[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*__premium_only\s*\(/i', $php_files );

		if ( ! empty( $matches ) ) {
			foreach ( $matches as $match ) {
				$this->add_result_error_for_file(
					$result,
					__( 'Function declarations ending in __premium_only look like premium/pro-only code and should not be included in a WordPress.org plugin.', 'plugin-check' ),
					'premium_only_function_found',
					$match['file'],
					$match['line'],
					$match['column'],
					'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/'
				);
			}
		}
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.2.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects function declarations ending in __premium_only.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.2.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/', 'plugin-check' );
	}
}

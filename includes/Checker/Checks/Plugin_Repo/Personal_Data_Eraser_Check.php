<?php
/**
 * Class Personal_Data_Eraser_Check.
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
 * Check to detect personal data handling without a registered eraser callback.
 *
 * Plugins that collect or store personal data are expected to register an
 * eraser callback via the `wp_privacy_personal_data_erasers` filter so that
 * WordPress's built-in Personal Data Removal tool can delete the plugin's
 * data on behalf of a user who submits a removal request.
 *
 * @since 1.3.0
 * @link https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-eraser-to-your-plugin/
 */
class Personal_Data_Eraser_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Regex pattern that matches common personal-data storage API calls.
	 *
	 * Matches function calls that are strong indicators that a plugin is
	 * collecting or storing personal data about users.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const PERSONAL_DATA_PATTERN = '/\b(?:add_user_meta|update_user_meta|add_comment_meta|update_comment_meta|\$wpdb\s*->\s*(?:insert|update|replace))\s*\(/';

	/**
	 * Regex pattern that matches registration of a personal data eraser.
	 *
	 * Matches add_filter() calls that hook into the wp_privacy_personal_data_erasers
	 * filter to register a data eraser callback.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const ERASER_REGISTRATION_PATTERN = '/add_filter\s*\(\s*[\'"]wp_privacy_personal_data_erasers[\'"]/';

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.3.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 1.3.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );

		$this->check_for_missing_eraser( $result, $php_files );
	}

	/**
	 * Checks whether the plugin handles personal data but omits the eraser filter.
	 *
	 * The check is intentionally a two-step process:
	 * 1. Confirm the plugin has at least one personal-data storage call.
	 * 2. Only then verify whether it registers the eraser filter.
	 *
	 * This avoids false positives for plugins that do not touch personal data at all.
	 *
	 * @since 1.3.0
	 *
	 * @param Check_Result $result    The check result to amend.
	 * @param array        $php_files List of absolute PHP file paths.
	 */
	protected function check_for_missing_eraser( Check_Result $result, array $php_files ) {
		// Step 1: detect personal data signals across all plugin PHP files.
		$signal_file = self::file_preg_match( self::PERSONAL_DATA_PATTERN, $php_files );

		if ( false === $signal_file ) {
			// No personal data handling detected — nothing to warn about.
			return;
		}

		// Step 2: check if the plugin already registers a personal data eraser.
		$has_eraser = self::file_preg_match( self::ERASER_REGISTRATION_PATTERN, $php_files );

		if ( false !== $has_eraser ) {
			// Eraser is registered — no issue.
			return;
		}

		// Personal data is handled but no eraser is registered: emit a warning.
		$this->add_result_warning_for_file(
			$result,
			__( 'Personal data was detected in this plugin but no data eraser has been registered. Plugins that store personal data should implement a data eraser via the <code>wp_privacy_personal_data_erasers</code> filter so that site administrators can fulfill data removal requests.', 'plugin-check' ),
			'missing_personal_data_eraser',
			$signal_file,
			0,
			0,
			'https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-eraser-to-your-plugin/',
			5
		);
	}

	/**
	 * Gets the description for the check.
	 *
	 * Every check must have a short description explaining what the check does.
	 *
	 * @since 1.3.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects plugins that store personal data without registering a personal data eraser for GDPR compliance.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * Every check must have a URL with further information about the check.
	 *
	 * @since 1.3.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return 'https://developer.wordpress.org/plugins/privacy/adding-the-personal-data-eraser-to-your-plugin/';
	}
}

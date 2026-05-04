<?php
/**
 * Class Privacy_Policy_Check.
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
 * Check that plugins handling personal data call wp_add_privacy_policy_content().
 *
 * Plugins that collect, use, store, or transmit personal data to a third party
 * are required by WordPress.org guidelines to suggest privacy policy text to site
 * administrators via wp_add_privacy_policy_content(). This check detects common
 * personal-data-handling patterns and warns if that function is not used.
 *
 * @since 1.7.0
 */
class Privacy_Policy_Check extends Abstract_File_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Regex patterns that indicate a plugin may handle personal data.
	 *
	 * Each pattern is accompanied by a human-readable label used in the
	 * warning message to help plugin authors understand why the check fired.
	 *
	 * @since 1.7.0
	 * @var array<string, string>
	 */
	const PERSONAL_DATA_PATTERNS = array(
		'wp_remote_post\s*\('     => 'wp_remote_post()',
		'wp_remote_get\s*\('      => 'wp_remote_get()',
		'setcookie\s*\('          => 'setcookie()',
		'\$_COOKIE\b'             => '$_COOKIE',
		'wp_set_auth_cookie\s*\(' => 'wp_set_auth_cookie()',
	);

	/**
	 * Gets the categories for the check.
	 *
	 * Every check must have at least one category.
	 *
	 * @since 1.7.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_PLUGIN_REPO );
	}

	/**
	 * Amends the given result by running the check on the given list of files.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files  List of absolute file paths.
	 */
	protected function check_files( Check_Result $result, array $files ) {
		$php_files = self::filter_files_by_extension( $files, 'php' );

		if ( empty( $php_files ) ) {
			return;
		}

		// First, detect whether the plugin already calls wp_add_privacy_policy_content().
		$has_privacy_call = (bool) self::file_preg_match(
			'#\bwp_add_privacy_policy_content\s*\(#',
			$php_files
		);

		// If the plugin already registers privacy policy content, nothing to warn about.
		if ( $has_privacy_call ) {
			return;
		}

		// Check for each personal-data-handling pattern.
		foreach ( self::PERSONAL_DATA_PATTERNS as $pattern => $label ) {
			$matches      = array();
			$matched_file = self::file_preg_match( '#' . $pattern . '#', $php_files, $matches );

			if ( $matched_file ) {
				$this->add_result_warning_for_file(
					$result,
					sprintf(
						/* translators: %s: The detected function or variable name indicating personal data usage. */
						__( '<strong>Missing privacy policy content registration.</strong><br>The plugin uses %s which may involve handling personal data, but does not call wp_add_privacy_policy_content(). Plugins that collect, store, or transmit personal data should suggest privacy policy text to site administrators.', 'plugin-check' ),
						'<code>' . esc_html( $label ) . '</code>'
					),
					'missing_privacy_policy_content',
					$result->plugin()->main_file(),
					0,
					0,
					'https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/',
					5
				);

				// One warning per plugin is sufficient — avoid duplicate messages.
				return;
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
		return __( 'Checks that plugins handling personal data call wp_add_privacy_policy_content() to suggest privacy policy text to site administrators.', 'plugin-check' );
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
		return __( 'https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/', 'plugin-check' );
	}
}

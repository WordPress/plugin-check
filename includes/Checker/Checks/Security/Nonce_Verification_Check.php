<?php
/**
 * Class Nonce_Verification_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Security;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for running nonce verification sniffs.
 *
 * Detects buggy and insecure usage patterns of wp_verify_nonce() that could
 * lead to CSRF vulnerabilities.
 *
 * @since 1.7.0
 */
class Nonce_Verification_Check extends Abstract_PHP_CodeSniffer_Check {

	use Stable_Check;

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
		return array(
			Check_Categories::CATEGORY_SECURITY,
			Check_Categories::CATEGORY_PLUGIN_REPO,
		);
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args( Check_Result $result ) {
		return array(
			'extensions' => 'php',
			'standard'   => 'PluginCheck',
			'sniffs'     => 'PluginCheck.Security.VerifyNonce',
		);
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
		return __( 'Checks for proper usage of wp_verify_nonce() to prevent CSRF vulnerabilities.', 'plugin-check' );
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
		return __( 'https://developer.wordpress.org/apis/security/nonces/', 'plugin-check' );
	}

	/**
	 * Amends the given result with a message for the specified file, including error information.
	 *
	 * @since 1.7.0
	 *
	 * @param Check_Result $result   The check result to amend, including the plugin context to check.
	 * @param bool         $error    Whether it is an error or notice.
	 * @param string       $message  Error message.
	 * @param string       $code     Error code.
	 * @param string       $file     Absolute path to the file where the issue was found.
	 * @param int          $line     The line on which the message occurred. Default is 0 (unknown line).
	 * @param int          $column   The column on which the message occurred. Default is 0 (unknown column).
	 * @param string       $docs     URL for further information about the message.
	 * @param int          $severity Severity level. Default is 5.
	 */
	protected function add_result_message_for_file( Check_Result $result, $error, $message, $code, $file, $line = 0, $column = 0, string $docs = '', $severity = 5 ) {
		switch ( $code ) {
			case 'PluginCheck.Security.VerifyNonce.UnsafeVerifyNonceStatement':
				$docs = __( 'https://developer.wordpress.org/reference/functions/check_admin_referer/', 'plugin-check' );
				break;

			case 'PluginCheck.Security.VerifyNonce.UnsafeVerifyNonceNegatedAnd':
				$docs = __( 'https://developer.wordpress.org/apis/security/nonces/#verifying-nonces', 'plugin-check' );
				break;

			case 'PluginCheck.Security.VerifyNonce.UnsafeVerifyNonceElse':
				$docs = __( 'https://developer.wordpress.org/apis/security/nonces/#verifying-nonces', 'plugin-check' );
				break;

			default:
				$docs = __( 'https://developer.wordpress.org/apis/security/nonces/', 'plugin-check' );
				break;
		}

		parent::add_result_message_for_file( $result, $error, $message, $code, $file, $line, $column, $docs, $severity );
	}
}

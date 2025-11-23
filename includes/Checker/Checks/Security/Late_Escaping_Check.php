<?php
/**
 * Class Late_Escaping_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Security;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for running WordPress escape output sniffs.
 *
 * @since 1.0.0
 */
class Late_Escaping_Check extends Abstract_PHP_CodeSniffer_Check {

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
		return array(
			Check_Categories::CATEGORY_SECURITY,
			Check_Categories::CATEGORY_PLUGIN_REPO,
		);
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args( Check_Result $result ) {
		return array(
			'extensions' => 'php',
			'standard'   => 'WordPress',
			'sniffs'     => 'WordPress.Security.EscapeOutput',
		);
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
		return __( 'Checks that all output is escaped before being sent to the browser.', 'plugin-check' );
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
		return __( 'https://developer.wordpress.org/apis/security/escaping/', 'plugin-check' );
	}

	/**
	 * Amends the given result with a message for the specified file, including error information.
	 *
	 * @since 1.3.0
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
			case 'WordPress.Security.EscapeOutput.OutputNotEscaped':
				$docs = __( 'https://developer.wordpress.org/apis/security/escaping/#escaping-functions', 'plugin-check' );
				break;

			case 'WordPress.Security.EscapeOutput.UnsafePrintingFunction':
				$docs = __( 'https://developer.wordpress.org/apis/security/escaping/#escaping-with-localization', 'plugin-check' );
				break;

			case 'WordPress.Security.EscapeOutput.UnsafeSearchQuery':
				$docs = __( 'https://developer.wordpress.org/reference/functions/get_search_query/', 'plugin-check' );
				break;

			default:
				$docs = __( 'https://developer.wordpress.org/apis/security/escaping/', 'plugin-check' );
				break;
		}

		parent::add_result_message_for_file( $result, $error, $message, $code, $file, $line, $column, $docs, $severity );
	}
}

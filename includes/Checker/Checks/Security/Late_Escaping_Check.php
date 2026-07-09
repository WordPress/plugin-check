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
				if ( $this->line_calls_save_dom_method( $file, $line ) ) {
					// DOMDocument::saveHtml() and saveXML() return safe HTML/XML
					// constructed through the DOM API — additional escaping is not needed.
					$error = false;
					$docs  = 'https://www.php.net/manual/en/domdocument.savehtml.php';
				} else {
					$docs = __( 'https://developer.wordpress.org/apis/security/escaping/#escaping-functions', 'plugin-check' );
				}
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

	/**
	 * Checks whether the given file/line contains a call to a safe DOM output method.
	 *
	 * DOMDocument::saveHtml() and DOMDocument::saveXML() return HTML/XML
	 * constructed through the DOM API. The output is already structured and
	 * does not need to be passed through an escaping function, so the
	 * WordPress.Security.EscapeOutput sniff reports a false positive here.
	 *
	 * @since 2.0.1
	 *
	 * @param string $file Absolute path to the file.
	 * @param int    $line Line number to inspect.
	 * @return bool True if the line contains a call to saveHtml() or saveXML().
	 */
	private function line_calls_save_dom_method( $file, $line ) {
		if ( $line <= 0 || ! is_string( $file ) || '' === $file || ! file_exists( $file ) ) {
			return false;
		}

		$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $contents ) {
			return false;
		}

		$lines = explode( "\n", $contents );
		if ( ! isset( $lines[ $line - 1 ] ) ) {
			return false;
		}

		$source = $lines[ $line - 1 ];

		return ( false !== stripos( $source, 'saveHtml(' ) )
			|| ( false !== stripos( $source, 'saveXML(' ) );
	}
}

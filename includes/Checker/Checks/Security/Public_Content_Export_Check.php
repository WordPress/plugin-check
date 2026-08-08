<?php
/**
 * Class Public_Content_Export_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\Security;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Advisory check for plugins that export restricted post content through
 * alternative public surfaces without apparent access-control guards.
 *
 * This check detects when post content (post_content, get_the_content(),
 * the_content, etc.) is written to a file or exposed through a public
 * endpoint. It flags the pattern as a warning because third-party access
 * control is not statically knowable — the finding prompts a manual
 * access-policy review rather than asserting a vulnerability.
 *
 * The check looks for code that both:
 * - obtains or renders post bodies; and
 * - writes that output to a file or exposes it through a public surface.
 *
 * Potential signals include directly reading post_content, exporting
 * content without an explicit post_password_required() guard, and
 * generating a public cache or static file from the current user context.
 *
 * @since 2.1.0
 */
class Public_Content_Export_Check extends Abstract_PHP_CodeSniffer_Check {

	use Amend_Check_Result;
	use Stable_Check;

	/**
	 * Bitwise flags to control check behavior.
	 *
	 * @since 2.1.0
	 * @var int
	 */
	protected $flags = 0;

	/**
	 * Gets the categories for the check.
	 *
	 * @since 2.1.0
	 *
	 * @return array The categories for the check.
	 */
	public function get_categories() {
		return array( Check_Categories::CATEGORY_SECURITY );
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result The check result to amend.
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args( Check_Result $result ) {
		return array(
			'extensions' => 'php',
			'standard'   => 'PluginCheck',
			'sniffs'     => 'PluginCheck.Security.PublicContentExport',
		);
	}

	/**
	 * Gets the description for the check.
	 *
	 * @since 2.1.0
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return __( 'Detects when post content is exported through a public surface without an apparent access-control guard.', 'plugin-check' );
	}

	/**
	 * Gets the documentation URL for the check.
	 *
	 * @since 2.1.0
	 *
	 * @return string The documentation URL.
	 */
	public function get_documentation_url(): string {
		return __( 'https://developer.wordpress.org/plugins/wordpress-org/plugin-guidelines/#wordpress-org-plugin-guidelines', 'plugin-check' );
	}

	/**
	 * Amends the given result for a plugin context, customizing the message
	 * for post-content export warnings.
	 *
	 * @since 2.1.0
	 *
	 * @param Check_Result $result   The check result to amend.
	 * @param bool         $error    Whether this is an error (true) or a warning (false).
	 * @param string       $message  The original message from the sniff.
	 * @param string       $code     The sniff error/warning code.
	 * @param string       $file     The file where the issue was found.
	 * @param int          $line     The line number.
	 * @param int          $column   The column number.
	 * @param string       $docs     Documentation URL override.
	 * @param int          $severity Severity level (1-9 per PHPCS convention).
	 */
	protected function add_result_message_for_file( $result, $error, $message, $code, $file, $line = 0, $column = 0, $docs = '', $severity = 5 ) {
		// All findings from this check are advisory warnings.
		parent::add_result_message_for_file(
			$result,
			false,
			$message,
			$code,
			$file,
			$line,
			$column,
			$docs,
			$severity
		);
	}
}

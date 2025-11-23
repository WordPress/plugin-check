<?php
/**
 * Class I18n_Usage_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\General;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for running WordPress internationalization sniffs.
 *
 * @since 1.0.0
 */
class I18n_Usage_Check extends Abstract_PHP_CodeSniffer_Check {

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
			Check_Categories::CATEGORY_GENERAL,
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
			'extensions'  => 'php',
			'standard'    => 'WordPress',
			'sniffs'      => 'WordPress.WP.I18n',
			'runtime-set' => array(
				'text_domain' => $result->plugin()->slug(),
			),
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
		return __( 'Checks for various internationalization best practices.', 'plugin-check' );
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
		return __( 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/', 'plugin-check' );
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
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	protected function add_result_message_for_file( Check_Result $result, $error, $message, $code, $file, $line = 0, $column = 0, string $docs = '', $severity = 5 ) {
		// Downgrade errors about usage of the 'default' text domain from WordPress Core to warnings.
		if ( $error && str_ends_with( $message, ' but got &#039;default&#039;.' ) ) {
			$error = false;
		}

		// Add documentation link.
		switch ( $code ) {
			case 'WordPress.WP.I18n.NonSingularStringLiteralDomain':
			case 'WordPress.WP.I18n.NonSingularStringLiteralText':
			case 'WordPress.WP.I18n.TooManyFunctionArgs':
				$docs = __( 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#basic-strings', 'plugin-check' );
				break;

			case 'WordPress.WP.I18n.NonSingularStringLiteralContext':
				$docs = __( 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#disambiguation-by-context', 'plugin-check' );
				break;

			case 'WordPress.WP.I18n.MissingTranslatorsComment':
				$docs = __( 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions', 'plugin-check' );
				break;

			case 'WordPress.WP.I18n.UnorderedPlaceholdersText':
				$docs = __( 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#variables', 'plugin-check' );
				break;

			default:
				$docs = __( 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/', 'plugin-check' );
				break;
		}

		// Update severity.
		switch ( $code ) {
			case 'WordPress.WP.I18n.MissingArgText':
			case 'WordPress.WP.I18n.NoEmptyStrings':
			case 'WordPress.WP.I18n.TooManyFunctionArgs':
				$severity = 7;
				break;

			default:
				break;
		}

		// Update severity for error code variations. Eg: WordPress.WP.I18n.NonSingularStringLiteralXXX.
		if ( str_starts_with( $code, 'WordPress.WP.I18n.InterpolatedVariable' ) || str_starts_with( $code, 'WordPress.WP.I18n.NonSingularStringLiteral' ) ) {
			$severity = 7;
		}

		if ( 'WordPress.WP.I18n.TextDomainMismatch' === $code ) {
			$restricted_textdomains = $this->get_restricted_textdomains();

			if ( preg_match( '/but\sgot\s&#039;([^&#039;]+)&#039;\.$/', $message, $matches ) ) {
				$textdomain = $matches[1];
				if ( preg_match( '/[^a-z0-9-]/', $textdomain ) || in_array( $textdomain, $restricted_textdomains, true ) ) {
					$severity = 7;
				}
			}
		}

		parent::add_result_message_for_file( $result, $error, $message, $code, $file, $line, $column, $docs, $severity );
	}

	/**
	 * Returns restricted textdomains.
	 *
	 * @since 1.5.0
	 *
	 * @return array Restricted textdomains.
	 */
	private function get_restricted_textdomains() {
		$restricted_textdomains = array(
			'textdomain',
			'text-domain',
			'text_domain',
			'your-text-domain',
		);

		/**
		 * Filter the list of restricted textdomains.
		 *
		 * @since 1.5.0
		 *
		 * @param array $restricted_textdomains Array of restricted textdomains.
		 */
		$restricted_textdomains = (array) apply_filters( 'wp_plugin_check_restricted_textdomains', $restricted_textdomains );

		return $restricted_textdomains;
	}
}

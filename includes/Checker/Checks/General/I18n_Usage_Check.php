<?php
/**
 * Class I18n_Usage_Check.
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks\General;

use WordPress\Plugin_Check\Checker\Check_Categories;
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
		return array( Check_Categories::CATEGORY_GENERAL );
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 1.0.0
	 *
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args() {
		$sniff_args = array(
			'standard'   => 'WordPress',
			'extensions' => 'php',
			'sniffs'     => 'WordPress.WP.I18n',
		);

		global $argv;
		$slug_prefix = '--force-slug=';
		$result      = array_filter(
			$argv,
			function( $element ) use ( $slug_prefix ) {
				return strpos( $element, $slug_prefix ) === 0;
			}
		);

		if ( ! empty( $result ) ) {
			$forced_slug               = str_replace( $slug_prefix, '', array_shift( $result ) );
			$sniff_args['runtime-set'] = 'text_domain ' . $forced_slug;
		}

		return $sniff_args;
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
}

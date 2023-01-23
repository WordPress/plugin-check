<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\I18n_Usage_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

/**
 * Check for running WordPress internationalization sniffs.
 *
 * @since n.e.x.t
 */
class I18n_Usage_Check extends Abstract_PHP_CodeSniffer_Check {

	/**
	 * List of I18n check arguments.
	 *
	 * @since n.e.x.t
	 * @var array
	 */
	public $allowed_args = array(
		'extensions' => 'php',
		'standard'   => 'WordPress,WordPress-Core,WordPress-Docs,WordPress-Extra',
		'sniffs'     => 'WordPress.WP.I18n',
	);

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	public function get_args() {

		return $this->allowed_args;
	}
}

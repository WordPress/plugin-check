<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Plugin_Review_PHPCS_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

/**
 * Check for running WordPress plugin review PHPCS standard.
 *
 * @since n.e.x.t
 */
class Plugin_Review_PHPCS_Check extends Abstract_PHP_CodeSniffer_Check {

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since n.e.x.t
	 *
	 * @return array An associative array of PHPCS CLI arguments.
	 */
	protected function get_args() {
		return array(
			'extensions' => 'php',
			'standard'   => WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'phpcs-rulesets/plugin-review.xml',
		);
	}
}

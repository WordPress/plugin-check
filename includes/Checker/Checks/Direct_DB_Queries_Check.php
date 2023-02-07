<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Direct_DB_Queries_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

/**
 * Check for running WordPress internationalization sniffs.
 *
 * @since n.e.x.t
 */
class Direct_DB_Queries_Check extends Abstract_PHP_CodeSniffer_Check {

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
			'standard'   => 'WordPress',
			'sniffs'     => 'WordPress.DB.DirectDatabaseQuery',
		);
	}
}

<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Enqueued_Scripts_In_Footer_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Stable_Check;

/**
 * Check for running WordPress enqueued resource parameters sniffs.
 *
 * @since n.e.x.t
 */
class Enqueued_Scripts_In_Footer_Check extends Abstract_PHP_CodeSniffer_Check {

	use Stable_Check;

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
			'sniffs'     => 'WordPress.WP.EnqueuedResourceParameters',
		);
	}
}

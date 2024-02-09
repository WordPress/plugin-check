<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Enqueued_Scripts_In_Footer_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Traits\Stable_Check;

/**
 * Check for running WordPress enqueued resource parameters sniffs.
 *
 * @since 1.0.0
 */
class Enqueued_Scripts_In_Footer_Check extends Abstract_PHP_CodeSniffer_Check {

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
		return array( Check_Categories::CATEGORY_PERFORMANCE );
	}

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 1.0.0
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

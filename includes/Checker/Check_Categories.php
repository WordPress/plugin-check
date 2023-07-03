<?php
/**
 * Class WordPress\Plugin_Check\Checker\Check_Categories
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Abstract Check Runner class.
 *
 * @since n.e.x.t
 */
class Check_Categories {

	// Constants for available categories.
	const CATEGORY_GENERAL       = 'general';
	const CATEGORY_PLUGIN_REPO   = 'plugin_repo';
	const CATEGORY_SECURITY      = 'security';
	const CATEGORY_PERFORMANCE   = 'performance';
	const CATEGORY_ACCESSIBILITY = 'accessibility';

	/**
	 * Returns an array of available categories.
	 *
	 * @since n.e.x.t
	 */
	public static function get_categories() {
		return array(
			self::CATEGORY_GENERAL,
			self::CATEGORY_PLUGIN_REPO,
			self::CATEGORY_SECURITY,
			self::CATEGORY_PERFORMANCE,
			self::CATEGORY_ACCESSIBILITY,
		);
	}

	/**
	 * Returns an array of checks.
	 *
	 * @since n.e.x.t
	 *
	 * @param array $checks     An array of Check instances.
	 * @param array $categories An array of available categories.
	 * @return array Filtered $categories list.
	 */
	public static function filter_checks_by_categories( array $checks, array $categories ) {
		return array_filter(
			$checks,
			static function( $check ) use ( $categories ) {
				return in_array( $check->get_category(), $categories, true );
			}
		);
	}
}

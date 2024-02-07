<?php
/**
 * Class WordPress\Plugin_Check\Checker\Check_Categories
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Check Categories class.
 *
 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return array An array of available categories.
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
	 * Returns an array of category labels.
	 *
	 * @since 1.0.0
	 *
	 * @return array An array of category labels.
	 */
	public static function get_category_labels() {
		return array(
			self::CATEGORY_GENERAL       => __( 'General', 'plugin-check' ),
			self::CATEGORY_PLUGIN_REPO   => __( 'Plugin Repo', 'plugin-check' ),
			self::CATEGORY_SECURITY      => __( 'Security', 'plugin-check' ),
			self::CATEGORY_PERFORMANCE   => __( 'Performance', 'plugin-check' ),
			self::CATEGORY_ACCESSIBILITY => __( 'Accessibility', 'plugin-check' ),
		);
	}

	/**
	 * Returns an array of checks.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Collection $collection Check collection.
	 * @param array            $categories An array of categories to filter by.
	 * @return Check_Collection Filtered check collection.
	 */
	public static function filter_checks_by_categories( Check_Collection $collection, array $categories ): Check_Collection {
		return $collection->filter(
			static function ( $check ) use ( $categories ) {
				// Return true if at least one of the check categories is among the filter categories.
				return (bool) array_intersect( $check->get_categories(), $categories );
			}
		);
	}
}

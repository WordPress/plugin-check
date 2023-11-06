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
	 *
	 * @return array An array of available categories.
	 */
	public static function get_categories() {
		static $categories = '';
		if ( ! $categories ) {
			$constants = ( new \ReflectionClass( __CLASS__ ) )->getConstants();

			/**
			 * List of categories.
			 *
			 * @var string[] $categories
			 */
			$categories = array_values(
				array_filter(
					$constants,
					static function ( $key ) {
						return strpos( $key, 'CATEGORY_' ) === 0;
					},
					ARRAY_FILTER_USE_KEY
				)
			);
		}

		return $categories;
	}

	/**
	 * Returns an array of checks.
	 *
	 * @since n.e.x.t
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

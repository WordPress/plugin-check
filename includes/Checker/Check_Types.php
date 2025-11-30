<?php
/**
 * Class WordPress\Plugin_Check\Checker\Check_Types
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Check Type class.
 *
 * @since 1.8.0
 */
class Check_Types {

	// Constants for available check types.
	const TYPE_ERROR   = 'error';
	const TYPE_WARNING = 'warning';

	/**
	 * Returns an array of check types.
	 *
	 * @since 1.8.0
	 *
	 * @return array An array of check types.
	 */
	public static function get_types() {
		$default_types = array(
			self::TYPE_ERROR   => __( 'Error', 'plugin-check' ),
			self::TYPE_WARNING => __( 'Warning', 'plugin-check' ),
		);

		/**
		 * Filters the check types.
		 *
		 * @since 1.8.0
		 *
		 * @param array<string, string> $default_types Associative array of type slugs to labels.
		 */
		$check_types = (array) apply_filters( 'wp_plugin_check_types', $default_types );

		return $check_types;
	}
}

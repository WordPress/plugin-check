<?php
/**
 * Test file declaring a polyfill with a comment between `function` and its name.
 *
 * @package test-plugin-wp-functions-compatibility-with-polyfill-file
 */

if ( ! function_exists( 'wp_get_wp_version' ) ) {
	function /* polyfill */ & /* by reference */ wp_get_wp_version() {
		$version = '6.7';
		return $version;
	}
}

<?php
/**
 * Test file declaring a polyfill inside a class method.
 *
 * @package test-plugin-wp-functions-compatibility-with-polyfill-file
 */

class Compat_Loader {

	public function load() {
		if ( ! function_exists( 'wp_is_development_mode' ) ) {
			function wp_is_development_mode( $mode ) {
				return false;
			}
		}
	}
}

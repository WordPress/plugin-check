<?php
/**
 * Test file declaring a polyfill inside a braced global namespace block.
 *
 * @package test-plugin-wp-functions-compatibility-with-polyfill-file
 */

namespace Test_Plugin\Notices {
	function wp_get_admin_notice( $message ) {
		return $message;
	}
}

namespace {
	if ( ! function_exists( 'wp_get_admin_notice' ) ) {
		function wp_get_admin_notice( $message, $args = array() ) {
			return $message;
		}
	}
}

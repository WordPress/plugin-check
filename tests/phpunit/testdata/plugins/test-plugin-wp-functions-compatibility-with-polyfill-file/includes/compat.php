<?php
/**
 * Polyfills for newer WordPress functions.
 *
 * @package test-plugin-wp-functions-compatibility-with-polyfill-file
 */

if ( ! function_exists( 'wp_trigger_error' ) ) {
	function wp_trigger_error( $function_name, $message, $error_level = E_USER_NOTICE ) {
		trigger_error( $message, $error_level );
	}
}

if ( ! function_exists( 'wp_admin_notice' ) ) {
	function &wp_admin_notice( $message, $args = array() ) {
		echo $message;
	}
}

<?php
/**
 * Plugin Name: Test Plugin Runtime Fatal Prevention Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for runtime fatal prevention check with guarded patterns.
 * Version: 0.1.0
 * Author: plugin-check
 * Author URI: https://github.com/WordPress/plugin-check
 * License: GPLv2 or later
 * Text Domain: test-plugin-runtime-fatal-prevention-without-errors
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

$optional_file = __DIR__ . '/includes/optional-integration.php';
if ( file_exists( $optional_file ) && is_readable( $optional_file ) ) {
	require_once $optional_file;
}

if ( function_exists( 'wc_get_orders' ) ) {
	wc_get_orders();
}

if ( class_exists( 'WC_Order' ) ) {
	$order = new WC_Order( 123 );
}

$callback = isset( $_GET['cb'] ) ? $_GET['cb'] : 'trim';
if ( is_callable( $callback ) ) {
	call_user_func( $callback, 'example' );
}

if ( class_exists( 'WooCommerce\\Admin\\Features\\Navigation\\Init' ) && method_exists( 'WooCommerce\\Admin\\Features\\Navigation\\Init', 'init' ) ) {
	add_action(
		'init',
		array( 'WooCommerce\\Admin\\Features\\Navigation\\Init', 'init' )
	);
}

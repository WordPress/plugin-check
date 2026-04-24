<?php
/**
 * Plugin Name: Test Plugin Runtime Fatal Prevention With Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for runtime fatal prevention check with risky patterns.
 * Version: 0.1.0
 * Author: plugin-check
 * Author URI: https://github.com/WordPress/plugin-check
 * License: GPLv2 or later
 * Text Domain: test-plugin-runtime-fatal-prevention-with-errors
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

$optional_file = __DIR__ . '/includes/optional-integration.php';
require_once $optional_file;

wc_get_orders();

$order = new WC_Order( 123 );

$callback = isset( $_GET['cb'] ) ? $_GET['cb'] : 'trim';
call_user_func( $callback, 'example' );

add_action(
	'init',
	array( 'WooCommerce\\Admin\\Features\\Navigation\\Init', 'init' )
);

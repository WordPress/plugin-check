<?php
/**
 * Plugin Name: Test Plugin Runtime Fatal Error Prevention Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Runtime Fatal Error Prevention check containing no errors.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * Text Domain: test-plugin-runtime-fatal-error-prevention-without-errors
 *
 * @package test-plugin-runtime-fatal-error-prevention-without-errors
 */

// Safe static imports.
require __DIR__ . '/file.php';

// Guarded import.
if ( file_exists( $dynamic_file_path ) ) {
	require $dynamic_file_path;
}

// Guarded WooCommerce function.
if ( function_exists( 'wc_get_product' ) ) {
	wc_get_product( 123 );
}

// Guarded WooCommerce class.
if ( class_exists( 'WC_Product' ) ) {
	$product = new WC_Product();
}

// Guarded dynamic callback.
if ( is_callable( $callback ) ) {
	$callback();
}

// Guarded dynamic callback via call_user_func.
if ( is_callable( $callback ) ) {
	call_user_func( $callback );
}

class My_Test_Plugin_Class_Ok {
	public function __construct() {
		add_action( 'init', array( $this, 'existing_method' ) );
	}

	public function existing_method() {
		// Method exists!
	}
}

<?php
/**
 * Plugin Name: Test Plugin Runtime Fatal Error Prevention Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Runtime Fatal Error Prevention check containing errors.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * Text Domain: test-plugin-runtime-fatal-error-prevention-with-errors
 *
 * @package test-plugin-runtime-fatal-error-prevention-with-errors
 */

// Cases triggering violations.
require $dynamic_file_path; // Warning.

wc_get_product( 123 ); // Error.

$product = new WC_Product(); // Error.

$callback(); // Error.

call_user_func( $callback ); // Error.

class My_Test_Plugin_Class {
	public function __construct() {
		add_action( 'init', array( $this, 'missing_method' ) ); // Error.
	}
}

class My_Extending_Test_Plugin_Class extends WP_Widget {
	public function __construct() {
		add_action( 'init', array( $this, 'missing_method' ) ); // Warning.
	}
}

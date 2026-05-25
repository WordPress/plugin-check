<?php

// Test Case 1: Require/Include (Dynamic vs static)
require __DIR__ . '/file.php'; // Good (static path)
require $dynamic_path; // Bad (dynamic path unguarded)

if ( file_exists( $dynamic_path ) ) {
	require $dynamic_path; // Good (guarded by file_exists)
}

if ( is_readable( $dynamic_path ) ) {
	require_once $dynamic_path; // Good (guarded by is_readable)
}

// Test Case 2: Third-party integration function calls
wc_get_product( $product_id ); // Bad (unguarded integration call)

if ( function_exists( 'wc_get_product' ) ) {
	wc_get_product( $product_id ); // Good (guarded by function_exists)
}

// Test Case 3: Instantiating optional classes
$product = new WC_Product(); // Bad (unguarded class instantiation)

if ( class_exists( 'WC_Product' ) ) {
	$product = new WC_Product(); // Good (guarded by class_exists)
}

// Test Case 4: Invoking dynamic callbacks
$callback(); // Bad (dynamic invocation unguarded)

if ( is_callable( $callback ) ) {
	$callback(); // Good (guarded by is_callable)
}

call_user_func( $callback ); // Bad (dynamic callback call_user_func unguarded)

if ( function_exists( $callback ) ) {
	call_user_func( $callback ); // Good (guarded by function_exists)
}

// Test Case 5: Hooking callbacks that may not exist
class My_Test_Plugin_Class {

	public function __construct() {
		add_action( 'init', array( $this, 'existing_method' ) ); // Good (method exists)
		add_action( 'init', array( $this, 'missing_method' ) ); // Bad (method missing)
	}

	public function existing_method() {
		// Does something.
	}
}

class My_Extending_Test_Plugin_Class extends WP_Widget {

	public function __construct() {
		add_action( 'init', array( $this, 'existing_method' ) ); // Good
		add_action( 'init', array( $this, 'missing_method' ) ); // Warn (class extends parent, might be defined there)
	}

	public function existing_method() {
		// Does something.
	}
}

<?php
/**
 * Test file with a newer WordPress function guarded by function_exists().
 *
 * The guard and the call live in different methods of the same file, wired
 * together through add_action(), mirroring the real-world deferred hook pattern.
 *
 * @package test-plugin-wp-functions-compatibility-with-function-exists-guard
 */

class Guarded_Feature {

	public function __construct() {
		if ( function_exists( 'wp_get_environment_type' ) ) {
			add_action( 'init', array( $this, 'run' ) );
		}
	}

	public function run() {
		wp_get_environment_type();
	}
}

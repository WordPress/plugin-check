<?php
/**
 * File contains errors for the enqueued script sizes check.
 */

function plugin_check_test_enqueue_large_script() {
	wp_enqueue_script(
		'plugin_check_large_script',
		plugin_dir_url( __FILE__ ) . 'assets/large-script.js'
	);
}

add_action( 'wp_enqueue_scripts', 'plugin_check_test_enqueue_large_script' );


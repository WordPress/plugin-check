<?php
/**
 * File contains errors for the enqueued script sizes check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		// Script size is 21 bytes.
		wp_enqueue_script(
			'plugin_check_test_script',
			plugin_dir_url( __FILE__ ) . 'test-script.js'
		);

		wp_add_inline_script(
			'plugin_check_test_script',
			'console.log("inline script");'
		);
	}
);


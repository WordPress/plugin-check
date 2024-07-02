<?php
/**
 * File contains errors for the enqueued style sizes check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		// Style size is 33 bytes.
		wp_enqueue_style(
			'plugin_check_test_style',
			plugin_dir_url( __FILE__ ) . 'test-style.css'
		);

		wp_add_inline_style(
			'plugin_check_test_style',
			'*{outline:none;}'
		);
	}
);


<?php
/**
 * The file does not contain any errors or notices for the enqueued scripts in the footer check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_script(
			'plugin_check_test_script',
			plugin_dir_url( __FILE__ ) . 'footer.js',
			array(),
			'1.0.0',
			true
		);
	}
);

<?php
/**
 * File contains errors for the non blocking scripts check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_script(
			'plugin_check_test_script_header',
			plugin_dir_url( __FILE__ ) . 'header.js',
			array(),
			false,
			array(
				'in_footer' => false,
			)
		);

		wp_enqueue_script(
			'plugin_check_test_script_footer',
			plugin_dir_url( __FILE__ ) . 'footer.js',
			array(),
			false,
			array(
				'in_footer' => true,
			)
		);

		wp_enqueue_script(
			'plugin_check_test_script_defer',
			plugin_dir_url( __FILE__ ) . 'defer.js',
			array(),
			false,
			array(
				'strategy' => 'defer'
			)
		);

		wp_enqueue_script(
			'plugin_check_test_script_async',
			plugin_dir_url( __FILE__ ) . 'async.js',
			array(),
			false,
			array(
				'strategy' => 'async'
			)
		);
	}
);


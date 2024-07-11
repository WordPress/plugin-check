<?php
/**
 * File contains errors for the enqueued scripts scope check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_script(
			'script',
			plugin_dir_url( __FILE__ ) . 'script.js',
			array(),
			'1.0'
		);
		if ( is_home() ) {
			wp_enqueue_script(
				'home-script',
				plugin_dir_url( __FILE__ ) . 'home.js',
				array(),
				'1.0'
			);
		}
	}
);

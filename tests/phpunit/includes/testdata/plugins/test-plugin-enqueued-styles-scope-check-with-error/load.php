<?php
/**
 * File contains errors for the enqueued styles scope check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_style(
			'style',
			plugin_dir_url( __FILE__ ) . 'style.css', 
			array(), 
			'1.0'
		);
		if ( is_home() ) {
			wp_enqueue_style(
				'home-style',
				plugin_dir_url( __FILE__ ) . 'home.css',
				array(),
				'1.0'
			);
		}
	}
);

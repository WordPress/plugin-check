<?php
/**
 * The file does not contain any errors or notices for the enqueued styles scope check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
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

<?php
/**
 * The file does not contain any errors or notices for the enqueued scripts scope check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		if ( is_home() ) {
			wp_enqueue_script(
				'home-script',
				plugin_dir_url( __FILE__ ) . 'script.js',
				array(),
				'1.0'
			);
		}
	}
);

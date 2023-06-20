<?php
/**
 * The file contains notices for the enqueued scripts in the footer check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_script(
			'plugin_check_header_script',
			plugin_dir_url( __FILE__ ) . 'header.js'
		);
	}
);

<?php
/**
 * File without errors for the editor dependencies check.
 * 
 * @package plugin-check
 */

add_action(
	'enqueue_block_assets',
	function() {
		wp_enqueue_script(
			'normal-script',
			plugin_dir_url( __FILE__ ) . 'script.js',
			array( 'wp-blocks' ),
			'1.0'
		);
	}
);

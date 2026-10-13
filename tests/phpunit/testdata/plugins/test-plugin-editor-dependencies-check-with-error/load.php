<?php
/**
 * File contains errors for the editor dependencies check.
 *
 * @package plugin-check
 */

add_action(
	'enqueue_block_assets',
	function() {
		wp_enqueue_script(
			'editor-dependency-script',
			plugin_dir_url( __FILE__ ) . 'script.js',
			array( 'wp-block-editor' ),
			'1.0'
		);

		wp_enqueue_script(
			'editor-dependency-script-2',
			plugin_dir_url( __FILE__ ) . 'script2.js',
			array( 'wp-edit-site' ),
			'1.0'
		);
	}
);

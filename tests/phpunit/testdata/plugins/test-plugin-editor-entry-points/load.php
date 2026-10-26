<?php
/**
 * Fixture loading one admin application and one reusable editor package.
 *
 * @package plugin-check
 */

add_action(
	'enqueue_block_assets',
	static function () {
		wp_enqueue_script( 'pcp-admin-editor', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'wp-edit-post' ), '1.0', true );
		wp_enqueue_script( 'pcp-frontend-editor', plugin_dir_url( __FILE__ ) . 'frontend.js', array( 'wp-block-editor' ), '1.0', true );
	}
);

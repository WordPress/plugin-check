<?php
/**
 * File contains an unrelated external script enqueued alongside the plugin script,
 * to verify the check does not attribute unrelated page scripts as plugin dependencies.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		// Plugin-owned script with no declared deps.
		wp_enqueue_script(
			'plugin_check_test_script',
			plugin_dir_url( __FILE__ ) . 'test-script.js'
		);

		// Unrelated external script that is NOT a dep of the audited plugin.
		// Force a large size on disk so any false-positive attribution stands out.
		wp_register_script(
			'plugin_check_unrelated_huge',
			'https://example.invalid/unrelated-huge.js'
		);
		wp_enqueue_script( 'plugin_check_unrelated_huge' );
	}
);

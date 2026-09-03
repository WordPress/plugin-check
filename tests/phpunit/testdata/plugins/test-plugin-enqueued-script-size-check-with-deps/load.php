<?php
/**
 * File contains errors for the enqueued script sizes check,
 * including external script dependencies.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		// A WP-core dep under the includes URL so the resolver can map it
		// to ABSPATH.
		wp_register_script(
			'plugin_check_test_dep',
			includes_url( 'js/jquery/jquery.min.js' )
		);

		// A CDN-style dep that the resolver cannot measure locally.
		wp_register_script(
			'plugin_check_external_dep',
			'https://example.invalid/x.js'
		);

		wp_enqueue_script(
			'plugin_check_test_script',
			plugin_dir_url( __FILE__ ) . 'test-script.js',
			array(
				'plugin_check_test_dep',
				'plugin_check_external_dep',
			)
		);

		wp_add_inline_script(
			'plugin_check_test_script',
			'console.log("inline script");'
		);
	}
);


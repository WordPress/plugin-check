<?php
/**
 * File contains errors for the enqueued script sizes check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		// A dependency served from outside the plugin directory, created on the fly so the test
		// does not rely on a particular core script being present. In the wild this stands in for
		// something like jQuery or a shared vendor library that a plugin script depends on.
		$uploads  = wp_upload_dir();
		$dep_file = trailingslashit( $uploads['basedir'] ) . 'plugin-check-external-dependency.js';

		if ( ! file_exists( $dep_file ) ) {
			file_put_contents( $dep_file, str_repeat( 'a', 1000 ) );
		}

		wp_register_script(
			'plugin_check_external_dependency',
			trailingslashit( $uploads['baseurl'] ) . 'plugin-check-external-dependency.js',
			array(),
			'1.0.0'
		);

		// Script size is 21 bytes. Depends on the external script above, which lives outside the
		// plugin directory, to exercise dependency size accounting.
		wp_enqueue_script(
			'plugin_check_test_script',
			plugin_dir_url( __FILE__ ) . 'test-script.js',
			array( 'plugin_check_external_dependency' )
		);

		wp_add_inline_script(
			'plugin_check_test_script',
			'console.log("inline script");'
		);
	}
);

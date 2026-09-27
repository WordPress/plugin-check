<?php
/**
 * File contains errors for the enqueued script sizes check.
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		// Dependencies served from outside the plugin directory, created on the fly so the test
		// does not rely on any particular core script being present. In the wild these stand in for
		// something like jQuery or a shared vendor library that a plugin script depends on.
		$uploads = wp_upload_dir();
		$basedir = trailingslashit( $uploads['basedir'] );
		$baseurl = trailingslashit( $uploads['baseurl'] );

		$dependency            = $basedir . 'plugin-check-external-dependency.js';
		$transitive_dependency = $basedir . 'plugin-check-external-transitive-dependency.js';

		if ( ! file_exists( $dependency ) ) {
			file_put_contents( $dependency, str_repeat( 'a', 1000 ) );
		}
		if ( ! file_exists( $transitive_dependency ) ) {
			file_put_contents( $transitive_dependency, str_repeat( 'b', 500 ) );
		}

		// A transitive dependency: the direct dependency (1000 bytes) itself depends on this one (500 bytes).
		wp_register_script(
			'plugin_check_external_transitive_dependency',
			$baseurl . 'plugin-check-external-transitive-dependency.js',
			array(),
			'1.0.0'
		);
		wp_register_script(
			'plugin_check_external_dependency',
			$baseurl . 'plugin-check-external-dependency.js',
			array( 'plugin_check_external_transitive_dependency' ),
			'1.0.0'
		);

		// A remote dependency whose size can't be read locally; it must be skipped, not fatal.
		wp_register_script(
			'plugin_check_remote_dependency',
			'https://cdn.example.com/remote-dependency.js',
			array(),
			'1.0.0'
		);

		// Script size is 21 bytes. Depends on the external scripts above, which live outside the
		// plugin directory, to exercise dependency size accounting.
		wp_enqueue_script(
			'plugin_check_test_script',
			plugin_dir_url( __FILE__ ) . 'test-script.js',
			array( 'plugin_check_external_dependency', 'plugin_check_remote_dependency' )
		);

		wp_add_inline_script(
			'plugin_check_test_script',
			'console.log("inline script");'
		);
	}
);

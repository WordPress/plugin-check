<?php
/**
 * Plugin Name: Test Plugin Enqueued Resources check without errors
 * Plugin URI: https://github.com/wordpress/plugin-check
 * Description: Test plugin for the Enqueued Resources check.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-enqueued-resources-without-errors
 *
 * @package test-plugin-enqueued-resources-without-errors
 */

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_script(
			'plugin_check_script',
			'http://someurl/somefile.js'
		);
		wp_enqueue_style(
			'plugin_check_style',
			'http://someurl/somefile.css'
		);
	}
);

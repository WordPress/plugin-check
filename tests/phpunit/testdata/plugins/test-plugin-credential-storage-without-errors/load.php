<?php
/**
 * Plugin Name: Test Plugin Credential Storage Without Errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Credential Storage check.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-credential-storage-without-errors
 *
 * @package test-plugin-credential-storage-without-errors
 */

if ( function_exists( 'wp_set_secret' ) ) {
	wp_set_secret( 'test-plugin/api-key', 'abc123' );
} else {
	update_option( 'test_plugin_api_key', 'abc123' );
}

update_option( 'test_plugin_settings', array( 'enabled' => true ) );

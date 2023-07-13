<?php
/**
 * Sets up the CLI command early in the WordPress load process.
 *
 * This is necessary to setup the environment to perform runtime checks.
 *
 * @package plugin-check
 * @since n.e.x.t
 */

use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\Plugin_Context;
use WordPress\Plugin_Check\CLI\Plugin_Check_Command;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

// Check if the plugin autoloading is set up.
if ( ! class_exists( 'WordPress\Plugin_Check\CLI\Plugin_Check_Command' ) ) {
	// Check the autoload file exists.
	if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		WP_CLI::error( 'Plugin Check autoloader not found.' );
		return;
	}

	// Load the Composer autoloader.
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! isset( $context ) ) {
	$context = new Plugin_Context( __DIR__ . '/plugin-check.php' );
}

// Create the CLI command instance and add to WP CLI.
$plugin_command = new Plugin_Check_Command( $context );
WP_CLI::add_command( 'plugin', $plugin_command );


/**
 * Adds hook to set up the object-cache.php drop-in file.
 *
 * @since n.e.x.t
 */
WP_CLI::add_hook(
	'before_wp_load',
	function() {
		if ( CLI_Runner::is_plugin_check() ) {
			if ( ! file_exists( ABSPATH . 'wp-content/object-cache.php' ) ) {
				if ( ! copy(  __DIR__ . '/drop-ins/object-cache.copy.php', ABSPATH . 'wp-content/object-cache.php' ) ) {
					WP_CLI::error( 'Unable to copy object-cache.php file.' );
				}
			}
		}
	}
);

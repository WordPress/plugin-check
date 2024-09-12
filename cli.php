<?php
/**
 * Sets up the CLI command early in the WordPress load process.
 *
 * This is necessary to setup the environment to perform runtime checks.
 *
 * @package plugin-check
 * @since 1.0.0
 */

use WordPress\Plugin_Check\Checker\CLI_Runner;
use WordPress\Plugin_Check\CLI\Plugin_Check_Command;
use WordPress\Plugin_Check\Plugin_Context;

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
	$context = new Plugin_Context( __DIR__ . '/plugin.php' );
}

// Create the CLI command instance and add to WP CLI.
$plugin_command = new Plugin_Check_Command( $context );
WP_CLI::add_command(
	'plugin',
	$plugin_command,
	array(
		/**
		 * This is a cleanup for the below hook which adds the object-cache.php drop-in.
		 *
		 * It is executed right after the command ran.
		 *
		 * Since the drop-in could be from somewhere else, a check of its contents is necessary
		 * to verify it is the one that was added below.
		 */
		'after_invoke' => function () {
			if (
				file_exists( ABSPATH . 'wp-content/object-cache.php' ) &&
				false !== strpos( file_get_contents( ABSPATH . 'wp-content/object-cache.php' ), 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION' )
			) {
				unlink( ABSPATH . 'wp-content/object-cache.php' );
			}
		},
	)
);

/*
 * Add hook to set up the object-cache.php drop-in file.
 *
 * Runs after wp-config.php is loaded and thus ABSPATH is defined,
 * but before any plugins are actually loaded.
 */
WP_CLI::add_hook(
	'after_wp_config_load',
	function () {
		if ( CLI_Runner::is_plugin_check() ) {
			if ( ! file_exists( ABSPATH . 'wp-content/object-cache.php' ) ) {
				if ( ! copy( __DIR__ . '/drop-ins/object-cache.copy.php', ABSPATH . 'wp-content/object-cache.php' ) ) {
					WP_CLI::error( 'Unable to copy object-cache.php file.' );
				}
			}
		}
	}
);

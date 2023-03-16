<?php
/**
 * Sets up the CLI command early in the WordPress load process.
 *
 * This is necessary to setup the environment to perform runtime checks.
 *
 * @package plugin-check
 * @since n.e.x.t
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

define( 'WP_PLUGIN_CHECK_CLI_PLUGIN_DIR_PATH', dirname( __FILE__ ) );

// Check if the plugin autoloading is setup.
if ( ! class_exists( 'WordPress\Plugin_Check\CLI\Plugin_Check_Command' ) ) {
	// Check the autoload file exists.
	if ( ! file_exists( WP_PLUGIN_CHECK_CLI_PLUGIN_DIR_PATH . '/vendor/autoload.php' ) ) {
		\WP_CLI::error( 'Plugin Check autoloaded not found.' );
		return;
	}

	// Load the Composer autoloader.
	require_once WP_PLUGIN_CHECK_CLI_PLUGIN_DIR_PATH . '/vendor/autoload.php';
}

if ( ! isset( $context ) ) {
	$context = new WordPress\Plugin_Check\Plugin_Context( WP_PLUGIN_CHECK_CLI_PLUGIN_DIR_PATH . '/plugin-check.php' );
}

// Create the CLI command instance and add to WP CLI.
$plugin_command = new WordPress\Plugin_Check\CLI\Plugin_Check_Command( $context );
\WP_CLI::add_command( 'plugin', $plugin_command );


// Add hook to setup the object-cache.php drop-in file.
\WP_CLI::add_hook( 'before_wp_load', 'wp_plugin_check_cli_object_cache' );

/**
 * Sets up the object-cache.php drop-in file.
 *
 * @since n.e.x.t
 */
function wp_plugin_check_cli_object_cache() {
	if ( ! file_exists( ABSPATH . 'wp-content/object-cache.php' ) ) {
		copy(  WP_PLUGIN_CHECK_CLI_PLUGIN_DIR_PATH . '/object-cache.copy.php', ABSPATH . 'wp-content/object-cache.php' );
	}
}

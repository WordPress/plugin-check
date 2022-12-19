<?php
/**
 * Plugin Name: Plugin Check
 * Plugin URI: https://github.com/wordpress/plugin-check
 * Description: Plugin Check plugin from the WordPress Performance Team, a collection of tests to help improve plugin performance.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 0.1.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: plugin-check
 *
 * @package plugin-check
 */

define( 'WP_PLUGIN_CHECK_VERSION', '0.1.0' );
define( 'WP_PLUGIN_CHECK_MINIMUM_PHP', '5.6' );

/**
 * Checks basic requirements and loads the plugin.
 *
 * @since 0.1.0
 */
function wp_plugin_check_load() {
	// Check for supported PHP version.
	if ( version_compare( phpversion(), WP_PLUGIN_CHECK_MINIMUM_PHP, '<' ) ) {
		return;
	}

	// Check Composer autoloader exists.
	if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
		return;
	}

	// Load the Composer autoloader.
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

	// Setup the plugin.
	$class_name = 'WordPress\\Plugin_Check\\Plugin_Main';
	$instance   = new $class_name( __FILE__ );
	$instance->add_hooks();
}

wp_plugin_check_load();

<?php
/**
 * Plugin Name: Plugin Check
 * Plugin URI: https://github.com/wordpress/plugin-check
 * Description: Plugin Check plugin from the WordPress Performance Team, a collection of tests to help improve plugin performance.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: n.e.x.t
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: plugin-check
 *
 * @package plugin-check
 */

define( 'WP_PLUGIN_CHECK_VERSION', 'n.e.x.t' );
define( 'WP_PLUGIN_CHECK_MINIMUM_PHP', '5.6' );

/**
 * Checks basic requirements and loads the plugin.
 *
 * @since n.e.x.t
 */
function wp_plugin_check_load() {
	// Check for supported PHP version.
	if ( version_compare( phpversion(), WP_PLUGIN_CHECK_MINIMUM_PHP, '<' ) ) {
		add_action( 'admin_notices', 'wp_plugin_check_display_php_version_notice' );
		return;
	}

	// Check Composer autoloader exists.
	if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
		add_action( 'admin_notices', 'wp_plugin_check_display_composer_autoload_notice' );
		return;
	}

	// Load the Composer autoloader.
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

	// Setup the plugin.
	$class_name = 'WordPress\\Plugin_Check\\Plugin_Main';
	$instance   = new $class_name( __FILE__ );
	$instance->add_hooks();
}

/**
 * Displays admin notice about unmet PHP version requirement.
 *
 * @since n.e.x.t
 */
function wp_plugin_check_display_php_version_notice() {
	echo '<div class="notice notice-error"><p>';
	printf(
		/* translators: 1: required version, 2: currently used version */
		__( 'Plugin Check requires at least PHP version %1$s. Your site is currently running on PHP %2$s.', 'plugin-check' ),
		WP_PLUGIN_CHECK_MINIMUM_PHP,
		phpversion()
	);
	echo '</p></div>';
}

/**
 * Displays admin notice about missing Composer autoload files.
 *
 * @since n.e.x.t
 */
function wp_plugin_check_display_composer_autoload_notice() {
	echo '<div class="notice notice-error"><p>';
	echo wp_kses(
		__( 'Composer autoload files are missing. Please run <code>composer install</code>.', 'plugin-check' ),
		array(
			'code' => array(),
		)
	);
	echo '</p></div>';
}


wp_plugin_check_load();

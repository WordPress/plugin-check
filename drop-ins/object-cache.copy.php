<?php
/**
 * Plugin Name: Plugin Check Object Cache Drop-In
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Plugin check drop-in to setup the test environment early. This is not a real object cache drop-in and will not override other actual object cache drop-ins.
 * Version: 1
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * Object cache drop-in from Plugin Check.
 *
 * This drop-in is used, admittedly as a hack, to be able to setup the
 * WordPress environment as early as possible. Once a plugin is loaded, it is
 * too late to configure the test environment.
 *
 * This file respects any real object cache implementation the site may already
 * be using, and it is implemented in a way that there is no risk for breakage.
 *
 * @package plugin-check
 * @since 1.0.0
 */

// Set constant to be able to later check for whether this file was loaded.
define( 'WP_PLUGIN_CHECK_OBJECT_CACHE_DROPIN_VERSION', 1 );

function plugin_check_initialize_runner() {
	$plugins_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
	$plugin_dir  = $plugins_dir . '/plugin-check/';
	if ( ! file_exists( $plugin_dir . 'vendor/autoload.php' ) ) {
		return;
	}

	require_once $plugin_dir . 'vendor/autoload.php';

	/*
	 * Load the consumer-supplied bootstrap file (if configured) before the runner is initialized.
	 * The bootstrap file is the earliest point at which an integration can register listeners for
	 * plugin-check actions/filters, since this drop-in runs before mu-plugins.
	 *
	 * The constant is expected to hold an absolute filesystem path. When the constant is defined
	 * but the file is missing, a PHP warning is emitted so that misconfiguration is visible; PCP
	 * keeps running regardless.
	 */
	if ( defined( 'WP_PLUGIN_CHECK_BOOTSTRAP_FILE' ) ) {
		$plugin_check_bootstrap_file = WP_PLUGIN_CHECK_BOOTSTRAP_FILE;
		if ( is_string( $plugin_check_bootstrap_file ) && '' !== $plugin_check_bootstrap_file ) {
			if ( is_file( $plugin_check_bootstrap_file ) ) {
				require_once $plugin_check_bootstrap_file;
			} else {
				trigger_error(
					sprintf(
						'Plugin Check: WP_PLUGIN_CHECK_BOOTSTRAP_FILE points to "%s", but the file does not exist.',
						$plugin_check_bootstrap_file
					),
					E_USER_WARNING
				);
			}
		}
	}

	if ( class_exists( 'WordPress\Plugin_Check\Utilities\Plugin_Request_Utility' ) ) {
		// Initialize the Check Runner class based on the request.
		WordPress\Plugin_Check\Utilities\Plugin_Request_Utility::initialize_runner();
	}
}
plugin_check_initialize_runner();

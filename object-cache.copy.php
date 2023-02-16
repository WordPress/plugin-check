
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
 * If you do not want the Plugin Check plugin to place this file and thus be
 * limited to static checks, you can remove this file and set the following
 * constant (e.g. in wp-config.php):
 *
 *     define( 'WP_PLUGIN_CHECK_DISABLE_OBJECT_CACHE_DROPIN', true );
 *
 * @package plugin-check
 * @since n.e.x.t
 */

// Set constant to be able to later check for whether this file was loaded.
define( 'WP_PLUGIN_CHECK_PERFLAB_OBJECT_CACHE_DROPIN_VERSION', 1 );

function plugin_check_initialize_runner() {
	if ( defined( 'WP_PLUGIN_CHECK_DISABLE_OBJECT_CACHE_DROPIN' ) && WP_PLUGIN_CHECK_DISABLE_OBJECT_CACHE_DROPIN ) {
		return;
	}

	$plugins_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
	$plugin_dir  = $plugins_dir . '/plugin-check/';
	if ( ! file_exists( $plugin_dir . 'vendor/autoload.php' ) ) {
		return;
	}

	require_once $plugin_dir . 'vendor/autoload.php';

	if ( class_exists( 'WordPress\Plugin_Check\Utilities\Plugin_Request_Utility' ) ) {
		// Initialize the Check Runner class based on the request.
		WordPress\Plugin_Check\Utilities\Plugin_Request_Utility::initialize_runner();

		var_dump('initialize!');
	}
}
plugin_check_initialize_runner();

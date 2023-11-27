<?php
/**
 * Plugin Name: Test Plugin Updater Routines Errors for Plugin Check
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: n.e.x.t
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-updater-routines-errors
 *
 * @package test-plugin-updater-routines-errors
 */

function update_plugins_callback( $transient ) {
	return $transient;
}
add_filter( 'test_site_transient_update_plugins', 'update_plugins_callback' );

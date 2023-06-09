<?php
/**
 * Plugin Name: Test Plugin updater with errors for Plugin Check
 * Plugin URI: https://github.com/wordpress/plugin-check
 * Description: Plugin Check plugin from the WordPress Performance Team, a collection of tests to help improve plugin performance.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: n.e.x.t
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-updater-check
 * Update URI: https://wordpress.org/plugins/example-plugin/
 *
 * @package test-plugin-updater-check
 */
 
// Disable automatic WordPress plugin updates.
add_filter( 'auto_update_plugin', '__return_false' );

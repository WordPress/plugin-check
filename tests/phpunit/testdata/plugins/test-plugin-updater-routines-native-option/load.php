<?php
/**
 * Plugin Name: Test Plugin Updater Routines Native Option for Plugin Check
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Some plugin description.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-updater-routines-native-option
 *
 * @package test-plugin-updater-routines-native-option
 */

function test_plugin_updater_routines_native_option_read() {
	return get_site_option( 'auto_update_plugins', array() );
}

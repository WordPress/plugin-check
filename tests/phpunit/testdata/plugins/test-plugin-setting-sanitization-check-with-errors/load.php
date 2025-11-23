<?php
/**
 * Plugin Name: Test Plugin Setting Sanitization check with errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Setting Sanitization check.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-setting-sanitization-check-with-errors
 *
 * @package test-plugin-setting-sanitization-check-with-errors
 */

// Settings.
register_setting( 'my_options_group', 'option_1' ); // Error.
register_setting( 'my_options_group', 'option_2', false ); // Error.
register_setting( 'my_options_group', 'option_3', 'absint' ); // Good.

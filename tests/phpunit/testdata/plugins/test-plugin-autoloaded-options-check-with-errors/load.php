<?php
/**
 * Plugin Name: Test Plugin Autoloaded Options check with errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Autoloaded Options check.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-autoloaded-options-check-with-errors
 *
 * @package test-plugin-autoloaded-options-check-with-errors
 */

// add_option without $autoload.
add_option( 'autoloaded_with_errors_a' );

// add_option with $value but no $autoload.
add_option( 'autoloaded_with_errors_b', 'value' );

// update_option without $autoload.
update_option( 'autoloaded_with_errors_c' );

// update_option with $value but no $autoload.
update_option( 'autoloaded_with_errors_d', 'value' );

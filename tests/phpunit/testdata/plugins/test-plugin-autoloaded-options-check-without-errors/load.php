<?php
/**
 * Plugin Name: Test Plugin Autoloaded Options check without errors
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Test plugin for the Autoloaded Options check (clean fixture).
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-autoloaded-options-check-without-errors
 *
 * @package test-plugin-autoloaded-options-check-without-errors
 */

// add_option with explicit autoload = false.
add_option( 'autoloaded_without_errors_a', 'value', '', false );

// add_option with explicit autoload = true.
add_option( 'autoloaded_without_errors_b', 'value', '', true );

// update_option with explicit autoload = false.
update_option( 'autoloaded_without_errors_c', 'value', false );

// update_option with explicit autoload = true.
update_option( 'autoloaded_without_errors_d', 'value', true );

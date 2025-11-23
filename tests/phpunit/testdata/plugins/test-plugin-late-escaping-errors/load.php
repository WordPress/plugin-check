<?php
/**
 * Plugin Name: Test Plugin escape output with Errors for Plugin Check
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0 Beta
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: Test-plugin-check_errors
 * Domain Path: /languages
 *
 * @package test-plugin-check-errors
 */

/**
 * File contains errors related to escape output issues.
 */

$test = '<p><strong>Hello World!</strong></p>';

echo $test;

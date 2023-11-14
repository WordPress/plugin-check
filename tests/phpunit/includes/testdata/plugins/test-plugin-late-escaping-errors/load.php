<?php
/**
 * Plugin Name: Test Plugin escape output with Errors for Plugin Check
 * Plugin URI: https://github.com/WordPress/plugin-check
 * Description: Plugin Check plugin from the WordPress Performance Team, a collection of tests to help improve plugin performance.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: n.e.x.t
 * Author: WordPress Performance Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-check-errors
 *
 * @package test-plugin-check-errors
 */

/**
 * File contains errors related to escape output issues.
 */

$test = '<p><strong>Hello World!</strong></p>';

echo $test;

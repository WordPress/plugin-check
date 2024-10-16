<?php
/**
 * Plugin Name: Test Plugin Prefix Globals check with errors
 * Plugin URI: https://github.com/wordpress/plugin-check
 * Description: Test plugin for the Prefix Globals check.
 * Requires at least: 6.0
 * Requires PHP: 5.6
 * Version: 1.0.0
 * Author: WordPress Review Team
 * Author URI: https://make.wordpress.org/performance/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: test-plugin-prefix-globals-with-errors
 *
 * @package test-plugin-prefix-globals-with-errors
 */

// This is a test plugin to check for the use of global variables without a prefix.
function dosomething() {
	echo 'Hello, World!';
}

function er_dosomething() {
	echo 'Hello, World!';
}
